<?php
require_once __DIR__ . '/ota_common.php';

$channel = otaNormalizeChannel((string)($_GET['channel'] ?? otaDefaultChannel()));
$registryPath = getenv('OTA_REGISTRY_PATH') ?: otaDefaultRegistryPath();
$registry = otaLoadRegistry($registryPath);

$channelData = $registry['channels'][$channel] ?? null;
if (!is_array($channelData)) {
    otaJsonResponse([
        'error' => 'Channel not found',
        'channel' => $channel,
    ], 404);
    exit;
}

$releases = $channelData['releases'] ?? [];
if (!is_array($releases)) {
    $releases = [];
}

$list = [];
foreach ($releases as $version => $release) {
    if (!is_array($release)) {
        continue;
    }
    $firmware = $release['firmware'] ?? [];
    $list[] = [
        'version' => (string)($release['version'] ?? $version),
        'build_date' => (string)($release['build_date'] ?? ''),
        'notes' => (string)($release['notes'] ?? ''),
        'min_loader_version' => (string)($release['min_loader_version'] ?? '0.0.0'),
        'firmware_path' => (string)($firmware['path'] ?? ''),
        'firmware_size' => (int)($firmware['size'] ?? 0),
        'created_utc' => (string)($release['created_utc'] ?? ''),
    ];
}

usort($list, function (array $a, array $b): int {
    return otaCompareVersions((string)$b['version'], (string)$a['version']);
});

otaJsonResponse([
    'channel' => $channel,
    'latest' => (string)($channelData['latest'] ?? ''),
    'count' => count($list),
    'releases' => $list,
], 200);
