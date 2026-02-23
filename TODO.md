# TODO / Wishlist

High-value next items
---------------------
- Move machine-format routing (`format=rss`, `format=json`) to execute before any static HTML in `html/espn_scores_rss.php`.
- Add endpoint-format regression checks in CI:
- Assert `format=rss` returns XML body shape
- Assert `format=json` returns JSON body shape
- Add optional date override passthrough for easier off-season testing:
- Example: `?sport=big10&format=rss&dates=20240907`
- Add fixture-based parser tests for status wording and score presentation.
- Add all-sports smoke tests (`nhl`, `nba`, `mlb`, `nfl`, `ncaaf`, `big10`).

Nice to have
------------
- Admin UI for managing `html/ncaateams.list` with token auth.
- Rate-limit/backoff support for ESPN fetch retries.
- Caching layer to reduce repeated API calls from multiple clients.
