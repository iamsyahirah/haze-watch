CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT chk_users_role CHECK (role IN ('user', 'admin')),
    CONSTRAINT chk_users_status CHECK (status IN ('active', 'disabled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE states (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_states_name (name),
    UNIQUE KEY uq_states_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE data_sources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    source_type VARCHAR(40) NOT NULL DEFAULT 'external_api',
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_data_sources_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    state_id BIGINT UNSIGNED NULL,
    data_source_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(100) NOT NULL,
    name VARCHAR(160) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    region VARCHAR(120) NULL,
    latitude DECIMAL(9,6) NULL,
    longitude DECIMAL(9,6) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_locations_source_external (data_source_id, external_id),
    KEY idx_locations_state_active (state_id, active),
    CONSTRAINT fk_locations_state FOREIGN KEY (state_id) REFERENCES states (id) ON DELETE SET NULL,
    CONSTRAINT fk_locations_source FOREIGN KEY (data_source_id) REFERENCES data_sources (id) ON DELETE RESTRICT,
    CONSTRAINT chk_locations_latitude CHECK (latitude IS NULL OR latitude BETWEEN -90 AND 90),
    CONSTRAINT chk_locations_longitude CHECK (longitude IS NULL OR longitude BETWEEN -180 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE air_quality_readings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    location_id BIGINT UNSIGNED NOT NULL,
    data_source_id BIGINT UNSIGNED NOT NULL,
    metric VARCHAR(40) NOT NULL,
    value DECIMAL(12,4) NOT NULL,
    unit VARCHAR(30) NULL,
    source_metric VARCHAR(80) NOT NULL,
    source_unit VARCHAR(30) NULL,
    category VARCHAR(50) NULL,
    observed_at DATETIME(3) NOT NULL,
    fetched_at DATETIME(3) NOT NULL,
    external_dedupe_key VARCHAR(191) NOT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    UNIQUE KEY uq_readings_dedupe (data_source_id, external_dedupe_key),
    KEY idx_readings_location_metric_time (location_id, metric, observed_at),
    KEY idx_readings_observed_at (observed_at),
    CONSTRAINT fk_readings_location FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_readings_source FOREIGN KEY (data_source_id) REFERENCES data_sources (id) ON DELETE RESTRICT,
    CONSTRAINT chk_readings_value CHECK (value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE favourite_locations (
    user_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    PRIMARY KEY (user_id, location_id),
    KEY idx_favourites_location (location_id),
    CONSTRAINT fk_favourites_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_favourites_location FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE alert_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    location_id BIGINT UNSIGNED NOT NULL,
    metric VARCHAR(40) NOT NULL,
    operator VARCHAR(10) NOT NULL,
    threshold DECIMAL(12,4) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_state TINYINT(1) NULL,
    cooldown_until DATETIME(3) NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    PRIMARY KEY (id),
    KEY idx_alert_rules_enabled_location (enabled, location_id),
    KEY idx_alert_rules_user (user_id),
    CONSTRAINT fk_alert_rules_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_rules_location FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE CASCADE,
    CONSTRAINT chk_alert_rules_operator CHECK (operator IN ('gt', 'gte', 'lt', 'lte')),
    CONSTRAINT chk_alert_rules_threshold CHECK (threshold >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    alert_rule_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    dedupe_key VARCHAR(191) NOT NULL,
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    read_at DATETIME(3) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_notifications_user_dedupe (user_id, dedupe_key),
    KEY idx_notifications_user_read_created (user_id, read_at, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_alert FOREIGN KEY (alert_rule_id) REFERENCES alert_rules (id) ON DELETE SET NULL,
    CONSTRAINT fk_notifications_location FOREIGN KEY (location_id) REFERENCES locations (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE api_sync_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    data_source_id BIGINT UNSIGNED NOT NULL,
    started_at DATETIME(3) NOT NULL,
    finished_at DATETIME(3) NULL,
    status VARCHAR(20) NOT NULL,
    http_status SMALLINT UNSIGNED NULL,
    request_id CHAR(36) NULL,
    records_received INT UNSIGNED NOT NULL DEFAULT 0,
    records_saved INT UNSIGNED NOT NULL DEFAULT 0,
    error_code VARCHAR(80) NULL,
    safe_error_summary VARCHAR(500) NULL,
    PRIMARY KEY (id),
    KEY idx_sync_logs_source_started (data_source_id, started_at),
    KEY idx_sync_logs_status_started (status, started_at),
    CONSTRAINT fk_sync_logs_source FOREIGN KEY (data_source_id) REFERENCES data_sources (id) ON DELETE RESTRICT,
    CONSTRAINT chk_sync_logs_status CHECK (status IN ('running', 'success', 'partial', 'failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
