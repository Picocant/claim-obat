<?php
require_once '../../conf/conf.php';

$koneksi = bukakoneksi();
if (!$koneksi) {
    die('Koneksi database gagal');
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($_GET['no_rkm_medis'] ?? '');
    header('Location: ' . $redir);
    exit;
}

$no_rawat    = $_POST['no_rawat'] ?? '';
$no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
$hasil        = trim($_POST['hasilEEG'] ?? '');
$keterangan   = trim($_POST['keteranganEEG'] ?? '');

if (empty($no_rawat)) {
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&error=no_rawat';
    header('Location: ' . $redir);
    exit;
}

if (!isset($_FILES['fileEEG']) || $_FILES['fileEEG']['error'] !== UPLOAD_ERR_OK) {
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&error=nofile';
    header('Location: ' . $redir);
    exit;
}

$allowed = ['pdf','jpg','jpeg','png','webp'];
$maxSize = 10 * 1024 * 1024; // 10 MB

$origName = $_FILES['fileEEG']['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$size = $_FILES['fileEEG']['size'];

if (!in_array($ext, $allowed) || $size > $maxSize) {
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&error=invalid_file';
    header('Location: ' . $redir);
    exit;
}

$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], "\\/") . "/webapps/berkasrawat/eeg/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$base = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($origName));
$filename = time() . '_' . $base;
$target = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['fileEEG']['tmp_name'], $target)) {
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&error=move_failed';
    header('Location: ' . $redir);
    exit;
}

$tgl = date('Y-m-d');
$jam = date('H:i:s');

$stmt = $koneksi->prepare("INSERT INTO hasil_eeg (no_rkm_medis, no_rawat, hasil_eeg, keterangan, lokasi_file, tgl_input, jam_input) VALUES (?,?,?,?,?,?,?)");
if (!$stmt) {
    // cleanup uploaded file
    @unlink($target);
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&error=dbstmt';
    header('Location: ' . $redir);
    exit;
}

$stmt->bind_param('sssssss', $no_rkm_medis, $no_rawat, $hasil, $keterangan, $filename, $tgl, $jam);
$exec = $stmt->execute();

if (!$exec) {
    @unlink($target);
    $redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&error=dberr';
    header('Location: ' . $redir);
    exit;
}

// Success
$redir = 'previewriwayat.php?no_rkm_medis=' . urlencode($no_rkm_medis) . '&uploaded=1';
header('Location: ' . $redir);
exit;
