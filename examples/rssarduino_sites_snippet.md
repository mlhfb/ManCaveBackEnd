Example `sites[]` snippet for rssArduinoPlatform
------------------------------------------------

Drop this snippet into `rssArduinoPlatform/src/main.cpp` (replace the existing `sites[]` entries or add new ones). Update `HOST` to the hostname where you serve the PHP files.

Example
```
const char *HOST = "your.server.example"; // replace with your host or IP

struct site_t sites[] = {
  {"MLB Scores", "https://" HOST "/html/mlb.php", mlb},
  {"NHL Scores", "https://" HOST "/html/nhl.php", nhl},
  {"NBA Scores", "https://" HOST "/html/nba.php", nba},
  {"NFL Scores", "https://" HOST "/html/nfl.php", nfl},
  // filtered Big Ten/selected NCAAF feed (same output as legacy ncaaf.php)
  {"NCAA FB",   "https://" HOST "/html/espn_scores_rss.php?sport=big10&format=rss", ncaaf},
  // legacy alias that returns the same filtered feed:
  // {"NCAA FB", "https://" HOST "/html/ncaaf.php", ncaaf},
  // optional: a combined feed
  {"All Sports", "https://" HOST "/html/espn_scores_rss.php?sport=all&format=rss", npr}
  // optional: JSON endpoint for debugging/integration tooling
  // {"All Sports JSON", "https://" HOST "/html/espn_scores_rss.php?sport=all&format=json", npr}
};

// Notes
- Use `http://` if your host does not have TLS. The ESP client must be able to reach the host.
- The Arduino code expects simple RSS with `<item><title>`, `<description>`, and `<pubDate>` tags produced by the PHP scripts.
- Current title format is compact for scrollers:
  - Pregame: `Away @ Home`
  - Live/Final: `Away 22 @ Home 30`
- Live descriptions are normalized by sport (clock/quarter, period, inning phrasing).
