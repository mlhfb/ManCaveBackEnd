# claude.md - Maintainer Guide

Purpose
- Quick orientation for AI/code assistants working in this repository.

Core files
- `html/espn_scores_common.php`: central logic for fetch, parse, filter, normalize, and output.
- `html/espn_scores_rss.php`: preview/dispatcher endpoint with `sport` and `format` query args.
- `html/ncaaf.php`: legacy alias endpoint that calls `outputRSS('big10')`.
- `html/test_ncaaf_filter.php`: filter-path regression script.
- `html/test_endpoint_formats.php`: endpoint output-shape regression script.

Sport maps
- Base sports are defined in `$sportEndpoints`.
- Custom feeds are defined in `$customSportFeeds`.
- Current custom feed: `big10` mapped to `ncaaf` endpoint + team-list filter.

Filter model
- Team-list file: `html/ncaateams.list`
- Active row format: `id,displayName`
- `#` prefix disables row.
- Filtering order:
- Prefer explicit ID matching
- Fallback to name matching if IDs are unavailable

Output contracts
- RSS:
- Rendered via `renderRSS()`
- Item keys: title, link, league/category, pubDate (`description` omitted on purpose)
- RSS item titles are scroller-optimized (`@` -> `at`, then six spaces, then detail text).
- `format=rss` dispatches before any static HTML in `espn_scores_rss.php`

- JSON:
- Rendered via `renderJSON()`
- Includes game state metadata and team objects:
- Top-level: `sport`, `items`
- Item-level: `league`, `isLive`, `leader`, `detail`, `home`, `away`
- Team-level fields: `name`, `score`, `teamColor`, `alternateColor`, `scoreColor`
- `format=json` dispatches before any static HTML in `espn_scores_rss.php`

Formatting logic
- Title:
- Pregame: `Away @ Home`
- In-progress/final: `Away SCORE @ Home SCORE`
- In-progress description normalization:
- Football/basketball quarter phrasing
- Hockey period phrasing
- Baseball inning phrasing

Color logic
- Prefer ESPN team color fields.
- Use fallback map for specific teams when ESPN colors are missing.
- Score colors indicate leader/trailer/tie/unknown.

CI and local checks
- Workflow: `.github/workflows/ncaaf-filter-test.yml`
- Local command: `php html/test_ncaaf_filter.php`
- Local command: `php html/test_endpoint_formats.php`

Documentation expectations
- Keep `README.md`, `DEVELOPMENT.md`, `CHANGELOG.md`, and `examples/` aligned with code behavior.
- If output shape changes, update docs in the same PR/commit.
