<?php

declare(strict_types=1);

namespace HazeWatch\Database;

use PDO;
use PDOException;
use RuntimeException;

final class ConnectionFactory
{
    /** @param array{host:string, port:string, database:string, username:string, password:string, charset:string} $config */
    public static function create(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'],
        );

        try {
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Could not connect to MySQL. Check the database service and local environment settings.', 0, $exception);
        }
    }
}
