<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$rows = q("SELECT bangsal.kd_bangsal, bangsal.nm_bangsal FROM bangsal inner join kamar on kamar.kd_bangsal=bangsal.kd_bangsal where kamar.statusdata ='1' group by bangsal.kd_bangsal ORDER BY bangsal.nm_bangsal ASC");

$out = [];
foreach ($rows as $r) $out[] = ['value'=>$r['kd_bangsal'], 'label'=>$r['nm_bangsal']];
echo json_encode($out);
