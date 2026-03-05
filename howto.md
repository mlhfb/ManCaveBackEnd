# OTA Backend HOWTO (EC2 + PHP)

This guide sets up OTA manifest + firmware hosting for your ESP32 scroller.

Short answer to your question:
- Is it just another `.php` page?
- Partly. You do need a PHP endpoint (`ota_manifest.php`), but you also need:
  - a place to host the `.bin` firmware files,
  - a release registry (`artifacts/ota/releases.json`),
  - a repeatable publish step to compute SHA-256 + file size and update metadata.

## What this branch adds

- `html/ota_manifest.php`: device-facing manifest endpoint.
- `html/ota_releases.php`: debug/list endpoint for your release catalog.
- `html/ota_common.php`: shared OTA helper functions.
- `tools/publish_ota_release.php`: CLI publisher for new firmware releases.
- `artifacts/ota/releases.json`: release registry file.
- `html/firmware/`: hosted firmware binaries (`.bin`).
- `html/test_ota_endpoints.php`: regression test.

## OTA flow from device perspective

1. Device calls `GET /ota_manifest.php?channel=stable`.
2. Backend returns JSON like:
```json
{
  "channel": "stable",
  "version": "0.2.0",
  "build_date": "2026-03-05",
  "firmware": {
    "url": "https://yourdomain.com/firmware/mancavescroller-0.2.0.bin",
    "size": 1234567,
    "sha256": "..."
  },
  "min_loader_version": "0.0.0",
  "notes": "..."
}
```
3. Device downloads firmware URL, validates size + SHA-256, flashes inactive OTA slot.

## Local development

From repo root:
```powershell
php -S localhost:8000 -t html
```

Check endpoints:
```powershell
curl "http://localhost:8000/ota_releases.php?channel=stable"
curl "http://localhost:8000/ota_manifest.php?channel=stable"
```

Run OTA endpoint test:
```powershell
php html/test_ota_endpoints.php
```

## Publishing a firmware release

You build firmware in your Arduino/PlatformIO repo, then publish into this backend repo.

Example:
```powershell
php tools/publish_ota_release.php `
  --version 0.2.0 `
  --file "C:\Users\mikelch\Documents\PlatformIO\Projects\ManCaveScrollerArduinoIDE\ManCaveScrollerArduinoIDE\.pio\build\esp32doit-devkit-v1-ota\firmware.bin" `
  --channel stable `
  --notes "OTA enabled release"
```

What the script does:
- Copies firmware to `html/firmware/mancavescroller-0.2.0.bin`
- Computes SHA-256 and byte size
- Updates `artifacts/ota/releases.json`
- Sets `stable.latest` to the new version (default behavior)

Then commit and deploy this backend repo to EC2.

## EC2 deployment (simple path)

Assumes Ubuntu + Apache + PHP.

1. Install stack:
```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php git
```

2. Clone/update repo:
```bash
cd /var/www
sudo git clone https://github.com/mlhfb/ManCaveBackEnd.git mancavebackend
cd mancavebackend
```

3. Point Apache to `html/` as web root (vhost or symlink).
4. Ensure web files are readable by Apache user.
5. Restart Apache:
```bash
sudo systemctl restart apache2
```

6. Validate:
```bash
curl "http://YOUR_DOMAIN/ota_releases.php?channel=stable"
curl "http://YOUR_DOMAIN/ota_manifest.php?channel=stable"
```

## HTTPS (strongly recommended)

OTA firmware delivery should use HTTPS.

Use Let's Encrypt:
```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d YOUR_DOMAIN
```

Then set your device manifest URL to:
- `https://YOUR_DOMAIN/ota_manifest.php?channel=stable`

## Rollback / channels

- Use channels like `stable`, `beta`, `dev`.
- Publish to non-stable first:
```powershell
php tools/publish_ota_release.php --version 0.2.1 --file ... --channel beta
```

## Operational notes

- Keep firmware files immutable once published.
- Never reuse version numbers.
- Keep `releases.json` in git so release history is auditable.
- If you're behind a reverse proxy, manifest URL generation already respects `X-Forwarded-Proto` and `X-Forwarded-Host`.

## Next upgrade ideas

- Add signed manifests (HMAC or public-key signatures).
- Add auth token for manifest access.
- Add cleanup tool for old firmware binaries.
- Add GitHub Actions release job that auto-runs `publish_ota_release.php`.
