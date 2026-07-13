#!/usr/bin/env python3
"""Build a deterministic Moodle-installable ZIP package."""

from __future__ import annotations

import re
import shutil
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DIST = ROOT / "dist"
PACKAGE_ROOT = "aitutoria"

EXCLUDED_PARTS = {
    ".git",
    ".github",
    ".venv",
    "dist",
    "node_modules",
    "tools",
    "vendor",
}

EXCLUDED_ROOT_FILES = {
    ".gitignore",
    "CONTRIBUTING.md",
    "SECURITY.md",
}


def release_name() -> str:
    version = (ROOT / "version.php").read_text(encoding="utf-8")
    match = re.search(r"\$plugin->release\s*=\s*'([^']+)'", version)
    if not match:
        raise RuntimeError("Could not read $plugin->release from version.php")
    return match.group(1)


def iter_package_files():
    for path in sorted(ROOT.rglob("*")):
        if not path.is_file():
            continue
        relative = path.relative_to(ROOT)
        if any(part in EXCLUDED_PARTS for part in relative.parts):
            continue
        if len(relative.parts) == 1 and relative.name in EXCLUDED_ROOT_FILES:
            continue
        yield path, relative


def main() -> None:
    release = release_name()
    DIST.mkdir(exist_ok=True)
    output = DIST / f"assignfeedback_aitutoria-{release}.zip"
    if output.exists():
        output.unlink()

    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for source, relative in iter_package_files():
            target = Path(PACKAGE_ROOT) / relative
            info = zipfile.ZipInfo(str(target).replace("\\", "/"))
            info.date_time = (1980, 1, 1, 0, 0, 0)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, source.read_bytes())

    shutil.copystat(ROOT / "version.php", output)
    print(output)


if __name__ == "__main__":
    main()
