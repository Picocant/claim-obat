<?php
require_once '../../conf/conf.php';

$koneksi = bukakoneksi();
if (!$koneksi) {
    die('Koneksi database gagal');
}

$docroot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
$eeg_dir = $docroot . '/webapps/berkasrawat/eeg/';
$root_dir = $docroot . '/webapps/berkasrawat/';

// Buat folder eeg jika belum ada
if (!is_dir($eeg_dir)) {
    mkdir($eeg_dir, 0755, true);
}

// Ambil semua EEG records
$qAll = $koneksi->query("SELECT id, lokasi_file FROM hasil_eeg WHERE lokasi_file IS NOT NULL AND lokasi_file != ''");

if (!$qAll) {
    die('Query gagal: ' . $koneksi->error);
}

$moved = 0;
$already_in_eeg = 0;
$not_found = 0;
$errors = [];

while ($row = $qAll->fetch_assoc()) {
    $id = $row['id'];
    $filename = $row['lokasi_file'];
    
    // Cek jika sudah di folder eeg
    if (strpos($filename, 'eeg/') === 0) {
        $already_in_eeg++;
        continue;
    }
    
    $src = $root_dir . $filename;
    $dest = $eeg_dir . $filename;
    
    // Jika file ada di /berkasrawat/
    if (file_exists($src)) {
        if (rename($src, $dest)) {
            // Update DB: tambah prefix 'eeg/' ke lokasi_file
            $new_path = 'eeg/' . $filename;
            $stmt = $koneksi->prepare("UPDATE hasil_eeg SET lokasi_file = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_path, $id);
                $stmt->execute();
                $moved++;
                $stmt->close();
            } else {
                $errors[] = "ID $id: File dipindah tapi update DB gagal";
            }
        } else {
            $errors[] = "ID $id: Gagal pindahkan file $filename";
        }
    } else {
        $not_found++;
    }
}

echo "<h3>Hasil Migrasi EEG Files</h3>";
echo "<p><strong>File dipindahkan & DB diupdate:</strong> $moved</p>";
echo "<p><strong>Sudah di folder eeg/:</strong> $already_in_eeg</p>";
echo "<p><strong>File tidak ditemukan di /berkasrawat/:</strong> $not_found</p>";

if (!empty($errors)) {
    echo "<h4>Errors:</h4>";
    echo "<ul>";
    foreach ($errors as $err) {
        echo "<li>$err</li>";
    }
    echo "</ul>";
}

echo "<p><a href='previewriwayat.php'>Kembali ke Preview</a></p>";
