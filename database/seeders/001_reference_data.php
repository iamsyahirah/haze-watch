<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $states = [
        ['Johor', 'JHR'],
        ['Kedah', 'KDH'],
        ['Kelantan', 'KTN'],
        ['Melaka', 'MLK'],
        ['Negeri Sembilan', 'NSN'],
        ['Pahang', 'PHG'],
        ['Perak', 'PRK'],
        ['Perlis', 'PLS'],
        ['Pulau Pinang', 'PNG'],
        ['Sabah', 'SBH'],
        ['Sarawak', 'SWK'],
        ['Selangor', 'SGR'],
        ['Terengganu', 'TRG'],
        ['W.P. Kuala Lumpur', 'KUL'],
        ['W.P. Labuan', 'LBN'],
        ['W.P. Putrajaya', 'PJY'],
    ];

    $pdo->beginTransaction();
    try {
        $stateStatement = $pdo->prepare(
            'INSERT INTO states (name, code) VALUES (:name, :code) '
            . 'ON DUPLICATE KEY UPDATE name = VALUES(name)'
        );
        foreach ($states as [$name, $code]) {
            $stateStatement->execute(['name' => $name, 'code' => $code]);
        }

        $sourceStatement = $pdo->prepare(
            'INSERT INTO data_sources (name, base_url, source_type, enabled) '
            . 'VALUES (:name, :base_url, :source_type, 1) '
            . 'ON DUPLICATE KEY UPDATE base_url = VALUES(base_url), enabled = 1'
        );
        $sourceStatement->execute([
            'name' => 'Haze Monitor (DOE Malaysia)',
            'base_url' => 'https://hazemonitor.com',
            'source_type' => 'external_api',
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
};
