# Changelog

All notable changes to this project will be documented in this file.

Unreleased
---------
- Initial import and documentation added.
- Enabled Big Ten teams and added Central Michigan (id 2117) and Western Michigan (id 2711) to the active `ncaateams.list` for the NCAAF filtered feed.
- Added `sport=big10` to `espn_scores_rss.php`, backed by shared filtering logic in `espn_scores_common.php`.
- Refactored `ncaaf.php` to use the shared `big10` filter path.

v0.1.0 - Initial release
------------------------
- Added `espn_scores_common.php` and `espn_scores_rss.php` to generate RSS from ESPN scoreboard APIs.
- Documented usage and integration notes for the legacy `rssArduinoPlatform` scroller.
