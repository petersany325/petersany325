#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SOURCE_DIR="$ROOT/source"
PATCH_FILE="$ROOT/patches/branding.patch"
HBB_CONFIG="$SOURCE_DIR/libs/hbb_common/src/config.rs"

# shellcheck disable=SC1091
source "$ROOT/config/branding.env"

RUSTDESK_TAG="${RUSTDESK_VERSION:-1.4.0}"
APP_NAME="${APP_DISPLAY_NAME:-HDD Land Remote Soft}"

echo "==> HDD Land Remote Soft setup"
echo "    RustDesk version: $RUSTDESK_TAG"
echo "    App name: $APP_NAME"

if [[ ! -d "$SOURCE_DIR/.git" ]]; then
  echo "==> Cloning RustDesk $RUSTDESK_TAG"
  git clone --depth 1 --branch "$RUSTDESK_TAG" https://github.com/rustdesk/rustdesk.git "$SOURCE_DIR"
  git -C "$SOURCE_DIR" submodule update --init --recursive --depth 1
else
  echo "==> Source already cloned"
fi

if [[ -f "$PATCH_FILE" ]]; then
  echo "==> Applying branding patch"
  if git -C "$SOURCE_DIR" apply --check "$PATCH_FILE" 2>/dev/null; then
    git -C "$SOURCE_DIR" apply "$PATCH_FILE"
  else
    echo "    Patch already applied or partially applied, continuing..."
    git -C "$SOURCE_DIR" apply --reject --whitespace=fix "$PATCH_FILE" 2>/dev/null || true
  fi
fi

if [[ -f "$HBB_CONFIG" ]]; then
  echo "==> Setting APP_NAME in hbb_common"
  sed -i 's/RwLock::new("RustDesk"\.to_owned())/RwLock::new("'"${APP_NAME//\//\\/}"'"\.to_owned())/' "$HBB_CONFIG"
fi

echo "==> Generating icons from HDD LAND brand assets"
python3 "$ROOT/scripts/generate-icons.py"

echo ""
echo "Setup complete."
echo "Next: build on Windows — see BUILD.md"
