<?php
require_once '../../conf/conf.php';

$koneksi = bukakoneksi();
if (!$koneksi) {
    die('Koneksi database gagal');
}

$sql = "CREATE TABLE IF NOT EXISTS hasil_eeg (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_rkm_medis VARCHAR(30) NOT NULL,
    no_rawat VARCHAR(30) NOT NULL,
    hasil_eeg TEXT,
    keterangan TEXT,
    lokasi_file VARCHAR(255) NOT NULL,
    tgl_input DATE NOT NULL,
    jam_input TIME NOT NULL,
    INDEX idx_no_rkm (no_rkm_medis),
    INDEX idx_no_rawat (no_rawat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($koneksi->query($sql) === TRUE) {
    echo "Tabel hasil_eeg berhasil dibuat atau sudah ada.\n";
} else {
    echo "Gagal membuat tabel: " . $koneksi->error . "\n";
}

echo "\nAkses kembali: <a href=\"previewriwayat.php\">Preview Riwayat</a>\n";
