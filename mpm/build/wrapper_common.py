"""Gemeinsamer Ablauf von `build_mpm.py` und `export_mpm.py` auf dem WSL-Host.

Die Wrapper rufen `vba_runner.py` über `powershell.exe` mit Windows-Python
(`py`) auf, reichen dessen Exit-Code unverändert durch und beenden bei
Zeitüberschreitung oder Strg+C gezielt den Runner und dessen Excel-Instanz,
nur wenn PID, Prozessname und Startzeit mit den Angaben des Runners
übereinstimmen.
"""

from __future__ import annotations

import argparse
import re
import shlex
import subprocess
import sys
import threading
from collections.abc import Callable
from pathlib import Path

import runner_protocol as protocol

DEFAULT_TIMEOUT_SECONDS = 300
PROCESS_EXIT_WAIT_SECONDS = 30
PROCESS_STOP_WAIT_MS = 15000
READER_JOIN_SECONDS = 10

BUILD_DIR = Path(__file__).resolve().parent
MPM_DIR = BUILD_DIR.parent
RUNNER_PATH = BUILD_DIR / "vba_runner.py"
WORKBOOK_PATH = MPM_DIR / "dist" / "MPM.xlsm"
LOCK_FILE_PATH = MPM_DIR / "dist" / "~$MPM.xlsm"
SOURCE_DIR = MPM_DIR / "src"

# Zulässige Prozessnamen (ohne `.exe`) der vom Runner gemeldeten Prozesse. Die
# Muster lassen nur Zeichen zu, die im PowerShell-Befehl unkritisch sind.
EXCEL_PROCESS_NAME_PATTERN = re.compile(r"EXCEL", re.IGNORECASE)
RUNNER_PROCESS_NAME_PATTERN = re.compile(r"pythonw?(\d+(\.\d+)*)?", re.IGNORECASE)

# Temporäre Dateien, die `vba-edit` 0.5.1 in `src/` anlegt: `<Name>.tmp`
# beim Export, `<Name>_temp.<Endung>` beim Import über eine Hilfsdatei.
TEMP_FILE_PATTERNS = {
    "export": ("*.tmp",),
    "import": ("*_temp.bas", "*_temp.cls", "*_temp.frm"),
}

STOPPED = "stopped"
NOT_RUNNING = "not_running"
MISMATCH = "mismatch"
UNVERIFIED = "unverified"
STOP_FAILED = "failed"


def decode_output(output: bytes) -> str:
    for encoding in ("utf-8", "cp1252", "cp850", "latin-1"):
        try:
            return output.decode(encoding)
        except UnicodeDecodeError:
            continue

    return output.decode("utf-8", errors="replace")


def to_windows_path(path: Path) -> str:
    result = subprocess.run(
        ["wslpath", "-w", str(path)],
        check=False,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or "wslpath -w ist fehlgeschlagen.")

    return result.stdout.strip()


def to_wsl_path(windows_path: str) -> str | None:
    result = subprocess.run(
        ["wslpath", "-u", windows_path],
        check=False,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0 or not result.stdout.strip():
        return None

    return result.stdout.strip()


def quote_powershell(value: str) -> str:
    return "'" + value.replace("'", "''") + "'"


def parse_arguments(
    description: str,
    configure_parser: Callable[[argparse.ArgumentParser], None] | None = None,
) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=description)
    parser.add_argument(
        "--timeout",
        type=int,
        default=DEFAULT_TIMEOUT_SECONDS,
        metavar="SEKUNDEN",
        help=(
            "Maximale Laufzeit des Runners in Sekunden "
            f"(Standard: {DEFAULT_TIMEOUT_SECONDS})"
        ),
    )
    if configure_parser is not None:
        configure_parser(parser)
    args = parser.parse_args()
    if args.timeout <= 0:
        parser.error("--timeout muss größer als 0 sein.")

    return args


def print_error(exit_code: int, detail: str) -> None:
    print(
        f"FEHLER [{exit_code}] {protocol.describe(exit_code)}: {detail}",
        file=sys.stderr,
    )


def print_hint(message: str) -> None:
    print(f"Hinweis: {message}", file=sys.stderr)


def build_powershell_command(
    mode: str, runner: str, workbook: str, source_dir: str
) -> str:
    # Ohne Prüfung auf `py` würde `exit $LASTEXITCODE` bei fehlendem Launcher
    # den Exit-Code 0 liefern.
    return (
        "if (-not (Get-Command py -CommandType Application "
        "-ErrorAction SilentlyContinue)) { "
        "[Console]::Error.WriteLine('Windows-Python-Launcher py nicht gefunden.'); "
        f"exit {protocol.WINDOWS_PYTHON_UNAVAILABLE} }}; "
        f"& py {quote_powershell(runner)} {mode} "
        f"{quote_powershell(workbook)} "
        f"--vba-directory {quote_powershell(source_dir)}; "
        "exit $LASTEXITCODE"
    )


def run_powershell(command: str) -> str | None:
    try:
        result = subprocess.run(
            ["powershell.exe", "-NoProfile", "-NonInteractive", "-Command", command],
            check=False,
            capture_output=True,
            timeout=PROCESS_EXIT_WAIT_SECONDS,
        )
    except (OSError, subprocess.TimeoutExpired):
        return None

    if result.returncode != 0:
        return None

    return decode_output(result.stdout).strip()


def build_stop_command(identity: protocol.ProcessIdentity) -> str:
    # Erst lesend prüfen, dann über ein frisches Prozessobjekt ein Handle
    # halten (`.Handle`) und die Startzeit erneut über dieses Handle lesen:
    # Solange es offen ist, vergibt Windows die PID nicht neu, und `Kill()`
    # sowie `WaitForExit()` verwenden dasselbe Handle.
    pid = identity.pid
    return (
        f"$name = {quote_powershell(identity.name or '')}; "
        f"$start = [long]{identity.start}; "
        f"try {{ $p = Get-Process -Id {pid} -ErrorAction Stop }} "
        f"catch {{ '{NOT_RUNNING}'; exit 0 }}; "
        "if ($p.ProcessName -ne $name) { "
        f"'{MISMATCH} Prozessname ' + $p.ProcessName; exit 0 }}; "
        "try { $actual = $p.StartTime.ToFileTimeUtc() } "
        f"catch {{ '{UNVERIFIED} Startzeit nicht lesbar'; exit 0 }}; "
        f"if ($actual -ne $start) {{ '{MISMATCH} Startzeit ' + $actual; exit 0 }}; "
        f"try {{ $q = [System.Diagnostics.Process]::GetProcessById({pid}); "
        "$null = $q.Handle; $actual = $q.StartTime.ToFileTimeUtc() } "
        f"catch {{ if (Get-Process -Id {pid} -ErrorAction SilentlyContinue) "
        f"{{ '{UNVERIFIED} kein Prozess-Handle' }} else {{ '{NOT_RUNNING}' }}; "
        "exit 0 }; "
        f"if ($actual -ne $start) {{ '{MISMATCH} Startzeit ' + $actual; exit 0 }}; "
        f"if ($q.HasExited) {{ '{NOT_RUNNING}'; exit 0 }}; "
        "try { $q.Kill() } catch { }; "
        f"if ($q.WaitForExit({PROCESS_STOP_WAIT_MS})) {{ '{STOPPED}' }} "
        f"else {{ '{STOP_FAILED}' }}"
    )


def stop_windows_process(
    identity: protocol.ProcessIdentity, name_pattern: re.Pattern[str]
) -> tuple[str, str]:
    """Beendet den Prozess nur, wenn PID, Prozessname und Startzeit stimmen.

    Liefert Status und Detail. Nur bei `STOPPED` und `NOT_RUNNING` steht fest,
    dass der gemeldete Prozess nicht mehr läuft.
    """
    if not identity.verifiable:
        return UNVERIFIED, "Startzeit oder Prozessname nicht gemeldet"
    if not name_pattern.fullmatch(identity.name or ""):
        return UNVERIFIED, f"gemeldeter Prozessname {identity.name} nicht zulässig"

    output = run_powershell(build_stop_command(identity))
    status, _, detail = (output or "").partition(" ")
    if status in (STOPPED, NOT_RUNNING, MISMATCH, UNVERIFIED):
        return status, detail

    return STOP_FAILED, output or "PowerShell-Aufruf fehlgeschlagen"


def stop_reported_process(
    label: str, identity: protocol.ProcessIdentity, name_pattern: re.Pattern[str]
) -> bool:
    """Beendet einen vom Runner gemeldeten Prozess; True, wenn er sicher endete."""
    status, detail = stop_windows_process(identity, name_pattern)
    pid = identity.pid
    if status == STOPPED:
        print(
            f"{label} (PID {pid}, Startzeit {identity.start}) beendet.",
            file=sys.stderr,
        )
    elif status == NOT_RUNNING:
        print(f"{label} (PID {pid}) läuft nicht mehr.", file=sys.stderr)
    elif status == MISMATCH:
        print_hint(
            f"PID {pid} gehört nicht mehr zum gemeldeten Prozess ({label}, "
            f"Abweichung: {detail}). Der Prozess mit dieser PID wurde nicht beendet."
        )
    elif status == UNVERIFIED:
        print_hint(
            f"{label} (PID {pid}) nicht beendet, weil seine Identität nicht prüfbar "
            f"ist ({detail}). Im Task-Manager prüfen."
        )
    else:
        print_hint(
            f"{label} (PID {pid}) ließ sich nicht sicher beenden ({detail}). Im "
            "Task-Manager prüfen."
        )

    return status in (STOPPED, NOT_RUNNING)


def settle_incomplete_backup(runner: RunnerProcess) -> None:
    """Klärt eine Sicherung, deren Fertigstellung der Runner nicht gemeldet hat.

    Der Runner benennt die Zwischendatei erst nach geprüfter Kopie auf den
    endgültigen Namen um. Nur eine Datei mit endgültigem Namen gilt als
    Sicherung. Die Zwischendatei gehört diesem Lauf (der Runner hat sie gemeldet)
    und wird entfernt, wenn der Runner sicher beendet ist.
    """
    incomplete = runner.incomplete_backup_path
    final = protocol.final_backup_path(incomplete) if incomplete else None
    if incomplete is None or final is None:
        return

    final_wsl = to_wsl_path(final)
    if final_wsl is None:
        print_hint(
            f"Ob die Sicherung {final} vorhanden ist, ließ sich nicht prüfen "
            "(wslpath -u). Nur eine Datei mit diesem Namen ist eine vollständige "
            "Sicherung."
        )
    elif Path(final_wsl).exists():
        runner.backup_path = final

    incomplete_wsl = to_wsl_path(incomplete)
    if incomplete_wsl is not None and not Path(incomplete_wsl).exists():
        return

    location = incomplete if incomplete_wsl is None else incomplete_wsl
    never_use = (
        "Sie ist keine Sicherung und darf nie zur Wiederherstellung verwendet werden."
    )
    if incomplete_wsl is None or not runner.runner_ended:
        reason = (
            "das Ende des Runners nicht sicher feststeht"
            if incomplete_wsl is not None
            else "der WSL-Pfad nicht ermittelbar ist"
        )
        print_hint(
            f"Unvollständige Sicherungsdatei {location} nicht entfernt, weil "
            f"{reason}. {never_use} Nach Ende des Runners kann sie gelöscht werden."
        )
        return

    try:
        Path(incomplete_wsl).unlink()
        print_hint(f"Unvollständige Sicherungsdatei dieses Laufs entfernt: {location}")
    except OSError as exc:
        print_hint(
            f"Unvollständige Sicherungsdatei {location} nicht entfernt: {exc}. "
            f"{never_use} Sie kann gelöscht werden."
        )


def report_backup(runner: RunnerProcess, exit_code: int) -> None:
    """Nennt eine verbliebene Sicherung der Mappe mit Windows- und WSL-Pfad."""
    if runner.backup_state == protocol.BACKUP_INCOMPLETE:
        settle_incomplete_backup(runner)
    if runner.backup_path is None or runner.backup_state == protocol.BACKUP_REMOVED:
        return

    wsl_path = to_wsl_path(runner.backup_path)
    if wsl_path is not None and not Path(wsl_path).exists():
        return

    location = runner.backup_path
    if wsl_path is not None:
        location += f" (WSL: {wsl_path})"
    if runner.backup_state == protocol.BACKUP_OBSOLETE:
        print_hint(
            f"Die Sicherung {location} wird nicht mehr benötigt und kann gelöscht "
            "werden."
        )
        return

    if runner.backup_state == protocol.BACKUP_KEPT:
        state = (
            "Der Runner konnte nicht belegen, dass mpm/dist/MPM.xlsm unverändert "
            "oder vollständig gespeichert ist."
        )
    else:
        state = (
            f"Der Lauf endete (Exit-Code {exit_code}), bevor der Runner den Stand "
            "von mpm/dist/MPM.xlsm prüfen konnte."
        )
    if wsl_path is not None:
        restore = f"cp {shlex.quote(wsl_path)} {shlex.quote(str(WORKBOOK_PATH))}"
    else:
        restore = (
            f"in PowerShell Copy-Item -LiteralPath {quote_powershell(runner.backup_path)} "
            "-Destination <Windows-Pfad von mpm/dist/MPM.xlsm> -Force"
        )
    print_hint(
        f"{state} Die Sicherung des Stands vor dem Build bleibt erhalten: {location}. "
        f"Wiederherstellen bei geschlossenem Excel: {restore}. Alternativ "
        "git checkout -- mpm/dist/MPM.xlsm (nur committeter Stand). Nach Prüfung "
        "der Mappe kann die Sicherung gelöscht werden."
    )


def count_excel_processes() -> int | None:
    output = run_powershell(
        "@(Get-Process -Name EXCEL -ErrorAction SilentlyContinue).Count"
    )
    if output is None or not output.isdigit():
        return None

    return int(output)


def list_temp_files(mode: str) -> set[Path]:
    if not SOURCE_DIR.is_dir():
        return set()

    return {
        path
        for pattern in TEMP_FILE_PATTERNS[mode]
        for path in SOURCE_DIR.glob(pattern)
        if path.is_file()
    }


class RunnerProcess:
    def __init__(self, command: list[str]) -> None:
        self.pids: dict[str, protocol.ProcessIdentity] = {}
        self.backup_path: str | None = None
        self.incomplete_backup_path: str | None = None
        self.backup_state: str | None = None
        self.runner_ended = False
        self.process = subprocess.Popen(
            command,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
        self.readers = [
            threading.Thread(
                target=self._forward, args=(self.process.stdout, sys.stdout), daemon=True
            ),
            threading.Thread(
                target=self._forward, args=(self.process.stderr, sys.stderr), daemon=True
            ),
        ]
        for reader in self.readers:
            reader.start()

    def _forward(self, source, target) -> None:
        for raw_line in source:
            line = decode_output(raw_line).rstrip("\r\n")
            if target is sys.stdout:
                self._remember(line)
            print(line, file=target, flush=True)

    def _remember(self, line: str) -> None:
        parsed = protocol.parse_pid_line(line)
        if parsed is not None:
            role, identity = parsed
            self.pids[role] = identity
            return

        if line.startswith(protocol.BACKUP_LINE_PREFIX):
            state, _, path = line[len(protocol.BACKUP_LINE_PREFIX):].partition("=")
            state, path = state.strip(), path.strip()
            if state == protocol.BACKUP_INCOMPLETE:
                self.incomplete_backup_path = path or None
            elif path:
                self.backup_path = path
            self.backup_state = state

    @property
    def runner_started(self) -> bool:
        return "runner" in self.pids

    def wait(self, timeout: int) -> int:
        return_code = self.process.wait(timeout=timeout)
        # `powershell.exe` endet erst nach `py` und dem Runner.
        self.runner_ended = True
        self.join_readers()
        return return_code

    def join_readers(self) -> None:
        for reader in self.readers:
            reader.join(timeout=READER_JOIN_SECONDS)

    def terminate(self) -> bool:
        """Beendet Excel-Instanz und Runner; True, wenn beide sicher beendet sind."""
        excel = self.pids.get("excel")
        excel_ended: bool | None = None
        if excel is None:
            print_hint(
                "PID der Excel-Instanz des Runners unbekannt. Eine vom Runner "
                "gestartete Excel-Instanz ggf. im Task-Manager prüfen."
            )
        else:
            excel_ended = stop_reported_process(
                "Excel-Instanz des Runners", excel, EXCEL_PROCESS_NAME_PATTERN
            )

        runner = self.pids.get("runner")
        runner_ended = False
        if runner is not None:
            runner_ended = stop_reported_process(
                "Runner-Prozess", runner, RUNNER_PROCESS_NAME_PATTERN
            )
        self.runner_ended = runner_ended

        self.process.kill()
        try:
            self.process.wait(timeout=PROCESS_EXIT_WAIT_SECONDS)
        except subprocess.TimeoutExpired:
            pass
        self.join_readers()

        if not runner_ended:
            return False
        if excel_ended is None:
            # Ohne bekannte PID gilt die Instanz nur als beendet, wenn
            # überhaupt kein Excel mehr läuft.
            return count_excel_processes() == 0

        return excel_ended


def clean_up_after_abort(
    mode: str, temp_files_before: set[Path], own_processes_ended: bool
) -> None:
    new_temp_files = sorted(list_temp_files(mode) - temp_files_before)
    lock_file_exists = LOCK_FILE_PATH.exists()
    if not new_temp_files and not lock_file_exists:
        return

    if not own_processes_ended:
        kept_files = list(new_temp_files)
        if lock_file_exists:
            kept_files.append(LOCK_FILE_PATH)
        for path in kept_files:
            print_hint(
                f"{path} nicht entfernt, weil das Ende von Runner und "
                "Excel-Instanz nicht sicher feststeht."
            )
        return

    for path in new_temp_files:
        try:
            path.unlink()
            print_hint(f"Temporäre Datei des abgebrochenen Laufs entfernt: {path}")
        except OSError as exc:
            print_hint(f"Temporäre Datei {path} nicht entfernt: {exc}")

    # Die Sperrdatei bestand vor dem Lauf nicht (sonst Exit-Code 30). Sie wird
    # nur entfernt, wenn keine Excel-Instanz mehr läuft, die die Mappe halten
    # könnte.
    if lock_file_exists:
        excel_count = count_excel_processes()
        if excel_count == 0:
            try:
                LOCK_FILE_PATH.unlink()
                print_hint(
                    f"{LOCK_FILE_PATH} entfernt (keine Excel-Instanz mehr aktiv)."
                )
            except OSError as exc:
                print_hint(f"{LOCK_FILE_PATH} nicht entfernt: {exc}")
        elif excel_count is None:
            print_hint(
                f"{LOCK_FILE_PATH} nicht entfernt, weil die laufenden "
                "Excel-Instanzen nicht ermittelt werden konnten."
            )
        else:
            print_hint(
                f"{LOCK_FILE_PATH} nicht entfernt, weil noch {excel_count} "
                "Excel-Instanz(en) laufen, die die Mappe halten könnten."
            )


def check_lock_file_after_run(exit_code: int) -> int:
    if not LOCK_FILE_PATH.exists():
        return exit_code

    detail = (
        f"{LOCK_FILE_PATH} ist vorhanden. Prüfen, dass keine Excel-Instanz "
        "MPM.xlsm geöffnet hat, und die Datei danach entfernen."
    )
    if exit_code == protocol.SUCCESS:
        print_error(protocol.LOCK_FILE_AFTER_RUN, detail)
        return protocol.LOCK_FILE_AFTER_RUN

    print_hint(detail)
    return exit_code


def execute(mode: str, timeout: int) -> int:
    if not WORKBOOK_PATH.exists():
        print_error(
            protocol.MISSING_INPUT,
            "Die Datei mpm/dist/MPM.xlsm wurde nicht gefunden. "
            "Bitte zuerst die kanonische Ausgangsdatei bereitstellen.",
        )
        return protocol.MISSING_INPUT

    if mode == "import":
        if not SOURCE_DIR.exists():
            print_error(
                protocol.MISSING_INPUT,
                "Das Verzeichnis mpm/src wurde nicht gefunden. "
                "Bitte zuerst den VBA-Quellcode exportieren.",
            )
            return protocol.MISSING_INPUT
    else:
        SOURCE_DIR.mkdir(parents=True, exist_ok=True)

    if LOCK_FILE_PATH.exists():
        print_error(
            protocol.LOCK_FILE_BEFORE_RUN,
            f"{LOCK_FILE_PATH} ist vorhanden. MPM.xlsm ist vermutlich in Excel "
            "geöffnet. Excel schließen; bleibt die Datei bestehen, sie entfernen.",
        )
        return protocol.LOCK_FILE_BEFORE_RUN

    try:
        windows_runner_path = to_windows_path(RUNNER_PATH)
        windows_workbook_path = to_windows_path(WORKBOOK_PATH)
        windows_source_dir = to_windows_path(SOURCE_DIR)
        powershell_command = build_powershell_command(
            mode, windows_runner_path, windows_workbook_path, windows_source_dir
        )
    except RuntimeError as exc:
        print_error(protocol.PATH_CONVERSION_FAILED, str(exc))
        return protocol.PATH_CONVERSION_FAILED

    if mode == "import":
        print(f"Importiere VBA-Quellcode nach {windows_workbook_path} ...", flush=True)
    else:
        print(
            "Exportiere VBA-Quellcode aus "
            f"{windows_workbook_path} nach {windows_source_dir} ...",
            flush=True,
        )

    temp_files_before = list_temp_files(mode)
    try:
        runner = RunnerProcess(
            [
                "powershell.exe",
                "-NoProfile",
                "-NonInteractive",
                "-Command",
                powershell_command,
            ]
        )
    except OSError as exc:
        print_error(protocol.WINDOWS_PYTHON_UNAVAILABLE, str(exc))
        return protocol.WINDOWS_PYTHON_UNAVAILABLE

    try:
        exit_code = runner.wait(timeout)
    except (subprocess.TimeoutExpired, KeyboardInterrupt) as exc:
        if isinstance(exc, subprocess.TimeoutExpired):
            exit_code = protocol.TIMEOUT
            print_error(
                exit_code, f"Der Runner hat nach {timeout} Sekunden nicht geantwortet."
            )
        else:
            exit_code = protocol.INTERRUPTED
            print_error(exit_code, "Lauf abgebrochen.")
        own_processes_ended = runner.terminate()
        clean_up_after_abort(mode, temp_files_before, own_processes_ended)
        if mode == "import" and exit_code == protocol.TIMEOUT:
            print_hint(
                "Beim Build kann ein Dialog im MPM-Code die Ursache sein, z. B. eine "
                "MsgBox in sFuelleCollection oder in den Formelfunktionen oder eine "
                "Kompilierfehlermeldung beim Aufruf. Die unsichtbare Excel-Instanz "
                "wartet dann auf eine Eingabe, die niemand geben kann."
            )
        report_backup(runner, exit_code)
        return exit_code

    # Ohne Startmeldung stammt der Exit-Code nicht vom Runner, sondern z. B.
    # vom `py`-Launcher (keine passende Laufzeit) oder von `python.exe`
    # (Script nicht lesbar, Exit-Code 2).
    if not runner.runner_started and exit_code != protocol.WINDOWS_PYTHON_UNAVAILABLE:
        print_error(
            protocol.RUNNER_NOT_STARTED,
            f"powershell.exe endete mit Exit-Code {exit_code}, ohne dass der Runner "
            "gestartet wurde. In PowerShell 'py --version' und den Zugriff auf "
            f"{windows_runner_path} prüfen.",
        )
        exit_code = protocol.RUNNER_NOT_STARTED

    report_backup(runner, exit_code)
    return check_lock_file_after_run(exit_code)


def finish(mode: str, exit_code: int) -> int:
    label = "Build" if mode == "import" else "Export"
    if exit_code == protocol.SUCCESS:
        print(f"{label} erfolgreich abgeschlossen (Exit-Code 0).")
    else:
        print(
            f"{label} fehlgeschlagen: Exit-Code {exit_code} "
            f"({protocol.describe(exit_code)}).",
            file=sys.stderr,
        )

    return exit_code


def main(
    mode: str,
    description: str,
    configure_parser: Callable[[argparse.ArgumentParser], None] | None = None,
    after_success: Callable[[argparse.Namespace], int] | None = None,
) -> int:
    """Führt den Lauf aus; `after_success` folgt nur auf Exit-Code 0 des Laufs."""
    try:
        args = parse_arguments(description, configure_parser)
    except SystemExit as exc:
        if exc.code in (None, 0):
            raise
        return finish(mode, protocol.INVALID_ARGUMENTS)

    try:
        exit_code = execute(mode, args.timeout)
        if exit_code == protocol.SUCCESS and after_success is not None:
            exit_code = after_success(args)
    except KeyboardInterrupt:
        print_error(protocol.INTERRUPTED, "Lauf abgebrochen.")
        exit_code = protocol.INTERRUPTED
    except Exception as exc:
        print_error(protocol.UNEXPECTED_ERROR, f"{type(exc).__name__}: {exc}")
        exit_code = protocol.UNEXPECTED_ERROR

    return finish(mode, exit_code)
