ManCaveBackEnd
================

Lightweight PHP feeds for LED scrollers and other small clients.
This project reads ESPN scoreboard JSON and exposes normalized RSS and JSON outputs.

What is in this repo
- `html/espn_scores_common.php`: shared fetch/parse/filter/format logic.
- `html/espn_scores_rss.php`: web preview page with query-based feed output options.
- `html/nhl.php`, `html/nba.php`, `html/mlb.php`, `html/nfl.php`: sport-specific RSS endpoints.
- `html/ncaaf.php`: legacy endpoint that outputs the filtered `big10` feed.
- `html/ncaateams.list`: enabled/disabled team list used by the `big10` filter.

Supported sport keys
- `nhl`
- `nba`
- `mlb`
- `nfl`
- `ncaaf` (unfiltered college football scoreboard)
- `big10` (college football scoreboard filtered by `ncaateams.list`)
- `all` (core leagues only, no duplicate custom feed)

Endpoint usage
--------------
Primary preview/dispatcher endpoint:
- `/html/espn_scores_rss.php`

Query parameters:
- `sport`: one of the keys above.
- `format`: `html` (default), `rss`, or `json`.
- For `format=rss` and `format=json`, routing runs before any static HTML is written, so the response body is machine-only.

Examples:
- HTML preview: `/html/espn_scores_rss.php?sport=nhl`
- RSS: `/html/espn_scores_rss.php?sport=nba&format=rss`
- JSON: `/html/espn_scores_rss.php?sport=nfl&format=json`
- Filtered NCAAF RSS: `/html/espn_scores_rss.php?sport=big10&format=rss`

Legacy single-sport RSS endpoints:
- `/html/nhl.php`
- `/html/nba.php`
- `/html/mlb.php`
- `/html/nfl.php`
- `/html/ncaaf.php` (alias for filtered `big10`)

Output formats
--------------
RSS:
- Standard RSS 2.0 channel
- `<item>` includes `title`, `link`, `category`, `pubDate` (`description` is intentionally omitted)
- RSS item `title` is scroller-optimized: `@` is replaced with `at`, then six spaces, then the status/detail text.

JSON:
- Top-level: `sport`, `items`
- Per item: `league`, `isLive`, `leader`, `detail`
- Team objects:
- `home`: `name`, `score`, `teamColor`, `alternateColor`, `scoreColor`
- `away`: `name`, `score`, `teamColor`, `alternateColor`, `scoreColor`

Formatting behavior
-------------------
Title format:
- Pregame: `Away Team @ Home Team`
- In-progress/final: `Away Team 22 @ Home Team 30`

In-progress description normalization:
- NFL / NCAA / NBA: `5:34 left in the 3rd quarter.`
- NHL: `3:02 to go in the 3rd period.`
- MLB: `top of the 7th.` / `bottom of the 9th.`

Score/state metadata:
- `isLive` is true when ESPN state is `in`
- `leader` is `home`, `away`, `tie`, or `unknown`
- `scoreColor` is green/red/gold/white depending on score state

Team colors
-----------
- Primary source: `$ledTeamColors` — a curated override map of LED-optimized colors covering all
  supported leagues (NFL, NBA, MLB, NHL, NCAA Football / Big10). Keys are `'<leagueLabel>:<teamId>'`.
  These always take precedence over ESPN API colors.
- Secondary source: ESPN team color fields (`team.color`, `team.alternateColor`)
- Tertiary source: `$fallbackTeamColors` — legacy per-ID map for Big Ten teams when ESPN fields are missing
- Colors in the LED map are selected for readability as text on a black 8×128 addressable RGB panel.
  Dark primaries (dark navy, maroon, forest green, near-black) are replaced with bright accents
  (e.g., Iowa `#FFCD00` gold instead of `#231F20`, Michigan `#FFCB05` maize instead of `#00274C` navy).

Team filtering (`big10`)
------------------------
- Team list file: `html/ncaateams.list`
- Active line format: `id,displayName`
- Disabled lines start with `#`
- Filtering prefers explicit team IDs, with name matching as fallback

Quickstart
----------
Local PHP server:
```powershell
php -S localhost:8000 -t html
```

Open:
- `http://localhost:8000/espn_scores_rss.php?sport=nhl`
- `http://localhost:8000/espn_scores_rss.php?sport=big10&format=rss`
- `http://localhost:8000/espn_scores_rss.php?sport=nfl&format=json`

Docker Compose:
```powershell
docker compose up -d
```

Open:
- `http://localhost:8080/espn_scores_rss.php?sport=nhl`

Testing
-------
Run the Big10 filter test harness:
```powershell
php html/test_ncaaf_filter.php
```

Run the endpoint-format regression checks:
```powershell
php html/test_endpoint_formats.php
```

See also
--------
- `DEVELOPMENT.md`
- `CHANGELOG.md`
- `TODO.md`
- `claude.md`
- `examples/rssarduino_sites_snippet.md`
- `html/NCAAF_TEAMS.md`
