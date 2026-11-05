"""Entfernt nach einem erfolgreichen Export das Formular-Rauschen gegenüber `HEAD`.

Excel schreibt bei jedem Export ein anderes `.frx` (Zeitstempel und zufällige
Füllbytes der Compound-Datei), und die Kopfzeilen `ClientLeft`, `ClientTop`,
`ClientWidth` und `ClientHeight` im `.frm` weichen je nach Rechner um wenige
Twips ab. Im `.frx` lassen sich Rauschen und echte Änderungen nicht
unterscheiden; es wird daher nur als Ganzes aus `HEAD` übernommen, wenn das
bereinigte `.frm` byte-identisch mit `HEAD` ist.

Git wird ausschließlich lesend aufgerufen (`rev-parse`, `ls-tree`, `show`).
"""

from __future__ import annotations

import argparse
import os
import re
import stat
import subprocess
from collections.abc import Sequence
from dataclasses import dataclass, field
from decimal import Decimal
from pathlib import Path

import runner_protocol as protocol
import wrapper_common

# Größte Abweichung von `ClientWidth` bzw. `ClientHeight` gegenüber `HEAD`, die
# als Rauschen gilt. Größere Abweichungen sind eine bewusste Größenänderung.
CLIENT_SIZE_TOLERANCE_TWIPS = Decimal(30)
# Die Position ist wegen `StartUpPosition = 1` (Fenstermitte) ohne Wirkung und
# wird unabhängig von der Abweichung auf den Wert aus `HEAD` gesetzt.
POSITION_PROPERTIES = ("ClientLeft", "ClientTop")
SIZE_PROPERTIES = ("ClientWidth", "ClientHeight")
GIT_TIMEOUT_SECONDS = 30

# Der Kopfbereich reicht von `VERSION 5.00` bis zur ersten Zeile `End`.
HEADER_START = b"VERSION "
HEADER_END = b"End"
CLIENT_PROPERTY_PATTERN = re.compile(
    rb"\s+(ClientLeft|ClientTop|ClientWidth|ClientHeight)\s*=\s*(-?\d+(?:\.\d+)?)\s*"
)
FORM_NAME_PATTERN = re.compile(r"[^\W\d_]\w*")
TEMP_SUFFIX = ".bereinigung"

UNCHANGED_NOISE_REMOVED = "unverändert, Rauschen entfernt"
UNCHANGED = "unverändert, kein Rauschen"
CHANGED_NOISE_REMOVED = "geändert, Client*-Rauschen entfernt"
CHANGED = "geändert"
KEPT = "beibehalten (--keep-forms)"
NEW = "neu (nicht in HEAD), unverändert übernommen"


class FormFormatError(Exception):
    """Der Kopfbereich eines `.frm` ist nicht im erwarteten Format."""


class GitUnavailable(Exception):
    """Kein Git, kein Repository oder kein Commit: nur Warnung."""


class GitFailed(Exception):
    """Ein lesender Git-Aufruf ist unerwartet fehlgeschlagen."""


@dataclass(frozen=True)
class ClientProperty:
    index: int
    content: bytes
    value: Decimal

    @property
    def text(self) -> str:
        return format(self.value, "f")


@dataclass
class HeaderCleanup:
    data: bytes
    removed: list[str] = field(default_factory=list)
    kept: list[str] = field(default_factory=list)


def line_content(line: bytes) -> bytes:
    return line.rstrip(b"\r\n")


def read_client_properties(lines: Sequence[bytes]) -> dict[str, ClientProperty]:
    if not lines or not lines[0].startswith(HEADER_START):
        raise FormFormatError("Kopfbereich beginnt nicht mit 'VERSION'")

    properties: dict[str, ClientProperty] = {}
    for index, line in enumerate(lines):
        content = line_content(line)
        if content == HEADER_END:
            return properties
        match = CLIENT_PROPERTY_PATTERN.fullmatch(content)
        if match is None:
            continue
        name = match.group(1).decode("ascii")
        if name in properties:
            raise FormFormatError(f"{name} steht mehrfach im Kopfbereich")
        properties[name] = ClientProperty(
            index, content, Decimal(match.group(2).decode("ascii"))
        )

    raise FormFormatError("Ende des Kopfbereichs ('End') nicht gefunden")


def clean_header(exported: bytes, head: bytes) -> HeaderCleanup:
    """Setzt `Client*`-Kopfzeilen des Exports auf den Stand aus `HEAD`.

    Ersetzt wird nur der Inhalt der betroffenen Zeilen; Zeilenenden und alle
    übrigen Bytes bleiben wie exportiert.
    """
    lines = exported.splitlines(keepends=True)
    exported_properties = read_client_properties(lines)
    head_properties = read_client_properties(head.splitlines(keepends=True))
    cleanup = HeaderCleanup(exported)
    for name in POSITION_PROPERTIES + SIZE_PROPERTIES:
        exported_property = exported_properties.get(name)
        head_property = head_properties.get(name)
        if exported_property is None and head_property is None:
            continue
        if exported_property is None:
            cleanup.kept.append(f"{name} fehlt im Export (HEAD {head_property.text})")
            continue
        if head_property is None:
            cleanup.kept.append(f"{name} {exported_property.text} fehlt in HEAD")
            continue
        if exported_property.content == head_property.content:
            continue

        deviation = abs(exported_property.value - head_property.value)
        if name in SIZE_PROPERTIES and deviation > CLIENT_SIZE_TOLERANCE_TWIPS:
            cleanup.kept.append(
                f"{name} {exported_property.text} bleibt (HEAD {head_property.text}, "
                f"Abweichung {format(deviation, 'f')} > {CLIENT_SIZE_TOLERANCE_TWIPS} "
                "Twips)"
            )
            continue

        line = lines[exported_property.index]
        lines[exported_property.index] = (
            head_property.content + line[len(exported_property.content):]
        )
        cleanup.removed.append(
            f"{name} {exported_property.text} -> {head_property.text}"
        )

    cleanup.data = b"".join(lines)
    return cleanup


def describe_git_result(result: subprocess.CompletedProcess[bytes]) -> str:
    detail = wrapper_common.decode_output(result.stderr).strip()
    return detail or f"Exit-Code {result.returncode}"


class GitHead:
    """Liest Dateien eines Verzeichnisses im Stand von `HEAD`, nur lesend."""

    def __init__(self, directory: Path) -> None:
        self.directory = directory
        self.names: set[str] = set()

    def _run(self, *arguments: str) -> subprocess.CompletedProcess[bytes]:
        try:
            return subprocess.run(
                ["git", "-C", str(self.directory), *arguments],
                check=False,
                capture_output=True,
                timeout=GIT_TIMEOUT_SECONDS,
            )
        except FileNotFoundError as exc:
            raise GitUnavailable("git wurde nicht gefunden") from exc
        except (OSError, subprocess.TimeoutExpired) as exc:
            raise GitFailed(f"git {arguments[0]}: {exc}") from exc

    def load(self) -> None:
        result = self._run("rev-parse", "--is-inside-work-tree")
        if result.returncode != 0 or result.stdout.strip() != b"true":
            raise GitUnavailable(
                f"{self.directory} liegt in keinem Git-Arbeitsverzeichnis "
                f"({describe_git_result(result)})"
            )

        result = self._run("rev-parse", "--verify", "--quiet", "HEAD^{commit}")
        if result.returncode != 0:
            raise GitUnavailable("Das Git-Repository hat noch keinen Commit (HEAD)")

        result = self._run("ls-tree", "-z", "--name-only", "HEAD", "--", ".")
        if result.returncode != 0:
            raise GitFailed(f"git ls-tree HEAD: {describe_git_result(result)}")
        self.names = {os.fsdecode(name) for name in result.stdout.split(b"\0") if name}

    def read(self, name: str) -> bytes | None:
        """Inhalt der Datei in `HEAD`; None, wenn sie dort nicht existiert."""
        if name not in self.names:
            return None

        result = self._run("show", "--no-textconv", f"HEAD:./{name}")
        if result.returncode != 0:
            raise GitFailed(f"git show HEAD:{name}: {describe_git_result(result)}")
        return result.stdout


def write_file(path: Path, data: bytes) -> None:
    """Schreibt über eine Zwischendatei, damit nie eine halb geschriebene Datei bleibt."""
    temp_path = path.with_name(f".{path.name}{TEMP_SUFFIX}")
    try:
        temp_path.write_bytes(data)
        if path.exists():
            os.chmod(temp_path, stat.S_IMODE(path.stat().st_mode))
        os.replace(temp_path, path)
    finally:
        temp_path.unlink(missing_ok=True)


@dataclass
class FormResult:
    status: str
    details: list[str] = field(default_factory=list)


def clean_form(frm_path: Path, head: GitHead, keep_frx: bool) -> FormResult:
    name = frm_path.stem
    head_frm = head.read(frm_path.name)
    if head_frm is None:
        return FormResult(NEW)

    # Beide Stände aus `HEAD` vor dem ersten Schreibzugriff lesen, damit ein
    # Git-Fehler das Formular nie teilweise bereinigt zurücklässt.
    frx_path = frm_path.with_suffix(".frx")
    head_frx = head.read(frx_path.name)
    exported_frm = frm_path.read_bytes()
    cleanup = clean_header(exported_frm, head_frm)
    if cleanup.data != exported_frm:
        write_file(frm_path, cleanup.data)
    details = cleanup.removed + cleanup.kept

    if keep_frx:
        status = KEPT
        if cleanup.removed:
            status += ", Client*-Rauschen entfernt"
        return FormResult(status, details)

    if cleanup.data != head_frm:
        status = CHANGED_NOISE_REMOVED if cleanup.removed else CHANGED
        return FormResult(status, details)

    if head_frx is None:
        status = UNCHANGED_NOISE_REMOVED if cleanup.removed else UNCHANGED
        return FormResult(status, details + [f"{name}.frx fehlt in HEAD, Export bleibt"])

    if frx_path.is_file() and frx_path.read_bytes() == head_frx:
        status = UNCHANGED_NOISE_REMOVED if cleanup.removed else UNCHANGED
        return FormResult(status, details)

    write_file(frx_path, head_frx)
    return FormResult(UNCHANGED_NOISE_REMOVED, [".frx aus HEAD"] + details)


def format_result(name: str, result: FormResult) -> str:
    line = f"Formular {name}: {result.status}"
    if result.details:
        line += f" ({'; '.join(result.details)})"
    return line


def clean_exported_forms(
    source_dir: Path, keep_forms: Sequence[str], head: GitHead | None = None
) -> int:
    """Bereinigt alle exportierten Formulare in `source_dir` und liefert den Exit-Code."""
    form_paths = sorted(path for path in source_dir.glob("*.frm") if path.is_file())
    forms_by_key = {path.stem.casefold(): path.stem for path in form_paths}
    unknown = [name for name in keep_forms if name.casefold() not in forms_by_key]
    if unknown:
        exported = ", ".join(path.stem for path in form_paths) or "keine"
        wrapper_common.print_error(
            protocol.UNKNOWN_KEEP_FORM,
            f"{', '.join(unknown)} (exportierte Formulare: {exported}). Das "
            "Formular-Rauschen wurde nicht entfernt; den Export mit korrigierter "
            "Angabe wiederholen.",
        )
        return protocol.UNKNOWN_KEEP_FORM
    if not form_paths:
        return protocol.SUCCESS

    keep = {forms_by_key[name.casefold()] for name in keep_forms}
    head = head or GitHead(source_dir)
    try:
        head.load()
    except GitUnavailable as exc:
        wrapper_common.print_hint(
            f"Formular-Rauschen nicht entfernt: {exc}. Die exportierten .frm und "
            ".frx bleiben unverändert."
        )
        return protocol.SUCCESS
    except GitFailed as exc:
        wrapper_common.print_error(
            protocol.FORM_GIT_COMPARISON_FAILED,
            f"{exc}. Die exportierten .frm und .frx bleiben unverändert.",
        )
        return protocol.FORM_GIT_COMPARISON_FAILED

    print(
        "Entferne Formular-Rauschen gegenüber HEAD (Toleranz für ClientWidth und "
        f"ClientHeight: {CLIENT_SIZE_TOLERANCE_TWIPS} Twips) ...",
        flush=True,
    )
    exit_code = protocol.SUCCESS
    for frm_path in form_paths:
        name = frm_path.stem
        try:
            result = clean_form(frm_path, head, name in keep)
        except GitFailed as exc:
            wrapper_common.print_error(
                protocol.FORM_GIT_COMPARISON_FAILED,
                f"Formular {name}: {exc}. Dieses und die folgenden Formulare bleiben "
                "wie exportiert.",
            )
            return protocol.FORM_GIT_COMPARISON_FAILED
        except (OSError, FormFormatError) as exc:
            wrapper_common.print_error(
                protocol.FORM_CLEANUP_FAILED,
                f"Formular {name}: {exc}. Das Formular ist nicht oder nur teilweise "
                "bereinigt.",
            )
            if exit_code == protocol.SUCCESS:
                exit_code = protocol.FORM_CLEANUP_FAILED
            continue
        print(format_result(name, result), flush=True)

    return exit_code


def parse_form_names(value: str) -> list[str]:
    names = [name.strip() for name in value.split(",")]
    if not all(FORM_NAME_PATTERN.fullmatch(name) for name in names):
        raise argparse.ArgumentTypeError(
            f"ungültige Formularnamen '{value}' (erwartet: Name[,Name])"
        )
    return names


def add_arguments(parser: argparse.ArgumentParser) -> None:
    parser.add_argument(
        "--keep-forms",
        type=parse_form_names,
        action="append",
        default=[],
        metavar="NAME[,NAME]",
        help=(
            "Exportiertes .frx dieser Formulare behalten, z. B. nach einer Änderung "
            "ausschließlich im Designer (Groß-/Kleinschreibung egal, wiederholbar)"
        ),
    )


def run(args: argparse.Namespace) -> int:
    keep_forms = [name for names in args.keep_forms for name in names]
    return clean_exported_forms(wrapper_common.SOURCE_DIR, keep_forms)
