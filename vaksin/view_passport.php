<?php
require_once('../lib_auth.php');
require_login();

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('No ID');
}

$id = (int) $_GET['id'];

// Ambil data paspor
$q   = query("SELECT passport_file, dov, no_paspor FROM vaksin_internasional WHERE id='$id' LIMIT 1");
$row = fetch_array($q);
if (!$row) {
    http_response_code(404);
    exit('Data tidak ditemukan');
}

$API_BASE   = "http://100.10.1.4/api-website/";
$passportFile = basename((string)$row['passport_file']);
$sourceUrl  = $API_BASE . $passportFile; // fallback lama
$privateCandidates = [
    getenv('VAKSIN_UPLOAD_DIR') ?: '',
    dirname(__DIR__, 3) . '/private/vaksin-passport',
    dirname(__DIR__, 2) . '/private/vaksin-passport',
    'C:/xampp/htdocs/api-website/upload/passport-private',
];
$sourceFile = null;
foreach ($privateCandidates as $dir) {
    if ($dir === '') continue;
    $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $passportFile;
    if (is_file($candidate) && is_readable($candidate)) { $sourceFile = $candidate; break; }
}
$uploadDir  = __DIR__ . '/generated_passport';   // folder simpan hasil

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- ambil gambar dari URL ke file lokal sementara ---
$tmpFile = tempnam(sys_get_temp_dir(), 'psp_');
$sourceBytes = $sourceFile !== null ? file_get_contents($sourceFile) : file_get_contents($sourceUrl);
if ($sourceBytes === false) {
    http_response_code(404);
    exit('File paspor tidak ditemukan');
}
file_put_contents($tmpFile, $sourceBytes);

// Deteksi ekstensi dari nama file asli
$pathInfo   = pathinfo($row['passport_file']);  // nama di DB
$ext        = strtolower($pathInfo['extension'] ?? 'jpg');
$basename   = $pathInfo['filename'];            // tanpa ekstensi

// Nama file baru dengan suffix _timestamp
$newFilename = $basename . '_timestamp.' . $ext;
$newPath     = $uploadDir . '/' . $newFilename;

// --- load image sesuai tipe ---
switch ($ext) {
    case 'png':
        $im = imagecreatefrompng($tmpFile);
        break;
    case 'gif':
        $im = imagecreatefromgif($tmpFile);
        break;
    default:
        $im = imagecreatefromjpeg($tmpFile);
        $ext = 'jpg';
        $newFilename = $basename . '_timestamp.jpg';
        $newPath     = $uploadDir . '/' . $newFilename;
        break;
}

if (!$im) {
    http_response_code(500);
    exit('Gagal load image');
}

// --- resize proporsional ke lebar max 800px ---
$origWidth  = imagesx($im);
$origHeight = imagesy($im);

$maxWidth  = 800;   // target lebar
$maxHeight = 800;   // opsional batas tinggi, bisa disesuaikan

$ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1); // 1 = jangan dibesarkan

$newWidth  = (int)($origWidth * $ratio);
$newHeight = (int)($origHeight * $ratio);

if ($ratio < 1) {
    $dst = imagecreatetruecolor($newWidth, $newHeight);

    // kalau source PNG/GIF transparan, jaga alpha
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    imagecopyresampled(
        $dst, $im,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $origWidth, $origHeight
    ); // [web:75][web:88][web:85]

    imagedestroy($im);
    $im = $dst;
}

$width  = imagesx($im);
$height = imagesy($im);

// Warna teks (putih)
$color = imagecolorallocate($im, 255, 255, 255);
// Warna shadow
$shadowColor = imagecolorallocate($im, 0, 0, 0);

// Teks timestamp & lokasi (contoh)
$lokasi   = "";
// $lokasi   = "No.29 Jalan Kebon Kelapa\nUtan Kayu Selatan\nKecamatan Matraman\nKota Jakarta Timur\nDaerah Khusus Ibukota Jakarta";
// $tanggal  = date('d M Y H.i.s'); // contoh "15 Des 2025 10.15.57"
$tanggal  = "";
$text     = $tanggal . "\n" . $lokasi;

// Font & warna
$fontFile = __DIR__ . '/fonts/Roboto-Regular.ttf';
$fontSize = 14;   // sesuaikan
$angle    = 0;

$white  = imagecolorallocate($im, 255, 255, 255);
$black  = imagecolorallocate($im, 0, 0, 0);

$margin  = 30;
$lineGap = 5; // jarak antar baris

$lines = explode("\n", $text);

// Cari lebar & tinggi total blok teks
$maxWidth   = 0;
$totalHeight = 0;
$lineHeights = [];

foreach ($lines as $line) {
    $bbox = imagettfbbox($fontSize, $angle, $fontFile, $line); // [web:32][web:25]
    $w = $bbox[2] - $bbox[0];
    $h = $bbox[1] - $bbox[7];
    $lineHeights[] = $h;
    if ($w > $maxWidth) {
        $maxWidth = $w;
    }
    $totalHeight += $h + $lineGap;
}
$totalHeight -= $lineGap;

// Posisi kanan bawah
$startX = $width  - $maxWidth - $margin;
$startY = $height - $margin - $totalHeight + $lineHeights[0];

$y = $startY;
foreach ($lines as $idx => $line) {
    $bbox = imagettfbbox($fontSize, $angle, $fontFile, $line);
    $w = $bbox[2] - $bbox[0];

    $x = $width - $w - $margin;

    // shadow
    imagettftext($im, $fontSize, $angle, $x+1, $y+1, $black, $fontFile, $line);
    // teks utama
    imagettftext($im, $fontSize, $angle, $x,   $y,   $white, $fontFile, $line); // [web:21][web:24][web:25]

    $y += $lineHeights[$idx] + $lineGap;
}

// Simpan ke file baru
switch ($ext) {
    case 'png':
        imagepng($im, $newPath); // bisa tambah level kompres kalau mau
        $mime = 'image/png';
        break;
    case 'gif':
        imagegif($im, $newPath);
        $mime = 'image/gif';
        break;
    default:
        imagejpeg($im, $newPath, 80); // [web:80][web:86][web:92]
        $mime = 'image/jpeg';
        break;
}


imagedestroy($im);
unlink($tmpFile);


$noPaspor = preg_replace('/[^A-Za-z0-9_-]/', '_', $row['no_paspor']); // sanitize
$downloadName = $noPaspor . '_timestamp.' . $ext;

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $downloadName . '"'); // atau attachment kalau mau selalu download [web:58][web:65]
header('Content-Length: ' . filesize($newPath));

readfile($newPath);
exit;
