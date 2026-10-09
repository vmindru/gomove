# Server architecture

The current POC uses Laravel/PHP with React and TypeScript through Inertia, and self-hosted PostgreSQL. The earlier Next.js implementation remains a superseded draft in temporary storage; it is not the current application.

```text
Browser → Apache / PHP / Laravel → PostgreSQL
```

The local Podman setup has two services:

- `web`: Apache and PHP 8.4 in one container, serving Laravel's public directory. Composer installs PHP dependencies. Node.js 22 compiles frontend assets and does not serve application requests.
- `database`: PostgreSQL 17 with persistent storage in a named volume, available to the web service on the internal Compose network.

The project worktree is mounted into the web service at `/var/www/html`; Apache's document root is `/var/www/html/public`. The host directory is `/home/vmindru/proj/gomove`. Rootless user mapping allows Apache's application user to write generated files as the host project owner.

The default browser address is http://localhost:8080. See [local container setup](local-containers.md) for startup commands, environment generation, and initialization behavior.

This is local development configuration. Production domain, HTTPS, backups, and email delivery remain to be configured. Begin with a shared platform and expand based on measured demand; future separate municipality installations can reuse the deployment structure with separate configuration and data.

Frontend templates and UI components must be free for now. The official Laravel React starter kit supplies the interface foundation. The requested appearance is professional and human-built.

References: [Laravel starter kits](https://laravel.com/starter-kits), [official PHP Apache image](https://hub.docker.com/_/php).
