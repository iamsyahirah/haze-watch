<?php

declare(strict_types=1);

use HazeWatch\Database\ConnectionFactory;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/bootstrap/env.php';

HazeWatch\Bootstrap\loadEnvironment($root . '/.env');

try {
    $pdo = ConnectionFactory::create(require $root . '/config/database.php');
    $command = $argv[1] ?? 'help';

    if ($command === 'migrate') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations ('
            . 'version VARCHAR(100) NOT NULL PRIMARY KEY, '
            . 'applied_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
        );

        $files = glob($root . '/database/migrations/*.up.sql') ?: [];
        sort($files, SORT_STRING);
        $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $appliedSet = array_fill_keys($applied, true);
        foreach ($files as $file) {
            $version = basename($file, '.up.sql');
            if (isset($appliedSet[$version])) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException('Could not read migration: ' . $version);
            }
            $pdo->exec($sql);
            $statement = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
            $statement->execute(['version' => $version]);
            fwrite(STDOUT, "Applied {$version}\n");
        }
        fwrite(STDOUT, "Migrations are up to date.\n");
        exit(0);
    }

    if ($command === 'rollback') {
        $version = $pdo->query('SELECT version FROM schema_migrations ORDER BY version DESC LIMIT 1')->fetchColumn();
        if ($version === false) {
            fwrite(STDOUT, "No migration to roll back.\n");
            exit(0);
        }

        $file = $root . '/database/migrations/' . $version . '.down.sql';
        $sql = is_file($file) ? file_get_contents($file) : false;
        if ($sql === false) {
            throw new RuntimeException('Rollback file not found for ' . $version);
        }
        $pdo->exec($sql);
        $statement = $pdo->prepare('DELETE FROM schema_migrations WHERE version = :version');
        $statement->execute(['version' => $version]);
        fwrite(STDOUT, "Rolled back {$version}\n");
        exit(0);
    }

    if ($command === 'seed') {
        $files = glob($root . '/database/seeders/*.php') ?: [];
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            $seed = require $file;
            $seed($pdo);
            fwrite(STDOUT, 'Seeded ' . basename($file) . "\n");
        }
        exit(0);
    }

    fwrite(STDOUT, "Usage: php database/console.php <migrate|rollback|seed>\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Database command failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
