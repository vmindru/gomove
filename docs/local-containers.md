# Local Apache and PostgreSQL with Podman

The local POC uses two rootless Podman services:

| Service | Configuration |
| --- | --- |
| `web` | Apache and PHP 8.4, with PostgreSQL extensions, Composer, and Node.js 22 for frontend builds |
| `database` | PostgreSQL 17 with a persistent named volume; accessible within the Compose network |

The host directory `/home/vmindru/proj/gomove` is mounted read/write at `/var/www/html`. Apache serves only `/var/www/html/public`, so application files and environment files are outside its document root. Laravel routing uses the existing `public/.htaccess` and Apache's rewrite module.

Node.js builds frontend assets. Apache/PHP serves application requests; a Node.js application server is not needed.

## Start

Run on the host as the user who owns the GoMove project. Requirements: Podman, a recent `podman-compose` supporting `keep-id:uid=33,gid=33`, and Python 3.

```bash
cd /home/vmindru/proj/gomove
./scripts/compose.sh up -d --build
./scripts/compose.sh logs -f web
```

Then open **http://localhost:8080**. The first build and startup download dependencies and can take a few minutes.

The wrapper creates `.env.compose` with a random Laravel key and database password, using mode `0600`. Existing settings are preserved. It passes that file explicitly to Compose; the existing Laravel `.env` is not replaced. Container environment settings take precedence over Laravel's `.env`.

Equivalent direct commands:

```bash
python3 scripts/init-compose-env.py
podman-compose --env-file .env.compose -f podman-compose.yml --in-pod=false up -d --build
```

## Initialization and file permissions

The web entrypoint waits for PostgreSQL, installs Composer dependencies, clears stale configuration/views, installs frontend dependencies from the lockfile, builds assets, and runs pending migrations. A failure stops startup instead of serving a partially initialized application. It does not seed data or reset the database.

For demo accounts and implemented product behavior, see [the MVP presentation guide](mvp-demo.md). Automated tests now use the isolated PostgreSQL database `gomove_test_mvp`; the test bootstrap guard rejects other databases.

The container runs Apache's parent process as container root, while PHP requests and initialization commands run as `www-data`. Rootless `keep-id` maps that account to the host user, so generated files remain owned by the project owner. The setup does not recursively change ownership or loosen permissions on the project. The mount uses the shared `z` SELinux label because the worktree may also be mounted into an agent container.

To skip initialization on later restarts, set `WEB_BOOTSTRAP=false` in `.env.compose` and recreate the web service. Dependencies, assets, and migrations must already be current. For normal POC use, leave it enabled.

## Common commands

```bash
# Run Laravel commands as the account mapped to the host project owner.
./scripts/compose.sh exec --user www-data web php artisan migrate
./scripts/test.sh
./scripts/test-concurrency.sh

# Rebuild frontend assets after source edits.
./scripts/compose.sh exec --user www-data web npm run build

# Open a PostgreSQL shell.
./scripts/compose.sh exec database psql -U gomove -d gomove

# Stop services; the named database volume is retained.
./scripts/compose.sh down
```

PostgreSQL connection details inside the web container: host `database`, port `5432`, database/user `gomove`, password from `.env.compose`.

The default web listener is local to the host. Change `WEB_PORT` or `WEB_BIND_ADDRESS` in `.env.compose` when needed, and update `APP_URL` to the browser-facing address. These files describe local development; production HTTPS and deployment settings are separate work.

Existing database volumes retain their original database password. Editing `.env.compose` does not rotate a password in an already initialized PostgreSQL volume; update the database credential as well when rotating it.

References: [official PHP image](https://hub.docker.com/_/php), [Podman volume and user namespace documentation](https://docs.podman.io/en/v5.6.2/markdown/podman-run.1.html).
