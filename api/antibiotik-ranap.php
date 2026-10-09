<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    require_once __DIR__.'/../lib_auth.php';
    if (!auth_user()) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'data'=>[],'message'=>'Sesi berakhir. Silakan login kembali.']);
        exit;
    }
    session_write_close();
    require_once __DIR__.'/antibiotik-ranap-lib.php';
    if (($_GET['action'] ?? '') === 'options') {
        $rows = pdo()->query("SELECT kdjns,nama FROM jenis WHERE kdjns IN ('J120','J160','J161') ORDER BY kdjns")->fetchAll();
        $obat = pdo()->query("SELECT kode_brng,nama_brng,kdjns FROM databarang WHERE kdjns IN ('J120','J160','J161') ORDER BY nama_brng")->fetchAll();
        echo json_encode(['ok'=>true,'data'=>$rows,'obat'=>$obat],JSON_UNESCAPED_UNICODE);
    } else {
        $filters = antibiotikFilters($_POST);
        echo json_encode(['ok'=>true,'data'=>antibiotikRows(pdo(),$filters),'filters'=>$filters,'tanggal_acuan'=>date('Y-m-d')],JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'data'=>[],'message'=>$e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('antibiotik-ranap: '.$e->getMessage());
    echo json_encode(['ok'=>false,'data'=>[],'message'=>'Gagal mengambil data antibiotik rawat inap.']);
}
