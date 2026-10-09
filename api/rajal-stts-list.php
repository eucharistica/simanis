<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$db = DB_NAME;
$sql = "
  SELECT COLUMN_TYPE
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA='$db'
    AND TABLE_NAME='reg_periksa'
    AND COLUMN_NAME='stts'
  LIMIT 1
";
$row = fetch_assoc(query($sql));
$colType = $row['COLUMN_TYPE'] ?? ''; // enum('Belum','Sudah',...)

$out = [];
if (preg_match("/^enum\\((.*)\\)$/i", $colType, $m)) {
  // ambil isi enum lalu split aman
  $vals = str_getcsv($m[1], ',', "'");
  foreach ($vals as $v) {
    $v = trim($v);
    if ($v !== '') $out[] = ['value' => $v, 'label' => $v];
  }
}
echo json_encode($out);
