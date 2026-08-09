<?php

namespace Tests\Support;

use PDO;
use RuntimeException;

final class TestingDatabaseLock
{
    private const TIMEOUT_SECONDS = 60;

    private static ?PDO $pdo = null;

    /** @param array<string, mixed> $config */
    public static function acquire(array $config, string $key): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '5432',
            $config['database'],
        );

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $statement = $pdo->prepare('select pg_try_advisory_lock(hashtext(?))');
        $startedAt = microtime(true);

        do {
            $statement->execute([$key]);

            $locked = $statement->fetchColumn();

            if ($locked === true || $locked === 1 || $locked === '1' || $locked === 't') {
                self::$pdo = $pdo;

                return;
            }

            usleep(250_000);
        } while ((microtime(true) - $startedAt) < self::TIMEOUT_SECONDS);

        throw new RuntimeException(
            'Timed out waiting for the shared PostgreSQL testing database lock. '.
            'Stop the other php artisan test process or use Laravel parallel testing databases.'
        );
    }
}
