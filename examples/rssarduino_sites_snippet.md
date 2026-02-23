Example `sites[]` snippet for rssArduinoPlatform
=================================================

Replace or extend `rssArduinoPlatform/src/main.cpp` entries with URLs to this backend.

```cpp
const char *HOST = "your.server.example"; // set to hostname or IP

struct site_t sites[] = {
  {"MLB Scores", "https://" HOST "/html/mlb.php", mlb},
  {"NHL Scores", "https://" HOST "/html/nhl.php", nhl},
  {"NBA Scores", "https://" HOST "/html/nba.php", nba},
  {"NFL Scores", "https://" HOST "/html/nfl.php", nfl},
  // Filtered NCAAF feed (Big Ten-focused list)
  {"NCAA FB", "https://" HOST "/html/espn_scores_rss.php?sport=big10&format=rss", ncaaf},
  // Optional combined RSS feed
  {"All Sports", "https://" HOST "/html/espn_scores_rss.php?sport=all&format=rss", npr}
};
```

Optional JSON endpoint example (for tooling/debug, not typical scroller input):
```cpp
// {"All Sports JSON", "https://" HOST "/html/espn_scores_rss.php?sport=all&format=json", npr}
```

Notes
- Use `http://` if TLS is not available.
- Ensure the device can resolve/reach `HOST`.
- Current title style:
- Pregame: `Away @ Home`
- In-progress/final: `Away 22 @ Home 30`
