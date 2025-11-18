# AC Portal

AC Portal is a lightweight companion site for a Wrath of the Lich King private server running on AzerothCore. It surfaces live realm telemetry, player-facing tools, and lore-forward UI so each character feels like part of an unfolding chronicle. The current codebase provides:

- **Character profiles** with equipment breakdowns, progression status, and narrative “chronicle” entries pulled from custom DB tables.
- **Armory search and top lists** filtered to real-player accounts, plus a responsive front-end styled for the shard’s fantasy theme.
- **Account dashboard and auth flows** wired directly to AzerothCore’s account tables, allowing players to manage characters via the portal.
- **Server status widgets** that read uptime and realm stats to keep players informed about maintenance windows and population.

## ⚠️ Not Ready for General Use

This repository reflects an in-progress deployment for a single realm. It is **not** packaged, documented, or configurable enough for other admins to drop into their own infrastructure yet. Critical caveats:

- Configuration is scattered across controllers, environment variables, and hard-coded account ID thresholds (e.g., account ≥ 301 = “real player”). These need consolidation before the project is reusable.
- Database schemas rely on custom tables such as `character_chronicle_log` and `character_chronicle_extras` that are not provisioned automatically here.
- Styling and copy are tailored to “Kardinal WoW” and include bespoke lore content; extracting it would require manual edits throughout the views.

If you want to adapt AC Portal today, be prepared for a deep dive into the codebase and database to trace all of the assumptions. Otherwise, keep an eye on this repository for future updates when the project is hardened for external use.

## Local Development Notes

1. Copy `.env_noSecret` to `.env` and provide valid MySQL credentials for the AzerothCore auth, characters, and world databases.
2. Install PHP dependencies with Composer (`composer install`), then point your web server to `public/index.php`.
3. Ensure the extra tables used by character chronicles exist and are populated; the UI expects them by default.

Contributions are welcome once the project is formally opened up. Until then, feel free to explore the code for inspiration or ideas, but deploy it at your own risk. Stay tuned!  

## Admin SOAP Console

GMs (level 3+) can issue worldserver commands from `/admin/soap`. Configure SOAP access in `.env`:

```
SOAP_HOST=127.0.0.1
SOAP_PORT=7878
SOAP_SCHEME=http
SOAP_URI=urn:ACSOAP
SOAP_USER=soap_user
SOAP_PASS=soap_pass
SOAP_TIMEOUT=10
```

The SOAP user should be limited to the commands you expect to run (e.g., `reload config`, `server info`). Bind the worldserver SOAP listener to localhost when possible.
