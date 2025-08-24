#!/usr/bin/env bash
set -eEuo pipefail
trap 'echo "safe-rsync: FAIL at line $LINENO running: $BASH_COMMAND" >&2' ERR

KEY_FILE="${KEY_FILE:?missing KEY_FILE}"
USER="${USER:?missing USER}"
HOST="${HOST:?missing HOST}"
PORT="${PORT:-22}"
WEBROOT="${WEBROOT:?missing WEBROOT}"
SRC_DIR="${SRC_DIR:-dist}"
EXPECT_TOKEN="${EXPECT_TOKEN:?missing EXPECT_TOKEN}"
MARKER_FILE="${MARKER_FILE:-.allow-deploy}"
DRY_RUN="${DRY_RUN:-yes}"      # yes|no
DELETE="${DELETE:-no}"         # no|yes (only if DRY_RUN=no)

die(){ echo "ABORT: $*" >&2; exit 1; }

[[ -d "$SRC_DIR" ]] || die "SRC_DIR not found: $SRC_DIR"
[[ "${WEBROOT:0:1}" == "/" ]] || die "WEBROOT must be absolute"
[[ "$WEBROOT" != "/" ]] || die "WEBROOT cannot be /"
[[ "$WEBROOT" == *"$EXPECT_TOKEN"* ]] || die "WEBROOT must include token: $EXPECT_TOKEN"

mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"

SSH_OPTS=( -i "$KEY_FILE" -o IdentitiesOnly=yes -o BatchMode=yes \
  -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$HOME/.ssh/known_hosts" -p "$PORT" )

ssh "${SSH_OPTS[@]}" "$USER@$HOST" "set -e;
  test -d '$WEBROOT';
  test \"\$HOME\" != '$WEBROOT';
  test '/' != '$WEBROOT';
  test -f '$WEBROOT/$MARKER_FILE'"

RSYNC_OPTS=( -a -v --human-readable --chmod=Du=rwx,Dgo=rx,Fu=rw,Fgo=r )
RSYNC_PROTECT=( --filter='P /.allow-deploy' --filter='P /.htaccess' --filter='P /docker-compose.yml' --filter='P /docker/' )
[[ "$DRY_RUN" == "yes" ]] && RSYNC_OPTS+=( --dry-run --itemize-changes )
[[ "$DELETE" == "yes" && "$DRY_RUN" == "no" ]] && RSYNC_OPTS+=( --delete-after )

echo ">>> safe-rsync: $SRC_DIR -> $USER@$HOST:$WEBROOT  (DRY_RUN=$DRY_RUN, DELETE=$DELETE)"
rsync "${RSYNC_OPTS[@]}" "${RSYNC_PROTECT[@]}" \
  -e "ssh ${SSH_OPTS[*]}" \
  "$SRC_DIR/" "$USER@$HOST:$WEBROOT/"
