# MusicBox: deploy to Render Free

This folder contains the PHP API, 13 referenced MP3 files and artwork, and the
Zing connector. It connects to the MySQL database already imported on Filess.
No database password or SQL backup is included. Do not import the database again.

1. Create a GitHub repository, for example `musicbox-cloud`. A private repository
   works if you grant Render access to it.
2. Upload the **contents of this folder** to the repository root. `Dockerfile`,
   `render.yaml`, `public`, `docker`, and `zing` must be at the root. Do not upload
   the ZIP itself, the Android project, or a folder nesting these files one level down.
3. Sign in to Render with GitHub. Choose **New > Blueprint**, select the repository,
   and continue. The included `render.yaml` creates one **Free** Docker web service.
4. When Render asks for `MUSICBOX_DB_PASSWORD`, paste the database password from
   the Filess console. Enter it only as a Render secret environment variable.
   The remaining database parameters are already in `render.yaml`:

   ```text
   MUSICBOX_DB_HOST=758e17.h.filess.io
   MUSICBOX_DB_PORT=3307
   MUSICBOX_DB_NAME=musicbox_parklabel
   MUSICBOX_DB_USER=musicbox_parklabel
   MUSICBOX_DB_TLS_MODE=required
   ```

5. Review that the service plan is **Free** and deploy. Render builds the Docker
   image and shows a public URL such as `https://musicbox-api-xxxx.onrender.com`.
   The exact name comes from your own Render service.
6. Open `<YOUR_RENDER_URL>/health.php`. A ready service returns:

   ```json
   {"status":"ok","database":"connected","songs":13,"zing":"ok"}
   ```

7. Test these URLs:

   ```text
   <YOUR_RENDER_URL>/musicbox_api/get_songs.php
   <YOUR_RENDER_URL>/musicbox_api/get_playlists.php
   <YOUR_RENDER_URL>/musicbox_api/music/denbaogio.mp3
   <YOUR_RENDER_URL>/zing/health
   <YOUR_RENDER_URL>/zing/search?q=Son%20Tung%20M-TP
   ```

8. Configure Music on Android:

   - **Library > music server:** `<YOUR_RENDER_URL>/musicbox_api/`
   - **Search > Zing MP3 > settings:** `<YOUR_RENDER_URL>/zing/`

   Check each connection, then save. Use the HTTPS public URL with the trailing `/`.
   The APK does not need the MySQL host, port or password.

Once verified from a phone with mobile data, Laragon and the laptop Zing terminal
can be stopped. Personal playlists, favorites and downloaded audio remain on each
Android device, as before.

Render Free sleeps after 15 minutes without requests and can take about a minute
to wake. Before a demo, open `/health.php` and wait for the ready response. The
provided Android update waits for this startup. Bundled audio survives service
restarts because it is part of the image. Files uploaded manually inside a
running container do not persist; update this repository to add more audio.

Prepared LRC remains available. Creating new Whisper LRC jobs is disabled because
this container does not run Whisper. Zing access may vary by the hosting region
and provider restrictions; test a search and actual playback after deployment.

References: [Render Docker](https://render.com/docs/docker),
[Render Free](https://render.com/docs/free),
[Blueprint configuration](https://render.com/docs/blueprint-spec).
