-- Split standalone boost products out of Tier Completion.

INSERT INTO shop_product (sku, name, description, category, scope, price_type, price_marks, active)
VALUES
  (
    'BOOST_60',
    'Tier 0 Boost (Level 60)',
    'Complete the Tier 0 boost step for one character (level 60 setup and starter gear).',
    'Progression',
    'character',
    'fixed',
    25,
    1
  ),
  (
    'BOOST_70',
    'Tier 7.5 Boost (Level 70)',
    'Complete the Tier 7.5 boost step for one Tier 7 character (level 70 setup and starter gear).',
    'Progression',
    'character',
    'fixed',
    100,
    1
  )
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  category = VALUES(category),
  scope = VALUES(scope),
  price_type = VALUES(price_type),
  price_marks = VALUES(price_marks),
  active = VALUES(active);

UPDATE shop_product
SET
  name = 'Tier Completion',
  description = 'Skip selected Tier Locks from Tier 1 through Tier 12. Tier 0 and Tier 7.5 are separate products.',
  category = 'Progression',
  scope = 'character',
  price_type = 'tier_skip',
  active = 1
WHERE sku = 'TIER_SKIP';
