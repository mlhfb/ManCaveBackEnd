<?php
require_once __DIR__ . '/espn_scores_common.php';

// Optional debug mode: append ?debug=1 to the URL to show errors inline
$debug = isset($_GET['debug']) && ($_GET['debug'] == '1' || $_GET['debug'] === 'true');
if ($debug) {
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);
}

try {
	// Read allowed teams from ncaateams.list (CSV lines: id,displayName)
	$listFile = __DIR__ . '/ncaateams.list';
	if (!is_readable($listFile)) {
		// Fallback to full feed if the list is not available
		outputRSS('ncaaf');
	}

	$lines = file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$teamNames = [];
	$teamIds = [];
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#') continue;
		if (strpos($line, ',') !== false) {
			list($id, $name) = explode(',', $line, 2);
			$id = trim($id);
			$name = trim($name);
			if ($id !== '') $teamIds[(string)$id] = true;
			if ($name !== '') $teamNames[] = mb_strtolower($name);
		} else {
			// legacy single-name lines
			$teamNames[] = mb_strtolower($line);
		}
	}

	// If $teamIds is populated, prefer explicit ID filtering (more reliable)
	global $sportEndpoints;
	if (!isset($sportEndpoints['ncaaf'])) {
		outputRSS('ncaaf');
	}
	$ep = $sportEndpoints['ncaaf'];
	$allItems = fetchScoreboard($ep['url'], $ep['label']);

	if (!empty($teamIds)) {
		$allowedIds = $teamIds;
	} else {
		// derive allowed IDs by matching display names from the feed
		$allowedIds = [];
		foreach ($allItems as $it) {
			$home = mb_strtolower($it['homeTeamName'] ?? '');
			$away = mb_strtolower($it['awayTeamName'] ?? '');
			foreach ($teamNames as $t) {
				if ($t === '') continue;
				if (mb_stripos($home, $t) !== false) {
					if (!empty($it['homeTeamId'])) $allowedIds[(string)$it['homeTeamId']] = true;
				}
				if (mb_stripos($away, $t) !== false) {
					if (!empty($it['awayTeamId'])) $allowedIds[(string)$it['awayTeamId']] = true;
				}
			}
		}
	}

	// Filter items
	if (empty($allowedIds)) {
		// Fall back to title/name matching if no IDs found
		$filtered = array_filter($allItems, function($item) use ($teamNames) {
			$title = mb_strtolower($item['title'] ?? '');
			foreach ($teamNames as $t) {
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

} catch (Throwable $e) {
	// On production keep it quiet but log; with ?debug=1 show details inline for troubleshooting
	error_log('ncaaf.php error: ' . $e->getMessage());
	if ($debug) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
		echo "Error: " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
	} else {
		// generic 500
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
		echo "Internal Server Error";
	}
	exit;
}
