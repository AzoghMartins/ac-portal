-- Extend shop_fulfillment for payload + audit
ALTER TABLE shop_fulfillment
    ADD COLUMN payload_json JSON NULL AFTER purchase_id,
    ADD COLUMN last_error TEXT NULL AFTER status,
    ADD COLUMN attempt_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_error,
    ADD COLUMN applied_at TIMESTAMP NULL DEFAULT NULL AFTER attempt_count,
    ADD INDEX idx_shop_fulfillment_status (status, created_at);
