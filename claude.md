# claude.md - guidance for AI maintainers

Purpose
- Quick orientation for assistants modifying this repository.

Core architecture
- `html/espn_scores_common.php` is the main library:
  - ESPN endpoint map (`$sportEndpoints`)
  - Custom feed map (`$customSportFeeds`, currently `big10`)
  - Scoreboard fetch/parsing
  - Team-list filtering
  - Scroller-friendly title and status formatting
  - RSS/JSON rendering and output (`outputRSS`, `outputJSON`)

- `html/espn_scores_rss.php` is the preview/dispatcher page:
  - `format=html` preview table + raw XML
  - `format=rss` direct RSS output
  - `format=json` direct JSON output

- Legacy entrypoints:
  - `html/nhl.php`, `html/nba.php`, `html/mlb.php`, `html/nfl.php`
  - `html/ncaaf.php` (alias for filtered `big10`)

Current feed behavior
- Title:
  - Pregame: `Away @ Home`
  - Live/Final: `Away SCORE @ Home SCORE`

- In-progress description normalization:
  - NFL/NCAA/NBA: `5:34 left in the 3rd quarter.`
  - NHL: `3:02 to go in the 3rd period.`
  - MLB: `top of the 7th.` / `bottom of the 9th.`

Team filtering
- Source file: `html/ncaateams.list`
- Active format: `id,displayName`
- `#` prefix disables a team
- Filtering prefers IDs, falls back to name matching only when needed

Tests and CI
- Local: `php html/test_ncaaf_filter.php`
- CI workflow: `.github/workflows/ncaaf-filter-test.yml`

Maintenance rules
- Keep RSS output structure simple and backward-compatible for legacy scroller parsers.
- Keep changes small and centralized in `espn_scores_common.php` where possible.
- If output wording/format changes, update docs:
  - `README.md`
  - `DEVELOPMENT.md`
  - `examples/rssarduino_sites_snippet.md`
  - `CHANGELOG.md`
