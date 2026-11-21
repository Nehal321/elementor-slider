"""Build and verify the installable WordPress plugin archive."""

import argparse
import hashlib
import re
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = "feather-slider"
FILES = (
    "feather-slider.php",
    "includes/class-slider-widget.php",
    "assets/slider.css",
    "assets/slider.js",
)
BUILD_LINE = b"const BUILD = 'development';"


def build_package(output, build):
    contents = {name: (ROOT / PLUGIN / name).read_bytes() for name in FILES}
    if build is None:
        digest = hashlib.sha256()
        for name, data in contents.items():
            digest.update(name.encode())
            digest.update(data)
        build = digest.hexdigest()
    if not re.fullmatch(r"[0-9a-f]{40}|[0-9a-f]{64}", build):
        raise ValueError("Build ID must be a Git commit or SHA-256 digest.")

    entry = FILES[0]
    if contents[entry].count(BUILD_LINE) != 1:
        raise ValueError("Could not find the build marker in the plugin bootstrap.")
    contents[entry] = contents[entry].replace(
        BUILD_LINE, f"const BUILD = '{build}';".encode()
    )
    output.parent.mkdir(parents=True, exist_ok=True)
    temporary = output.with_suffix(".tmp")
    try:
        with ZipFile(temporary, "w", compression=ZIP_DEFLATED) as archive:
            for name, data in contents.items():
                item = ZipInfo(f"{PLUGIN}/{name}", date_time=(2020, 1, 1, 0, 0, 0))
                item.external_attr = 0o100644 << 16
                archive.writestr(item, data, compress_type=ZIP_DEFLATED)

        with ZipFile(temporary) as archive:
            members = [f"{PLUGIN}/{name}" for name in contents]
            if archive.namelist() != members or archive.testzip():
                raise ValueError("Invalid WordPress plugin archive.")
            if any(archive.read(path) != data for path, data in zip(members, contents.values())):
                raise ValueError("The archive does not match the packaged sources.")
        temporary.replace(output)
    finally:
        temporary.unlink(missing_ok=True)
    print(f"{output} ({output.stat().st_size:,} bytes, build {build})")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--build")
    parser.add_argument("--output", type=Path, default=ROOT / "dist" / f"{PLUGIN}.zip")
    arguments = parser.parse_args()
    build_package(arguments.output, arguments.build)
