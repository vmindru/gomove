#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
# Only a dedicated test database is ever refreshed by PHPUnit.
if [[ $(./scripts/compose.sh exec -T database sh -c 'psql -U "$POSTGRES_USER" -d postgres -Atc "SELECT 1 FROM pg_database WHERE datname = '\''gomove_test_mvp'\''"' | tail -n 1) != 1 ]]; then
    ./scripts/compose.sh exec -T database sh -c 'createdb -U "$POSTGRES_USER" gomove_test_mvp'
fi
./scripts/compose.sh exec -T --user www-data \
    -e APP_ENV=testing -e DB_CONNECTION=pgsql -e DB_DATABASE=gomove_test_mvp -e DB_URL= \
    -e SESSION_DRIVER=array -e CACHE_STORE=array -e MAIL_MAILER=array -e QUEUE_CONNECTION=sync \
    web php artisan test "$@"
