<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$rows = q("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' and kd_sps not in ('S0001','S0002','S0004','S0021','S0022','S0023','UMUM','-') ORDER BY nm_dokter ASC");

$out = [];
foreach ($rows as $r) $out[] = ['value'=>$r['kd_dokter'], 'label'=>$r['nm_dokter']];
echo json_encode($out);
