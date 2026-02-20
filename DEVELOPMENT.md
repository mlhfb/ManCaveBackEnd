Local development & quickstart
----------------------------

Run a local PHP server (quick, no Docker required):

PowerShell
```powershell
php -S localhost:8000 -t html
```

Then open one of:
- http://localhost:8000/espn_scores_rss.php?sport=nhl
- http://localhost:8000/espn_scores_rss.php?sport=big10&format=rss

Run with Docker Compose (recommended if you have Docker):

PowerShell
```powershell
docker compose up -d
```

Then open one of:
- http://localhost:8080/espn_scores_rss.php?sport=nhl
- http://localhost:8080/espn_scores_rss.php?sport=big10&format=rss

Notes
- The Docker Compose service uses `php:8.1-apache` and serves the repository `html/` directory as `/var/www/html`.
- For the legacy ESP device, ensure your device can reach the host (use local IP or public hostname). If using HTTPS be sure the device trusts the server certificate or use HTTP.
- `sport=big10` is the filtered NCAAF feed (team IDs from `html/ncaateams.list`).
