<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$rows = q("
  SELECT kd_poli, nm_poli
  FROM poliklinik
  Where status = '1'
  ORDER BY nm_poli
");

echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);