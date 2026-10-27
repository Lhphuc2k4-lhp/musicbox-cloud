#!/usr/bin/env bash
set -euo pipefail

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*) echo "PORT must be a number" >&2; exit 1 ;;
esac
sed -i "s/__PORT__/$port/g" /etc/apache2/sites-available/000-default.conf
printf 'Listen %s\n' "$port" > /etc/apache2/ports.conf
export MUSICBOX_ZING_HOST=127.0.0.1
export MUSICBOX_ZING_PORT=8877
node /opt/musicbox-zing/server.cjs &
zing_pid=$!
apache2-foreground &
apache_pid=$!
trap 'kill -TERM "$zing_pid" "$apache_pid" 2>/dev/null || true; wait || true' TERM INT EXIT
# If either server stops, restart the container instead of serving a broken half.
wait -n "$zing_pid" "$apache_pid"
exit 1
