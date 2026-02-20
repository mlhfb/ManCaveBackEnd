<?php
// ─── Shared ESPN Scoreboard → RSS logic ───
// Provide minimal mbstring polyfills when the PHP `mbstring` extension
// is not available in the runtime. These fallbacks use single-byte
// functions and are intentionally small — enabling `mbstring` in
// production is recommended for full Unicode correctness.
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($s, $enc = null) { return strtolower($s); }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($s, $enc = null) { return strtoupper($s); }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $enc = null) { return strpos($haystack, $needle, $offset); }
}
if (!function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0, $enc = null) { return stripos($haystack, $needle, $offset); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $length = null, $enc = null) {
        if ($length === null) return substr($s, $start);
        return substr($s, $start, $length);
    }
}
// Include this file, then call outputRSS('nhl') etc.

$sportEndpoints = [
    'nhl'   => ['url' => 'https://site.api.espn.com/apis/site/v2/sports/hockey/nhl/scoreboard',              'label' => 'NHL'],
    'nba'   => ['url' => 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba/scoreboard',          'label' => 'NBA'],
    'mlb'   => ['url' => 'https://site.api.espn.com/apis/site/v2/sports/baseball/mlb/scoreboard',            'label' => 'MLB'],
    'nfl'   => ['url' => 'https://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard',            'label' => 'NFL'],
    'ncaaf' => ['url' => 'https://site.api.espn.com/apis/site/v2/sports/football/college-football/scoreboard','label' => 'NCAA Football'],
];

$customSportFeeds = [
    // Uses the NCAAF endpoint and filters to teams enabled in ncaateams.list.
    'big10' => [
        'source' => 'ncaaf',
        'label'  => 'NCAA Football',
        'filter' => [
            'type' => 'team_list',
            'path' => __DIR__ . '/ncaateams.list',
        ],
    ],
];

function getSupportedSportMap(bool $includeCustom = true): array {
    global $sportEndpoints, $customSportFeeds;

    $supported = $sportEndpoints;
    if (!$includeCustom) {
        return $supported;
    }

    foreach ($customSportFeeds as $sportKey => $config) {
        $source = $config['source'] ?? '';
        if (!isset($sportEndpoints[$source])) {
            continue;
        }
        $base = $sportEndpoints[$source];
        $supported[$sportKey] = [
            'url'    => $base['url'],
            'label'  => $config['label'] ?? $base['label'],
            'filter' => $config['filter'] ?? null,
        ];
    }

    return $supported;
}

function parseTeamListFile(string $listFile): array {
    if (!is_readable($listFile)) {
        return ['teamIds' => [], 'teamNames' => []];
    }

    $lines = file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return ['teamIds' => [], 'teamNames' => []];
    }

    $teamIds = [];
    $teamNames = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (strpos($line, ',') !== false) {
            list($id, $name) = explode(',', $line, 2);
            $id = trim($id);
            $name = trim($name);
            if ($id !== '') {
                $teamIds[(string)$id] = true;
            }
            if ($name !== '') {
                $teamNames[] = mb_strtolower($name);
            }
            continue;
        }

        // Legacy support for one team name per line.
        $teamNames[] = mb_strtolower($line);
    }

    return ['teamIds' => $teamIds, 'teamNames' => $teamNames];
}

function filterItemsByTeamList(array $items, string $listFile): array {
    $parsed = parseTeamListFile($listFile);
    $teamIds = $parsed['teamIds'];
    $teamNames = $parsed['teamNames'];

    // Missing/empty list means "do not filter".
    if (empty($teamIds) && empty($teamNames)) {
        return $items;
    }

    $allowedIds = $teamIds;
    if (empty($allowedIds)) {
        // Derive allowed IDs by matching display names from the feed.
        foreach ($items as $it) {
            $home = mb_strtolower($it['homeTeamName'] ?? '');
            $away = mb_strtolower($it['awayTeamName'] ?? '');
            foreach ($teamNames as $teamName) {
                if ($teamName === '') {
                    continue;
                }
                if (mb_stripos($home, $teamName) !== false && !empty($it['homeTeamId'])) {
                    $allowedIds[(string)$it['homeTeamId']] = true;
                }
                if (mb_stripos($away, $teamName) !== false && !empty($it['awayTeamId'])) {
                    $allowedIds[(string)$it['awayTeamId']] = true;
                }
            }
        }
    }

    if (empty($allowedIds)) {
        // Last-resort fallback: title/name matching.
        $filtered = array_filter($items, function ($item) use ($teamNames) {
            $title = mb_strtolower($item['title'] ?? '');
            foreach ($teamNames as $teamName) {
                if ($teamName === '') {
                    continue;
                }
                if (mb_stripos($title, $teamName) !== false) {
                    return true;
                }
            }
            return false;
        });
        return array_values($filtered);
    }

    $filtered = array_filter($items, function ($item) use ($allowedIds) {
        $hid = isset($item['homeTeamId']) ? (string)$item['homeTeamId'] : null;
        $aid = isset($item['awayTeamId']) ? (string)$item['awayTeamId'] : null;
        return ($hid && isset($allowedIds[$hid])) || ($aid && isset($allowedIds[$aid]));
    });

    return array_values($filtered);
}

function applyFeedFilter(array $items, ?array $filter): array {
    if (empty($filter) || !isset($filter['type'])) {
        return $items;
    }

    switch ($filter['type']) {
        case 'team_list':
            $listFile = isset($filter['path']) ? (string)$filter['path'] : '';
            if ($listFile === '') {
                return $items;
            }
            return filterItemsByTeamList($items, $listFile);
        default:
            return $items;
    }
}

function formatGameTitle(
    string $awayName,
    string $homeName,
    string $state,
    string $awayScore,
    string $homeScore
): string {
    // Keep titles compact for legacy scrollers.
    if ($state === 'pre') {
        return "$awayName @ $homeName";
    }
    return "$awayName $awayScore @ $homeName $homeScore";
}

function normalizeInProgressDetail(string $shortDetail, string $detail): string {
    $source = trim($shortDetail !== '' ? $shortDetail : $detail);
    if ($source === '') {
        return $detail;
    }

    // ESPN often returns "5:34 - 3rd" or "5:34 - 3rd Qtr".
    if (preg_match('/^(\d{1,2}:\d{2})\s*-\s*(\d+(?:st|nd|rd|th))(?:\s*(?:qtr|quarter))?$/i', $source, $m)) {
        return $m[1] . ' left in the ' . strtolower($m[2]);
    }

    return $detail !== '' ? $detail : $source;
}

function fetchScoreboard(string $apiUrl, string $leagueLabel): array {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: PersonalRSSReader/1.0 (educational use)\r\n",
        ],
    ]);

    $json = @file_get_contents($apiUrl, false, $context);
    if ($json === false) return [];

    $data = json_decode($json, true);
    if (!isset($data['events'])) return [];

    $items = [];
    foreach ($data['events'] as $event) {
        $comp = $event['competitions'][0] ?? null;
        if (!$comp) continue;

        $home = $away = null;
        foreach ($comp['competitors'] as $c) {
            if ($c['homeAway'] === 'home') $home = $c;
            if ($c['homeAway'] === 'away') $away = $c;
        }
        if (!$home || !$away) continue;

        $homeName  = $home['team']['displayName'];
        $awayName  = $away['team']['displayName'];
        $homeId    = $home['team']['id'] ?? null;
        $awayId    = $away['team']['id'] ?? null;
        $homeAbbr  = $home['team']['abbreviation'] ?? '';
        $awayAbbr  = $away['team']['abbreviation'] ?? '';
        $homeScore = $home['score'] ?? '0';
        $awayScore = $away['score'] ?? '0';

        $state       = $comp['status']['type']['state']       ?? 'pre';
        $detail      = $comp['status']['type']['detail']      ?? '';
        $shortDetail = $comp['status']['type']['shortDetail'] ?? '';

        // ESPN sometimes returns just "Scheduled" in detail for pregame
        // (e.g. MLB spring training). Fall back to the event's startDate
        // and format it to match the verbose style other sports use,
        // e.g. "Fri, February 20th at 1:05 PM EST".
        if ($state === 'pre' && $detail === 'Scheduled') {
            $startDate = $event['date'] ?? $event['startDate'] ?? '';
            if ($startDate !== '') {
                // Convert UTC timestamp to US Eastern (handles EST/EDT automatically)
                $eastern = new DateTimeZone('America/New_York');
                $dt = new DateTime($startDate);
                $dt->setTimezone($eastern);

                $day   = $dt->format('D');           // Fri
                $month = $dt->format('F');           // February
                $dom   = (int) $dt->format('j');     // 20
                $time  = $dt->format('g:i A');       // 1:05 PM
                $tz    = $dt->format('T');           // EST or EDT

                // Ordinal suffix (1st, 2nd, 3rd, 4th…)
                $suffixes = [1 => 'st', 2 => 'nd', 3 => 'rd'];
                if ($dom % 100 >= 11 && $dom % 100 <= 13) {
                    $suffix = 'th';
                } else {
                    $suffix = isset($suffixes[$dom % 10]) ? $suffixes[$dom % 10] : 'th';
                }

                $detail = "$day, $month {$dom}{$suffix} at $time $tz";
            }
        }

        $title = formatGameTitle($awayName, $homeName, $state, (string)$awayScore, (string)$homeScore);
        if ($state === 'in') {
            $detail = normalizeInProgressDetail((string)$shortDetail, (string)$detail);
        }

        $link = $event['links'][0]['href']
             ?? 'https://www.espn.com/' . strtolower($leagueLabel);

        $items[] = [
            'title'          => $title,
            'description'    => $detail,
            'link'           => $link,
            'pubDate'        => date(DATE_RSS, strtotime($event['date'] ?? 'now')),
            'league'         => $leagueLabel,
            // expose team metadata for downstream filtering by ID
            'homeTeamName'   => $homeName,
            'awayTeamName'   => $awayName,
            'homeTeamId'     => $homeId,
            'awayTeamId'     => $awayId,
            'homeTeamAbbrev' => $homeAbbr,
            'awayTeamAbbrev' => $awayAbbr,
        ];
    }

    return $items;
}

function renderRSS(array $items, string $feedTitle): string {
    $buildDate = date(DATE_RSS);
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<rss version="2.0">' . "\n";
    $xml .= "<channel>\n";
    $xml .= "  <title>" . xmlSafe($feedTitle) . "</title>\n";
    $xml .= "  <description>ESPN live scores and game times — educational/personal use</description>\n";
    $xml .= "  <link>https://www.espn.com</link>\n";
    $xml .= "  <lastBuildDate>$buildDate</lastBuildDate>\n";

    foreach ($items as $item) {
        $xml .= "  <item>\n";
        $xml .= "    <title>"       . xmlSafe($item['title'])       . "</title>\n";
        $xml .= "    <description>" . xmlSafe($item['description']) . "</description>\n";
        $xml .= "    <link>"        . xmlSafe($item['link'])        . "</link>\n";
        $xml .= "    <category>"    . xmlSafe($item['league'])      . "</category>\n";
        $xml .= "    <pubDate>"     . $item['pubDate']              . "</pubDate>\n";
        $xml .= "  </item>\n";
    }

    $xml .= "</channel>\n</rss>\n";
    return $xml;
}

function xmlSafe(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Fetch one or more sports and output RSS XML, then exit.
 * @param string|array $sports  e.g. 'nhl' or ['nhl','nba'] or 'all'
 */
function outputRSS($sports = 'all'): void {
    if ($sports === 'all') {
        // Keep "all" as the core leagues only to avoid duplicate NCAAF output.
        $selected = getSupportedSportMap(false);
    } elseif (is_array($sports)) {
        $supported = getSupportedSportMap(true);
        $selected = array_intersect_key($supported, array_flip($sports));
    } else {
        $supported = getSupportedSportMap(true);
        $selected = isset($supported[$sports])
            ? [$sports => $supported[$sports]]
            : [];
    }

    $allItems = [];
    foreach ($selected as $ep) {
        $items = fetchScoreboard($ep['url'], $ep['label']);
        $items = applyFeedFilter($items, $ep['filter'] ?? null);
        $allItems = array_merge($allItems, $items);
    }

    $labels = array_column($selected, 'label');
    $feedTitle = 'ESPN Scores — ' . implode(', ', $labels);

    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo renderRSS($allItems, $feedTitle);
    exit;
}
