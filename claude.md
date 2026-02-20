# claude.md - guidance for AI maintainers

Purpose
- Brief for an assistant (Claude/GPT/etc.) working on this repository.

Key points to know
- `html/espn_scores_common.php` is the core library for fetching ESPN scoreboard JSON and rendering RSS.
- Core sports live in `$sportEndpoints`.
- Custom filtered feeds live in `$customSportFeeds` (for example `big10`).
- `outputRSS($sports)` supports both core sports and custom feeds.
- `html/espn_scores_rss.php` is the preview/dispatcher endpoint (`format=rss` returns feed XML).

Team filtering
- Team list file: `html/ncaateams.list` (`id,displayName`; `#` disables a line).
- `sport=big10` and legacy `html/ncaaf.php` use that list for filtering.

Constraints
- Keep changes minimal and backward compatible with legacy `rssArduinoPlatform` RSS consumers.
- Respect ESPN usage limits and terms.

If you change feed structure
- Update `README.md`, `DEVELOPMENT.md`, and `examples/rssarduino_sites_snippet.md`.
