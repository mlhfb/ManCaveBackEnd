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

        $title = "$leagueLabel: $awayName @ $homeName";
        if ($state !== 'pre') {
            $title .= "  $awayScore - $homeScore";
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
    global $sportEndpoints;

    if ($sports === 'all') {
        $selected = $sportEndpoints;
    } elseif (is_array($sports)) {
        $selected = array_intersect_key($sportEndpoints, array_flip($sports));
    } else {
        $selected = isset($sportEndpoints[$sports])
            ? [$sports => $sportEndpoints[$sports]]
            : [];
    }

    $allItems = [];
    foreach ($selected as $ep) {
        $allItems = array_merge($allItems, fetchScoreboard($ep['url'], $ep['label']));
    }

    $labels = array_column($selected, 'label');
    $feedTitle = 'ESPN Scores — ' . implode(', ', $labels);

    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo renderRSS($allItems, $feedTitle);
    exit;
}
