<?php

function otaDefaultRegistryPath(): string {
    return __DIR__ . '/../artifacts/ota/releases.json';
}

function otaDefaultChannel(): string {
    return 'stable';
}

function otaNormalizeChannel(string $channel): string {
    $value = strtolower(trim($channel));
    if ($value === '' || !preg_match('/^[a-z0-9_.-]+$/', $value)) {
        return otaDefaultChannel();
    }
    return $value;
}

function otaNormalizeVersion(string $version): string {
    return trim($version);
}

function otaIsLikelyVersion(string $version): bool {
    return preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', $version) === 1;
}

function otaCompareVersions(string $left, string $right): int {
    $l = preg_replace('/[-+].*$/', '', trim($left));
    $r = preg_replace('/[-+].*$/', '', trim($right));
    return version_compare($l, $r);
}

function otaLoadRegistry(?string $path = null): array {
    $resolved = $path !== null && $path !== '' ? $path : otaDefaultRegistryPath();
    if (!is_readable($resolved)) {
        return ['channels' => []];
    }

    $raw = file_get_contents($resolved);
    if ($raw === false || trim($raw) === '') {
        return ['channels' => []];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['channels' => []];
    }
    if (!isset($decoded['channels']) || !is_array($decoded['channels'])) {
        $decoded['channels'] = [];
    }
    return $decoded;
}

function otaSaveRegistry(array $registry, ?string $path = null): bool {
    $resolved = $path !== null && $path !== '' ? $path : otaDefaultRegistryPath();
    $dir = dirname($resolved);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    $encoded = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }
    $encoded .= "\n";
    return file_put_contents($resolved, $encoded, LOCK_EX) !== false;
}

function otaGetRelease(array $registry, string $channel, ?string $version = null): ?array {
    if (!isset($registry['channels'][$channel]) || !is_array($registry['channels'][$channel])) {
        return null;
    }
    $channelData = $registry['channels'][$channel];
    $releases = $channelData['releases'] ?? [];
    if (!is_array($releases)) {
        return null;
    }

    $targetVersion = $version;
    if ($targetVersion === null || trim($targetVersion) === '') {
        $targetVersion = $channelData['latest'] ?? '';
    }
    if (!is_string($targetVersion) || $targetVersion === '' || !isset($releases[$targetVersion])) {
        return null;
    }

    $release = $releases[$targetVersion];
    if (!is_array($release)) {
        return null;
    }
    return $release;
}

function otaServerBaseUrl(array $server): string {
    if (isset($server['HTTP_X_FORWARDED_PROTO']) && $server['HTTP_X_FORWARDED_PROTO'] !== '') {
        $scheme = strtolower((string)$server['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
    } elseif (isset($server['REQUEST_SCHEME']) && $server['REQUEST_SCHEME'] !== '') {
        $scheme = (string)$server['REQUEST_SCHEME'];
    } else {
        $https = $server['HTTPS'] ?? '';
        $scheme = (!empty($https) && strtolower((string)$https) !== 'off') ? 'https' : 'http';
    }

    $host = '';
    if (isset($server['HTTP_X_FORWARDED_HOST']) && $server['HTTP_X_FORWARDED_HOST'] !== '') {
        $host = (string)$server['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($server['HTTP_HOST']) && $server['HTTP_HOST'] !== '') {
        $host = (string)$server['HTTP_HOST'];
    } elseif (isset($server['SERVER_NAME']) && $server['SERVER_NAME'] !== '') {
        $host = (string)$server['SERVER_NAME'];
        $port = (int)($server['SERVER_PORT'] ?? 0);
        if ($port > 0 && $port !== 80 && $port !== 443) {
            $host .= ':' . $port;
        }
    }

    if ($host === '') {
        $host = 'localhost';
    }

    return $scheme . '://' . $host;
}

function otaToAbsoluteUrl(string $maybeRelativePath, array $server): string {
    $trimmed = trim($maybeRelativePath);
    if ($trimmed === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $trimmed) === 1) {
        return $trimmed;
    }
    $path = '/' . ltrim($trimmed, '/');
    return rtrim(otaServerBaseUrl($server), '/') . $path;
}

function otaBuildManifestPayload(array $release, string $channel, array $server): ?array {
    $version = isset($release['version']) ? trim((string)$release['version']) : '';
    $buildDate = isset($release['build_date']) ? trim((string)$release['build_date']) : '';
    $notes = isset($release['notes']) ? (string)$release['notes'] : '';
    $loaderVersion = isset($release['min_loader_version'])
        ? trim((string)$release['min_loader_version'])
        : '0.0.0';
    $firmware = $release['firmware'] ?? null;
    if (!is_array($firmware)) {
        return null;
    }

    $firmwarePath = isset($firmware['path']) ? (string)$firmware['path'] : '';
    $firmwareSize = isset($firmware['size']) ? (int)$firmware['size'] : 0;
    $firmwareSha = isset($firmware['sha256']) ? strtolower(trim((string)$firmware['sha256'])) : '';
    $firmwareUrl = otaToAbsoluteUrl($firmwarePath, $server);

    if ($version === '' || $buildDate === '' || $firmwareUrl === '' || $firmwareSize <= 0) {
        return null;
    }
    if (preg_match('/^[a-f0-9]{64}$/', $firmwareSha) !== 1) {
        return null;
    }

    return [
        'channel' => $channel,
        'version' => $version,
        'build_date' => $buildDate,
        'firmware' => [
            'url' => $firmwareUrl,
            'size' => $firmwareSize,
            'sha256' => $firmwareSha,
        ],
        'min_loader_version' => $loaderVersion === '' ? '0.0.0' : $loaderVersion,
        'notes' => $notes,
    ];
}

function otaJsonResponse($payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}
