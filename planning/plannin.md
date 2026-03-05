# Shop Changes Implementation Plan

Date: 2026-03-05

## Objective
Implement the following shop changes:
1. Split Tier 0 (reach level 60 + gear) into its own standalone product.
2. Split Tier 7.5 (reach level 70 + gear) into its own standalone product.
3. Require level 60 for Tier Skip purchases that skip up to Tier 7.
4. Require level 70 for Tier Skip purchases that skip beyond Tier 7.
5. Set Tier 4 ("Chaos and Destruction") skip cost to 0 while the server-core bug exists.

Note: New GM-command-based fulfillment behavior for the two new products is out of scope for this pass and will be added later.

## Proposed Product Model
Create/activate three shop products:

- `TIER_SKIP`
  - Purpose: Skip progression tiers except the lifted standalone tiers.
  - `price_type`: `tier_skip` (variable)
  - Tier selection excludes `0` and `7.5`.

- `BOOST_60` (new)
  - Purpose: Complete Tier 0 behavior (set to level 60 + starter gear path currently in worker).
  - `price_type`: `fixed`
  - `price_marks`: 25 (same as old Tier 0 cost)
  - `scope`: `character`

- `BOOST_70` (new)
  - Purpose: Complete Tier 7.5 behavior (set to level 70 + starter gear path currently in worker).
  - `price_type`: `fixed`
  - `price_marks`: 100 (same as old Tier 7.5 cost)
  - `scope`: `character`

## Functional Rules To Implement

### Tier Skip availability and pricing
- Remove `0` and `7.5` from selectable targets for `TIER_SKIP`.
- Keep Tier ordering logic for continuity, but filter selectable options in UI/controller.
- Set `TIER_SKIP` cost map for Tier `4` to `0`.

### Tier Skip level requirements
- If selected target tier is `<= 7`, character level must be `>= 60`.
- If selected target tier is `>= 8`, character level must be `>= 70`.
- Reject invalid purchase server-side with explicit error.
- Show requirement hint in UI before submit.

### New standalone product behavior (current pass)
- `BOOST_60` and `BOOST_70` use the existing queued fulfillment pipeline.
- Build payloads that reuse current worker-compatible fields (`result_tier`, `boost`, `gold_copper`) so no worker rewrite is required yet.
- Keep this intentionally minimal until GM-command upgrade phase.

## File-Level Implementation Steps

### 1) Database/migration updates
Add a new migration file (recommended: `docs/migrations/004_split_tier_products.sql`):
- Upsert `BOOST_60` and `BOOST_70` into `shop_product`.
- Keep `TIER_SKIP` active and update its description text to reflect scope after split.
- Do not remove historical purchase rows.

### 2) ShopController updates (`src/Controllers/ShopController.php`)
- Add new SKU constants for standalone products.
- Update `TIER_SKIP_COSTS` so tier `4 => 0`.
- Add dedicated payload builders for `BOOST_60` and `BOOST_70`.
- In `buy()`:
  - Handle `fixed` standalone products by SKU and attach correct payload.
  - Keep existing transaction and ledger flow unchanged.
- In tier skip preview/selection:
  - Exclude `0` and `7.5` from selectable options.
  - Enforce new level requirements before pricing/purchase.

### 3) Shop product UI updates (`views/shop-product.php`)
- Stop rendering Tier Completion long-form copy for all products.
- Render product-specific body based on SKU or `price_type`:
  - `TIER_SKIP`: tier selector, preview, level requirements.
  - `BOOST_60`: character selector + fixed total.
  - `BOOST_70`: character selector + fixed total.
- Keep current character auto-select behavior, only tier selector behavior should remain tied to `TIER_SKIP`.

### 4) Storefront copy (`views/shop.php`)
- Ensure product cards display correct descriptions for all 3 products.
- Keep category grouping as-is.

### 5) Optional worker hardening (small)
File: `scripts/fulfillment-worker.php`
- Validate payload fields before applying progression updates.
- Ensure notes/error messaging clearly identifies malformed product payloads.

## Validation and Test Plan

### Manual tests
1. Open `/shop` and verify 3 active products appear (`TIER_SKIP`, `BOOST_60`, `BOOST_70`).
2. `BOOST_60` purchase:
   - Creates `shop_purchase`, negative `marks_ledger`, and queued `shop_fulfillment`.
   - Worker processes and marks fulfillment `done`.
3. `BOOST_70` purchase: same checks as above.
4. `TIER_SKIP` tier options:
   - `0` and `7.5` are not selectable.
   - Tier `4` contributes `0` to total price.
5. Level gating:
   - Level 59 character cannot buy tier skip to 1..7.
   - Level 65 character can buy up to tier 7 but not tier 8+.
   - Level 70 character can buy 8+.
6. Ledger/purchases pages show accurate amounts and queue statuses.

### Regression checks
- Existing fulfilled purchases remain visible.
- Existing marks calculation still works in nav and shop pages.
- No syntax errors in modified PHP files (`php -l`).

## Rollout Order
1. Apply migration in staging.
2. Deploy controller + view changes.
3. Restart/reload fulfillment worker service.
4. Execute manual test checklist.
5. Promote to production.

## Open Decisions (to confirm before coding)
- `BOOST_70` minimum progression requirement:
  - Decided: require the character to already be at Tier 7 progression.
- Copy/branding names for the two new products in storefront.
