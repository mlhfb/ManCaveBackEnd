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

Endpoint format regression:
```powershell
php html/test_endpoint_formats.php
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
- `html/test_endpoint_formats.php`
- Upload test output artifact

Team color map
--------------
`$ledTeamColors` in `espn_scores_common.php` is the primary color source for JSON output.
Keys use the format `'<leagueLabel>:<espnTeamId>'` (e.g., `'NFL:12'`, `'NBA:9'`, `'NHL:37'`).

To look up ESPN team IDs for a league:
```powershell
# MLB
curl "https://site.api.espn.com/apis/site/v2/sports/baseball/mlb/teams" |
  python -c "import json,sys; [print(t['team']['id'], t['team']['displayName'])
             for t in json.load(sys.stdin)['sports'][0]['leagues'][0]['teams']]"

# NHL, NBA, NFL — swap sport/league path:
# basketball/nba  |  football/nfl  |  hockey/nhl
```

League label values (must match `$sportEndpoints` label field):
- `'NFL'`, `'NBA'`, `'MLB'`, `'NHL'`, `'NCAA Football'`

Notes
-----
- `html/ncaaf.php` intentionally returns the filtered `big10` feed.
- `html/espn_scores_rss.php` routes `format=rss` and `format=json` before emitting static HTML.
- ESPN endpoint structure can change; keep parsing defensive.
- Legacy scroller clients expect simple RSS fields (`title`, `description`, `pubDate`).
- `$ledTeamColors` overrides take priority over ESPN API colors; designed for 8×128 RGB LED panels.
