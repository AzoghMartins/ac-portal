# Tier 0 / Tier 7.5 Boost Rework Plan

Date: 2026-03-07

## Objective
Replace the current Tier 0 and Tier 7.5 boost fulfillment flow with a player-configured flow built around `specplayer`.

For Tier 0, after the rework, the remaining execution steps should be:
- run `specplayer`,
- send gold through SOAP mail if configured,
- update progression in `character_settings` to `1`.

The old direct level/talent/gear SOAP steps should be removed because `specplayer` now owns leveling, talents, and gearing.

Tier 0 is the first target:
1. User opens the Tier 0 product.
2. User selects a character.
3. Only characters below level 60 are listed.
4. User selects a talent specification for that character's class.
5. User selects two trade skills.
6. Existing professions on the character are preselected when present.
7. Purchase is validated and queued.
8. Confirmation explains that online characters are updated immediately, while offline characters are updated on next login.
9. Fulfillment issues a SOAP command in this format:

```text
specplayer <name> <spec> <level> <skill1> <skill2>
```

Example:

```text
specplayer Farsong marksman 60 skinning leatherworking
```

Tier 7.5 should use the same architecture afterward, with the product-specific eligibility and level target changed to 70.

## Current State
- `BOOST_60` and `BOOST_70` already exist as fixed-price shop products.
- `BOOST_60` currently filters to characters below level 60.
- `BOOST_70` currently filters to characters below level 70 and Tier 7 progression or higher.
- The product page currently only supports character selection.
- Fulfillment is handled by [`scripts/fulfillment-worker.php`](/srv/www/ac-portal/scripts/fulfillment-worker.php), which:
  - levels the character through SOAP,
  - resets talents,
  - infers gear from class-only logic,
  - sends gear and gold through SOAP mail commands,
  - updates progression in `character_settings`.
- There is no current UI or payload support for:
  - talent spec selection,
  - profession selection,
  - preloading existing professions,
  - issuing `specplayer`.

## Module Findings
Reference source inspected:
- `/home/azoghmartins/azeroth-repo/AzerothDev/modules/mod-playerbot-bettersetup/src/PlayerbotBetterSetup.cpp`
- `/home/azoghmartins/azeroth-repo/AzerothDev/modules/mod-playerbot-bettersetup/README.md`

What is confirmed from the module:
- `.specplayer` is registered as a GM command in the module,
- SOAP should call it as `specplayer`,
- exact command shape is:

```text
specplayer <name> <spec> <level> [skill1] [skill2]
```

- `skill1` and `skill2` are optional together:
  - omit both, or
  - provide both,
  - duplicate professions are rejected,
- the command resolves spec aliases to canonical specs,
- if the target is offline, the command queues the request and applies it on next login,
- if the target is online, the setup is applied immediately,
- the command performs:
  - level change,
  - talent/spec application,
  - post-spec maintenance,
  - spell learning,
  - profession override when provided,
  - non-language skill normalization to `level * 5`,
  - `gearself`-style gearing,
- offline requests are persisted in `character_settings` with source `mod-playerbot-bettersetup-specplayer`.

Confirmed canonical spec tokens from the module:
- Warrior: `arms`, `fury`, `protection`
- Paladin: `holy`, `protection`, `retribution`
- Hunter: `beastmaster`, `marksman`, `survival`
- Rogue: `assassination`, `combat`, `subtlety`
- Priest: `discipline`, `holy`, `shadow`
- Death Knight: `blood_tank`, `blood_dps`, `frost`, `unholy`
- Shaman: `elemental`, `enhancement`, `restoration`
- Mage: `arcane`, `fire`, `frost`
- Warlock: `affliction`, `demonology`, `destruction`
- Druid: `balance`, `feral_tank`, `feral_dps`, `restoration`

Portal recommendation based on the module:
- store and send canonical spec tokens,
- accept aliases in UI/query parsing only if that becomes useful later,
- store and send canonical profession tokens.

Confirmed profession tokens accepted by the module:
- `alchemy`
- `blacksmithing`
- `enchanting`
- `engineering`
- `herbalism`
- `inscription`
- `jewelcrafting`
- `leatherworking`
- `mining`
- `skinning`
- `tailoring`

Accepted profession aliases also exist server-side, but the portal should emit canonical values only.

Important behavior note:
- the server command does not guarantee "next login" semantics for online characters,
- the chosen portal behavior is:
  - allow both online and offline targets,
  - use copy that says online targets apply immediately,
  - say offline targets apply on next login.

## Recommended Approach

### 1. Keep the existing purchase and fulfillment queue
Do not execute external SOAP commands inside the purchase transaction.

Reason:
- the current queue already gives retries, status tracking, and error storage,
- purchase payment should commit before any worldserver side effects,
- SOAP outages should not silently lose a paid purchase.

Result:
- `shop_purchase` and `shop_fulfillment` stay in use,
- the boost payload changes,
- the fulfillment worker changes from "directly level, reset talents, infer/send gear, and then finish" to:
  - issue `specplayer`,
  - send gold if configured,
  - update progression in `character_settings`,
- the portal allows online targets and should surface immediate-vs-next-login behavior in its copy.

### 2. Replace boost payloads with explicit user choices
For `BOOST_60` and `BOOST_70`, stop using the current generic `boost.target_level` + `gear_profile` payload for new purchases.

Recommended payload shape:

```json
{
  "action": "specplayer_boost",
  "sku": "BOOST_60",
  "character_guid": 123,
  "character_name": "Farsong",
  "class_id": 3,
  "target_level": 60,
  "target_progression": 1,
  "spec": "marksman",
  "professions": ["skinning", "leatherworking"],
  "gold_copper": 5000000
}
```

For Tier 7.5, the same payload structure should be used with:
- `sku = BOOST_70`
- `target_level = 70`
- `target_progression = 8`
- `gold_copper` set from the product's configured reward behavior

### 3. Add a shared option catalog for specs and professions
Introduce one source of truth for:
- class-to-spec options,
- allowed profession options,
- SOAP-safe tokens for both.

This should be a dedicated helper/config structure rather than hardcoded directly in the view.

For specs, seed this from the confirmed module vocabulary above.
For professions, seed this from the confirmed canonical profession list above.

## Functional Requirements

### Tier 0 product page
- Show only characters below level 60.
- After character selection, show only specs valid for that class.
- Show two profession selectors.
- Preselect the character's current professions if the character already has them trained.
- Prevent the same profession from being selected twice.
- Require all fields before purchase unless the server command explicitly supports an empty placeholder.

### Tier 7.5 product page
- Reuse the same UI structure.
- Show only characters below level 70.
- Keep the existing Tier 7+ progression gate unless a new requirement is decided later.
- Use level 70 in the eventual SOAP command.

### Purchase confirmation copy
- Update the post-purchase success message in [`views/shop-purchases.php`](/srv/www/ac-portal/views/shop-purchases.php) to say:
  - if the character is online when the request is processed, changes apply immediately,
  - if the character is offline when processed, changes apply on next login.
- Remove the current "log out and log back in" wording.
- Prefer wording that is always accurate before fulfillment runs, for example:
  - `If your character is online when this order is processed, the changes apply immediately. Otherwise they will apply on next login.`
- If desired, add a more specific post-fulfillment note later using worker-captured result text.

### Fulfillment behavior
- For new `BOOST_60` and `BOOST_70` purchases, issue exactly one `specplayer` SOAP command per purchase.
- For Tier 0, keep the gold mail step after `specplayer` if `gold_copper > 0`.
- Only mark fulfillment as `done` after the SOAP command succeeds.
- Update progression state only after successful SOAP execution.
- For Tier 0, the progression update must still write `1` into `character_settings`.
- Preserve fulfillment errors in `shop_fulfillment.last_error`.
- Fulfillment should allow online targets and let the server module apply them immediately.
- Offline targets should continue to rely on the module's built-in queued-on-login behavior.

## File-Level Implementation Outline

### 1. [`src/Controllers/ShopController.php`](/srv/www/ac-portal/src/Controllers/ShopController.php)
- Extend boost product handling to load:
  - eligible characters,
  - selected character professions,
  - spec choices for the selected class,
  - profession defaults.
- Parse and validate new inputs:
  - `spec`
  - `skill1`
  - `skill2`
- Add server-side validation for:
  - character eligibility,
  - spec belongs to selected character class,
  - profession values are in the allow-list,
  - profession choices are not duplicated.
- Store the selected values in both:
  - `shop_purchase.details`
  - `shop_fulfillment.payload_json`
- Replace `buildBoost60Payload()` and `buildBoost70Payload()` for new purchases with a `specplayer`-oriented payload builder.

### 2. Product view: [`views/shop-product.php`](/srv/www/ac-portal/views/shop-product.php)
- Rework the `BOOST_60` and `BOOST_70` form sections to render:
  - character selector,
  - spec selector,
  - profession selector 1,
  - profession selector 2,
  - purchase button.
- Keep the existing tier-skip form separate.
- Preserve the current page-reload pattern if it keeps implementation simple:
  - select character,
  - reload with `guid=...`,
  - show class-specific spec options and profession defaults.
- Show a clear summary before purchase:
  - character,
  - target level,
  - chosen spec,
  - chosen professions.
- Add explanatory copy near the purchase button:
  - online characters are updated immediately when the order is processed,
  - offline characters are updated on next login.

### 3. New boost option helper
Recommended new file:
- `src/BoostOptions.php`

Responsibilities:
- return spec options by class id,
- return profession options,
- map display labels to SOAP tokens,
- expose validation helpers used by the controller and worker.

This keeps the controller and view from duplicating class/spec logic.

### 4. Character profession lookup
Add a small character-data method in the shop flow to read the selected character's professions from `character_skills`.

Expected behavior:
- detect trained primary professions,
- map skill IDs to SOAP tokens,
- prefill the two selectors when matches exist,
- ignore non-profession skills.

Skill ID to canonical token mapping should follow the module's own profession names.

This likely belongs in `ShopController` initially unless it becomes broad enough to justify a shared character repository helper.

### 5. [`scripts/fulfillment-worker.php`](/srv/www/ac-portal/scripts/fulfillment-worker.php)
- Add handling for `action = specplayer_boost`.
- Build the exact command string:

```text
specplayer <name> <spec> <level> <skill1> <skill2>
```

- Execute it via [`src/WorldServerSoap.php`](/srv/www/ac-portal/src/WorldServerSoap.php).
- If no professions were chosen, omit the skill arguments entirely.
- After successful `specplayer`, keep the existing gold-mail step when `gold_copper > 0`.
- After successful `specplayer`, keep the progression write in `character_settings`.
- For Tier 0, that progression write must set the state to `1`.
- Stop using the current boost-specific logic for new `BOOST_60` / `BOOST_70` purchases:
  - no class-profile gear generation,
  - no `send items`,
  - no separate `character level` / `reset talents` sequence.
- Keep backward compatibility for already-queued legacy payloads until the queue is clean.

### 6. SOAP audit / notes
Current admin SOAP commands are logged through [`src/SoapAudit.php`](/srv/www/ac-portal/src/SoapAudit.php).

For automated fulfillment, pick one of these approaches:
1. Write the raw `specplayer` command and result into `shop_fulfillment.notes`.
2. Extend `SoapAudit` to support a system actor for worker-issued commands.

Recommendation:
- start with `shop_fulfillment.notes`,
- add broader audit integration only if needed.

### 7. Product copy and data
Optional but likely needed:
- update product descriptions so `BOOST_60` and `BOOST_70` clearly mention class spec and profession selection,
- update boost confirmation/storefront copy to reflect immediate application for online targets and next-login application for offline targets,
- confirm whether any migration is needed to adjust `shop_product.description`.

No schema change is required for the core implementation because selections can be stored in the existing JSON columns.

## Validation Rules

### Tier 0
- character level must be below 60,
- spec must be valid for the selected class,
- if professions are provided, profession 1 and profession 2 must both be valid options,
- if professions are provided, profession 1 and profession 2 must not be the same,
- the portal should preferably require both profession fields to match the module contract cleanly,
- payload must include target level 60.
- payload must keep the Tier 0 progression target at `1`.

### Tier 7.5
- character level must be below 70 and at active progression Tier 8 (Defeat Prince Malchezaar).
- progression must meet the existing `BOOST_70` rule,
- spec must be valid for the selected class,
- if professions are provided, professions must be valid and distinct,
- payload must include target level 70.

## Testing Plan

### Manual UI tests
1. Open the Tier 0 product.
2. Confirm only characters below level 60 are listed.
3. Select a character and verify the spec list matches that class only.
4. Verify profession selectors appear after character selection.
5. Verify already-trained professions are preselected.
6. Verify duplicate professions are rejected.
7. Complete a purchase and confirm the success message explains immediate application for online targets and next-login application for offline targets.

### Fulfillment tests
1. Confirm a Tier 0 purchase writes the selected spec and professions into `shop_purchase.details`.
2. Confirm `shop_fulfillment.payload_json` stores the `specplayer_boost` payload.
3. Run the fulfillment worker once and verify it issues the exact `specplayer` command.
4. Confirm gold is still sent through SOAP mail when `gold_copper > 0`.
5. Confirm fulfillment becomes `done` only on SOAP success.
6. Confirm `last_error` is populated on SOAP failure.
7. Confirm progression is updated only after successful fulfillment.
8. Confirm Tier 0 writes progression state `1`.
9. Confirm an online target is updated immediately by the module.
10. Confirm an offline target is queued and applies on next login.
11. If worker notes are stored, confirm they reflect whether the server applied immediately or queued for login.

### Backward-compatibility tests
1. Existing tier-skip purchases still process.
2. Any legacy `BOOST_60` or `BOOST_70` payload already in the queue still processes.
3. The purchases page still renders historical rows with older payload shapes.

## Implementation Order
1. Add the shared spec/profession option catalog.
2. Extend `ShopController` data loading and validation.
3. Rework the `BOOST_60` product UI.
4. Update boost copy to describe immediate application for online targets and next-login application for offline targets.
5. Add `specplayer_boost` handling to the fulfillment worker.
6. Test Tier 0 end-to-end.
7. Apply the same pattern to `BOOST_70`.

## Open Questions To Resolve Before Coding
1. If a character has fewer than two professions, should the UI still force the user to choose two for the boost flow?
2. Should worker-issued SOAP commands also be written to the general SOAP audit log, or is fulfillment-local logging enough?

## Scope Boundary
This plan is for the new boost workflow only.

It does not propose changing the general `TIER_SKIP` tier-selection product in the same pass, except for any copy that needs to stay accurate once Tier 0 and Tier 7.5 use the new `specplayer` path.
