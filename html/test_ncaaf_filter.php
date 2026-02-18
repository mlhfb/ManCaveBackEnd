<?php
// Test harness for NCAAF filtering logic
require_once __DIR__ . '/espn_scores_common.php';

$listFile = __DIR__ . '/ncaateams.list';
if (!is_readable($listFile)) {
    echo "ncaateams.list not found or unreadable\n";
    exit(1);
}

$raw = file_get_contents($listFile);
$tokens = preg_split('/[,\r\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
$teams = array_map(function($s){ return trim(mb_strtolower($s)); }, $tokens);

$aliases = [
    'ohio st' => 'ohio state',
    'ohio st.' => 'ohio state',
    'mich' => 'michigan',
    'msu' => 'michigan state',
    "pitt" => 'pittsburgh',
];

$patterns = $teams;
foreach ($aliases as $a => $canonical) {
    $patterns[] = $a;
    if (!in_array($canonical, $patterns, true)) $patterns[] = $canonical;
}

function titleMatchesTeam(string $title, array $patterns, array $teams): bool {
    $ltitle = mb_strtolower($title);
    foreach ($patterns as $p) {
        if ($p === '') continue;
        if (mb_strpos($ltitle, mb_strtolower($p)) !== false) return true;
    }
    foreach ($teams as $t) {
        if ($t === '') continue;
        if (mb_strpos($ltitle, $t) !== false) return true;
    }
    foreach ($teams as $t) {
        similar_text($t, $ltitle, $percent);
        if ($percent > 55) return true;
    }
    return false;
}

global $sportEndpoints;
if (!isset($sportEndpoints['ncaaf'])) {
    echo "ncaaf endpoint not configured\n";
    exit(1);
}

$ep = $sportEndpoints['ncaaf'];
$items = fetchScoreboard($ep['url'], $ep['label']);

$total = count($items);

// Derive allowed team IDs from the feed by matching names in ncaateams.list
$allowedIds = [];
foreach ($items as $it) {
    $home = mb_strtolower($it['homeTeamName'] ?? '');
    $away = mb_strtolower($it['awayTeamName'] ?? '');
    foreach ($teams as $t) {
        if ($t === '') continue;
        if (mb_stripos($home, $t) !== false && !empty($it['homeTeamId'])) {
            $allowedIds[(string)$it['homeTeamId']] = true;
        }
        if (mb_stripos($away, $t) !== false && !empty($it['awayTeamId'])) {
            $allowedIds[(string)$it['awayTeamId']] = true;
        }
    }
}

$filtered = [];
if (empty($allowedIds)) {
    // fallback to title matching
    foreach ($items as $it) {
        if (titleMatchesTeam($it['title'] ?? '', $patterns, $teams)) $filtered[] = $it;
    }
} else {
    foreach ($items as $it) {
        $hid = isset($it['homeTeamId']) ? (string)$it['homeTeamId'] : null;
        $aid = isset($it['awayTeamId']) ? (string)$it['awayTeamId'] : null;
        if (($hid && isset($allowedIds[$hid])) || ($aid && isset($allowedIds[$aid]))) {
            $filtered[] = $it;
        }
    }
}

echo "Total items: $total\n";
echo "Filtered items: " . count($filtered) . "\n\n";
echo "Samples:\n";
foreach (array_slice($filtered, 0, 10) as $f) {
    echo "- " . ($f['title'] ?? '') . " | " . ($f['description'] ?? '') . "\n";
}

if (count($filtered) === 0) {
    fwrite(STDERR, "ERROR: filtered items == 0\n");
    exit(2);
}

exit(0);

?>
