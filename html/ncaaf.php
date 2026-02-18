<?php
require_once __DIR__ . '/espn_scores_common.php';

// Read allowed teams from ncaateams.list (comma or newline separated)
$listFile = __DIR__ . '/ncaateams.list';
if (!is_readable($listFile)) {
	// Fallback to full feed if the list is not available
	outputRSS('ncaaf');
}

$raw = file_get_contents($listFile);
$tokens = preg_split('/[,\r\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
$teams = array_map(function($s){ return trim(mb_strtolower($s)); }, $tokens);

global $sportEndpoints;
if (!isset($sportEndpoints['ncaaf'])) {
	outputRSS('ncaaf');
}

$ep = $sportEndpoints['ncaaf'];
$allItems = fetchScoreboard($ep['url'], $ep['label']);

// Filter items: keep only games where either team name matches the list
$filtered = array_filter($allItems, function($item) use ($teams) {
	$title = mb_strtolower($item['title'] ?? '');
	foreach ($teams as $t) {
		if ($t === '') continue;
		if (mb_stripos($title, $t) !== false) return true;
	}
	return false;
});

$labels = [$ep['label']];
$feedTitle = 'ESPN Scores — ' . implode(', ', $labels);

header('Content-Type: application/rss+xml; charset=UTF-8');
echo renderRSS(array_values($filtered), $feedTitle);
exit;
