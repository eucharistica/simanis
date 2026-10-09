<?php
declare(strict_types=1);
require_once __DIR__.'/../config.php';
header('Content-Type: application/json; charset=utf-8');

function morbidDate(string $v): ?string { $v=substr($v,0,10);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:null; }
function morbidPoli(): array { return q("SELECT kd_poli,nm_poli FROM poliklinik WHERE status='1' ORDER BY nm_poli"); }
try {
  if (($_GET['action']??'')==='poli') { $out=[]; foreach(morbidPoli() as $r) $out[]=['value'=>$r['kd_poli'],'label'=>$r['nm_poli']]; echo json_encode($out,JSON_UNESCAPED_UNICODE); exit; }
  $start=morbidDate((string)($_POST['tgl1']??''));$end=morbidDate((string)($_POST['tgl2']??''));
  if(!$start||!$end||$start>$end) throw new InvalidArgumentException('Rentang tanggal tidak valid.');
  $poli=$_POST['kd_poli']??[];if(!is_array($poli))$poli=[$poli];$poli=array_values(array_filter(array_map('trim',$poli)));
  $allowed=['<7','7_28','29d_1y','1_4y','5_14y','15_24y','25_44y','45_64y','65plus'];
  $age="CASE WHEN TIMESTAMPDIFF(DAY,p.tgl_lahir,rp.tgl_registrasi)<7 THEN '<7' WHEN TIMESTAMPDIFF(DAY,p.tgl_lahir,rp.tgl_registrasi)<=28 THEN '7_28' WHEN TIMESTAMPDIFF(YEAR,p.tgl_lahir,rp.tgl_registrasi)<1 THEN '29d_1y' WHEN TIMESTAMPDIFF(YEAR,p.tgl_lahir,rp.tgl_registrasi)<=4 THEN '1_4y' WHEN TIMESTAMPDIFF(YEAR,p.tgl_lahir,rp.tgl_registrasi)<=14 THEN '5_14y' WHEN TIMESTAMPDIFF(YEAR,p.tgl_lahir,rp.tgl_registrasi)<=24 THEN '15_24y' WHEN TIMESTAMPDIFF(YEAR,p.tgl_lahir,rp.tgl_registrasi)<=44 THEN '25_44y' WHEN TIMESTAMPDIFF(YEAR,p.tgl_lahir,rp.tgl_registrasi)<=64 THEN '45_64y' ELSE '65plus' END";
  $where="rp.status_lanjut='Ralan' AND rp.stts<>'Batal' AND rp.tgl_registrasi BETWEEN ? AND ?";$params=[$start,$end];
  if($poli){$place=implode(',',array_fill(0,count($poli),'?'));$where.=" AND rp.kd_poli IN ($place)";$params=array_merge($params,$poli);}
  $sql="SELECT dp.kd_penyakit,pny.nm_penyakit,$age AS kelompok,p.jk,COUNT(DISTINCT rp.no_rawat) jumlah FROM reg_periksa rp INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis INNER JOIN diagnosa_pasien dp ON dp.no_rawat=rp.no_rawat LEFT JOIN penyakit pny ON pny.kd_penyakit=dp.kd_penyakit WHERE $where AND dp.kd_penyakit IS NOT NULL AND TRIM(dp.kd_penyakit)<>'' GROUP BY dp.kd_penyakit,pny.nm_penyakit,kelompok,p.jk";
  $st=pdo()->prepare($sql);$st->execute($params);$map=[];
  foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){$key=$r['kd_penyakit'];$map[$key]??=['kd_penyakit'=>$key,'nm_penyakit'=>$r['nm_penyakit']?:'-'];$ageKey=$r['kelompok'];$sex=strtoupper((string)$r['jk'])==='P'?'p':'l';$map[$key][$ageKey.'_'.$sex]=(int)$r['jumlah'];}
  $rows=[];foreach($map as &$r){$totalL=0;$totalP=0;foreach($allowed as $a){$r[$a.'_l']=(int)($r[$a.'_l']??0);$r[$a.'_p']=(int)($r[$a.'_p']??0);$totalL+=$r[$a.'_l'];$totalP+=$r[$a.'_p'];}$r['total_l']=$totalL;$r['total_p']=$totalP;$r['total']=$totalL+$totalP;$rows[]=$r;}unset($r);usort($rows,fn($a,$b)=>$b['total']<=>$a['total']?:strcmp($a['kd_penyakit'],$b['kd_penyakit']));foreach($rows as $i=>&$r)$r['no']=$i+1;unset($r);
  echo json_encode(['ok'=>true,'data'=>$rows,'filters'=>['tgl1'=>$start,'tgl2'=>$end]],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'data'=>[],'message'=>$e->getMessage()]);}catch(Throwable $e){http_response_code(500);error_log('morbiditas-rajal: '.$e->getMessage());echo json_encode(['ok'=>false,'data'=>[],'message'=>'Gagal mengambil data morbiditas rajal.']);}

