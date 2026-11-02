<?php

declare(strict_types=1);

use HazeWatch\Database\ConnectionFactory;
use HazeWatch\Providers\HazeMonitorClient;
use HazeWatch\Services\DataSyncService;
use HazeWatch\Services\HazeMonitorNormalizer;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/bootstrap/env.php';

HazeWatch\Bootstrap\loadEnvironment($root . '/.env');

try {
    $pdo = ConnectionFactory::create(require $root . '/config/database.php');
    $sync = new DataSyncService($pdo, new HazeMonitorNormalizer());
    $client = new HazeMonitorClient($root . '/storage/cache/hazemonitor');
    $snapshot = $client->fetchCurrent();
    $summary = $sync->importCurrent($snapshot);
    $history = $client->fetchRecentHistory();
    $historySummary = $sync->importRecentHistory($history);
    fwrite(STDOUT, sprintf(
        "Sync complete: %d locations, %d current readings, %d history readings, %d unavailable readings.\n",
        $summary['locations'],
        $summary['readings'],
        $historySummary['readings'],
        $summary['unavailable'] + $historySummary['unavailable'],
    ));
} catch (Throwable $exception) {
    if (isset($sync)) {
        try {
            $sync->recordFailure($exception);
        } catch (Throwable) {
            // Preserve the original error if the database cannot record a failed sync.
        }
    }
    fwrite(STDERR, 'Sync failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
