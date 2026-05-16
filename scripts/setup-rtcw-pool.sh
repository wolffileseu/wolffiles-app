#!/bin/bash
#
# Build/rebuild RtCW asset pool from PK3 files in storage/app/rtcw-paks/
#
# Idempotent: existing files in the pool are never overwritten (--ignore-existing).
# Custom textures placed manually in the pool are safe.
#
# Run as root or with sudo. Will chown to wolffiles.eu_lkiogmaiktl:psacln.
#
# Usage:
#   bash scripts/setup-rtcw-pool.sh [--dry-run]

set -euo pipefail

APP_ROOT=/var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app
PAKS_DIR=$APP_ROOT/storage/app/rtcw-paks
POOL_DIR=$APP_ROOT/public/rtcw-assets
TMP_DIR=/tmp/rtcw-pool-build-$$

# Which paks to include, in load order (later overrides earlier for conflicts)
# We use --ignore-existing on the final rsync so conflicts don't matter much,
# but order documents intent.
PAKS=(
    pak0.pk3          # SP base assets (302 MB)
    mp_pak0.pk3       # MP base assets (61 MB)
    sp_pak2.pk3       # SP patch 2 (11 MB)
)

# Which top-level dirs to extract from each pak.
# textures = wall/object textures, scripts = .shader files,
# models = mapobject models + textures, gfx = HUD, levelshots = thumbnails
FILTERS=(
    'textures/*'
    'scripts/*'
    'models/*'
    'gfx/*'
    'levelshots/*'
)

DRY_RUN=0
[[ "${1:-}" == "--dry-run" ]] && DRY_RUN=1

echo "==> RtCW asset pool builder"
echo "    PAKS_DIR=$PAKS_DIR"
echo "    POOL_DIR=$POOL_DIR"
[[ $DRY_RUN -eq 1 ]] && echo "    MODE: dry-run (will not write)"

# Sanity: paks present?
for pak in "${PAKS[@]}"; do
    if [[ ! -f "$PAKS_DIR/$pak" ]]; then
        echo "    ERROR: missing $PAKS_DIR/$pak"
        echo "    Please upload all required PK3s before running this script."
        exit 1
    fi
done
echo "    All required PK3s present."

# Prepare tmp
trap "rm -rf $TMP_DIR" EXIT
mkdir -p "$TMP_DIR"

echo ""
echo "==> Extracting paks to $TMP_DIR"
cd "$TMP_DIR"
for pak in "${PAKS[@]}"; do
    echo "    + $pak"
    # -n = never overwrite (within tmp, later paks lose to earlier; final --ignore-existing
    #      protects pool's existing files anyway)
    unzip -qn "$PAKS_DIR/$pak" "${FILTERS[@]}" 2>&1         | grep -v "caution: filename not matched"         | sed 's/^/        /' || true
done

EXTRACTED=$(find "$TMP_DIR" -type f | wc -l)
echo "    Extracted $EXTRACTED files ($(du -sh "$TMP_DIR" | cut -f1))"

if [[ $DRY_RUN -eq 1 ]]; then
    echo ""
    echo "==> Dry-run diff vs current pool"
    ( cd "$TMP_DIR" && find . -type f | sort ) > /tmp/pool-tmp.txt
    if [[ -d "$POOL_DIR" ]]; then
        ( cd "$POOL_DIR" && find . -type f | sort ) > /tmp/pool-current.txt
        echo "    NEW (would be added):     $(comm -23 /tmp/pool-tmp.txt /tmp/pool-current.txt | wc -l)"
        echo "    EXISTING (kept as-is):    $(comm -12 /tmp/pool-tmp.txt /tmp/pool-current.txt | wc -l)"
        echo "    CUSTOM (in pool only):    $(comm -13 /tmp/pool-tmp.txt /tmp/pool-current.txt | wc -l)"
    else
        echo "    Pool does not exist yet; would create with $EXTRACTED files"
    fi
    rm -f /tmp/pool-tmp.txt /tmp/pool-current.txt
    exit 0
fi

echo ""
echo "==> Merging into $POOL_DIR (--ignore-existing protects custom assets)"
mkdir -p "$POOL_DIR"
rsync -a --ignore-existing "$TMP_DIR/" "$POOL_DIR/"

echo "==> Fixing ownership and permissions"
chown -R wolffiles.eu_lkiogmaiktl:psacln "$POOL_DIR"
find "$POOL_DIR" -type d -exec chmod 755 {} \;
find "$POOL_DIR" -type f -exec chmod 644 {} \;

POOL_FILES=$(find "$POOL_DIR" -type f | wc -l)
POOL_SIZE=$(du -sh "$POOL_DIR" | cut -f1)

echo ""
echo "==> Done. Pool: $POOL_FILES files, $POOL_SIZE"
echo ""
echo "    Note: Laravel shader cache for the pool should be cleared:"
echo "      php artisan tinker --execute='Cache::forget(\"shaders:pool:rtcw-assets\");'"
