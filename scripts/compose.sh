#!/usr/bin/env bash
set -euo pipefail

project_root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
cd "$project_root"
command -v podman >/dev/null || { echo 'Run this script on a host with Podman installed.' >&2; exit 1; }
command -v podman-compose >/dev/null || { echo 'Install podman-compose on the host first.' >&2; exit 1; }
python3 scripts/init-compose-env.py
exec podman-compose --env-file .env.compose -f podman-compose.yml --in-pod=false "$@"
