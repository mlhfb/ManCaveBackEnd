Local Development
-----------------

Requirements
- PHP 8.1+ for local CLI/server usage.
- Optional: Docker + Docker Compose.

Run locally (no Docker)
-----------------------
PowerShell
```powershell
php -S localhost:8000 -t html
```

Open:
- `http://localhost:8000/espn_scores_rss.php?sport=nhl`
- `http://localhost:8000/espn_scores_rss.php?sport=big10&format=rss`
- `http://localhost:8000/espn_scores_rss.php?sport=big10&format=json`

Run with Docker Compose
-----------------------
PowerShell
```powershell
docker compose up -d
```

Open:
- `http://localhost:8080/espn_scores_rss.php?sport=nhl`
- `http://localhost:8080/espn_scores_rss.php?sport=big10&format=rss`
- `http://localhost:8080/espn_scores_rss.php?sport=big10&format=json`

Run the filter test
-------------------
PowerShell
```powershell
php html/test_ncaaf_filter.php
```

Notes
-----
- The Docker Compose service uses `php:8.1-apache` and serves `html/` as `/var/www/html`.
- `sport=big10` is the filtered NCAA feed based on `html/ncaateams.list`.
- `html/ncaaf.php` is a legacy endpoint alias for the same filtered output.
- `espn_scores_rss.php` supports `format=html`, `format=rss`, and `format=json`.
- Ensure legacy ESP clients can reach your host (IP/DNS, HTTP/HTTPS trust, firewall).
