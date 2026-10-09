<?php
declare(strict_types=1);
const RL3192_RANAP_SPS=['S0006','S0013','S0011','S0017','S0012'];
const RL3192_RANAP_DRUG=['J120','J160','J161'];
function rl3192RanapDate(string $v):?string{$v=substr($v,0,10);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:null;}
function rl3192RanapFilters(array $in):array{$a=rl3192RanapDate((string)($in['startDate']??''));$b=rl3192RanapDate((string)($in['endDate']??''));if(!$a||!$b||$a>$b)throw new InvalidArgumentException('Rentang tanggal tidak valid.');return ['startDate'=>$a,'endDate'=>$b];}
function rl3192RanapRows(PDO $db,array $f):array{
 $s=implode(',',array_fill(0,count(RL3192_RANAP_SPS),'?'));$g=implode(',',array_fill(0,count(RL3192_RANAP_DRUG),'?'));
 $stay="INNER JOIN (SELECT no_rawat,MIN(NULLIF(tgl_masuk,'0000-00-00')) tgl_masuk FROM kamar_inap GROUP BY no_rawat) ki ON ki.no_rawat=rp.no_rawat";
 $sql="SELECT sp.kd_sps,sp.nm_sps,COALESCE(ab.n,0) jumlah_antibiotik,COALESCE(sm.n,0) jumlah_sampel FROM spesialis sp
 LEFT JOIN (SELECT jd.kd_sps,COUNT(DISTINCT o.no_rawat) n FROM dpjp_ranap j INNER JOIN dokter jd ON jd.kd_dokter=j.kd_dokter INNER JOIN reg_periksa rp ON rp.no_rawat=j.no_rawat AND rp.status_lanjut='Ranap' AND rp.stts<>'Batal' $stay INNER JOIN detail_pemberian_obat o ON o.no_rawat=rp.no_rawat AND o.status='Ranap' INNER JOIN databarang b ON b.kode_brng=o.kode_brng WHERE ki.tgl_masuk BETWEEN ? AND ? AND jd.kd_sps IN ($s) AND b.kdjns IN ($g) GROUP BY jd.kd_sps) ab ON ab.kd_sps=sp.kd_sps
 LEFT JOIN (SELECT jd.kd_sps,COUNT(DISTINCT rp.no_rawat) n FROM dpjp_ranap j INNER JOIN dokter jd ON jd.kd_dokter=j.kd_dokter INNER JOIN reg_periksa rp ON rp.no_rawat=j.no_rawat AND rp.status_lanjut='Ranap' AND rp.stts<>'Batal' $stay WHERE ki.tgl_masuk BETWEEN ? AND ? AND jd.kd_sps IN ($s) GROUP BY jd.kd_sps) sm ON sm.kd_sps=sp.kd_sps
 WHERE sp.kd_sps IN ($s) AND COALESCE(ab.n,0)>0 ORDER BY FIELD(sp.kd_sps,'S0006','S0013','S0011','S0017','S0012')";
 $params=array_merge([$f['startDate'],$f['endDate']],RL3192_RANAP_SPS,RL3192_RANAP_DRUG,[$f['startDate'],$f['endDate']],RL3192_RANAP_SPS,RL3192_RANAP_SPS);$st=$db->prepare($sql);$st->execute($params);$out=[];foreach($st->fetchAll(PDO::FETCH_ASSOC) as $i=>$r){$a=(int)$r['jumlah_antibiotik'];$n=(int)$r['jumlah_sampel'];$out[]=['no'=>$i+1,'kd_sps'=>$r['kd_sps'],'nm_sps'=>$r['nm_sps'],'jumlah_antibiotik'=>$a,'jumlah_sampel'=>$n,'persen'=>$n?round($a/$n*100,2):0.0];}return $out;
}
