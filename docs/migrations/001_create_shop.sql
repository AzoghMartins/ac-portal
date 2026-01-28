-- AC Portal shop schema (ac_portal)

CREATE TABLE IF NOT EXISTS shop_product (
    sku          VARCHAR(64)  NOT NULL,
    name         VARCHAR(120) NOT NULL,
    description  TEXT         NULL,
    category     VARCHAR(60)  NOT NULL DEFAULT 'General',
    scope        ENUM('account','character') NOT NULL DEFAULT 'character',
    price_type   ENUM('fixed','tier_skip') NOT NULL DEFAULT 'fixed',
    price_marks  INT UNSIGNED NULL,
    active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shop_purchase (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id     INT UNSIGNED    NOT NULL,
    character_guid INT UNSIGNED    NULL,
    sku            VARCHAR(64)     NOT NULL,
    product_name   VARCHAR(120)    NOT NULL,
    price_marks    INT UNSIGNED    NOT NULL,
    status         VARCHAR(20)     NOT NULL DEFAULT 'paid',
    details        JSON            NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_shop_purchase_account (account_id, created_at),
    KEY idx_shop_purchase_character (character_guid, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marks_ledger (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id  INT UNSIGNED    NOT NULL,
    delta       INT             NOT NULL,
    reason      VARCHAR(120)    NOT NULL,
    purchase_id BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_marks_ledger_account (account_id, created_at),
    KEY idx_marks_ledger_purchase (purchase_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shop_fulfillment (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    purchase_id BIGINT UNSIGNED NOT NULL,
    status      VARCHAR(20)     NOT NULL DEFAULT 'queued',
    notes       VARCHAR(255)    NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_shop_fulfillment_purchase (purchase_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed products
INSERT INTO shop_product (sku, name, description, category, scope, price_type, price_marks, active)
VALUES
  ('TIER_SKIP', 'Tier Completion', 'Auto-complete the current tier and advance through the selected tier. Pricing is based on tiers completed.', 'Progression', 'character', 'tier_skip', NULL, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  category = VALUES(category),
  scope = VALUES(scope),
  price_type = VALUES(price_type),
  price_marks = VALUES(price_marks),
  active = VALUES(active);

-- Disable legacy products that are no longer available.
UPDATE shop_product
SET active = 0
WHERE sku IN ('TIER_7_5', 'START_TBC_AT_60');
