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
- `format` - `html` (default), `rss`, or `json`.

Examples
- HTML preview for NHL: `/html/espn_scores_rss.php?sport=nhl`
- RSS for NBA: `/html/espn_scores_rss.php?sport=nba&format=rss`
- JSON for NHL (with team and score colors): `/html/espn_scores_rss.php?sport=nhl&format=json`
- RSS for filtered Big Ten/selected NCAAF teams: `/html/espn_scores_rss.php?sport=big10&format=rss`

Feed shape
----------
Each RSS `<item>` contains:
- `title` (league + matchup + optional score)
- `description` (status/time/detail)
- `link` (ESPN match link fallback)
- `pubDate`
- `<category>` with league label

JSON feed shape (`format=json`)
-------------------------------
Top-level fields:
- `feedTitle`
- `sport`
- `generatedAt`
- `itemCount`
- `items[]`

Each JSON item includes:
- `title`, `description`, `link`, `pubDate`, `league`
- `state` (`pre`, `in`, `post`, etc.)
- `isLive` (boolean)
- `leader` (`home`, `away`, `tie`, `unknown`)
- `home` and `away` objects with:
  - `id`, `name`, `abbr`, `score`
  - `teamColor` (primary team color)
  - `alternateColor`
  - `scoreColor` (`#00FF00` leader, `#FF0000` trailer, tie/unknown fallback color)

Color sourcing
--------------
- Team colors are sourced from ESPN API team metadata (`team.color` and `team.alternateColor`) whenever available.
- A small fallback map is included for currently enabled `big10` team IDs in case ESPN color data is missing.

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
