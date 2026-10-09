<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['type'] ?? '');
if ($id < 1 || !in_array($type, ['signature', 'passport'], true)) {
    http_response_code(400);
    exit('Parameter file tidak valid');
}

$storedPath = '';
if ($type === 'passport') {
    $storedStmt = pdo()->prepare('SELECT passport_file FROM vaksin_internasional WHERE id = :id LIMIT 1');
    $storedStmt->execute([':id' => $id]);
    $storedPath = str_replace('\\', '/', trim((string)$storedStmt->fetchColumn()));

    // Kompatibilitas file lama yang memang dahulu disajikan melalui URL publik API.
    if (preg_match('~^upload/passport/[A-Za-z0-9._-]+$~i', $storedPath)) {
        $legacyUrl = rtrim(VAKSIN_API_BASE, '/') . '/' . $storedPath;
        $legacy = curl_init($legacyUrl);
        curl_setopt_array($legacy, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
        ]);
        $legacyBody = curl_exec($legacy);
        $legacyStatus = (int)curl_getinfo($legacy, CURLINFO_HTTP_CODE);
        $legacyMime = strtolower(trim(explode(';', (string)curl_getinfo($legacy, CURLINFO_CONTENT_TYPE))[0]));
        curl_close($legacy);

        if ($legacyBody !== false && $legacyStatus >= 200 && $legacyStatus < 300 && in_array($legacyMime, ['image/png', 'image/jpeg', 'application/pdf'], true)) {
            header('Content-Type: ' . $legacyMime);
            header('Content-Length: ' . strlen($legacyBody));
            header('Content-Disposition: inline');
            header('Cache-Control: private, no-store');
            echo $legacyBody;
            exit;
        }
    }
}

$apiBase = rtrim(VAKSIN_API_BASE, '/');
$apiKey = trim(VAKSIN_API_KEY);
if ($apiKey === '') {
    http_response_code(500);
    exit('API internal belum dikonfigurasi');
}

$url = $apiBase . '/vaksin/internal-file?' . http_build_query(['id' => $id, 'type' => $type]);
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Accept: image/png,image/jpeg,application/pdf', 'X-API-Key: ' . $apiKey],
]);
$body = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$mime = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

if ($body === false || $status < 200 || $status >= 300) {
    error_log('[SIMANIS] Gagal mengambil file vaksin dari API: HTTP ' . $status . ' ' . $error);
    http_response_code($status >= 400 ? $status : 502);
    exit('File tidak dapat ditampilkan');
}

$allowedMime = ['image/png', 'image/jpeg', 'application/pdf'];
$mime = strtolower(trim(explode(';', $mime)[0]));
if (!in_array($mime, $allowedMime, true)) {
    http_response_code(415);
    exit('Tipe file tidak diizinkan');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($body));
header('Content-Disposition: inline');
header('Cache-Control: private, no-store');
echo $body;
