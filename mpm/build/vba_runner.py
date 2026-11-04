"""Windows-seitiger Runner für Build (`import`) und Export (`export`) des MPM.

Wird von `build_mpm.py` und `export_mpm.py` über `powershell.exe` mit
Windows-Python (`py`) aufgerufen. Nutzt `vba_edit` als Bibliothek und öffnet
die Mappe in einer eigenen, unsichtbaren Excel-Instanz ohne Events und ohne
Dialoge, die nach jedem Lauf beendet wird.

- Export: ohne Makroausführung, die Mappe wird nicht gespeichert.
- Build: Makros erlaubt. Nach dem Import aller Komponenten werden nur die
  Übersetzungen initialisiert (`sFuelleCollection`) und alle Formeln neu
  berechnet, bevor gespeichert wird. Eine Schutzprüfung verhindert, dass die
  Mappe mit zusätzlichen Fehlerwerten in Formelzellen hinterlassen wird.
"""

from __future__ import annotations

import argparse
import ctypes
import filecmp
import functools
import gc
import hashlib
import importlib.metadata
import logging
import os
import shutil
import sys
import tempfile
import time
from pathlib import Path, PureWindowsPath
from typing import NoReturn

sys.dont_write_bytecode = True

import runner_protocol as protocol  # noqa: E402

REQUIRED_VBA_EDIT_VERSION = "0.5.1"
VBA_EDIT_INSTALL_HINT = (
    f"Installation: py -m pip install --user vba-edit=={REQUIRED_VBA_EDIT_VERSION}"
)
ENCODING = "cp1252"
VBA_FILE_SUFFIXES = (".bas", ".cls", ".frm")
MSO_AUTOMATION_SECURITY_LOW = 1
MSO_AUTOMATION_SECURITY_FORCE_DISABLE = 3
XL_UPDATE_LINKS_NEVER = 0
EXCEL_EXIT_WAIT_MS = 15000
EXCEL_TERMINATE_WAIT_MS = 5000
TRANSLATION_MACRO = "Modul6_Sprache.sFuelleCollection"
BACKUP_NAME_ATTEMPTS = 5
RESTORE_ATTEMPTS = 5
RESTORE_RETRY_SECONDS = 1.0
MAX_REPORTED_CELLS = 5
EXCEL_PROCESS_NAME = "EXCEL"
PROCESS_QUERY_LIMITED_INFORMATION = 0x1000
TH32CS_SNAPPROCESS = 0x00000002
ERROR_NO_MORE_FILES = 18
MAX_IMAGE_PATH_LENGTH = 32768
RPC_E_CALL_REJECTED = -2147418111
RPC_E_SERVERCALL_RETRYLATER = -2147417846
REJECTED_CALL_HRESULTS = (RPC_E_CALL_REJECTED, RPC_E_SERVERCALL_RETRYLATER)
REJECTED_CALL_RETRY_SECONDS = 30
REJECTED_CALL_RETRY_INTERVAL_MS = 500


class RunnerError(Exception):
    def __init__(self, exit_code: int, message: str) -> None:
        super().__init__(message)
        self.exit_code = exit_code


class ForeignExcelInstanceError(RuntimeError):
    """`Application.Hwnd` gehört zu einem Excel-Prozess, der schon vorher lief."""

    def __init__(self, pid: int) -> None:
        super().__init__(f"PID {pid} lief schon vor dem Start der eigenen Instanz")
        self.pid = pid


class _LevelFilter(logging.Filter):
    def filter(self, record: logging.LogRecord) -> bool:
        return record.levelno < logging.WARNING


class _LogFormatter(logging.Formatter):
    def format(self, record: logging.LogRecord) -> str:
        message = record.getMessage()
        if record.levelno == logging.INFO:
            return message
        return f"{record.levelname}: {message}"


def configure_output() -> None:
    for stream in (sys.stdout, sys.stderr):
        stream.reconfigure(encoding="utf-8", errors="replace", line_buffering=True)

    stdout_handler = logging.StreamHandler(sys.stdout)
    stdout_handler.addFilter(_LevelFilter())
    stdout_handler.setFormatter(_LogFormatter())
    stderr_handler = logging.StreamHandler(sys.stderr)
    stderr_handler.setLevel(logging.WARNING)
    stderr_handler.setFormatter(_LogFormatter())

    root_logger = logging.getLogger()
    root_logger.handlers[:] = [stdout_handler, stderr_handler]
    root_logger.setLevel(logging.INFO)


def report_error(exit_code: int, detail: str) -> None:
    print(
        f"FEHLER [{exit_code}] {protocol.describe(exit_code)}: {detail}",
        file=sys.stderr,
    )


def report_warning(message: str) -> None:
    print(f"WARNUNG: {message}", file=sys.stderr)


def report_note(message: str) -> None:
    print(f"Hinweis: {message}", file=sys.stderr)


def quote_powershell(value: str) -> str:
    return "'" + value.replace("'", "''") + "'"


@functools.cache
def kernel32():
    from ctypes import wintypes

    library = ctypes.WinDLL("kernel32", use_last_error=True)
    library.GetCurrentProcess.restype = wintypes.HANDLE
    library.GetCurrentProcess.argtypes = []
    library.GetProcessTimes.restype = wintypes.BOOL
    library.GetProcessTimes.argtypes = [wintypes.HANDLE] + [
        ctypes.POINTER(wintypes.FILETIME)
    ] * 4
    library.QueryFullProcessImageNameW.restype = wintypes.BOOL
    library.QueryFullProcessImageNameW.argtypes = [
        wintypes.HANDLE,
        wintypes.DWORD,
        wintypes.LPWSTR,
        ctypes.POINTER(wintypes.DWORD),
    ]
    library.CreateToolhelp32Snapshot.restype = wintypes.HANDLE
    library.CreateToolhelp32Snapshot.argtypes = [wintypes.DWORD, wintypes.DWORD]
    library.Process32FirstW.restype = wintypes.BOOL
    library.Process32FirstW.argtypes = [wintypes.HANDLE, ctypes.c_void_p]
    library.Process32NextW.restype = wintypes.BOOL
    library.Process32NextW.argtypes = [wintypes.HANDLE, ctypes.c_void_p]
    library.CloseHandle.restype = wintypes.BOOL
    library.CloseHandle.argtypes = [wintypes.HANDLE]
    return library


def process_start_time(handle: int) -> int:
    """Startzeit des Prozesses als FILETIME, wie `StartTime.ToFileTimeUtc()`."""
    from ctypes import wintypes

    times = [wintypes.FILETIME() for _ in range(4)]
    if not kernel32().GetProcessTimes(handle, *(ctypes.byref(item) for item in times)):
        raise ctypes.WinError(ctypes.get_last_error())
    return (times[0].dwHighDateTime << 32) | times[0].dwLowDateTime


def process_name(handle: int) -> str:
    """Prozessname ohne `.exe`, wie `Get-Process` ihn als `ProcessName` meldet."""
    from ctypes import wintypes

    buffer = ctypes.create_unicode_buffer(MAX_IMAGE_PATH_LENGTH)
    size = wintypes.DWORD(len(buffer))
    if not kernel32().QueryFullProcessImageNameW(handle, 0, buffer, ctypes.byref(size)):
        raise ctypes.WinError(ctypes.get_last_error())
    image = PureWindowsPath(buffer.value)
    return image.stem if image.suffix.lower() == ".exe" else image.name


def own_process_identity() -> protocol.ProcessIdentity:
    start = name = None
    try:
        handle = kernel32().GetCurrentProcess()
        start = process_start_time(handle)
        name = process_name(handle)
    except Exception:
        # Ohne Startzeit und Namen beendet der Wrapper den Runner nicht.
        pass
    return protocol.ProcessIdentity(pid=os.getpid(), start=start, name=name)


def list_process_ids(name: str) -> set[int]:
    """PIDs aller laufenden Prozesse mit diesem Namen.

    Nutzt einen Toolhelp-Snapshot der Prozessliste und öffnet keine Handles auf
    die gelisteten Prozesse.
    """
    from ctypes import wintypes

    class ProcessEntry(ctypes.Structure):
        _fields_ = [
            ("dwSize", wintypes.DWORD),
            ("cntUsage", wintypes.DWORD),
            ("th32ProcessID", wintypes.DWORD),
            ("th32DefaultHeapID", ctypes.c_size_t),
            ("th32ModuleID", wintypes.DWORD),
            ("cntThreads", wintypes.DWORD),
            ("th32ParentProcessID", wintypes.DWORD),
            ("pcPriClassBase", ctypes.c_long),
            ("dwFlags", wintypes.DWORD),
            ("szExeFile", ctypes.c_wchar * 260),
        ]

    library = kernel32()
    snapshot = library.CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0)
    if snapshot in (None, ctypes.c_void_p(-1).value):
        raise ctypes.WinError(ctypes.get_last_error())
    try:
        entry = ProcessEntry()
        entry.dwSize = ctypes.sizeof(ProcessEntry)
        image_name = f"{name}.exe".lower()
        pids: set[int] = set()
        found = library.Process32FirstW(snapshot, ctypes.byref(entry))
        while found:
            if entry.szExeFile.lower() == image_name:
                pids.add(int(entry.th32ProcessID))
            found = library.Process32NextW(snapshot, ctypes.byref(entry))
        error = ctypes.get_last_error()
        if error != ERROR_NO_MORE_FILES:
            raise ctypes.WinError(error)
        return pids
    finally:
        library.CloseHandle(snapshot)


def file_digest(path: Path) -> tuple[int, str]:
    """Größe und SHA-256 des Dateiinhalts."""
    with open(path, "rb") as stream:
        size = os.fstat(stream.fileno()).st_size
        digest = hashlib.file_digest(stream, "sha256").hexdigest()
    return size, digest


def reserve_backup_paths() -> tuple[int, Path, Path]:
    """Legt die Zwischendatei der Sicherung exklusiv an.

    Liefert den offenen Datei-Deskriptor, den Pfad der Zwischendatei und den
    endgültigen Pfad. Die Kennung im Namen stammt von `mkstemp` und ist damit
    diesem Lauf vorbehalten; eine Kennung, deren endgültiger Name schon belegt
    ist, wird verworfen.
    """
    for _ in range(BACKUP_NAME_ATTEMPTS):
        handle, name = tempfile.mkstemp(
            prefix=protocol.BACKUP_PREFIX, suffix=protocol.BACKUP_INCOMPLETE_SUFFIX
        )
        incomplete_path = Path(name)
        try:
            final_path = Path(protocol.final_backup_path(name) or "")
            if final_path.name and not final_path.exists():
                return handle, incomplete_path, final_path
        except BaseException:
            os.close(handle)
            incomplete_path.unlink(missing_ok=True)
            raise
        os.close(handle)
        incomplete_path.unlink(missing_ok=True)
    raise OSError("Kein freier Name für die Sicherung gefunden")


def parse_arguments(argv: list[str] | None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Importiert bzw. exportiert den VBA-Code der MPM-Mappe in einer "
            "eigenen Excel-Instanz ohne Events und ohne Dialoge."
        )
    )
    parser.add_argument("mode", choices=("import", "export"))
    parser.add_argument("workbook", help="Windows-Pfad der Mappe")
    parser.add_argument(
        "--vba-directory",
        required=True,
        help="Windows-Pfad des VBA-Quellverzeichnisses",
    )
    return parser.parse_args(argv)


def check_environment() -> None:
    try:
        installed_version = importlib.metadata.version("vba-edit")
    except importlib.metadata.PackageNotFoundError:
        raise RunnerError(
            protocol.VBA_EDIT_VERSION,
            f"vba-edit ist nicht installiert. {VBA_EDIT_INSTALL_HINT}",
        ) from None

    if installed_version != REQUIRED_VBA_EDIT_VERSION:
        raise RunnerError(
            protocol.VBA_EDIT_VERSION,
            f"Installiert ist vba-edit {installed_version}, erforderlich ist "
            f"{REQUIRED_VBA_EDIT_VERSION}. {VBA_EDIT_INSTALL_HINT}",
        )

    try:
        import pywintypes  # noqa: F401
        import win32api  # noqa: F401
        import win32com.client  # noqa: F401
        import win32con  # noqa: F401
        import win32event  # noqa: F401
        import win32process  # noqa: F401
    except ImportError as exc:
        raise RunnerError(
            protocol.PYWIN32_MISSING,
            f"{exc}. Installation: py -m pip install --user pywin32",
        ) from None


def check_inputs(args: argparse.Namespace) -> None:
    workbook_path = Path(args.workbook)
    if not workbook_path.is_file():
        raise RunnerError(
            protocol.MISSING_INPUT, f"Mappe nicht gefunden: {workbook_path}"
        )

    if args.mode != "import":
        return

    vba_directory = Path(args.vba_directory)
    if not vba_directory.is_dir():
        raise RunnerError(
            protocol.MISSING_INPUT,
            f"Quellverzeichnis nicht gefunden: {vba_directory}",
        )
    if not any(
        path.suffix.lower() in VBA_FILE_SUFFIXES for path in vba_directory.iterdir()
    ):
        raise RunnerError(
            protocol.MISSING_INPUT,
            f"Keine VBA-Dateien ({', '.join(VBA_FILE_SUFFIXES)}) in {vba_directory}",
        )


def column_letters(column: int) -> str:
    letters = ""
    while column > 0:
        column, remainder = divmod(column - 1, 26)
        letters = chr(ord("A") + remainder) + letters
    return letters


def as_rows(values) -> tuple:
    # `Range.Value2` und `Range.Formula` liefern für eine einzelne Zelle einen
    # Skalar statt eines zweidimensionalen Tupels.
    if isinstance(values, tuple):
        return values
    return ((values,),)


def is_error_value(value) -> bool:
    # pywin32 liefert Fehlerwerte (`VT_ERROR`) als `int`; Zahlen kommen über
    # `Value2` immer als `float`, Wahrheitswerte als `bool`.
    return isinstance(value, int) and not isinstance(value, bool)


def read_used_range(sheet) -> tuple:
    """Name, erste Zeile und Spalte, Formeln und Werte des benutzten Bereichs."""
    used_range = sheet.UsedRange
    return (
        str(sheet.Name),
        int(used_range.Row),
        int(used_range.Column),
        as_rows(used_range.Formula),
        as_rows(used_range.Value2),
    )


def collect_formula_errors(workbook, call_excel) -> dict[str, set[tuple[int, int]]]:
    """Ermittelt je Blatt die Formelzellen, deren Wert ein Fehlerwert ist.

    Liest `UsedRange.Formula` und `UsedRange.Value2` als Ganzes statt
    `SpecialCells` zu verwenden: `SpecialCells` meldet "keine Zellen" mit
    demselben COM-Fehler wie andere Fehler und ist auf geschützten Blättern
    nicht verlässlich; alle Blätter der MPM-Mappe sind geschützt.
    """
    result: dict[str, set[tuple[int, int]]] = {}
    sheets = call_excel("Workbook.Worksheets", lambda: list(workbook.Worksheets))
    for sheet in sheets:
        name, first_row, first_column, formulas, values = call_excel(
            "Worksheet.UsedRange", functools.partial(read_used_range, sheet)
        )
        cells: set[tuple[int, int]] = set()
        for row_offset, (formula_row, value_row) in enumerate(zip(formulas, values)):
            for column_offset, (formula, value) in enumerate(zip(formula_row, value_row)):
                if (
                    is_error_value(value)
                    and isinstance(formula, str)
                    and formula.startswith("=")
                ):
                    cells.add((first_row + row_offset, first_column + column_offset))
        result[name] = cells
    return result


def format_cells(cells) -> str:
    addresses = [
        f"{column_letters(column)}{row}"
        for row, column in sorted(cells)[:MAX_REPORTED_CELLS]
    ]
    if len(cells) > MAX_REPORTED_CELLS:
        addresses.append("...")
    return ", ".join(addresses)


def format_error_counts(before, after) -> str:
    return ", ".join(
        f"{name} {len(before.get(name, ()))}/{len(cells)}" for name, cells in after.items()
    )


def find_error_increases(before, after) -> list[str]:
    increases = []
    for name, cells in after.items():
        previous = before.get(name, set())
        if len(cells) > len(previous):
            increases.append(
                f"{name}: {len(previous)} vorher, {len(cells)} nachher "
                f"(neu: {format_cells(cells - previous)})"
            )
    return increases


def create_handler_class() -> type:
    # `vba_edit` wird erst nach der Versionsprüfung importiert, weil die
    # überschriebenen Methoden interne APIs von genau dieser Version sind.
    import pywintypes
    import win32api
    import win32com.client
    import win32con
    import win32event
    import win32process

    try:
        from vba_edit.office_vba import ExcelVBAHandler
    except ImportError as exc:
        raise RunnerError(
            protocol.VBA_EDIT_VERSION,
            f"vba-edit lässt sich nicht importieren: {exc}. {VBA_EDIT_INSTALL_HINT}",
        ) from exc

    class IsolatedExcelVBAHandler(ExcelVBAHandler):
        def __init__(self, *args, build: bool, **kwargs) -> None:
            super().__init__(*args, **kwargs)
            self.build = build
            # Build: Makros erlaubt (für `sFuelleCollection` und die
            # Formelfunktionen); Export: keine Makroausführung.
            self.automation_security = (
                MSO_AUTOMATION_SECURITY_LOW
                if build
                else MSO_AUTOMATION_SECURITY_FORCE_DISABLE
            )
            self.excel_started = False
            self.excel_foreign = False
            self.retry_rejected_calls = False
            self.excel_pids_before: set[int] | None = None
            self.excel_pid: int | None = None
            self.excel_process_handle = None
            self.processed_components: list[str] = []
            self.failed_components: list[str] = []
            self.initial_errors: dict[str, set[tuple[int, int]]] = {}
            self.backup_path: Path | None = None
            self.save_attempted = False
            self.save_verified = False
            self.restore_verified = False
            self.saved = False

        def initialize_app(self) -> None:
            if self.app is not None:
                return

            try:
                self.excel_pids_before = list_process_ids(EXCEL_PROCESS_NAME)
            except Exception as exc:
                report_warning(f"Laufende Excel-Prozesse nicht ermittelbar: {exc}")
            # `DispatchEx` startet immer eine neue Instanz; `Dispatch` würde
            # eine laufende Excel-Instanz des Operators mitnutzen.
            self.app = win32com.client.DispatchEx(self.app_progid)
            self.excel_started = True
            try:
                self._attach_excel_process()
            except ForeignExcelInstanceError as exc:
                self._release_foreign_instance()
                raise RunnerError(
                    protocol.COM_ERROR,
                    f"Excel lieferte keine nachweislich neue Instanz ({exc}). Die "
                    "Instanz wurde ohne Quit freigegeben und nicht beendet, an ihr "
                    "wurde nichts eingestellt, und die Mappe wurde nicht geöffnet. "
                    "Laufende Excel-Instanzen im Task-Manager prüfen und den Lauf "
                    "wiederholen.",
                ) from None
            # Erst ab hier gilt die Instanz als eigene; vorher wird kein
            # abgelehnter Aufruf wiederholt.
            self.retry_rejected_calls = True
            self._set_app_property("Visible", False)
            self._set_app_property("DisplayAlerts", False)
            self._set_app_property("EnableEvents", False)
            self._set_app_property("AutomationSecurity", self.automation_security)

        def call_excel(self, call: str, function):
            """Führt `function` aus und wiederholt sie, solange Excel ablehnt.

            Mit `RPC_E_CALL_REJECTED` bzw. `RPC_E_SERVERCALL_RETRYLATER` hat
            Excel den Aufruf nicht angenommen und damit nicht ausgeführt. Jeder
            andere Fehler, auch ein Fehler aus der Ausführung, wird sofort
            weitergereicht. `function` darf daher höchstens einen Aufruf mit
            Wirkung enthalten, und zwar als letzten; davor nur lesende Zugriffe.
            """
            if not self.retry_rejected_calls:
                return function()

            deadline: float | None = None
            retries = 0
            while True:
                try:
                    result = function()
                except pywintypes.com_error as exc:
                    if exc.hresult not in REJECTED_CALL_HRESULTS:
                        raise
                    now = time.monotonic()
                    if deadline is None:
                        deadline = now + REJECTED_CALL_RETRY_SECONDS
                        report_note(
                            f"Excel hat {call} abgelehnt ({exc.hresult}, "
                            f"{exc.strerror}). Der Aufruf wurde nicht ausgeführt und "
                            f"wird alle {REJECTED_CALL_RETRY_INTERVAL_MS} ms wiederholt, "
                            f"höchstens {REJECTED_CALL_RETRY_SECONDS} Sekunden lang."
                        )
                    elif now >= deadline:
                        report_note(
                            f"Excel lehnt {call} seit {REJECTED_CALL_RETRY_SECONDS} "
                            "Sekunden ab; keine weitere Wiederholung."
                        )
                        raise
                    retries += 1
                    time.sleep(REJECTED_CALL_RETRY_INTERVAL_MS / 1000)
                    continue
                if retries:
                    report_note(f"{call} nach {retries} Wiederholung(en) angenommen.")
                return result

        def _set_app_property(self, name: str, value) -> None:
            self.call_excel(f"Application.{name}", lambda: setattr(self.app, name, value))

        def _release_foreign_instance(self) -> None:
            # Weder Eigenschaften setzen noch `Close` oder `Quit`: Die Instanz
            # gehört möglicherweise nicht diesem Lauf. War sie doch die eigene,
            # beendet sie sich ohne Mappe nach Freigabe der letzten Referenz.
            self.excel_foreign = True
            self.app = None
            gc.collect()

        def _attach_excel_process(self) -> None:
            try:
                identity, handle = self._identify_excel_process()
            except ForeignExcelInstanceError:
                raise
            except Exception as exc:
                report_warning(
                    f"Eigene Excel-Instanz nicht eindeutig identifiziert: {exc}. Das "
                    "Beenden der Instanz kann nicht überprüft werden."
                )
                return

            self.excel_pid = identity.pid
            self.excel_process_handle = handle
            print(protocol.format_pid_line("excel", identity))

        def _excel_window_pid(self) -> int:
            _, pid = win32process.GetWindowThreadProcessId(self.app.Hwnd)
            if not pid:
                raise RuntimeError("PID über Application.Hwnd nicht ermittelbar")
            return int(pid)

        def _identify_excel_process(self):
            if self.excel_pids_before is None:
                raise RuntimeError(
                    "ohne Liste der vorher laufenden Excel-Prozesse nicht prüfbar"
                )
            pid = self._excel_window_pid()
            if pid in self.excel_pids_before:
                raise ForeignExcelInstanceError(pid)

            handle = win32api.OpenProcess(
                win32con.SYNCHRONIZE
                | win32con.PROCESS_TERMINATE
                | PROCESS_QUERY_LIMITED_INFORMATION,
                False,
                pid,
            )
            try:
                name = process_name(int(handle))
                if name.upper() != EXCEL_PROCESS_NAME:
                    raise RuntimeError(f"PID {pid} gehört zu {name}, nicht zu Excel")
                # Solange das Handle offen ist, vergibt Windows die PID nicht neu.
                # Meldet die Instanz danach noch dieselbe PID, gehört das Handle zu
                # ihr; `TerminateProcess` über dieses Handle trifft nur sie.
                if self._excel_window_pid() != pid:
                    raise RuntimeError(f"PID {pid} gehört nicht mehr zur Instanz")
                start = process_start_time(int(handle))
            except BaseException:
                win32api.CloseHandle(handle)
                raise
            return protocol.ProcessIdentity(pid=pid, start=start, name=name), handle

        def _open_document_impl(self):
            self._set_app_property("AutomationSecurity", self.automation_security)
            workbooks = self.call_excel("Application.Workbooks", lambda: self.app.Workbooks)
            workbook = self.call_excel(
                "Workbooks.Open",
                lambda: workbooks.Open(
                    Filename=str(self.doc_path),
                    UpdateLinks=XL_UPDATE_LINKS_NEVER,
                    ReadOnly=False,
                    IgnoreReadOnlyRecommended=True,
                    Notify=False,
                    AddToMru=False,
                ),
            )
            if self.call_excel("Workbook.ReadOnly", lambda: workbook.ReadOnly):
                self._close_workbook(workbook)
                raise RunnerError(
                    protocol.WORKBOOK_READ_ONLY,
                    f"{self.doc_path} wurde nur schreibgeschützt geöffnet. Die Mappe "
                    "ist vermutlich in einem anderen Excel geöffnet oder die Datei "
                    "ist schreibgeschützt.",
                )
            if self.build:
                # Ausgangswert vor jeder Neuberechnung: Gezählt werden die in
                # der Mappe gespeicherten Werte.
                try:
                    self.initial_errors = collect_formula_errors(workbook, self.call_excel)
                except Exception as exc:
                    self._close_workbook(workbook)
                    raise RunnerError(
                        protocol.FORMULA_CHECK_FAILED,
                        "Fehlerwerte nach dem Öffnen nicht zählbar. Die Mappe "
                        "wurde nicht gespeichert.",
                    ) from exc
            return workbook

        def _close_workbook(self, workbook) -> None:
            try:
                self.call_excel(
                    "Workbook.Close", lambda: workbook.Close(SaveChanges=False)
                )
            except Exception as exc:
                report_warning(f"Mappe konnte nicht geschlossen werden: {exc}")

        def _handle_form_binary_export(self, name: str) -> None:
            # Keine `.frx`-Kopie aus dem Verzeichnis der Mappe: Excel schreibt
            # das `.frx` beim Export direkt in das Quellverzeichnis.
            return

        def _handle_form_binary_import(self, name: str) -> None:
            # Keine `.frx`-Kopie in das Verzeichnis der Mappe: Excel liest das
            # `.frx` beim Import direkt neben dem `.frm`.
            return

        def import_component(self, file_path: Path, components) -> None:
            try:
                super().import_component(file_path, components)
            except Exception:
                self.failed_components.append(file_path.name)
                raise
            self.processed_components.append(file_path.name)

        def export_component(self, component, directory: Path) -> None:
            try:
                name = str(component.Name)
            except Exception:
                name = "(unbekannt)"
            try:
                super().export_component(component, directory)
            except Exception:
                self.failed_components.append(name)
                raise
            self.processed_components.append(name)

        def save_document(self) -> None:
            # Wird von `import_vba` erst nach dem letzten Import aufgerufen.
            # Der Import setzt den Zustand des VBA-Projekts zurück; die
            # Initialisierung muss daher hier und nicht früher erfolgen.
            if self.doc is None:
                return
            if self.failed_components:
                report_warning(
                    "Mappe wird wegen fehlgeschlagener Komponenten nicht gespeichert."
                )
                return

            self._initialize_formulas()
            self._check_formula_errors_before_save()
            # Ab hier ist der Stand der Datei nur noch durch die Schutzprüfung
            # nach dem Speichern oder eine geprüfte Wiederherstellung belegt.
            self.save_attempted = True
            try:
                self.call_excel("Workbook.Save", lambda: self.doc.Save())
            except Exception as exc:
                raise RunnerError(
                    protocol.SAVE_FAILED, f"{self.doc_path}: {exc}"
                ) from exc
            self.saved = True
            print(f"Mappe gespeichert: {self.doc_path}")
            self._check_formula_errors_after_save()
            self.save_verified = True

        def _initialize_formulas(self) -> None:
            workbook_name = str(self.call_excel("Workbook.Name", lambda: self.doc.Name))
            workbook_name = workbook_name.replace("'", "''")
            macro = f"'{workbook_name}'!{TRANSLATION_MACRO}"
            try:
                self.call_excel("Application.Run", lambda: self.app.Run(macro))
            except Exception as exc:
                raise RunnerError(
                    protocol.FORMULA_INITIALIZATION_FAILED,
                    f"Aufruf von {macro} fehlgeschlagen. Die Mappe wurde nicht "
                    "gespeichert.",
                ) from exc
            print(f"Übersetzungen initialisiert ({TRANSLATION_MACRO}).")

            try:
                self.call_excel(
                    "Application.CalculateFull", lambda: self.app.CalculateFull()
                )
            except Exception as exc:
                raise RunnerError(
                    protocol.FORMULA_INITIALIZATION_FAILED,
                    "Application.CalculateFull fehlgeschlagen. Die Mappe wurde "
                    "nicht gespeichert.",
                ) from exc
            print("Alle Formeln neu berechnet (Application.CalculateFull).")

        def _check_formula_errors_before_save(self) -> None:
            try:
                current = collect_formula_errors(self.doc, self.call_excel)
            except Exception as exc:
                raise RunnerError(
                    protocol.FORMULA_CHECK_FAILED,
                    "Fehlerwerte nach der Neuberechnung nicht zählbar. Die Mappe "
                    "wurde nicht gespeichert.",
                ) from exc

            print(
                "Schutzprüfung nach der Neuberechnung (Fehlerwerte vorher/nachher): "
                + format_error_counts(self.initial_errors, current)
            )
            increases = find_error_increases(self.initial_errors, current)
            if increases:
                raise RunnerError(
                    protocol.FORMULA_CHECK_FAILED,
                    "Fehlerwerte in Formelzellen nach der Neuberechnung gestiegen: "
                    + "; ".join(increases)
                    + ". Die Mappe wurde nicht gespeichert.",
                )

        def _check_formula_errors_after_save(self) -> None:
            # Ob Excel beim Speichern erneut rechnet, ist nicht steuerbar.
            try:
                current = collect_formula_errors(self.doc, self.call_excel)
            except Exception:
                self._restore_backup("Fehlerwerte nach dem Speichern nicht zählbar")

            print(
                "Schutzprüfung nach dem Speichern (Fehlerwerte vorher/nachher): "
                + format_error_counts(self.initial_errors, current)
            )
            increases = find_error_increases(self.initial_errors, current)
            if increases:
                self._restore_backup(
                    "Fehlerwerte in Formelzellen nach dem Speichern gestiegen: "
                    + "; ".join(increases)
                )

        def create_backup(self) -> None:
            """Sichert die Mappe vor dem Öffnen.

            Kopiert wird in eine Zwischendatei. Erst wenn Größe und SHA-256 mit
            der Mappe übereinstimmen, erhält sie den endgültigen Namen. Eine
            Datei mit endgültigem Namen ist damit immer vollständig.
            """
            incomplete_path: Path | None = None
            try:
                handle, incomplete_path, final_path = reserve_backup_paths()
                with os.fdopen(handle, "wb") as target:
                    print(
                        f"{protocol.BACKUP_LINE_PREFIX}{protocol.BACKUP_INCOMPLETE}="
                        f"{incomplete_path}"
                    )
                    with open(self.doc_path, "rb") as source:
                        shutil.copyfileobj(source, target)
                    target.flush()
                    os.fsync(target.fileno())
                if file_digest(incomplete_path) != file_digest(self.doc_path):
                    raise OSError("Kopie weicht in Größe oder SHA-256 von der Mappe ab")
                # Anders als `os.replace` überschreibt `os.rename` unter Windows
                # keine vorhandene Datei.
                os.rename(incomplete_path, final_path)
                self.backup_path = final_path
            except BaseException as exc:
                if incomplete_path is not None:
                    self._discard_incomplete_backup(incomplete_path)
                if not isinstance(exc, Exception):
                    raise
                raise RunnerError(
                    protocol.BACKUP_FAILED,
                    f"{self.doc_path} nicht gesichert ({exc}). Der Build wurde nicht "
                    "gestartet.",
                ) from exc
            print(f"{protocol.BACKUP_LINE_PREFIX}{protocol.BACKUP_CREATED}={final_path}")
            print(f"Sicherung der Mappe: {final_path}")

        def _discard_incomplete_backup(self, path: Path) -> None:
            try:
                path.unlink(missing_ok=True)
            except OSError as exc:
                report_warning(
                    f"Unvollständige Sicherungsdatei {path} nicht entfernt: {exc}. Sie "
                    "ist keine Sicherung, darf nie zur Wiederherstellung verwendet "
                    "werden und kann gelöscht werden."
                )

        def _restore_backup(self, problem: str) -> NoReturn:
            self.saved = False
            if self.doc is not None:
                self._close_workbook(self.doc)
                self.doc = None

            if self.backup_path is None:
                raise RunnerError(
                    protocol.RESTORE_FAILED,
                    f"{problem}. Die Mappe {self.doc_path} enthält den fehlerhaften "
                    "Stand, eine Sicherung ist nicht vorhanden.",
                )

            last_error: Exception | None = None
            for attempt in range(RESTORE_ATTEMPTS):
                if attempt:
                    time.sleep(RESTORE_RETRY_SECONDS)
                try:
                    shutil.copyfile(self.backup_path, self.doc_path)
                    if filecmp.cmp(self.backup_path, self.doc_path, shallow=False):
                        last_error = None
                        break
                    last_error = OSError("Inhalt weicht nach dem Kopieren ab")
                except Exception as exc:
                    last_error = exc

            if last_error is not None:
                raise RunnerError(
                    protocol.RESTORE_FAILED,
                    f"{problem}. Die Mappe {self.doc_path} enthält den fehlerhaften "
                    f"Stand (Wiederherstellung: {last_error}). Ausgangsstand "
                    f"gesichert unter {self.backup_path}.",
                )

            self.restore_verified = True
            raise RunnerError(
                protocol.FORMULA_CHECK_FAILED,
                f"{problem}. Ausgangsstand der Mappe aus der Sicherung "
                "wiederhergestellt.",
            )

        def _backup_removable(self) -> bool:
            if self.save_verified or self.restore_verified:
                return True
            if self.save_attempted:
                return False
            # Ohne Speicherversuch hat der Runner nicht in die Mappe geschrieben;
            # der Inhaltsvergleich weist nach, dass sie unverändert ist.
            return filecmp.cmp(self.backup_path, self.doc_path, shallow=False)

        def finish_backup(self) -> None:
            """Entfernt die Sicherung nur bei belegtem Stand der Mappe.

            Belegt ist er nach bestandener Schutzprüfung nach dem Speichern, nach
            geprüfter Wiederherstellung oder, ohne Speicherversuch, durch
            Inhaltsgleichheit mit der Sicherung. Sonst bleibt sie erhalten.
            """
            if self.backup_path is None:
                return
            try:
                removable = self._backup_removable()
            except BaseException as exc:
                report_warning(f"Mappe nicht mit der Sicherung vergleichbar: {exc!r}")
                removable = False
            if not removable:
                print(
                    f"{protocol.BACKUP_LINE_PREFIX}{protocol.BACKUP_KEPT}="
                    f"{self.backup_path}"
                )
                report_warning(
                    f"Sicherung der Mappe bleibt erhalten: {self.backup_path}. Sie "
                    f"enthält den Stand vor dem Build. Ob {self.doc_path} unverändert "
                    "oder vollständig gespeichert ist, ist nicht nachgewiesen. "
                    "Wiederherstellen bei geschlossenem Excel, z. B. in PowerShell: "
                    f"Copy-Item -LiteralPath {quote_powershell(str(self.backup_path))} "
                    f"-Destination {quote_powershell(str(self.doc_path))} -Force. "
                    "Nach Prüfung der Mappe kann die Sicherung gelöscht werden."
                )
                return

            try:
                self.backup_path.unlink(missing_ok=True)
            except OSError as exc:
                print(
                    f"{protocol.BACKUP_LINE_PREFIX}{protocol.BACKUP_OBSOLETE}="
                    f"{self.backup_path}"
                )
                report_warning(
                    f"Sicherung {self.backup_path} konnte nicht entfernt werden: "
                    f"{exc}. Der Stand von {self.doc_path} ist geprüft; die "
                    "Sicherung kann gelöscht werden."
                )
                return
            print(f"{protocol.BACKUP_LINE_PREFIX}{protocol.BACKUP_REMOVED}")

        def run_import(self) -> None:
            self.create_backup()
            self.import_vba()
            if self.failed_components:
                raise RunnerError(
                    protocol.IMPORT_COMPONENTS_FAILED,
                    "Die Mappe wurde nicht gespeichert. Betroffene Komponenten: "
                    + ", ".join(self.failed_components),
                )
            if not self.saved:
                raise RunnerError(
                    protocol.UNEXPECTED_ERROR,
                    "Import ohne Fehlermeldung beendet, die Mappe wurde aber nicht "
                    "gespeichert.",
                )
            print(f"{len(self.processed_components)} Komponenten importiert.")

        def run_export(self) -> None:
            self.export_vba(
                save_metadata=False,
                overwrite=True,
                interactive=False,
                keep_open=True,
            )
            if self.failed_components:
                raise RunnerError(
                    protocol.EXPORT_COMPONENTS_FAILED,
                    "Betroffene Komponenten: " + ", ".join(self.failed_components),
                )
            vba_project = self.get_vba_project()
            component_count = self.call_excel(
                "VBComponents.Count", lambda: vba_project.VBComponents.Count
            )
            if len(self.processed_components) != component_count:
                raise RunnerError(
                    protocol.EXPORT_COMPONENTS_FAILED,
                    f"{len(self.processed_components)} von {component_count} "
                    "Komponenten exportiert.",
                )
            print(f"{component_count} Komponenten exportiert.")

        def shutdown(self) -> int:
            if self.excel_foreign:
                # Kein `Close` und kein `Quit`, siehe `_release_foreign_instance`.
                self.doc = None
                self.app = None
                gc.collect()
                return protocol.SUCCESS

            if self.doc is not None:
                self._close_workbook(self.doc)
                self.doc = None

            if self.app is not None:
                try:
                    self.call_excel("Application.Quit", lambda: self.app.Quit())
                except Exception as exc:
                    report_warning(f"Excel.Quit fehlgeschlagen: {exc}")
                self.app = None

            gc.collect()
            return self._wait_for_excel_exit()

        def _wait_for_excel_exit(self) -> int:
            if not self.excel_started:
                return protocol.SUCCESS

            handle = self.excel_process_handle
            if handle is None:
                instance = (
                    f"PID {self.excel_pid}" if self.excel_pid else "PID unbekannt"
                )
                report_warning(
                    f"Ob die eigene Excel-Instanz ({instance}) beendet ist, konnte "
                    "nicht überprüft werden. Im Task-Manager prüfen, dass kein "
                    "unsichtbares Excel übrig ist."
                )
                return protocol.SUCCESS

            self.excel_process_handle = None
            try:
                if (
                    win32event.WaitForSingleObject(handle, EXCEL_EXIT_WAIT_MS)
                    == win32event.WAIT_OBJECT_0
                ):
                    print(f"Eigene Excel-Instanz (PID {self.excel_pid}) beendet.")
                    return protocol.SUCCESS

                report_warning(
                    f"Eigene Excel-Instanz (PID {self.excel_pid}) hat sich nicht "
                    "beendet und wird zwangsweise beendet."
                )
                try:
                    win32api.TerminateProcess(handle, 1)
                except Exception as exc:
                    report_error(
                        protocol.EXCEL_NOT_TERMINATED, f"PID {self.excel_pid}: {exc}"
                    )
                    return protocol.EXCEL_NOT_TERMINATED

                if (
                    win32event.WaitForSingleObject(handle, EXCEL_TERMINATE_WAIT_MS)
                    != win32event.WAIT_OBJECT_0
                ):
                    report_error(
                        protocol.EXCEL_NOT_TERMINATED,
                        f"PID {self.excel_pid} läuft weiter.",
                    )
                    return protocol.EXCEL_NOT_TERMINATED

                print(f"Eigene Excel-Instanz (PID {self.excel_pid}) beendet.")
                return protocol.SUCCESS
            finally:
                win32api.CloseHandle(handle)

    return IsolatedExcelVBAHandler


def iter_exception_chain(exc: BaseException):
    seen: set[int] = set()
    current: BaseException | None = exc
    while current is not None and id(current) not in seen:
        seen.add(id(current))
        yield current
        following = current.__cause__
        if following is None and not current.__suppress_context__:
            following = current.__context__
        current = following


def classify_exception(exc: BaseException) -> tuple[int, str]:
    try:
        from vba_edit.exceptions import DocumentNotFoundError, VBAAccessError, VBAError
    except ImportError:
        DocumentNotFoundError = VBAAccessError = VBAError = ()

    chain = list(iter_exception_chain(exc))
    messages: list[str] = []
    for item in chain:
        message = str(item).strip()
        if message and message not in messages:
            messages.append(message)
    detail = " | ".join(messages) or type(exc).__name__

    for item in chain:
        if isinstance(item, RunnerError):
            return item.exit_code, detail
    for item in chain:
        if isinstance(item, VBAAccessError):
            return protocol.VBA_PROJECT_ACCESS_DENIED, detail
    for item in chain:
        if isinstance(item, DocumentNotFoundError):
            return protocol.MISSING_INPUT, detail

    try:
        from pywintypes import com_error
    except ImportError:
        com_error = ()
    for item in chain:
        if isinstance(item, VBAError) or isinstance(item, com_error):
            return protocol.COM_ERROR, detail

    return protocol.UNEXPECTED_ERROR, f"{type(exc).__name__}: {detail}"


def run(args: argparse.Namespace) -> int:
    handler = None
    exit_code = protocol.UNEXPECTED_ERROR
    try:
        try:
            handler_class = create_handler_class()
            handler = handler_class(
                doc_path=args.workbook,
                vba_dir=args.vba_directory,
                encoding=ENCODING,
                in_file_headers=True,
                build=args.mode == "import",
            )
            if args.mode == "import":
                handler.run_import()
            else:
                handler.run_export()
            exit_code = protocol.SUCCESS
        except Exception as exc:
            exit_code, detail = classify_exception(exc)
            report_error(exit_code, detail)
        except KeyboardInterrupt:
            exit_code = protocol.INTERRUPTED
            report_error(exit_code, "Lauf im Runner abgebrochen.")
        except BaseException as exc:
            exit_code = protocol.UNEXPECTED_ERROR
            report_error(exit_code, f"{type(exc).__name__}: {exc}")
    finally:
        if handler is not None:
            try:
                shutdown_code = handler.shutdown()
            except KeyboardInterrupt:
                shutdown_code = protocol.INTERRUPTED
                report_error(
                    shutdown_code,
                    "Aufräumen abgebrochen. Im Task-Manager prüfen, dass kein "
                    "unsichtbares Excel übrig ist.",
                )
            except Exception as exc:
                shutdown_code = protocol.EXCEL_NOT_TERMINATED
                report_error(shutdown_code, f"Aufräumen fehlgeschlagen: {exc}")
            handler.finish_backup()
            if exit_code == protocol.SUCCESS:
                exit_code = shutdown_code

    return exit_code


def main(argv: list[str] | None = None) -> int:
    configure_output()
    # Startmeldung vor der Argumentprüfung: Der Wrapper unterscheidet daran
    # Exit-Codes des Runners von denen von `py` bzw. `python.exe`.
    print(protocol.format_pid_line("runner", own_process_identity()))
    args = parse_arguments(argv)

    try:
        check_environment()
        check_inputs(args)
    except RunnerError as exc:
        report_error(exc.exit_code, str(exc))
        return exc.exit_code
    except KeyboardInterrupt:
        report_error(protocol.INTERRUPTED, "Lauf im Runner abgebrochen.")
        return protocol.INTERRUPTED

    return run(args)


if __name__ == "__main__":
    raise SystemExit(main())
