# TODO / Wishlist

High-value next items
---------------------
- Add optional date override passthrough for easier off-season testing:
- Example: `?sport=big10&format=rss&dates=20240907`
- Add fixture-based parser tests for status wording and score presentation.
- Add all-sports smoke tests (`nhl`, `nba`, `mlb`, `nfl`, `ncaaf`, `big10`).

Recently completed (2026-02-23)
-------------------------------
- Moved machine-format routing (`format=rss`, `format=json`) to run before static HTML in `html/espn_scores_rss.php`.
- Added endpoint-format regression checks in CI for `format=rss` XML body shape and `format=json` JSON body shape.

Nice to have
------------
- Admin UI for managing `html/ncaateams.list` with token auth.
- Rate-limit/backoff support for ESPN fetch retries.
- Caching layer to reduce repeated API calls from multiple clients.
