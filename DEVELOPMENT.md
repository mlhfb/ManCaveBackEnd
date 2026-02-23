Development Notes
=================

Prerequisites
- PHP 8.1 or newer.
- `allow_url_fopen` enabled in PHP (required for ESPN fetches).
- Optional: Docker + Docker Compose.

Run locally
-----------
```powershell
php -S localhost:8000 -t html
```

Useful URLs:
- `http://localhost:8000/espn_scores_rss.php?sport=nhl`
- `http://localhost:8000/espn_scores_rss.php?sport=big10&format=rss`
- `http://localhost:8000/espn_scores_rss.php?sport=nfl&format=json`
- `http://localhost:8000/ncaaf.php`

Run with Docker
---------------
```powershell
docker compose up -d
```

Then browse:
- `http://localhost:8080/espn_scores_rss.php?sport=nhl`

Validation scripts
------------------
Big10 filter regression:
```powershell
php html/test_ncaaf_filter.php
```

Team list generation:
- PowerShell generator:
```powershell
pwsh tools/generate_ncaateams.ps1
```
- PHP generator:
```powershell
php html/generate_ncaateams_list.php
```

CI
--
- Workflow: `.github/workflows/ncaaf-filter-test.yml`
- Current CI checks:
- PHP setup and runtime info
- `html/test_ncaaf_filter.php`
- Upload test output artifact

Notes
-----
- `html/ncaaf.php` intentionally returns the filtered `big10` feed.
- ESPN endpoint structure can change; keep parsing defensive.
- Legacy scroller clients expect simple RSS fields (`title`, `description`, `pubDate`).
