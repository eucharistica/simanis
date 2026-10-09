<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$exclude = ['U0015','U0016','U0035','U0021','U0012','U0036'];
$rows = q("
  SELECT kd_poli, nm_poli 
  FROM poliklinik 
  WHERE status='1' and kd_poli NOT IN ('".implode("','", $exclude)."')
  ORDER BY nm_poli ASC
");

$out = [];
foreach ($rows as $r) {
  $out[] = ['value'=>$r['kd_poli'], 'label'=>$r['nm_poli']];
}
echo json_encode($out);
?>
