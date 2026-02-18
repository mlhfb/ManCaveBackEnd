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

// Alias map: common abbreviations or alternate display names -> canonical lower-case form
$aliases = [
	'ohio st' => 'ohio state',
	'ohio st.' => 'ohio state',
	'st\.?' => 'state',
	'mich' => 'michigan',
	'msu' => 'michigan state',
	'u\.? of ' => '',
	"pitt" => 'pittsburgh',
];

// Prepare a flat list of patterns to check (teams + aliases)
$patterns = $teams;
foreach ($aliases as $a => $canonical) {
	$patterns[] = $a;
	// also include canonical if not already present
	if (!in_array($canonical, $patterns, true)) $patterns[] = $canonical;
}

// Matching helper: checks title against patterns and uses fuzzy fallback
function titleMatchesTeam(string $title, array $patterns, array $teams): bool {
	$ltitle = mb_strtolower($title);

	// Exact substring checks first
	foreach ($patterns as $p) {
		if ($p === '') continue;
		// treat patterns that look like simple tokens literally
		if (mb_strpos($ltitle, mb_strtolower($p)) !== false) return true;
	}

	// Exact team names
	foreach ($teams as $t) {
		if ($t === '') continue;
		if (mb_strpos($ltitle, $t) !== false) return true;
	}

	// Fuzzy fallback: if similar_text percent exceeds threshold for any team
	foreach ($teams as $t) {
		similar_text($t, $ltitle, $percent);
		if ($percent > 55) return true;
	}

	return false;
}

global $sportEndpoints;
if (!isset($sportEndpoints['ncaaf'])) {
	outputRSS('ncaaf');
}

$ep = $sportEndpoints['ncaaf'];
$allItems = fetchScoreboard($ep['url'], $ep['label']);

// Build allowed team IDs by matching the canonical team names in `ncaateams.list`
$allowedIds = [];
foreach ($allItems as $it) {
	$home = mb_strtolower($it['homeTeamName'] ?? '');
	$away = mb_strtolower($it['awayTeamName'] ?? '');
	foreach ($teams as $t) {
		if ($t === '') continue;
		if (mb_stripos($home, $t) !== false) {
			if (!empty($it['homeTeamId'])) $allowedIds[(string)$it['homeTeamId']] = true;
		}
		if (mb_stripos($away, $t) !== false) {
			if (!empty($it['awayTeamId'])) $allowedIds[(string)$it['awayTeamId']] = true;
		}
	}
}

// If we couldn't derive any allowed IDs from the list, fall back to title-based filtering
if (empty($allowedIds)) {
	$filtered = array_filter($allItems, function($item) use ($teams) {
		$title = mb_strtolower($item['title'] ?? '');
		foreach ($teams as $t) {
			if ($t === '') continue;
			if (mb_stripos($title, $t) !== false) return true;
		}
		return false;
	});
} else {
	$filtered = array_filter($allItems, function($item) use ($allowedIds) {
		$hid = isset($item['homeTeamId']) ? (string)$item['homeTeamId'] : null;
		$aid = isset($item['awayTeamId']) ? (string)$item['awayTeamId'] : null;
		return ($hid && isset($allowedIds[$hid])) || ($aid && isset($allowedIds[$aid]));
	});
}

$labels = [$ep['label']];
$feedTitle = 'ESPN Scores — ' . implode(', ', $labels);

header('Content-Type: application/rss+xml; charset=UTF-8');
echo renderRSS(array_values($filtered), $feedTitle);
exit;
