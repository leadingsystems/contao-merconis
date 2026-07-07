#!/usr/bin/env python3

from __future__ import annotations

import subprocess
import sys
from pathlib import Path


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


def run_command(command: list[str]) -> int:
    result = subprocess.run(command, check=False, capture_output=True)

    if result.stdout:
        print(decode_output(result.stdout), end="")
    if result.stderr:
        print(decode_output(result.stderr), end="", file=sys.stderr)

    return result.returncode


def main() -> int:
    mpm_dir = Path(__file__).resolve().parents[1]
    workbook_path = mpm_dir / "dist" / "MPM.xlsm"
    source_dir = mpm_dir / "src"

    if not workbook_path.exists():
        print(
            "Fehler: Die Datei mpm/dist/MPM.xlsm wurde nicht gefunden. "
            "Bitte zuerst die kanonische Ausgangsdatei bereitstellen.",
            file=sys.stderr,
        )
        return 1

    source_dir.mkdir(parents=True, exist_ok=True)

    try:
        windows_workbook_path = to_windows_path(workbook_path)
        windows_source_dir = to_windows_path(source_dir)
    except RuntimeError as exc:
        print(f"Fehler bei der Windows-Pfadkonvertierung: {exc}", file=sys.stderr)
        return 1

    print(
        "Exportiere VBA-Quellcode aus "
        f"{windows_workbook_path} nach {windows_source_dir} ..."
    )

    return_code = run_command(
        [
            "powershell.exe",
            "-NoProfile",
            "-Command",
            (
                f"excel-vba export -f '{windows_workbook_path}' "
                f"--vba-directory '{windows_source_dir}' "
                "--in-file-headers "
                "--force-overwrite"
            ),
        ]
    )

    if return_code == 0:
        print("Export erfolgreich abgeschlossen.")
    else:
        print("Export fehlgeschlagen.", file=sys.stderr)

    return return_code


if __name__ == "__main__":
    raise SystemExit(main())
