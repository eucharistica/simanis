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
    require_once __DIR__.'/rl3192-antibiotik-rajal-lib.php';
    $filters = rl3192Filters($_POST);
    echo json_encode(['ok'=>true,'data'=>rl3192Rows(pdo(),$filters),'filters'=>$filters],JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'data'=>[],'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('rl3192-antibiotik-rajal: '.$e->getMessage());
    echo json_encode(['ok'=>false,'data'=>[],'message'=>'Gagal mengambil data RL 3.19.2.'],JSON_UNESCAPED_UNICODE);
}
