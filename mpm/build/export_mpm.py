#!/usr/bin/env python3

from __future__ import annotations

import wrapper_common


def main() -> int:
    return wrapper_common.main(
        "export", "Exportiert den VBA-Quellcode aus mpm/dist/MPM.xlsm nach mpm/src."
    )


if __name__ == "__main__":
    raise SystemExit(main())
