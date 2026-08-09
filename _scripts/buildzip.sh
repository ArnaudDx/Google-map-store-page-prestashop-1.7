#!/usr/bin/env bash
set -euo pipefail

MODULE_NAME="storeggmap"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
OUTPUT_DIR="$MODULE_DIR/_versions"

VERSION=$(grep -oP "\\\$this->version\s*=\s*'\K[^']+" "$MODULE_DIR/storeggmap.php")
if [ -z "$VERSION" ]; then
    echo "Error: unable to read module version from storeggmap.php" >&2
    exit 1
fi

ZIP_NAME="${MODULE_NAME}-${VERSION}.zip"
ZIP_PATH="$OUTPUT_DIR/$ZIP_NAME"

if [ -f "$ZIP_PATH" ]; then
    echo "Error: $ZIP_PATH already exists. Bump the version before releasing." >&2
    exit 1
fi

BUILD_ROOT=$(mktemp -d)
trap 'rm -rf "$BUILD_ROOT"' EXIT

BUILD_DIR="$BUILD_ROOT/$MODULE_NAME"
mkdir -p "$BUILD_DIR"

# Fichiers suivis par git mais sans utilite dans un module installe en production :
# doc interne, outillage de release et archives des versions precedentes.
EXCLUDES=(
    ':(exclude)CLAUDE.md'
    ':(exclude).gitignore'
    ':(exclude)_scripts/'
    ':(exclude)_versions/'
)

cd "$MODULE_DIR"
git ls-files -z -- "${EXCLUDES[@]}" | xargs -0 -I{} sh -c 'mkdir -p "$1/$(dirname "$2")" && cp "$2" "$1/$2"' _ "$BUILD_DIR" {}

mkdir -p "$OUTPUT_DIR"

cd "$BUILD_ROOT"
zip -r -X -q "$ZIP_PATH" "$MODULE_NAME"

echo "Built $ZIP_PATH"
