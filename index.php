<?php
declare(strict_types=1);
require_once __DIR__ . '/lib_auth.php';
require_login();

$page = $_GET['page'] ?? 'dashboard';

$allowed = [
  'dashboard'     => __DIR__.'/pages/dashboard.php',
  'rm_rajal'      => __DIR__.'/pages/rm_rajal.php',
  'rm_ranap'      => __DIR__.'/pages/rm_ranap.php',
  'igd'           => __DIR__.'/pages/igd.php',
  'icd_rajal'     => __DIR__.'/pages/icd_rajal.php',
  'icd_ranap'     => __DIR__.'/pages/icd_ranap.php',
  'waktu_tunggu'  => __DIR__.'/pages/waktu_tunggu.php',
  'laporan_ispa'  => __DIR__.'/pages/laporan_ispa.php',
  'pendapatan_rajal' => __DIR__.'/pages/pendapatan_rajal.php',
  'tindakan_harian' => __DIR__.'/pages/tindakan_harian_dokter.php',
  'tindakan_harian_dokter' => __DIR__.'/pages/tindakan_harian_dokter.php',
  'rl51_sirs' => __DIR__.'/pages/rl51_sirs.php',
  'rl318_farmasi' => __DIR__.'/pages/rl318_farmasi.php',
  'lplpo' => __DIR__.'/pages/lplpo.php',
  'rekap_obat_poli' => __DIR__.'/pages/rekap_obat_poli.php',
  'rekap_bhp' => __DIR__.'/pages/rekap_bhp.php',
  'rekap_resep' => __DIR__.'/pages/rekap_resep.php',
  'antibiotik_ranap' => __DIR__.'/pages/antibiotik_ranap.php',
  'monitoring_expertise_radiologi' => __DIR__.'/pages/monitoring_expertise_radiologi.php',
  'morbiditas_rajal' => __DIR__.'/pages/morbiditas_rajal.php',
  'rl3192_antibiotik_rajal' => __DIR__.'/pages/rl3192_antibiotik_rajal.php',
  'rl3192_antibiotik_ranap' => __DIR__.'/pages/rl3192_antibiotik_ranap.php',
  'vaksin_internasional' => __DIR__.'/pages/vaksin_internasional.php',
  'vaksin_internasional_view' => __DIR__.'/pages/vaksin_internasional_view.php',
  'bukti_laporan_kemkes' => __DIR__.'/pages/bukti_laporan_kemkes.php',
];
if (!isset($allowed[$page])) $page = 'dashboard';

$user = auth_user();

include __DIR__.'/layout/header.php';
include __DIR__.'/layout/sidebar.php';
include $allowed[$page];
include __DIR__.'/layout/footer.php';
