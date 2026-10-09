<?php

declare(strict_types=1);

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;connect_timeout=2',
    getenv('DB_HOST') ?: 'database',
    getenv('DB_PORT') ?: '5432',
    getenv('DB_DATABASE') ?: 'gomove',
);

for ($attempt = 0; $attempt < 60; $attempt++) {
    try {
        new PDO($dsn, getenv('DB_USERNAME') ?: 'gomove', getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        exit(0);
    } catch (PDOException) {
        sleep(1);
    }
}

fwrite(STDERR, "PostgreSQL is not ready. Check the database service and .env.compose settings.\n");
exit(1);
