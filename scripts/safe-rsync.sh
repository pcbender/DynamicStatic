#!/usr/bin/env bash
set -eEuo pipefail
trap 'echo "safe-rsync: FAIL at line $LINENO running: $BASH_COMMAND" >&2' ERR

# Inputs (required)
KEY_FILE="${KEY_FILE:?missing KEY_FILE}"
USER="${USER:?missing USER}"
HOST="${HOST:?missing HOST}"
PORT="${PORT:-22}"
WEBROOT="${WEBROOT:?missing WEBROOT}"     # absolute path on remote
SRC_DIR="${SRC_DIR:-dist}"                 # local source dir to sync

# Safety knobs
EXPECT_TOKEN="${EXPECT_TOKEN:?missing EXPECT_TOKEN}"   # e.g., dynamicstatic.net
MARKER_FILE="${MARKER_FILE:-.allow-deploy}"            # required file in WEBROOT
DRY_RUN="${DRY_RUN:-yes}"                              # yes|no
DELETE="${DELETE:-no}"                                  # no|yes (only becomes live if DRY_RUN=no)

die(){ echo "ABORT: $*" >&2; exit 1; }

# Local checks
[[ -d "$SRC_DIR" ]] || die "SRC_DIR not found: $SRC_DIR"
[[ "${WEBROOT:0:1}" == "/" ]] || die "WEBROOT must be absolute"
[[ "$WEBROOT" != "/" ]] || die "WEBROOT cannot be /"
[[ "$WEBROOT" == *"$EXPECT_TOKEN"* ]] || die "WEBROOT must include token: $EXPECT_TOKEN"

# Ensure .htaccess presence if expected (improves cache/security behavior)
if [[ -f "public/.htaccess" && ! -f "$SRC_DIR/.htaccess" ]]; then
  die ".htaccess expected (public/.htaccess exists) but missing in $SRC_DIR/. Did you run npm run build:site?"
fi

# SSH options (known_hosts must be pre-populated by workflow)
SSH_OPTS=( -i "$KEY_FILE" -o IdentitiesOnly=yes -o BatchMode=yes \
  -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$HOME/.ssh/known_hosts" -p "$PORT" )

# Remote preflight: path exists, not $HOME, marker & .htaccess present (warn if missing)
ssh "${SSH_OPTS[@]}" "$USER@$HOST" "set -e;
  test -d '$WEBROOT';
  test \"$HOME\" != '$WEBROOT';
  test '/' != '$WEBROOT';
  test -f '$WEBROOT/$MARKER_FILE';
  test -f '$WEBROOT/.htaccess' || echo '[warn] .htaccess not found in webroot'"

# Compose rsync
RSYNC_OPTS=( -a -v --human-readable --chmod=Du=rwx,Dgo=rx,Fu=rw,Fgo=r )
if [[ "$DRY_RUN" == "yes" ]]; then RSYNC_OPTS+=( --dry-run --itemize-changes ); fi
if [[ "$DELETE" == "yes" && "$DRY_RUN" == "no" ]]; then RSYNC_OPTS+=( --delete-after ); fi

echo ">>> safe-rsync: $SRC_DIR -> $USER@$HOST:$WEBROOT  (DRY_RUN=$DRY_RUN, DELETE=$DELETE)"
rsync "${RSYNC_OPTS[@]}" \
  -e "ssh ${SSH_OPTS[*]}" \
  "$SRC_DIR/" "$USER@$HOST:$WEBROOT/"
