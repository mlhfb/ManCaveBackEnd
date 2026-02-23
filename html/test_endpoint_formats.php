<?php
// Regression checks for machine-format output on the dispatcher endpoint.

function fail(string $message): void
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

function stopServer($process, array $pipes): void
{
    if (is_resource($pipes[0] ?? null)) {
        fclose($pipes[0]);
    }
    if (is_resource($pipes[1] ?? null)) {
        fclose($pipes[1]);
    }
    if (is_resource($pipes[2] ?? null)) {
        fclose($pipes[2]);
    }
    if (is_resource($process)) {
        @proc_terminate($process);
        @proc_close($process);
    }
}

function httpGet(string $url): array
{
    $ctx = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'timeout' => 30,
        ],
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        $body = '';
    }

    $headers = $http_response_header ?? [];
    $status = 0;
    $contentType = '';

    foreach ($headers as $headerLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $headerLine, $m)) {
            $status = (int)$m[1];
        }
        if (stripos($headerLine, 'Content-Type:') === 0) {
            $contentType = trim(substr($headerLine, strlen('Content-Type:')));
        }
    }

    return [
        'status' => $status,
        'contentType' => $contentType,
        'body' => $body,
    ];
}

$docRoot = realpath(__DIR__);
if ($docRoot === false) {
    fail('Unable to resolve html/ path');
}

$port = 8098;
$command = [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $docRoot];
$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$pipes = [];
$process = @proc_open($command, $descriptorSpec, $pipes);

if (!is_resource($process)) {
    fail('Could not start local PHP server');
}

register_shutdown_function(function () use ($process, $pipes): void {
    stopServer($process, $pipes);
});

$baseUrl = "http://127.0.0.1:{$port}";
$ready = false;
for ($i = 0; $i < 40; $i++) {
    $probe = @file_get_contents("{$baseUrl}/espn_scores_rss.php");
    if ($probe !== false) {
        $ready = true;
        break;
    }
    usleep(125000);
}
if (!$ready) {
    fail('Local PHP server did not become ready');
}

$rss = httpGet("{$baseUrl}/espn_scores_rss.php?sport=nfl&format=rss");
if ($rss['status'] !== 200) {
    fail("RSS request returned HTTP {$rss['status']}");
}
if (stripos($rss['contentType'], 'xml') === false) {
    fail("RSS response content-type is not XML ({$rss['contentType']})");
}
$rssBody = ltrim($rss['body']);
if (!str_starts_with($rssBody, '<?xml')) {
    fail('RSS body does not start with XML declaration');
}
if (strpos($rssBody, '<rss version="2.0">') === false || strpos($rssBody, '<channel>') === false) {
    fail('RSS body does not contain expected rss/channel tags');
}

$json = httpGet("{$baseUrl}/espn_scores_rss.php?sport=nfl&format=json");
if ($json['status'] !== 200) {
    fail("JSON request returned HTTP {$json['status']}");
}
if (stripos($json['contentType'], 'application/json') === false) {
    fail("JSON response content-type is not application/json ({$json['contentType']})");
}
$jsonBody = ltrim($json['body']);
if (!str_starts_with($jsonBody, '{')) {
    fail('JSON body does not start with a JSON object');
}
$payload = json_decode($jsonBody, true);
if (!is_array($payload)) {
    fail('JSON body is not valid JSON object');
}
foreach (['feedTitle', 'sport', 'generatedAt', 'itemCount', 'items'] as $requiredKey) {
    if (!array_key_exists($requiredKey, $payload)) {
        fail("JSON body missing key '{$requiredKey}'");
    }
}
if (!is_array($payload['items'])) {
    fail("JSON 'items' key is not an array");
}
if (!is_int($payload['itemCount'])) {
    fail("JSON 'itemCount' key is not an integer");
}

echo "RSS shape check passed\n";
echo "JSON shape check passed\n";
echo "Endpoint format checks passed\n";

exit(0);
