Local development & quickstart
----------------------------

Run a local PHP server (quick, no Docker required):

PowerShell
```powershell
php -S localhost:8000 -t html
```

Then open http://localhost:8000/espn_scores_rss.php?sport=nhl

Run with Docker Compose (recommended if you have Docker):

PowerShell
```powershell
docker compose up -d
```

Then open http://localhost:8080/espn_scores_rss.php?sport=nhl

Notes
- The Docker Compose service uses `php:8.1-apache` and serves the repository `html/` directory as `/var/www/html`.
- For the legacy ESP device, ensure your device can reach the host (use local IP or public hostname). If using HTTPS be sure the device trusts the server certificate or use HTTP.
