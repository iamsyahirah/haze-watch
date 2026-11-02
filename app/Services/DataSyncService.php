<?php

declare(strict_types=1);

namespace HazeWatch\Services;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;
use HazeWatch\Providers\ProviderException;

final class DataSyncService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly HazeMonitorNormalizer $normalizer,
    ) {
    }

    /** @param array<string, mixed> $snapshot @return array{locations:int, readings:int, unavailable:int} */
    public function importCurrent(array $snapshot): array
    {
        $rows = $this->normalizer->normalizeCurrent($snapshot);
        $sourceId = $this->sourceId();
        $startedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');

        $this->pdo->beginTransaction();
        try {
            $log = $this->pdo->prepare(
                "INSERT INTO api_sync_logs (data_source_id, started_at, status) VALUES (:source_id, :started_at, 'running')"
            );
            $log->execute(['source_id' => $sourceId, 'started_at' => $startedAt]);
            $logId = (int) $this->pdo->lastInsertId();

            $stateLookup = $this->stateLookup();
            $locationStatement = $this->pdo->prepare(
                'INSERT INTO locations (state_id, data_source_id, external_id, name, full_name, region, latitude, longitude, active) '
                . 'VALUES (:state_id, :source_id, :external_id, :name, :full_name, :region, :latitude, :longitude, 1) '
                . 'ON DUPLICATE KEY UPDATE state_id = VALUES(state_id), name = VALUES(name), full_name = VALUES(full_name), '
                . 'region = VALUES(region), latitude = VALUES(latitude), longitude = VALUES(longitude), active = 1'
            );
            $locationIdStatement = $this->pdo->prepare(
                'SELECT id FROM locations WHERE data_source_id = :source_id AND external_id = :external_id'
            );
            $readingStatement = $this->pdo->prepare(
                'INSERT INTO air_quality_readings '
                . '(location_id, data_source_id, metric, value, unit, source_metric, source_unit, category, observed_at, fetched_at, external_dedupe_key) '
                . 'VALUES (:location_id, :source_id, :metric, :value, :unit, :source_metric, :source_unit, :category, :observed_at, :fetched_at, :dedupe_key) '
                . 'ON DUPLICATE KEY UPDATE value = VALUES(value), category = VALUES(category), fetched_at = VALUES(fetched_at)'
            );

            $fetchedAt = $this->toUtc((string) ($snapshot['fetchedAt'] ?? ''));
            $locationIds = [];
            foreach (($snapshot['stations'] ?? []) as $station) {
                if (!is_array($station) || !is_string($station['id'] ?? null) || !is_string($station['name'] ?? null) || !is_string($station['fullName'] ?? null)) {
                    continue;
                }
                $stateName = is_string($station['state'] ?? null) ? $station['state'] : '';
                $locationStatement->execute([
                    'state_id' => $stateLookup[$this->canonicalState($stateName)] ?? null,
                    'source_id' => $sourceId,
                    'external_id' => $station['id'],
                    'name' => $station['name'],
                    'full_name' => $station['fullName'],
                    'region' => is_string($station['region'] ?? null) ? $station['region'] : null,
                    'latitude' => is_numeric($station['latitude'] ?? null) ? $station['latitude'] : null,
                    'longitude' => is_numeric($station['longitude'] ?? null) ? $station['longitude'] : null,
                ]);
                $locationIdStatement->execute(['source_id' => $sourceId, 'external_id' => $station['id']]);
                $locationIds[$station['id']] = (int) $locationIdStatement->fetchColumn();
            }

            $saved = 0;
            foreach ($rows as $row) {
                $readingStatement->execute([
                    'location_id' => $locationIds[$row['external_id']],
                    'source_id' => $sourceId,
                    'metric' => $row['metric'],
                    'value' => $row['value'],
                    'unit' => $row['unit'],
                    'source_metric' => $row['source_metric'],
                    'source_unit' => $row['source_unit'],
                    'category' => $row['category'],
                    'observed_at' => $row['observed_at'],
                    'fetched_at' => $fetchedAt,
                    'dedupe_key' => $row['dedupe_key'],
                ]);
                $saved++;
            }

            $reporting = (int) ($snapshot['reportingCount'] ?? $saved);
            $stationCount = (int) ($snapshot['stationCount'] ?? count($snapshot['stations'] ?? []));
            $status = $reporting < $stationCount ? 'partial' : 'success';
            $finish = $this->pdo->prepare(
                'UPDATE api_sync_logs SET finished_at = :finished_at, status = :status, records_received = :received, records_saved = :saved WHERE id = :id'
            );
            $finish->execute([
                'finished_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v'),
                'status' => $status,
                'received' => $stationCount,
                'saved' => $saved,
                'id' => $logId,
            ]);
            $this->pdo->commit();

            return ['locations' => count($locationIds), 'readings' => $saved, 'unavailable' => max(0, $stationCount - $saved)];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $history @return array{readings:int, unavailable:int} */
    public function importRecentHistory(array $history): array
    {
        $rows = $this->normalizer->normalizeRecentHistory($history);
        $sourceId = $this->sourceId();
        $startedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
        $fetchedAt = $this->toUtc((string) ($history['fetchedAt'] ?? ''));

        $this->pdo->beginTransaction();
        try {
            $log = $this->pdo->prepare(
                "INSERT INTO api_sync_logs (data_source_id, started_at, status) VALUES (:source_id, :started_at, 'running')"
            );
            $log->execute(['source_id' => $sourceId, 'started_at' => $startedAt]);
            $logId = (int) $this->pdo->lastInsertId();

            $location = $this->pdo->prepare(
                'SELECT id FROM locations WHERE data_source_id = :source_id AND external_id = :external_id AND active = 1'
            );
            $reading = $this->pdo->prepare(
                'INSERT INTO air_quality_readings '
                . '(location_id, data_source_id, metric, value, unit, source_metric, source_unit, category, observed_at, fetched_at, external_dedupe_key) '
                . 'VALUES (:location_id, :source_id, :metric, :value, :unit, :source_metric, :source_unit, :category, :observed_at, :fetched_at, :dedupe_key) '
                . 'ON DUPLICATE KEY UPDATE value = VALUES(value), fetched_at = VALUES(fetched_at)'
            );

            $saved = 0;
            foreach ($rows as $row) {
                $location->execute(['source_id' => $sourceId, 'external_id' => $row['external_id']]);
                $locationId = $location->fetchColumn();
                if ($locationId === false) {
                    continue;
                }
                $reading->execute([
                    'location_id' => (int) $locationId,
                    'source_id' => $sourceId,
                    'metric' => $row['metric'],
                    'value' => $row['value'],
                    'unit' => $row['unit'],
                    'source_metric' => $row['source_metric'],
                    'source_unit' => $row['source_unit'],
                    'category' => $row['category'],
                    'observed_at' => $row['observed_at'],
                    'fetched_at' => $fetchedAt,
                    'dedupe_key' => $row['dedupe_key'],
                ]);
                $saved++;
            }

            $finish = $this->pdo->prepare(
                'UPDATE api_sync_logs SET finished_at = :finished_at, status = :status, records_received = :received, records_saved = :saved WHERE id = :id'
            );
            $finish->execute([
                'finished_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v'),
                'status' => $saved === count($rows) ? 'success' : 'partial',
                'received' => count($rows),
                'saved' => $saved,
                'id' => $logId,
            ]);
            $this->pdo->commit();

            return ['readings' => $saved, 'unavailable' => count($rows) - $saved];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function recordFailure(Throwable $exception): void
    {
        $sourceId = $this->sourceId();
        $startedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
        $statement = $this->pdo->prepare(
            "INSERT INTO api_sync_logs (data_source_id, started_at, finished_at, status, http_status, request_id, error_code, safe_error_summary) "
            . "VALUES (:source_id, :started_at, :finished_at, 'failed', :http_status, :request_id, :error_code, :summary)"
        );
        $statement->execute([
            'source_id' => $sourceId,
            'started_at' => $startedAt,
            'finished_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v'),
            'http_status' => $exception instanceof ProviderException ? $exception->httpStatus : null,
            'request_id' => $exception instanceof ProviderException ? $exception->requestId : null,
            'error_code' => $exception instanceof ProviderException ? $exception->providerCode : 'SYNC_FAILED',
            'summary' => mb_substr($exception->getMessage(), 0, 500),
        ]);
    }

    private function sourceId(): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM data_sources WHERE name = :name AND enabled = 1');
        $statement->execute(['name' => 'Haze Monitor (DOE Malaysia)']);
        $id = $statement->fetchColumn();
        if ($id === false) {
            throw new \RuntimeException('Haze Monitor data source is missing. Run the database seeder first.');
        }

        return (int) $id;
    }

    /** @return array<string, int> */
    private function stateLookup(): array
    {
        $lookup = [];
        foreach ($this->pdo->query('SELECT id, name FROM states')->fetchAll(PDO::FETCH_ASSOC) as $state) {
            $lookup[$this->canonicalState((string) $state['name'])] = (int) $state['id'];
        }

        return $lookup;
    }

    private function canonicalState(string $state): string
    {
        $state = strtolower(preg_replace('/[^a-z]/i', '', $state) ?? '');
        $state = preg_replace('/^wp/', 'wilayahpersekutuan', $state) ?? $state;

        return match ($state) {
            'pulaupinang', 'penang' => 'pulaupinang',
            'kualalumpur', 'wilayahpersekutuankualalumpur' => 'wilayahpersekutuankualalumpur',
            'labuan', 'wilayahpersekutuanlabuan' => 'wilayahpersekutuanlabuan',
            'putrajaya', 'wilayahpersekutuanputrajaya' => 'wilayahpersekutuanputrajaya',
            default => $state,
        };
    }

    private function toUtc(string $timestamp): string
    {
        try {
            return (new DateTimeImmutable($timestamp))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.v');
        } catch (\Exception $exception) {
            throw new \RuntimeException('Provider fetch timestamp is invalid.', 0, $exception);
        }
    }
}
