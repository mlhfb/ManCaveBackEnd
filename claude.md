# claude.md — guidance for AI maintainers

Purpose
- Brief for an assistant (Claude/GPT/etc.) working on the ManCaveBackEnd repository.

Key points to know
- `espn_scores_common.php` fetches ESPN scoreboard JSON endpoints (see `$sportEndpoints`) and converts each event into an RSS `<item>`: `title`, `description`, `link`, `pubDate`, `category`.
- `outputRSS($sports)` is the primary entrypoint for machine use — it gathers selected leagues and echoes RSS XML with appropriate headers.
- `espn_scores_rss.php` is a human-facing preview page that calls the common library; it supports `format=rss` to return raw RSS.

Suggested tasks an assistant can do
- Add small API docs that list supported `sport` codes and example URLs.
- Add a docker-compose or simple PHP dev server recipe for local testing.
- Improve error handling: retry/backoff for HTTP fetches, and more robust nil-checks when JSON structure changes.
- Add unit tests for `fetchScoreboard()` parsing behavior by adding a small test harness that can load canned JSON.

Constraints & cautions
- This code targets personal/educational use. Respect ESPN terms and avoid aggressive polling.
- Keep changes minimal and backwards-compatible with the legacy `rssArduinoPlatform` which expects basic RSS `<item>` tags.

If you change the RSS structure
- Update `README.md` and `claude.md` to include the new fields and provide examples.
- Notify the owner about required changes to the Arduino `sites[]` consumer if tag names/structure change.
