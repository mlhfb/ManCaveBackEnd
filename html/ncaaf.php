<?php
require_once __DIR__ . '/espn_scores_common.php';

// Optional debug mode: append ?debug=1 to the URL to show errors inline.
$debug = isset($_GET['debug']) && ($_GET['debug'] == '1' || $_GET['debug'] === 'true');
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

try {
    // Keep legacy ncaaf.php behavior: output the filtered team-list feed.
    outputRSS('big10');
} catch (Throwable $e) {
    error_log('ncaaf.php error: ' . $e->getMessage());
    if ($debug) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Error: " . $e->getMessage() . "\n\n" . $e->getTraceAsString();
    } else {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Internal Server Error';
    }
    exit;
}
