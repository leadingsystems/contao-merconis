#!/usr/bin/env python3

from __future__ import annotations

import wrapper_common


def main() -> int:
    return wrapper_common.main(
        "import", "Importiert den VBA-Quellcode aus mpm/src in mpm/dist/MPM.xlsm."
    )


if __name__ == "__main__":
    raise SystemExit(main())
