<?php

declare(strict_types=1);

namespace HazeWatch\Services;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** Convert only documented, available official API/IPU values to our reading format. */
final class HazeMonitorNormalizer
{
    /** @param array<string, mixed> $snapshot @return list<array<string, mixed>> */
    public function normalizeCurrent(array $snapshot): array
    {
        foreach (['source', 'sourceUrl', 'fetchedAt', 'stations'] as $required) {
            if (!array_key_exists($required, $snapshot)) {
                throw new RuntimeException('Provider snapshot is missing required field: ' . $required);
            }
        }
        if (($snapshot['source'] ?? null) !== 'Department of Environment Malaysia' || !is_array($snapshot['stations'])) {
            throw new RuntimeException('Provider snapshot has an unsupported source or station list.');
        }

        $rows = [];
        foreach ($snapshot['stations'] as $station) {
            if (!is_array($station)) {
                throw new RuntimeException('Provider station entry must be an object.');
            }
            foreach (['id', 'name', 'fullName', 'state', 'region', 'latitude', 'longitude', 'api', 'pm10Api', 'category', 'pollutant', 'observedAt'] as $required) {
                if (!array_key_exists($required, $station)) {
                    throw new RuntimeException('Provider station is missing required field: ' . $required);
                }
            }
            if (!is_string($station['id']) || $station['id'] === '' || !is_string($station['name']) || $station['name'] === '' || !is_string($station['fullName']) || !is_string($station['state'])) {
                throw new RuntimeException('Provider station identity fields have invalid types.');
            }

            $latitude = $station['latitude'];
            $longitude = $station['longitude'];
            if (!is_numeric($latitude) || !is_numeric($longitude) || (float) $latitude < -90 || (float) $latitude > 90 || (float) $longitude < -180 || (float) $longitude > 180) {
                throw new RuntimeException('Provider station coordinates are invalid.');
            }

            $api = $station['api'];
            $observedAt = $station['observedAt'];
            if ($api === null || $observedAt === null) {
                continue;
            }
            if (!is_numeric($api) || (float) $api < 0 || !is_string($observedAt)) {
                throw new RuntimeException('Provider API/IPU value or observation time is invalid.');
            }

            $observedUtc = $this->toUtc($observedAt);
            $rows[] = [
                'external_id' => $station['id'],
                'name' => $station['name'],
                'full_name' => $station['fullName'],
                'state_name' => $station['state'],
                'region' => is_string($station['region']) ? $station['region'] : null,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'metric' => 'malaysia_api_ipu',
                'value' => (float) $api,
                'unit' => 'index',
                'source_metric' => 'api',
                'source_unit' => null,
                'category' => is_string($station['category']) ? $station['category'] : null,
                'observed_at' => $observedUtc,
                'dedupe_key' => hash('sha256', $station['id'] . '|api|' . $observedUtc),
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed> $history @return list<array<string, mixed>> */
    public function normalizeRecentHistory(array $history): array
    {
        if (($history['source'] ?? null) !== 'Department of Environment Malaysia' || !is_array($history['stations'] ?? null)) {
            throw new RuntimeException('Provider history has an unsupported source or station list.');
        }

        $rows = [];
        foreach ($history['stations'] as $station) {
            if (!is_array($station) || !is_string($station['id'] ?? null) || !is_array($station['readings'] ?? null)) {
                throw new RuntimeException('Provider history station has an invalid structure.');
            }
            foreach ($station['readings'] as $reading) {
                if (!is_array($reading) || !array_key_exists('observedAt', $reading) || !array_key_exists('api', $reading)) {
                    throw new RuntimeException('Provider history reading is missing required fields.');
                }
                if ($reading['api'] === null) {
                    continue;
                }
                if (!is_numeric($reading['api']) || (float) $reading['api'] < 0 || !is_string($reading['observedAt'])) {
                    throw new RuntimeException('Provider history API/IPU or timestamp is invalid.');
                }

                $observedUtc = $this->toUtc($reading['observedAt']);
                $rows[] = [
                    'external_id' => $station['id'],
                    'metric' => 'malaysia_api_ipu',
                    'value' => (float) $reading['api'],
                    'unit' => 'index',
                    'source_metric' => 'api',
                    'source_unit' => null,
                    'category' => null,
                    'observed_at' => $observedUtc,
                    'dedupe_key' => hash('sha256', $station['id'] . '|api|' . $observedUtc),
                ];
            }
        }

        return $rows;
    }

    private function toUtc(string $timestamp): string
    {
        try {
            return (new DateTimeImmutable($timestamp))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.v');
        } catch (\Exception $exception) {
            throw new RuntimeException('Provider timestamp is invalid.', 0, $exception);
        }
    }
}
