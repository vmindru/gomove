#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
./scripts/compose.sh exec -T --user www-data \
  -e APP_ENV=testing -e DB_CONNECTION=pgsql -e DB_DATABASE=gomove_test_mvp -e DB_URL= \
  web sh -c '
    set -eu
    php tests/concurrency.php setup
    php tests/concurrency.php attempt > /tmp/gomove-mvp-race-a.log &
    first_pid=$!
    php tests/concurrency.php attempt > /tmp/gomove-mvp-race-b.log &
    second_pid=$!
    wait "$first_pid"
    wait "$second_pid"
    php tests/concurrency.php verify
  '
