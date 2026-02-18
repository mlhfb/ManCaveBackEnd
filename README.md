ManCaveBackEnd
================

Lightweight PHP helper that generates RSS feeds from ESPN scoreboard APIs for use by LED scrollers and legacy projects (e.g. ManCaveScroller / rssArduinoPlatform).

What this repo contains
- `html/espn_scores_common.php` — shared logic: fetch ESPN scoreboard JSON → normalize items → render RSS XML. Main helpers: `fetchScoreboard()`, `renderRSS()` and `outputRSS()`.
- `html/espn_scores_rss.php` — small web UI / preview that uses `espn_scores_common.php`. It can return an HTML preview or output raw RSS when `format=rss` is requested.
- other sport-specific PHP files in `html/` are simple entry points used in legacy setups.

Usage
-----
Host the `html/` directory on a PHP-capable webserver (Apache, nginx+php-fpm, etc.).

Basic query parameters

- `sport` — one of `nhl`, `nba`, `mlb`, `nfl`, `ncaaf`, or `all`.
- `format` — `html` (default) or `rss`. When `format=rss` the script outputs an RSS feed and exits.

Examples

- HTML preview for NHL: `/html/espn_scores_rss.php?sport=nhl`
- RSS feed (machine-readable) for NBA: `/html/espn_scores_rss.php?sport=nba&format=rss`

Feed shape
----------
- Each RSS `<item>` contains: `title` (league + matchup + optional score), `description` (status/time/detail), `link` (ESPN match link fallback), `pubDate`, and `<category>` with league label.

Integration notes for ManCaveScroller (legacy)
--------------------------------------------
- The Arduino/ESP code in the legacy `rssArduinoPlatform` (see `src/main.cpp`) fetches XML/RSS from configured URLs in its `sites[]` array. Typical entries point at `https://<host>/mlb.php`, `nhl.php`, etc.
- Host these PHP files on a reachable server and update `sites[]` in the ESP sketch to point to your instance.
- The Arduino code expects simple RSS with `<item><title>`, `<description>`, and `<pubDate>` tags — the PHP scripts produce those.

Notes & cautions
----------------
- The PHP scripts use ESPN's public JSON endpoints. Observe ESPN's Terms of Use and rate limits. These scripts include a short HTTP timeout and a simple User-Agent header for personal/educational use.
- Timezone formatting in `espn_scores_common.php` uses `America/New_York` to produce human-friendly kickoff strings for pregame events.

Quickstart
----------
Run a local PHP server (quick, no Docker required):

PowerShell
```powershell
php -S localhost:8000 -t html
```

Open the preview page in your browser:

http://localhost:8000/espn_scores_rss.php?sport=nhl

Docker Compose (recommended)
---------------------------
Bring up a PHP+Apache container that serves the `html/` directory:

PowerShell
```powershell
docker compose up -d
```

Then open http://localhost:8080/espn_scores_rss.php?sport=nhl

Examples & integration
----------------------
- Example `sites[]` snippet for the legacy ESP scroller: [examples/rssarduino_sites_snippet.md](examples/rssarduino_sites_snippet.md)
- Local dev quickstart and Docker instructions: [DEVELOPMENT.md](DEVELOPMENT.md)
- Compose file: [docker-compose.yml](docker-compose.yml)

Would you like me to create a branch and commit these docs changes now? (I can also push and open a PR if you provide the remote name or allow pushing.)
