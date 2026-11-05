#!/usr/bin/env python3

from __future__ import annotations

import form_cleanup
import wrapper_common


def main() -> int:
    return wrapper_common.main(
        "export",
        "Exportiert den VBA-Quellcode aus mpm/dist/MPM.xlsm nach mpm/src und "
        "entfernt danach das Formular-Rauschen gegenüber HEAD.",
        configure_parser=form_cleanup.add_arguments,
        after_success=form_cleanup.run,
    )


if __name__ == "__main__":
    raise SystemExit(main())
