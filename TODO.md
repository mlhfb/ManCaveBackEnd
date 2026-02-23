# TODO / Wishlist

Potential next improvements:

- Add `html/teams_admin.php`:
  - Web UI to enable/disable teams in `html/ncaateams.list`
  - Token-protected POST updates (`ADMIN_TOKEN`)
  - Validate `id,displayName` format and write atomically with backup

- Add optional date override passthrough for testing:
  - Example target: `?sport=big10&format=rss&dates=20240907`
  - Would make off-season/daytime validation easier for scroller devices

- Add canned fixture tests for feed formatting:
  - Verify title output (`Team A 22 @ Team B 30`)
  - Verify sport-specific in-progress wording normalization

- Add endpoint-level smoke tests for all sports:
  - `nhl`, `nba`, `mlb`, `nfl`, `ncaaf`, `big10`

Notes:
- Team list tooling already exists: `tools/generate_ncaateams.ps1` and `html/generate_ncaateams_list.php`.
- Current CI validates the filtered `big10`/NCAAF path via `html/test_ncaaf_filter.php`.
