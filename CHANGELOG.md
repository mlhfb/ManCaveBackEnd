# Changelog

All notable changes to this project are tracked here.

Unreleased
----------
- Added `$ledTeamColors` — a comprehensive LED-optimized team color override map covering all 32 NFL
  teams, all 30 NBA teams, all 30 MLB teams, all 32 NHL teams, and the Big10 NCAA Football teams
  (plus active non-conference teams in `ncaateams.list`).
- Added `lookupLedTeamColor(string $leagueLabel, ?string $teamId): ?string` helper.
- Modified `getTeamColorBundle()` to accept `$leagueLabel` and check `$ledTeamColors` first before
  ESPN API colors or the legacy `$fallbackTeamColors` map.
- Updated `fetchScoreboard()` call sites to pass `$leagueLabel` to `getTeamColorBundle()`.
- Color selection criteria: bright, saturated hues for text legibility on a black 8×128 addressable
  RGB LED panel; dark primaries replaced with bright accents (e.g., Iowa gold `#FFCD00`, Michigan
  maize `#FFCB05`, Penn State blue `#0099FF` in place of near-black/dark-navy originals).
- Removed RSS item-level `description` element now that scroll text is fully encoded in item `title`.
- Updated RSS item title rendering for scrollers: replaced `@` with `at`, added six spaces, and appended the item detail text.
- Removed `state` from JSON game objects while keeping `isLive`.
- Simplified JSON payload for ESP32 clients: removed `generatedAt` and legacy RSS-style fields (`title`, `description`, `link`, `pubDate`) from JSON items.
- JSON team objects now use full `name` (no `abbr`) while retaining `teamColor`, `alternateColor`, and `scoreColor`.
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
