ManCaveBackEnd
================

Lightweight PHP helper that generates RSS feeds from ESPN scoreboard APIs for use by LED scrollers and legacy projects (for example, ManCaveScroller / rssArduinoPlatform).

What this repo contains
- `html/espn_scores_common.php` - shared logic: fetch ESPN scoreboard JSON, normalize items, render RSS XML, and apply optional feed filters.
- `html/espn_scores_rss.php` - web UI / preview that can render HTML or output raw RSS (`format=rss`).
- sport entrypoints in `html/` (for example `mlb.php`, `nhl.php`, `ncaaf.php`) for legacy consumers.

Usage
-----
Host the `html/` directory on a PHP-capable web server (Apache, nginx+php-fpm, and similar).

Query parameters
- `sport` - one of `nhl`, `nba`, `mlb`, `nfl`, `ncaaf`, `big10`, or `all`.
- `format` - `html` (default) or `rss`.

Examples
- HTML preview for NHL: `/html/espn_scores_rss.php?sport=nhl`
- RSS for NBA: `/html/espn_scores_rss.php?sport=nba&format=rss`
- RSS for filtered Big Ten/selected NCAAF teams: `/html/espn_scores_rss.php?sport=big10&format=rss`

Feed shape
----------
Each RSS `<item>` contains:
- `title` (league + matchup + optional score)
- `description` (status/time/detail)
- `link` (ESPN match link fallback)
- `pubDate`
- `<category>` with league label

Integration notes for ManCaveScroller
-------------------------------------
- The legacy `rssArduinoPlatform` consumer reads XML/RSS URLs from its `sites[]` array.
- Point those URLs to your hosted PHP files (for example `https://<host>/html/nhl.php`).
- Expected tags: `<item><title>`, `<description>`, and `<pubDate>`.

Team filtering
--------------
- `sport=big10` applies filtering from `html/ncaateams.list` (format: `id,displayName`, `#` to disable a line).
- `html/ncaaf.php` is kept as a legacy endpoint and now outputs the same filtered feed as `sport=big10`.
- `sport=ncaaf` in `espn_scores_rss.php` remains the full unfiltered NCAA Football scoreboard feed.

Quickstart
----------
PowerShell
```powershell
php -S localhost:8000 -t html
```

Open:
- `http://localhost:8000/espn_scores_rss.php?sport=nhl`
- `http://localhost:8000/espn_scores_rss.php?sport=big10&format=rss`

Docker Compose
--------------
PowerShell
```powershell
docker compose up -d
```

Open:
- `http://localhost:8080/espn_scores_rss.php?sport=nhl`
- `http://localhost:8080/espn_scores_rss.php?sport=big10&format=rss`

See also
--------
- `examples/rssarduino_sites_snippet.md`
- `DEVELOPMENT.md`
- `docker-compose.yml`
