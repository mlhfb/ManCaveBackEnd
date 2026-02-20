<?php
// Test harness for the shared Big10/NCAAF team-list filter path.
require_once __DIR__ . '/espn_scores_common.php';

$supported = getSupportedSportMap(true);
if (!isset($supported['ncaaf'])) {
    echo "ncaaf endpoint not configured\n";
    exit(1);
}
if (!isset($supported['big10'])) {
    echo "big10 endpoint not configured\n";
    exit(1);
}

$base = $supported['ncaaf'];
$big10 = $supported['big10'];
$items = fetchScoreboard($base['url'], $base['label']);
$filtered = applyFeedFilter($items, $big10['filter'] ?? null);

$total = count($items);
$filteredCount = count($filtered);

echo "Total items: $total\n";
echo "Filtered items: $filteredCount\n\n";
echo "Samples:\n";
foreach (array_slice($filtered, 0, 10) as $item) {
    echo "- " . ($item['title'] ?? '') . " | " . ($item['description'] ?? '') . "\n";
}

if ($filteredCount === 0) {
    fwrite(STDERR, "ERROR: filtered items == 0\n");
    exit(2);
}

// When the list provides explicit IDs, every filtered game must include one.
$filter = $big10['filter'] ?? null;
if (!empty($filter) && ($filter['type'] ?? '') === 'team_list' && !empty($filter['path'])) {
    $parsed = parseTeamListFile((string)$filter['path']);
    $teamIds = $parsed['teamIds'] ?? [];
    if (!empty($teamIds)) {
        foreach ($filtered as $item) {
            $hid = isset($item['homeTeamId']) ? (string)$item['homeTeamId'] : null;
            $aid = isset($item['awayTeamId']) ? (string)$item['awayTeamId'] : null;
            if (!(($hid && isset($teamIds[$hid])) || ($aid && isset($teamIds[$aid])))) {
                fwrite(STDERR, "ERROR: filtered item missing allowed team id: " . ($item['title'] ?? '') . "\n");
                exit(3);
            }
        }
    }
}

exit(0);
