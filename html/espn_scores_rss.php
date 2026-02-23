<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ESPN Scores RSS Generator</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { margin-bottom: 5px; }
        p.note { color: #666; font-size: 0.9em; margin-top: 0; }
        .feeds { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
        .feeds a {
            display: inline-block; padding: 10px 18px; background: #0b3d91;
            color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.95em;
        }
        .feeds a:hover { background: #1a5cc8; }
        pre { background: #f4f4f4; padding: 15px; overflow-x: auto; font-size: 0.85em; border-radius: 6px; }
    </style>
</head>
<body>
<h1>ESPN Scores RSS Feed Generator</h1>
<p class="note">For educational and personal use only. Data sourced from ESPN public APIs.</p>

<div class="feeds">
    <a href="?sport=nhl">NHL</a>
    <a href="?sport=nba">NBA</a>
    <a href="?sport=mlb">MLB</a>
    <a href="?sport=nfl">NFL</a>
    <a href="?sport=ncaaf">NCAA Football</a>
    <a href="?sport=big10">Big10 (Filtered)</a>
    <a href="?sport=all">All Sports</a>
    <a href="?sport=nhl&format=rss">NHL RSS</a>
    <a href="?sport=nba&format=rss">NBA RSS</a>
    <a href="?sport=mlb&format=rss">MLB RSS</a>
    <a href="?sport=nfl&format=rss">NFL RSS</a>
    <a href="?sport=ncaaf&format=rss">NCAA FB RSS</a>
    <a href="?sport=big10&format=rss">Big10 RSS</a>
    <a href="?sport=all&format=rss">All RSS</a>
    <a href="?sport=nhl&format=json">NHL JSON</a>
    <a href="?sport=nba&format=json">NBA JSON</a>
    <a href="?sport=mlb&format=json">MLB JSON</a>
    <a href="?sport=nfl&format=json">NFL JSON</a>
    <a href="?sport=ncaaf&format=json">NCAA FB JSON</a>
    <a href="?sport=big10&format=json">Big10 JSON</a>
    <a href="?sport=all&format=json">All JSON</a>
</div>

<?php
require_once __DIR__ . '/espn_scores_common.php';

$sport  = $_GET['sport']  ?? '';
$format = $_GET['format'] ?? 'html';
$supportedSports = getSupportedSportMap(true);

if ($sport !== '') {
    // If RSS/JSON format requested, use shared helpers and exit.
    if ($format === 'rss') {
        outputRSS($sport);
        // outputRSS calls exit, so nothing below runs
    } elseif ($format === 'json') {
        outputJSON($sport);
        // outputJSON calls exit, so nothing below runs
    }

    // Determine which sports to fetch for HTML preview
    if ($sport === 'all') {
        $selected = getSupportedSportMap(false);
    } elseif (isset($supportedSports[$sport])) {
        $selected = [$sport => $supportedSports[$sport]];
    } else {
        echo "<p>Unknown sport: " . htmlspecialchars($sport) . "</p>";
        $selected = [];
    }

    $allItems = [];
    foreach ($selected as $ep) {
        $items = fetchScoreboard($ep['url'], $ep['label']);
        $items = applyFeedFilter($items, $ep['filter'] ?? null);
        $allItems = array_merge($allItems, $items);
    }

    $feedTitle = ($sport === 'all')
        ? 'ESPN Scores — All Sports'
        : 'ESPN Scores — ' . ($supportedSports[$sport]['label'] ?? strtoupper($sport));

    $rssXml = renderRSS($allItems, $feedTitle);

    echo "<h2>$feedTitle (" . count($allItems) . " games)</h2>\n";

    if (empty($allItems)) {
        echo "<p>No games found for today's schedule.</p>\n";
    } else {
        echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%'>\n";
        echo "<tr style='background:#eee'><th>Game</th><th>Status / Time</th></tr>\n";
        foreach ($allItems as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['title']) . "</td>";
            echo "<td>" . htmlspecialchars($item['description']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    echo "<h3>Raw RSS XML</h3>\n";
    echo "<pre>" . htmlspecialchars($rssXml) . "</pre>\n";
}
?>
</body>
</html>
