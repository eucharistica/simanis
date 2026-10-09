<?php
declare(strict_types=1);
require_once __DIR__.'/../lib_auth.php'; require_login();
header('Content-Type: application/json; charset=utf-8');
try {
  $db=pdo();
  // Tabel dibuat melalui sql/bukti_laporan_kemkes.sql pada deployment.
  $reports=['INM','IKP','PPRA','HAIS','MPDN','Simatneo','SITB','SIGIZI Pelita kesmas','SIHA','SIGA'];
  if($_SERVER['REQUEST_METHOD']==='GET'){
    $y=(int)($_GET['tahun']??date('Y'));$st=$db->prepare('SELECT * FROM bukti_laporan_kemkes WHERE periode_tahun=? ORDER BY periode_bulan,id');$st->execute([$y]);$map=[];foreach($st as $r)$map[(int)$r['periode_bulan']][$r['jenis_laporan']]=$r;echo json_encode(['ok'=>true,'reports'=>$reports,'data'=>$map,'tahun'=>$y]);exit;
  }
  $writeDb = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER_WRITE, DB_PASS_WRITE, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
  $m=(int)($_POST['bulan']??0);$y=(int)($_POST['tahun']??0);$jenis=(string)($_POST['jenis_laporan']??'');if($m<1||$m>12||$y<2000||$y>2100||!in_array($jenis,$reports,true))throw new InvalidArgumentException('Periode atau jenis laporan tidak valid.');
  if(empty($_FILES['bukti'])||$_FILES['bukti']['error']!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Pilih file gambar atau PDF.');$f=$_FILES['bukti'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$allow=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];if(!isset($allow[$mime]))throw new InvalidArgumentException('Format hanya JPG, PNG, WEBP, atau PDF.');
  $dir=ROOT_PATH.'/uploads/bukti-laporan-kemkes';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Folder upload tidak dapat dibuat.');$name=$y.'-'.$m.'-'.preg_replace('/[^A-Za-z0-9_-]/','_',strtolower($jenis)).'-'.bin2hex(random_bytes(6)).'.'.$allow[$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('File gagal disimpan.');$path='uploads/bukti-laporan-kemkes/'.$name;$user=auth_user();$st=$writeDb->prepare('INSERT INTO bukti_laporan_kemkes(periode_bulan,periode_tahun,jenis_laporan,file_path,file_name,file_mime,uploaded_by) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE file_path=VALUES(file_path),file_name=VALUES(file_name),file_mime=VALUES(file_mime),uploaded_by=VALUES(uploaded_by),uploaded_at=CURRENT_TIMESTAMP');$st->execute([$m,$y,$jenis,$path,$f['name'],$mime,$user['username']??null]);echo json_encode(['ok'=>true,'message'=>'Bukti laporan tersimpan.']);
}catch(InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}catch(Throwable $e){http_response_code(500);error_log('bukti-laporan-kemkes: '.$e->getMessage());echo json_encode(['ok'=>false,'message'=>'Gagal menyimpan bukti laporan.']);}
