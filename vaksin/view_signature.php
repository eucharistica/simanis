<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib_auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(400); exit('Invalid ID'); }

$stmt = pdo()->prepare('SELECT consent_signature_file FROM vaksin_internasional WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$file = basename((string)$stmt->fetchColumn());
if (!preg_match('/^[a-f0-9]{48}\.png$/i', $file)) { http_response_code(404); exit('Signature not found'); }

$candidates = [];
$configured = getenv('VAKSIN_SIGNATURE_DIR');
if (is_string($configured) && $configured !== '') $candidates[] = $configured;
$candidates[] = dirname(__DIR__, 3) . '/private/vaksin-signature';
$candidates[] = dirname(__DIR__, 2) . '/private/vaksin-signature';
$candidates[] = 'C:/xampp/htdocs/vaksin/upload';

$path = null;
foreach ($candidates as $dir) {
    $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    if (is_file($candidate) && is_readable($candidate)) { $path = $candidate; break; }
}
if ($path === null) { http_response_code(404); exit('Signature file not found'); }

header('Content-Type: image/png');
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($path));
readfile($path);
