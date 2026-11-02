<?php

declare(strict_types=1);

use HazeWatch\Services\HazeMonitorNormalizer;

require dirname(__DIR__) . '/vendor/autoload.php';

$tests = 0;
$assert = static function (bool $condition, string $message) use (&$tests): void {
    $tests++;
    if (!$condition) {
        throw new RuntimeException('Assertion failed: ' . $message);
    }
};

$current = json_decode((string) file_get_contents(__DIR__ . '/fixtures/haze-monitor-current.json'), true, 512, JSON_THROW_ON_ERROR);
$history = json_decode((string) file_get_contents(__DIR__ . '/fixtures/haze-monitor-recent-history.json'), true, 512, JSON_THROW_ON_ERROR);
$normalizer = new HazeMonitorNormalizer();

$currentRows = $normalizer->normalizeCurrent($current);
$assert(count($currentRows) > 0, 'current snapshot yields available index readings');
$assert($currentRows[0]['metric'] === 'malaysia_api_ipu', 'official API maps to a clearly named internal index metric');
$assert($currentRows[0]['source_metric'] === 'api', 'provider metric is retained');
$assert($currentRows[0]['unit'] === 'index', 'index is not represented as a particulate concentration');
$assert(str_ends_with($currentRows[0]['observed_at'], '.000'), 'timestamps normalize to UTC millisecond storage');
$assert(!array_key_exists('pm10', $currentRows[0]), 'provider pm10Api is not misrepresented as PM10 concentration');

$historyRows = $normalizer->normalizeRecentHistory($history);
$assert(count($historyRows) > 0, 'recent history yields available hourly readings');
$assert($historyRows[0]['metric'] === 'malaysia_api_ipu', 'history API values use the same metric mapping');

$missingValueSnapshot = $current;
$missingValueSnapshot['stations'][0]['api'] = null;
$missingValueSnapshot['stations'][0]['observedAt'] = null;
$assert(count($normalizer->normalizeCurrent($missingValueSnapshot)) === count($currentRows) - 1, 'null values and timestamps are omitted instead of stored as zero');

$invalidSnapshot = $current;
$invalidSnapshot['stations'][0]['latitude'] = 100;
try {
    $normalizer->normalizeCurrent($invalidSnapshot);
    $assert(false, 'invalid coordinates must be rejected');
} catch (RuntimeException) {
    $assert(true, 'invalid coordinates are rejected');
}

fwrite(STDOUT, sprintf("Passed %d provider normalization checks.\n", $tests));
