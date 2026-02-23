# Changelog

All notable changes to this project will be documented in this file.

Unreleased
---------
- Initial import and documentation added.
- Enabled Big Ten teams and added Central Michigan (id 2117) and Western Michigan (id 2711) to the active `ncaateams.list` for the NCAAF filtered feed.
- Added `sport=big10` to `espn_scores_rss.php`, backed by shared filtering logic in `espn_scores_common.php`.
- Refactored `ncaaf.php` to use the shared `big10` filter path.
- Updated score title formatting for scrollers: `Team A 22 @ Team B 30`.
- Added sport-aware in-progress wording normalization:
  - NFL/NCAA/NBA: `5:34 left in the 3rd quarter.`
  - NHL: `3:02 to go in the 3rd period.`
  - MLB: `top of the 7th.` / `bottom of the 9th.`
- Added JSON feed output support (`format=json`) in `espn_scores_rss.php`/`espn_scores_common.php`.
- Refreshed repository documentation (`README.md`, `DEVELOPMENT.md`, `claude.md`, `examples/`, and `html/NCAAF_TEAMS.md`).

v0.1.0 - Initial release
------------------------
- Added `espn_scores_common.php` and `espn_scores_rss.php` to generate RSS from ESPN scoreboard APIs.
- Documented usage and integration notes for the legacy `rssArduinoPlatform` scroller.
