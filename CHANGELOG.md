# Changelog

All notable changes to this project are tracked here.

Unreleased
----------
- Moved machine-format routing (`format=rss`, `format=json`) to run before static HTML in `html/espn_scores_rss.php`.
- Added `html/test_endpoint_formats.php` to assert `format=rss` XML/RSS output shape and `format=json` JSON output shape.
- Updated `.github/workflows/ncaaf-filter-test.yml` to run endpoint format checks in CI and upload both test artifacts.
- Added/kept Big Ten filtered feed support via `sport=big10`.
- Kept `html/ncaaf.php` as a legacy alias of the filtered `big10` feed.
- Added richer game metadata and styling helpers in `espn_scores_common.php`:
- Team color parsing with fallback map
- Leader/tie/unknown detection
- Score color hints for home/away
- Added JSON output path with structured home/away objects.
- Added sport-specific in-progress status normalization:
- Football/basketball quarter wording
- Hockey period wording
- Baseball inning wording
- Kept scroller-friendly title formatting:
- Pregame `Away @ Home`
- In-progress/final `Away SCORE @ Home SCORE`
- Updated repository documentation to match current code paths and outputs.

v0.1.0 - Initial release
------------------------
- Introduced shared ESPN-to-RSS conversion logic.
- Added multi-sport preview/dispatcher endpoint.
- Added legacy sport-specific entrypoints for scroller compatibility.
