<?php
require_once __DIR__ . '/../html/ota_common.php';

function usage(): void {
    $script = basename(__FILE__);
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php tools/{$script} --version 0.2.0 --file C:\\path\\firmware.bin [--channel stable]\n");
    fwrite(STDERR, "       [--notes \"Bug fixes\"] [--build-date 2026-03-05] [--min-loader-version 0.0.0]\n");
}

$opts = getopt('', [
    'version:',
    'file:',
    'channel::',
    'notes::',
    'build-date::',
    'min-loader-version::',
    'registry-path::',
    'firmware-name::',
    'set-latest::',
]);

$version = otaNormalizeVersion((string)($opts['version'] ?? ''));
$sourceFile = trim((string)($opts['file'] ?? ''));
$channel = otaNormalizeChannel((string)($opts['channel'] ?? otaDefaultChannel()));
$notes = (string)($opts['notes'] ?? '');
$buildDate = trim((string)($opts['build-date'] ?? ''));
$minLoaderVersion = otaNormalizeVersion((string)($opts['min-loader-version'] ?? '0.0.0'));
$registryPath = (string)($opts['registry-path'] ?? otaDefaultRegistryPath());
$firmwareName = trim((string)($opts['firmware-name'] ?? ''));
$setLatestInput = strtolower(trim((string)($opts['set-latest'] ?? 'true')));
$setLatest = !in_array($setLatestInput, ['0', 'false', 'no'], true);

if ($version === '' || $sourceFile === '') {
    usage();
    exit(2);
}
if (!otaIsLikelyVersion($version)) {
    fwrite(STDERR, "Invalid --version. Expected something like 0.2.0\n");
    exit(2);
}
if (!is_readable($sourceFile)) {
    fwrite(STDERR, "Firmware file not found/readable: {$sourceFile}\n");
    exit(2);
}
if ($buildDate === '') {
    $buildDate = gmdate('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $buildDate)) {
    fwrite(STDERR, "Invalid --build-date. Expected YYYY-MM-DD\n");
    exit(2);
}
if (!otaIsLikelyVersion($minLoaderVersion)) {
    fwrite(STDERR, "Invalid --min-loader-version. Expected semantic version format.\n");
    exit(2);
}

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Could not resolve repo root.\n");
    exit(2);
}

$firmwareDir = $repoRoot . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'firmware';
if (!is_dir($firmwareDir) && !mkdir($firmwareDir, 0775, true) && !is_dir($firmwareDir)) {
    fwrite(STDERR, "Could not create firmware directory: {$firmwareDir}\n");
    exit(2);
}

if ($firmwareName === '') {
    $firmwareName = "mancavescroller-{$version}.bin";
}
if (preg_match('/^[A-Za-z0-9._-]+\.bin$/', $firmwareName) !== 1) {
    fwrite(STDERR, "Invalid --firmware-name. Use letters/numbers/._- and end with .bin\n");
    exit(2);
}

$destination = $firmwareDir . DIRECTORY_SEPARATOR . $firmwareName;
if (!copy($sourceFile, $destination)) {
    fwrite(STDERR, "Failed to copy firmware to {$destination}\n");
    exit(1);
}

$size = filesize($destination);
$sha256 = hash_file('sha256', $destination);
if ($size === false || $size <= 0 || $sha256 === false) {
    fwrite(STDERR, "Failed to read firmware metadata after copy.\n");
    exit(1);
}

$relativePath = 'firmware/' . $firmwareName;
$registry = otaLoadRegistry($registryPath);
if (!isset($registry['channels']) || !is_array($registry['channels'])) {
    $registry['channels'] = [];
}
if (!isset($registry['channels'][$channel]) || !is_array($registry['channels'][$channel])) {
    $registry['channels'][$channel] = ['latest' => '', 'releases' => []];
}
if (!isset($registry['channels'][$channel]['releases']) || !is_array($registry['channels'][$channel]['releases'])) {
    $registry['channels'][$channel]['releases'] = [];
}

$registry['channels'][$channel]['releases'][$version] = [
    'version' => $version,
    'build_date' => $buildDate,
    'notes' => $notes,
    'min_loader_version' => $minLoaderVersion,
    'firmware' => [
        'path' => $relativePath,
        'size' => (int)$size,
        'sha256' => strtolower($sha256),
    ],
    'created_utc' => gmdate(DATE_ATOM),
];

if ($setLatest) {
    $registry['channels'][$channel]['latest'] = $version;
}

if (!otaSaveRegistry($registry, $registryPath)) {
    fwrite(STDERR, "Failed to write registry file: {$registryPath}\n");
    exit(1);
}

fwrite(STDOUT, "OTA release published.\n");
fwrite(STDOUT, "Channel: {$channel}\n");
fwrite(STDOUT, "Version: {$version}\n");
fwrite(STDOUT, "Firmware: {$relativePath}\n");
fwrite(STDOUT, "Size: {$size}\n");
fwrite(STDOUT, "SHA256: {$sha256}\n");
fwrite(STDOUT, "Registry: {$registryPath}\n");
