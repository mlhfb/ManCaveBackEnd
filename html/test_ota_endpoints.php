<?php

function fail(string $message): void
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

function stopServer($process, array $pipes): void
{
    foreach ([0, 1, 2] as $index) {
        if (is_resource($pipes[$index] ?? null)) {
            fclose($pipes[$index]);
        }
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
            'timeout' => 15,
        ],
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        $body = '';
    }

    $headers = $http_response_header ?? [];
    $status = 0;
    foreach ($headers as $line) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $line, $m) === 1) {
            $status = (int)$m[1];
        }
    }
    return ['status' => $status, 'body' => $body];
}

$docRoot = realpath(__DIR__);
if ($docRoot === false) {
    fail('Could not resolve html directory');
}

$tmpRegistry = tempnam(sys_get_temp_dir(), 'ota_registry_');
if ($tmpRegistry === false) {
    fail('Could not create temp registry file');
}

$registry = [
    'channels' => [
        'stable' => [
            'latest' => '0.2.0',
            'releases' => [
                '0.2.0' => [
                    'version' => '0.2.0',
                    'build_date' => '2026-03-05',
                    'notes' => 'OTA bootstrap release',
                    'min_loader_version' => '0.0.0',
                    'firmware' => [
                        'path' => 'firmware/mancavescroller-0.2.0.bin',
                        'size' => 1234567,
                        'sha256' => str_repeat('a', 64),
                    ],
                    'created_utc' => gmdate(DATE_ATOM),
                ],
            ],
        ],
    ],
];

if (file_put_contents($tmpRegistry, json_encode($registry, JSON_PRETTY_PRINT) . "\n") === false) {
    @unlink($tmpRegistry);
    fail('Could not write temp registry');
}

$port = 8097;
$command = [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $docRoot];
$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$pipes = [];
$env = array_merge($_ENV, ['OTA_REGISTRY_PATH' => $tmpRegistry]);
$process = @proc_open($command, $descriptorSpec, $pipes, null, $env);

if (!is_resource($process)) {
    @unlink($tmpRegistry);
    fail('Could not start local PHP server');
}

register_shutdown_function(function () use ($process, $pipes, $tmpRegistry): void {
    stopServer($process, $pipes);
    @unlink($tmpRegistry);
});

$baseUrl = "http://127.0.0.1:{$port}";
$ready = false;
for ($i = 0; $i < 40; $i++) {
    $probe = @file_get_contents("{$baseUrl}/ota_releases.php");
    if ($probe !== false) {
        $ready = true;
        break;
    }
    usleep(125000);
}
if (!$ready) {
    fail('Local PHP server did not become ready');
}

$manifestResp = httpGet("{$baseUrl}/ota_manifest.php?channel=stable&current_version=0.1.0");
if ($manifestResp['status'] !== 200) {
    fail("ota_manifest.php returned HTTP {$manifestResp['status']}");
}
$manifest = json_decode($manifestResp['body'], true);
if (!is_array($manifest)) {
    fail('Manifest response is not valid JSON');
}
foreach (['version', 'build_date', 'firmware', 'min_loader_version', 'notes'] as $key) {
    if (!array_key_exists($key, $manifest)) {
        fail("Manifest missing key '{$key}'");
    }
}
if (($manifest['version'] ?? '') !== '0.2.0') {
    fail('Manifest version mismatch');
}
if (($manifest['update_available'] ?? false) !== true) {
    fail('Expected update_available=true for current_version=0.1.0');
}

$firmware = $manifest['firmware'] ?? null;
if (!is_array($firmware)) {
    fail('Manifest firmware block missing');
}
foreach (['url', 'size', 'sha256'] as $key) {
    if (!array_key_exists($key, $firmware)) {
        fail("Manifest firmware missing key '{$key}'");
    }
}
if (str_starts_with((string)$firmware['url'], 'http://127.0.0.1:8097/firmware/') !== true) {
    fail('Manifest firmware URL was not expanded to an absolute server URL');
}

$releasesResp = httpGet("{$baseUrl}/ota_releases.php?channel=stable");
if ($releasesResp['status'] !== 200) {
    fail("ota_releases.php returned HTTP {$releasesResp['status']}");
}
$releases = json_decode($releasesResp['body'], true);
if (!is_array($releases)) {
    fail('Releases response is not valid JSON');
}
if (($releases['latest'] ?? '') !== '0.2.0') {
    fail('Latest release mismatch');
}
if ((int)($releases['count'] ?? 0) < 1) {
    fail('Expected at least one release');
}

$missingResp = httpGet("{$baseUrl}/ota_manifest.php?channel=beta");
if ($missingResp['status'] !== 404) {
    fail("Expected 404 for missing OTA channel, got {$missingResp['status']}");
}

echo "OTA endpoint checks passed\n";
exit(0);
