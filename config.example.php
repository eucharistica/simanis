<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

define('ABSPATH', __DIR__ . '/');
define('ROOT_PATH', realpath(__DIR__));

define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

// API internal untuk mengambil file privat vaksin dari server API.
// Isi VAKSIN_API_KEY dengan nilai API_WEBSITE_KEY yang sama di production.
define('VAKSIN_API_BASE', getenv('VAKSIN_API_BASE') ?: '');
define('VAKSIN_API_KEY', getenv('VAKSIN_API_KEY') ?: '');

define('DB_USER_WRITE', '');
define('DB_PASS_WRITE', '');

// timezone
date_default_timezone_set('Asia/Jakarta');

// ---------- mysqli legacy (untuk query() lama) ----------
$GLOBALS['connection'] = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$GLOBALS['connection']) {
  die('Unable to connect database (mysqli)');
}

$connection = mysqli_connect(DB_HOST, DB_USER_WRITE, DB_PASS_WRITE, DB_NAME);

function querywrite($sql) {
    global $connection;
    $query = mysqli_query($connection, $sql);
    confirm($query);
    return $query;
}

// ---------- PDO (untuk login yang rapi) ----------
function pdo(): PDO {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;

  $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  return $pdo;
}

// ---------- helpers legacy ----------
function escape(string $string): string {
  return mysqli_real_escape_string($GLOBALS['connection'], $string);
}
function confirm($query): void {
  if(!$query) die('Query failed: ' . mysqli_error($GLOBALS['connection']));
}
function query(string $sql) {
  $q = mysqli_query($GLOBALS['connection'], $sql);
  confirm($q);
  return $q;
}
function fetch_array($result) { return mysqli_fetch_array($result); }
function fetch_assoc($result) { return mysqli_fetch_assoc($result); }
function num_rows($result): int { return mysqli_num_rows($result); }

function FormatTgl(string $format, string $tanggal): string {
  return date($format, strtotime($tanggal));
}

// Helper untuk dashboard - fetch all rows
function q(string $sql): array {
    $result = query($sql);
    $rows = [];
    while ($row = fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
