#!/bin/bash
set -e

PLUGIN_NAME="jetstop-spam"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="${PLUGIN_DIR}/build"
DIST_DIR="${BUILD_DIR}/${PLUGIN_NAME}"

echo "Building ${PLUGIN_NAME}..."

# Get version
VERSION=$(grep -m1 "Version:" "${PLUGIN_DIR}/jetstop-spam.php" | sed 's/.*Version:[[:space:]]*//' | tr -d ' ')
echo "Version: ${VERSION}"

# Clean
rm -rf "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# PHP syntax check
echo "Checking PHP syntax..."
find "${PLUGIN_DIR}" -name "*.php" -not -path "*/vendor/*" -not -path "*/build/*" | xargs -n1 php -l > /dev/null
echo "PHP syntax OK"

# Copy files
cp "${PLUGIN_DIR}/jetstop-spam.php" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/includes" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/admin" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/assets" "${DIST_DIR}/"
cp -r "${PLUGIN_DIR}/languages" "${DIST_DIR}/" 2>/dev/null || true
cp "${PLUGIN_DIR}/README.md" "${DIST_DIR}/" 2>/dev/null || true
cp "${PLUGIN_DIR}/LICENSE" "${DIST_DIR}/" 2>/dev/null || true

# Security index files
find "${DIST_DIR}" -type d -exec sh -c 'echo "<?php // Silence is golden." > "$1/index.php"' _ {} \;

# Clean dev files
find "${DIST_DIR}" -name ".DS_Store" -delete
find "${DIST_DIR}" -name "*.map" -delete

# Create ZIP
cd "${BUILD_DIR}"
zip -r "${PLUGIN_NAME}.zip" "${PLUGIN_NAME}" -x "*.DS_Store" -x "*__MACOSX*"

echo ""
echo "Build complete: ${BUILD_DIR}/${PLUGIN_NAME}.zip"
echo "Version: ${VERSION}"
