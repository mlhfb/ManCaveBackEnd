<?php
require_once __DIR__ . '/ota_common.php';

$channel = otaNormalizeChannel((string)($_GET['channel'] ?? otaDefaultChannel()));
$version = otaNormalizeVersion((string)($_GET['version'] ?? ''));
$currentVersion = otaNormalizeVersion((string)($_GET['current_version'] ?? ''));

$registryPath = getenv('OTA_REGISTRY_PATH') ?: otaDefaultRegistryPath();
$registry = otaLoadRegistry($registryPath);
$release = otaGetRelease($registry, $channel, $version !== '' ? $version : null);
if ($release === null) {
    otaJsonResponse([
        'error' => 'No OTA release found',
        'channel' => $channel,
    ], 404);
    exit;
}

$manifest = otaBuildManifestPayload($release, $channel, $_SERVER);
if ($manifest === null) {
    otaJsonResponse([
        'error' => 'OTA release metadata is invalid',
        'channel' => $channel,
    ], 500);
    exit;
}

if ($currentVersion !== '') {
    $manifest['current_version'] = $currentVersion;
    $manifest['update_available'] = otaCompareVersions(
        (string)$manifest['version'],
        $currentVersion
    ) > 0;
}

otaJsonResponse($manifest, 200);
