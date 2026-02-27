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

// Fallback colors only for teams where ESPN color data may be missing.
// ESPN team.color/team.alternateColor is preferred whenever present.
$fallbackTeamColors = [
    // Big10 feed team IDs from ncaateams.list
    '356'  => '#FF5F05', // Illinois
    '84'   => '#970310', // Indiana
    '2294' => '#231F20', // Iowa
    '120'  => '#CE1126', // Maryland
    '127'  => '#173F35', // Michigan State
    '130'  => '#00274C', // Michigan
    '135'  => '#5E0A2F', // Minnesota
    '158'  => '#E31937', // Nebraska
    '77'   => '#492F92', // Northwestern
    '194'  => '#BA0C2F', // Ohio State
    '213'  => '#061440', // Penn State
    '2509' => '#CEB888', // Purdue
    '164'  => '#CE0E2D', // Rutgers
    '275'  => '#A00000', // Wisconsin
    // Additional IDs currently enabled in this list
    '2117' => '#4C0027', // Central Michigan
    '2711' => '#532E1F', // Western Michigan
];

// LED-optimized team color overrides.
// Keys: '<leagueLabel>:<espnTeamId>' — leagueLabel matches the string passed to fetchScoreboard().
// Colors are chosen for readability as TEXT on a black 8×128 addressable RGB LED panel.
// Dark primaries (navy, maroon, forest green, near-black) are replaced with bright accents.
$ledTeamColors = [
    // ── NCAA Football (Big10 + active non-conference teams) ───────────────
    // IDs sourced from ncaateams.list
    'NCAA Football:356'  => '#FF5F05', // Illinois — orange
    'NCAA Football:84'   => '#E31837', // Indiana — bright crimson (was #970310 too dark)
    'NCAA Football:2294' => '#FFCD00', // Iowa — Hawkeye gold (was #231F20 near-black!)
    'NCAA Football:120'  => '#E03A3E', // Maryland — bright red
    'NCAA Football:127'  => '#18A558', // Michigan State — bright green (was #173F35 too dark)
    'NCAA Football:130'  => '#FFCB05', // Michigan — maize gold (was #00274C navy unreadable)
    'NCAA Football:135'  => '#FFD700', // Minnesota — gold accent (was #5E0A2F maroon too dark)
    'NCAA Football:158'  => '#FF2244', // Nebraska — bright scarlet
    'NCAA Football:77'   => '#836ECC', // Northwestern — medium purple (was #492F92 too dark)
    'NCAA Football:194'  => '#E51837', // Ohio State — bright scarlet
    'NCAA Football:213'  => '#0099FF', // Penn State — bright blue (was #061440 near-black!)
    'NCAA Football:2509' => '#FFD700', // Purdue — bright gold (was #CEB888 muted sand)
    'NCAA Football:164'  => '#FF3333', // Rutgers — bright scarlet
    'NCAA Football:275'  => '#E00034', // Wisconsin — bright cardinal red (was #A00000 too dark)
    'NCAA Football:2117' => '#D4A854', // Central Michigan — gold (was #4C0027 near-black)
    'NCAA Football:2711' => '#D4A854', // Western Michigan — gold (was #532E1F too dark)
    // New Big10 members (IDs from ncaateams.list; activate in list to use)
    'NCAA Football:2483' => '#00CC66', // Oregon — bright green (was #154733 too dark)
    'NCAA Football:26'   => '#FFB300', // UCLA — bright gold (navy too dark)
    'NCAA Football:30'   => '#CC2200', // USC — bright cardinal red (was #990000 too dark)
    'NCAA Football:264'  => '#9933FF', // Washington — bright purple (was #4B2E83 too dark)

    // ── NFL ──────────────────────────────────────────────────────────────
    'NFL:22' => '#E00040', // Arizona Cardinals — bright cardinal red
    'NFL:1'  => '#E81B23', // Atlanta Falcons — bright red (navy too dark)
    'NFL:33' => '#9155E3', // Baltimore Ravens — bright purple (dark purple too dark)
    'NFL:2'  => '#0080FF', // Buffalo Bills — bright blue (navy too dark)
    'NFL:29' => '#00A0DD', // Carolina Panthers — process blue
    'NFL:3'  => '#E85C2C', // Chicago Bears — bright orange (navy too dark)
    'NFL:4'  => '#FF6600', // Cincinnati Bengals — bright orange
    'NFL:5'  => '#FF6000', // Cleveland Browns — bright orange
    'NFL:6'  => '#A8C8FF', // Dallas Cowboys — silver-blue accent (navy too dark)
    'NFL:7'  => '#FF6600', // Denver Broncos — bright orange
    'NFL:8'  => '#0099FF', // Detroit Lions — bright blue
    'NFL:9'  => '#FFB612', // Green Bay Packers — gold
    'NFL:34' => '#E00030', // Houston Texans — battle red
    'NFL:11' => '#0055CC', // Indianapolis Colts — bright blue (navy too dark)
    'NFL:30' => '#00C0D8', // Jacksonville Jaguars — bright teal
    'NFL:12' => '#E31837', // Kansas City Chiefs — bright red
    'NFL:13' => '#C0C0C0', // Las Vegas Raiders — silver (black too dark)
    'NFL:24' => '#0099FF', // Los Angeles Chargers — bright powder blue
    'NFL:14' => '#FFA300', // Los Angeles Rams — bright gold
    'NFL:15' => '#00C0CC', // Miami Dolphins — bright teal
    'NFL:16' => '#9933FF', // Minnesota Vikings — bright purple
    'NFL:17' => '#0055FF', // New England Patriots — bright blue (navy too dark)
    'NFL:18' => '#D4AF37', // New Orleans Saints — gold
    'NFL:19' => '#0055FF', // New York Giants — bright blue
    'NFL:20' => '#00CC66', // New York Jets — bright green
    'NFL:21' => '#0099AA', // Philadelphia Eagles — bright midnight green
    'NFL:23' => '#FFB612', // Pittsburgh Steelers — gold
    'NFL:25' => '#E00000', // San Francisco 49ers — bright scarlet
    'NFL:26' => '#69BE28', // Seattle Seahawks — action green
    'NFL:27' => '#E00000', // Tampa Bay Buccaneers — bright red
    'NFL:10' => '#0099FF', // Tennessee Titans — bright blue
    'NFL:28' => '#FFB612', // Washington Commanders — gold

    // ── NBA ──────────────────────────────────────────────────────────────
    'NBA:1'  => '#FF5544', // Atlanta Hawks — bright red
    'NBA:2'  => '#00CC66', // Boston Celtics — bright green
    'NBA:17' => '#FFFFFF', // Brooklyn Nets — white (black too dark)
    'NBA:30' => '#00C8FF', // Charlotte Hornets — bright cyan
    'NBA:4'  => '#FF3333', // Chicago Bulls — bright red
    'NBA:5'  => '#D4AF37', // Cleveland Cavaliers — gold accent
    'NBA:6'  => '#0077FF', // Dallas Mavericks — bright blue
    'NBA:7'  => '#FFD700', // Denver Nuggets — gold
    'NBA:8'  => '#FF3333', // Detroit Pistons — bright red
    'NBA:9'  => '#FFD700', // Golden State Warriors — gold
    'NBA:10' => '#FF3333', // Houston Rockets — bright red
    'NBA:11' => '#FFD700', // Indiana Pacers — gold accent
    'NBA:12' => '#FF3333', // LA Clippers — bright red
    'NBA:13' => '#9933FF', // Los Angeles Lakers — bright purple
    'NBA:29' => '#6699FF', // Memphis Grizzlies — bright steel blue
    'NBA:14' => '#FF3333', // Miami Heat — bright red
    'NBA:15' => '#00CC66', // Milwaukee Bucks — bright green
    'NBA:16' => '#0099FF', // Minnesota Timberwolves — bright blue
    'NBA:3'  => '#0099FF', // New Orleans Pelicans — bright blue
    'NBA:18' => '#0099FF', // New York Knicks — bright blue
    'NBA:25' => '#FF6600', // Oklahoma City Thunder — orange accent
    'NBA:19' => '#0099FF', // Orlando Magic — bright blue
    'NBA:20' => '#0099FF', // Philadelphia 76ers — bright blue
    'NBA:21' => '#FF6600', // Phoenix Suns — bright orange
    'NBA:22' => '#FF3333', // Portland Trail Blazers — bright red
    'NBA:23' => '#9933FF', // Sacramento Kings — bright purple
    'NBA:24' => '#C0C0C0', // San Antonio Spurs — silver
    'NBA:28' => '#FF3333', // Toronto Raptors — bright red
    'NBA:26' => '#0099FF', // Utah Jazz — bright blue
    'NBA:27' => '#FF3333', // Washington Wizards — bright red

    // ── MLB ──────────────────────────────────────────────────────────────
    'MLB:29' => '#E81B23', // Arizona Diamondbacks — bright red (purple too dark)
    'MLB:11' => '#00CC66', // Athletics — bright green (dark green too dark)
    'MLB:15' => '#FF3333', // Atlanta Braves — bright red
    'MLB:1'  => '#FF6600', // Baltimore Orioles — bright orange
    'MLB:2'  => '#FF3333', // Boston Red Sox — bright red
    'MLB:16' => '#0099FF', // Chicago Cubs — bright blue (navy too dark)
    'MLB:4'  => '#C0C0C0', // Chicago White Sox — silver (black too dark)
    'MLB:17' => '#FF3333', // Cincinnati Reds — bright red
    'MLB:5'  => '#FF3333', // Cleveland Guardians — bright red
    'MLB:27' => '#9933FF', // Colorado Rockies — bright purple
    'MLB:6'  => '#0099FF', // Detroit Tigers — bright blue (navy too dark)
    'MLB:18' => '#FF8833', // Houston Astros — bright orange
    'MLB:7'  => '#0099FF', // Kansas City Royals — bright blue (navy too dark)
    'MLB:3'  => '#FF3333', // Los Angeles Angels — bright red
    'MLB:19' => '#0099FF', // Los Angeles Dodgers — bright blue (navy too dark)
    'MLB:28' => '#0099FF', // Miami Marlins — bright blue
    'MLB:8'  => '#FFD700', // Milwaukee Brewers — gold accent
    'MLB:9'  => '#0099FF', // Minnesota Twins — bright blue (navy too dark)
    'MLB:21' => '#0099FF', // New York Mets — bright blue (navy too dark)
    'MLB:10' => '#0099FF', // New York Yankees — bright blue (navy too dark)
    'MLB:22' => '#FF3333', // Philadelphia Phillies — bright red
    'MLB:23' => '#FFD700', // Pittsburgh Pirates — gold (black too dark)
    'MLB:25' => '#FFD700', // San Diego Padres — gold/sand accent
    'MLB:26' => '#FF6600', // San Francisco Giants — bright orange
    'MLB:12' => '#0099FF', // Seattle Mariners — bright blue
    'MLB:24' => '#FF3333', // St. Louis Cardinals — bright red
    'MLB:30' => '#0099FF', // Tampa Bay Rays — bright blue (navy too dark)
    'MLB:13' => '#0099FF', // Texas Rangers — bright blue (navy too dark)
    'MLB:14' => '#0099FF', // Toronto Blue Jays — bright blue
    'MLB:20' => '#FF3333', // Washington Nationals — bright red

    // ── NHL ──────────────────────────────────────────────────────────────
    'NHL:25'     => '#FF9933',  // Anaheim Ducks — bright orange
    'NHL:1'      => '#FFD700',  // Boston Bruins — gold
    'NHL:2'      => '#0099FF',  // Buffalo Sabres — bright blue (navy too dark)
    'NHL:3'      => '#FF3333',  // Calgary Flames — bright red
    'NHL:7'      => '#FF3333',  // Carolina Hurricanes — bright red
    'NHL:4'      => '#FF3333',  // Chicago Blackhawks — bright red
    'NHL:17'     => '#0099FF',  // Colorado Avalanche — bright blue
    'NHL:29'     => '#0099FF',  // Columbus Blue Jackets — bright blue (navy too dark)
    'NHL:9'      => '#00CC66',  // Dallas Stars — bright green (dark green too dark)
    'NHL:5'      => '#FF3333',  // Detroit Red Wings — bright red
    'NHL:6'      => '#FF6600',  // Edmonton Oilers — bright orange
    'NHL:26'     => '#FF3333',  // Florida Panthers — bright red
    'NHL:8'      => '#C0C0C0',  // Los Angeles Kings — silver
    'NHL:30'     => '#00CC66',  // Minnesota Wild — bright green (dark green too dark)
    'NHL:10'     => '#FF3333',  // Montreal Canadiens — bright red
    'NHL:27'     => '#FFD700',  // Nashville Predators — gold
    'NHL:11'     => '#FF3333',  // New Jersey Devils — bright red
    'NHL:12'     => '#0099FF',  // New York Islanders — bright blue
    'NHL:13'     => '#0099FF',  // New York Rangers — bright blue
    'NHL:14'     => '#FF3333',  // Ottawa Senators — bright red
    'NHL:15'     => '#FF6600',  // Philadelphia Flyers — bright orange
    'NHL:16'     => '#FFD700',  // Pittsburgh Penguins — gold
    'NHL:18'     => '#00D0FF',  // San Jose Sharks — bright cyan (teal too dark)
    'NHL:124292' => '#00D0FF',  // Seattle Kraken — ice blue/cyan
    'NHL:19'     => '#0099FF',  // St. Louis Blues — bright blue
    'NHL:20'     => '#0099FF',  // Tampa Bay Lightning — bright blue (navy too dark)
    'NHL:21'     => '#0099FF',  // Toronto Maple Leafs — bright blue (navy too dark)
    'NHL:129764' => '#0099FF',  // Utah Mammoth — bright blue
    'NHL:22'     => '#0099FF',  // Vancouver Canucks — bright blue (navy too dark)
    'NHL:37'     => '#FFD700',  // Vegas Golden Knights — gold
    'NHL:23'     => '#FF3333',  // Washington Capitals — bright red
    'NHL:28'     => '#0099FF',  // Winnipeg Jets — bright blue
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

function normalizeHexColor(?string $value): ?string {
    if ($value === null) return null;
    $trimmed = trim($value);
    if ($trimmed === '') return null;
    if ($trimmed[0] === '#') {
        $trimmed = substr($trimmed, 1);
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $trimmed)) {
        return null;
    }
    return '#' . strtoupper($trimmed);
}

function lookupFallbackTeamColor(?string $teamId): ?string {
    global $fallbackTeamColors;
    if ($teamId === null || $teamId === '') return null;
    return $fallbackTeamColors[(string)$teamId] ?? null;
}

function lookupLedTeamColor(string $leagueLabel, ?string $teamId): ?string {
    global $ledTeamColors;
    if ($teamId === null || $teamId === '') return null;
    return $ledTeamColors[$leagueLabel . ':' . $teamId] ?? null;
}

function getTeamColorBundle(array $team, string $leagueLabel = ''): array {
    $teamId = isset($team['id']) ? (string)$team['id'] : null;

    // LED override takes highest priority — curated bright colors for LED text display.
    $ledColor = lookupLedTeamColor($leagueLabel, $teamId);
    if ($ledColor !== null) {
        $alternate = normalizeHexColor($team['alternateColor'] ?? null) ?? '#000000';
        return ['primary' => $ledColor, 'alternate' => $alternate];
    }

    $primary = normalizeHexColor($team['color'] ?? null);
    $alternate = normalizeHexColor($team['alternateColor'] ?? null);

    if ($primary === null) {
        $primary = lookupFallbackTeamColor($teamId);
    }
    if ($primary === null && $alternate !== null) {
        $primary = $alternate;
    }
    if ($primary === null) {
        $primary = '#FFFFFF';
    }
    if ($alternate === null) {
        $alternate = ($primary === '#FFFFFF') ? '#000000' : '#FFFFFF';
    }

    return [
        'primary' => $primary,
        'alternate' => $alternate,
    ];
}

function parseScoreOrNull($score): ?int {
    if ($score === null) return null;
    if (is_int($score)) return $score;
    if (!is_string($score)) return null;
    $trimmed = trim($score);
    if ($trimmed === '' || !preg_match('/^-?\d+$/', $trimmed)) return null;
    return (int)$trimmed;
}

function scorePresentation(?int $homeScore, ?int $awayScore): array {
    $neutral = '#FFFFFF';
    $leading = '#00FF00';
    $trailing = '#FF0000';
    $tie = '#FFD700';

    if ($homeScore === null || $awayScore === null) {
        return [
            'leader' => 'unknown',
            'homeScoreColor' => $neutral,
            'awayScoreColor' => $neutral,
        ];
    }

    if ($homeScore > $awayScore) {
        return [
            'leader' => 'home',
            'homeScoreColor' => $leading,
            'awayScoreColor' => $trailing,
        ];
    }
    if ($awayScore > $homeScore) {
        return [
            'leader' => 'away',
            'homeScoreColor' => $trailing,
            'awayScoreColor' => $leading,
        ];
    }

    return [
        'leader' => 'tie',
        'homeScoreColor' => $tie,
        'awayScoreColor' => $tie,
    ];
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

function ordinalizeNumber(int $n): string {
    $suffixes = [1 => 'st', 2 => 'nd', 3 => 'rd'];
    if ($n % 100 >= 11 && $n % 100 <= 13) {
        return $n . 'th';
    }
    $suffix = isset($suffixes[$n % 10]) ? $suffixes[$n % 10] : 'th';
    return $n . $suffix;
}

function normalizeBaseballInProgressDetail(string $source): ?string {
    $s = trim($source);

    if (preg_match('/^(top|bot|bottom|mid|end)\s+(\d{1,2})(?:st|nd|rd|th)?\b/i', $s, $m)) {
        $half = strtolower($m[1]);
        $inning = ordinalizeNumber((int)$m[2]);
        if ($half === 'bot') {
            $half = 'bottom';
        } elseif ($half === 'mid') {
            $half = 'middle';
        } elseif ($half === 'end') {
            $half = 'end';
        }
        return  $half . ' of the ' . $inning . '.';
    }

    if (preg_match('/^([tb])(\d{1,2})$/i', $s, $m)) {
        $half = strtolower($m[1]) === 't' ? 'top' : 'bottom';
        $inning = ordinalizeNumber((int)$m[2]);
        return   $half . ' of the ' . $inning . '.';
    }

    return null;
}

function normalizeClockPeriodDetail(string $source, string $leagueLabel): ?string {
    $s = trim($source);
    $clockPattern = '/^\d{1,2}:\d{2}$/';
    $clock = null;
    $periodRaw = null;

    $parts = preg_split('/\s*-\s*/', $s);
    if (is_array($parts) && count($parts) === 2) {
        $a = trim((string)$parts[0]);
        $b = trim((string)$parts[1]);

        if (preg_match($clockPattern, $a)) {
            $clock = $a;
            $periodRaw = $b;
        } elseif (preg_match($clockPattern, $b)) {
            $clock = $b;
            $periodRaw = $a;
        }
    }

    if ($clock === null || $periodRaw === null) {
        // Fallback for forms like "3rd 5:34" or "5:34 3rd".
        if (preg_match('/^(.+)\s+(\d{1,2}:\d{2})$/', $s, $m)) {
            $periodRaw = trim($m[1]);
            $clock = $m[2];
        } elseif (preg_match('/^(\d{1,2}:\d{2})\s+(.+)$/', $s, $m)) {
            $clock = $m[1];
            $periodRaw = trim($m[2]);
        }
    }

    if ($clock === null || $periodRaw === null || !preg_match($clockPattern, $clock)) {
        return null;
    }

    $period = preg_replace('/\b(qtr|quarter|period)\b/i', '', $periodRaw);
    $period = trim((string)$period);
    if ($period === '') {
        return null;
    }

    $periodLower = strtolower($period);
    $isOrdinal = preg_match('/^\d+(?:st|nd|rd|th)$/', $periodLower) === 1;
    $isOt = preg_match('/^\d*ot$/i', $period) === 1;

    if ($leagueLabel === 'NHL') {
        if ($isOrdinal) {
            return $clock . ' to go in the ' . $periodLower . ' period.';
        }
        if ($isOt) {
            return $clock . ' to go in ' . strtoupper($period) . '.';
        }
    }

    if ($leagueLabel === 'NFL' || $leagueLabel === 'NCAA Football' || $leagueLabel === 'NBA') {
        if ($isOrdinal) {
            return $clock . ' left in the ' . $periodLower . ' quarter.';
        }
        if ($isOt) {
            return $clock . ' left in ' . strtoupper($period) . '.';
        }
        return $clock . ' left in the ' . $periodLower . '.';
    }

    if ($isOrdinal) {
        return $clock . ' left in the ' . $periodLower . '.';
    }
    if ($isOt) {
        return $clock . ' left in ' . strtoupper($period) . '.';
    }

    return $clock . ' left in the ' . $periodLower . '.';
}

function normalizeInProgressDetail(string $leagueLabel, string $shortDetail, string $detail): string {
    $source = trim($shortDetail !== '' ? $shortDetail : $detail);
    if ($source === '') {
        return $detail;
    }

    if ($leagueLabel === 'MLB') {
        $baseball = normalizeBaseballInProgressDetail($source);
        if ($baseball !== null) {
            return $baseball;
        }
    }

    $clockPeriod = normalizeClockPeriodDetail($source, $leagueLabel);
    if ($clockPeriod !== null) {
        return $clockPeriod;
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

        $homeTeam = $home['team'] ?? [];
        $awayTeam = $away['team'] ?? [];

        $homeName  = $homeTeam['displayName'] ?? '';
        $awayName  = $awayTeam['displayName'] ?? '';
        $homeId    = $homeTeam['id'] ?? null;
        $awayId    = $awayTeam['id'] ?? null;
        $homeAbbr  = $homeTeam['abbreviation'] ?? '';
        $awayAbbr  = $awayTeam['abbreviation'] ?? '';
        $homeScore = $home['score'] ?? '0';
        $awayScore = $away['score'] ?? '0';

        $homeColors = getTeamColorBundle($homeTeam, $leagueLabel);
        $awayColors = getTeamColorBundle($awayTeam, $leagueLabel);
        $homeScoreNum = parseScoreOrNull($homeScore);
        $awayScoreNum = parseScoreOrNull($awayScore);
        $scoreStyling = scorePresentation($homeScoreNum, $awayScoreNum);

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
            $detail = normalizeInProgressDetail($leagueLabel, (string)$shortDetail, (string)$detail);
        }

        $link = $event['links'][0]['href']
             ?? 'https://www.espn.com/' . strtolower($leagueLabel);

        $items[] = [
            'title'          => $title,
            'description'    => $detail,
            'link'           => $link,
            'pubDate'        => date(DATE_RSS, strtotime($event['date'] ?? 'now')),
            'league'         => $leagueLabel,
            'state'          => $state,
            'isLive'         => ($state === 'in'),
            'leader'         => $scoreStyling['leader'],
            'homeScoreColor' => $scoreStyling['homeScoreColor'],
            'awayScoreColor' => $scoreStyling['awayScoreColor'],
            // expose team metadata for downstream filtering by ID
            'homeTeamName'   => $homeName,
            'awayTeamName'   => $awayName,
            'homeTeamId'     => $homeId,
            'awayTeamId'     => $awayId,
            'homeTeamAbbrev' => $homeAbbr,
            'awayTeamAbbrev' => $awayAbbr,
            'homeTeamColor'  => $homeColors['primary'],
            'awayTeamColor'  => $awayColors['primary'],
            'homeTeamAltColor' => $homeColors['alternate'],
            'awayTeamAltColor' => $awayColors['alternate'],
            'homeScore'      => $homeScoreNum,
            'awayScore'      => $awayScoreNum,
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
        $baseTitle = str_replace(' @ ', ' at ', (string)($item['title'] ?? ''));
        $detail = (string)($item['description'] ?? '');
        $scrollTitle = rtrim($baseTitle) . '      ' . $detail;

        $xml .= "  <item>\n";
        $xml .= "    <title>"       . xmlSafe($scrollTitle)         . "</title>\n";
        $xml .= "    <link>"        . xmlSafe($item['link'])        . "</link>\n";
        $xml .= "    <category>"    . xmlSafe($item['league'])      . "</category>\n";
        $xml .= "    <pubDate>"     . $item['pubDate']              . "</pubDate>\n";
        $xml .= "  </item>\n";
    }

    $xml .= "</channel>\n</rss>\n";
    return $xml;
}

function renderJSON(array $items, string $sport): string {
    $jsonItems = [];
    foreach ($items as $item) {
        $jsonItems[] = [
            'league' => $item['league'] ?? '',
            'isLive' => (bool)($item['isLive'] ?? false),
            'leader' => $item['leader'] ?? 'unknown',
            'detail' => $item['description'] ?? '',
            'home' => [
                'name' => $item['homeTeamName'] ?? '',
                'score' => $item['homeScore'] ?? null,
                'teamColor' => $item['homeTeamColor'] ?? '#FFFFFF',
                'alternateColor' => $item['homeTeamAltColor'] ?? '#000000',
                'scoreColor' => $item['homeScoreColor'] ?? '#FFFFFF',
            ],
            'away' => [
                'name' => $item['awayTeamName'] ?? '',
                'score' => $item['awayScore'] ?? null,
                'teamColor' => $item['awayTeamColor'] ?? '#FFFFFF',
                'alternateColor' => $item['awayTeamAltColor'] ?? '#000000',
                'scoreColor' => $item['awayScoreColor'] ?? '#FFFFFF',
            ],
        ];
    }

    $payload = [
        'sport' => $sport,
        'items' => $jsonItems,
    ];

    return json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . "\n";
}

function xmlSafe(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Fetch one or more sports and output JSON, then exit.
 * @param string|array $sports  e.g. 'nhl' or ['nhl','nba'] or 'all'
 */
function outputJSON($sports = 'all'): void {
    if ($sports === 'all') {
        // Keep "all" as the core leagues only to avoid duplicate NCAAF output.
        $selected = getSupportedSportMap(false);
        $sportLabel = 'all';
    } elseif (is_array($sports)) {
        $supported = getSupportedSportMap(true);
        $selected = array_intersect_key($supported, array_flip($sports));
        $sportLabel = 'multiple';
    } else {
        $supported = getSupportedSportMap(true);
        $selected = isset($supported[$sports])
            ? [$sports => $supported[$sports]]
            : [];
        $sportLabel = is_string($sports) ? $sports : 'unknown';
    }

    $allItems = [];
    foreach ($selected as $ep) {
        $items = fetchScoreboard($ep['url'], $ep['label']);
        $items = applyFeedFilter($items, $ep['filter'] ?? null);
        $allItems = array_merge($allItems, $items);
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo renderJSON($allItems, $sportLabel);
    exit;
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
    $feedTitle = 'ESPN Scores - ' . implode(', ', $labels);

    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo renderRSS($allItems, $feedTitle);
    exit;
}
