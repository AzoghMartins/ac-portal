CREATE TABLE IF NOT EXISTS character_tier_awards (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    character_guid  INT UNSIGNED    NOT NULL,
    account_id      INT UNSIGNED    NOT NULL,
    tier            VARCHAR(8)      NOT NULL,
    source          ENUM('earned','skipped') NOT NULL,
    purchase_id     BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_character_tier (character_guid, tier),
    KEY idx_character_tier_account (account_id, created_at),
    KEY idx_character_tier_purchase (purchase_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
