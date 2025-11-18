# Wowser integration notes

We cloned the Wowser (3.3.5a) viewer into `public/vendor/wowser`. This is the codebase that can render models/worlds in-browser using your MPQ data.

## Prerequisites
- Node.js + npm (use nvm if system Node is not available)
- Build tools for native deps (build-essential, python) and StormLib/BLPConverter as Wowser requires them to handle MPQs/BLP textures
- Your client data already lives at `/srv/www/ac-portal/ClientData`

## Build & serve Wowser
From the project root:

```bash
cd /srv/www/ac-portal/public/vendor/wowser
npm install           # install JS deps (run once)
npm run gulp          # build pipeline server (creates lib/)
npm run serve         # start pipeline; pick data path: /srv/www/ac-portal/ClientData ; keep running
```

In a second terminal build the web client bundle:

```bash
cd /srv/www/ac-portal/public/vendor/wowser
npm run web-release   # emits public/wowser-<hash>.js and index.html
```

## Launch in the portal
- The portal already serves `public/`, so after building, open `http://<your-host>/vendor/wowser/public/` (or via reverse proxy) while the pipeline server is running.
- To change pipeline settings (data path/port), run `npm run reset` and restart `npm run serve`.

## Notes
- Wowser does not ship prebuilt assets; the steps above produce both the pipeline (game data server) and the browser bundle.
- Once a stable build exists, we can add a direct link/embed from the character page to the viewer entrypoint in `public/vendor/wowser/public/index.html`.
