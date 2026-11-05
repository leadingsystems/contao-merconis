"""Gemeinsame Festlegungen von `vba_runner.py` und den WSL-Wrappern.

Die Fehlerarten der Exit-Code-Tabelle in `mpm/README.md` stimmen ohne
Backticks wörtlich mit `DESCRIPTIONS` überein.
"""

from __future__ import annotations

from dataclasses import dataclass
from pathlib import PureWindowsPath

SUCCESS = 0
UNEXPECTED_ERROR = 1
INVALID_ARGUMENTS = 2
MISSING_INPUT = 10
PATH_CONVERSION_FAILED = 11
WINDOWS_PYTHON_UNAVAILABLE = 12
RUNNER_NOT_STARTED = 13
VBA_EDIT_VERSION = 20
PYWIN32_MISSING = 21
LOCK_FILE_BEFORE_RUN = 30
WORKBOOK_READ_ONLY = 31
LOCK_FILE_AFTER_RUN = 32
VBA_PROJECT_ACCESS_DENIED = 40
IMPORT_COMPONENTS_FAILED = 41
EXPORT_COMPONENTS_FAILED = 42
SAVE_FAILED = 43
EXCEL_NOT_TERMINATED = 44
FORMULA_CHECK_FAILED = 45
FORMULA_INITIALIZATION_FAILED = 46
BACKUP_FAILED = 47
RESTORE_FAILED = 48
TIMEOUT = 50
COM_ERROR = 60
FORM_GIT_COMPARISON_FAILED = 70
UNKNOWN_KEEP_FORM = 71
FORM_CLEANUP_FAILED = 72
INTERRUPTED = 130

DESCRIPTIONS = {
    SUCCESS: "Erfolg",
    UNEXPECTED_ERROR: "Unerwarteter Fehler",
    INVALID_ARGUMENTS: "Ungültiger Aufruf",
    MISSING_INPUT: "Mappe, Quellverzeichnis oder VBA-Dateien fehlen",
    PATH_CONVERSION_FAILED: "Windows-Pfadkonvertierung (wslpath -w) fehlgeschlagen",
    WINDOWS_PYTHON_UNAVAILABLE: "powershell.exe oder Windows-Python (py) nicht verfügbar",
    RUNNER_NOT_STARTED: "Runner nicht gestartet (powershell.exe, py oder python.exe)",
    VBA_EDIT_VERSION: "vba-edit fehlt oder hat nicht die geforderte Version",
    PYWIN32_MISSING: "pywin32 fehlt",
    LOCK_FILE_BEFORE_RUN: "Sperrdatei dist/~$MPM.xlsm vor dem Lauf vorhanden",
    WORKBOOK_READ_ONLY: "Mappe nur schreibgeschützt geöffnet",
    LOCK_FILE_AFTER_RUN: "Sperrdatei dist/~$MPM.xlsm nach dem Lauf vorhanden",
    VBA_PROJECT_ACCESS_DENIED: "Zugriff auf das VBA-Projekt verweigert",
    IMPORT_COMPONENTS_FAILED: "Importfehler einzelner Komponenten, nicht gespeichert",
    EXPORT_COMPONENTS_FAILED: "Exportfehler einzelner Komponenten",
    SAVE_FAILED: "Speichern der Mappe fehlgeschlagen",
    EXCEL_NOT_TERMINATED: "Eigene Excel-Instanz ließ sich nicht beenden",
    FORMULA_CHECK_FAILED: "Schutzprüfung der Formelzellen fehlgeschlagen, Mappe unverändert",
    FORMULA_INITIALIZATION_FAILED: (
        "Übersetzungen oder Neuberechnung fehlgeschlagen, nicht gespeichert"
    ),
    BACKUP_FAILED: "Sicherung der Mappe vor dem Build fehlgeschlagen",
    RESTORE_FAILED: "Wiederherstellung der Mappe fehlgeschlagen",
    TIMEOUT: "Zeitüberschreitung",
    COM_ERROR: "Sonstiger COM- oder Excel-Fehler",
    FORM_GIT_COMPARISON_FAILED: "Vergleich der Formulare mit HEAD per Git fehlgeschlagen",
    UNKNOWN_KEEP_FORM: "Unbekanntes Formular in --keep-forms",
    FORM_CLEANUP_FAILED: "Formular nicht bereinigt (Datei- oder Formatfehler)",
    INTERRUPTED: "Abbruch mit Strg+C",
}

# Präfix der Zeilen, mit denen der Runner seinen Prozess und den der eigenen
# Excel-Instanz meldet, z. B.
# `[mpm-runner] pid excel=1234 start=134382725934280258 name=EXCEL`.
# `start` ist die Windows-Prozess-Startzeit als FILETIME (100-ns-Schritte seit
# 1601-01-01 UTC), wie sie PowerShell mit `StartTime.ToFileTimeUtc()` liefert;
# `name` ist der Prozessname ohne `.exe` wie `Get-Process`. Fehlen `start` oder
# `name`, darf der Wrapper den Prozess nicht beenden. Die Zeile mit `runner=` ist
# zugleich die Startmeldung des Runners.
PID_LINE_PREFIX = "[mpm-runner] pid "

# Präfix der Zeilen zur Sicherung der Mappe beim Build:
# `incomplete=<Windows-Pfad>` vor dem Kopieren (Zwischendatei, keine
# Sicherung), `created=<Windows-Pfad>` nach geprüfter Kopie und Umbenennung auf
# den endgültigen Namen, `removed` nach dem Entfernen, `kept=<Windows-Pfad>`,
# wenn sie erhalten bleibt, weil der Stand der Mappe nicht belegt ist, und
# `obsolete=<Windows-Pfad>`, wenn der Stand belegt ist, das Entfernen aber
# scheiterte.
BACKUP_LINE_PREFIX = "[mpm-runner] backup "
BACKUP_INCOMPLETE = "incomplete"
BACKUP_CREATED = "created"
BACKUP_REMOVED = "removed"
BACKUP_KEPT = "kept"
BACKUP_OBSOLETE = "obsolete"

# Namensmuster im Windows-Temp-Verzeichnis: `MPM-build-sicherung-<Kennung>.xlsm`
# ist eine vollständige, geprüfte Sicherung. Die Zwischendatei
# `MPM-build-sicherung-<Kennung>.unvollstaendig` ist nie eine Sicherung und darf
# nicht zur Wiederherstellung verwendet werden.
BACKUP_PREFIX = "MPM-build-sicherung-"
BACKUP_SUFFIX = ".xlsm"
BACKUP_INCOMPLETE_SUFFIX = ".unvollstaendig"


@dataclass(frozen=True)
class ProcessIdentity:
    pid: int
    start: int | None = None
    name: str | None = None

    @property
    def verifiable(self) -> bool:
        return self.start is not None and bool(self.name)


def format_pid_line(role: str, identity: ProcessIdentity) -> str:
    line = f"{PID_LINE_PREFIX}{role}={identity.pid}"
    if identity.start is not None:
        line += f" start={identity.start}"
    if identity.name:
        line += f" name={identity.name}"
    return line


def parse_pid_line(line: str) -> tuple[str, ProcessIdentity] | None:
    if not line.startswith(PID_LINE_PREFIX):
        return None

    tokens = line[len(PID_LINE_PREFIX):].split()
    if not tokens:
        return None
    role, _, pid = tokens[0].partition("=")
    if not role or not pid.isdigit():
        return None

    fields = dict(token.partition("=")[::2] for token in tokens[1:])
    start = fields.get("start", "")
    name = fields.get("name", "")
    return role, ProcessIdentity(
        pid=int(pid),
        start=int(start) if start.isdigit() else None,
        name=name or None,
    )


def final_backup_path(incomplete_path: str) -> str | None:
    """Endgültiger Windows-Pfad der Sicherung zu ihrer Zwischendatei.

    Liefert None, wenn der Pfad nicht dem Muster der Zwischendatei entspricht.
    """
    path = PureWindowsPath(incomplete_path)
    name = path.name
    if (
        not name.startswith(BACKUP_PREFIX)
        or not name.endswith(BACKUP_INCOMPLETE_SUFFIX)
        or len(name) <= len(BACKUP_PREFIX) + len(BACKUP_INCOMPLETE_SUFFIX)
    ):
        return None
    return str(path.with_name(name[: -len(BACKUP_INCOMPLETE_SUFFIX)] + BACKUP_SUFFIX))


def describe(exit_code: int) -> str:
    return DESCRIPTIONS.get(exit_code, "Unbekannter Exit-Code")
