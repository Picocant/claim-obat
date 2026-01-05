<?php
/************************************************
 * SCRIPT CREATE TABLE BERKAS DIGITAL APOTEK
 * Database baru khusus untuk berkas digital apotek
 ************************************************/

require_once '../../conf/conf.php';
$koneksi = bukakoneksi();

if (!$koneksi) {
    die("Koneksi database gagal");
}

// SQL untuk membuat tabel baru
$sql_create = "
CREATE TABLE IF NOT EXISTS `berkas_digital_apotek` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `no_rkm_medis` VARCHAR(15) NOT NULL,
  `kode_berkas` VARCHAR(20) NOT NULL,
  `nama_berkas` VARCHAR(100) NOT NULL,
  `lokasi_file` TEXT NOT NULL,
  `tgl_upload` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_berkas` (`no_rkm_medis`, `kode_berkas`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `kode_berkas` (`kode_berkas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
";

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Create Table Berkas Digital Apotek</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
</head>
<body>
<div class='container my-5'>
    <div class='card'>
        <div class='card-header bg-success text-white'>
            <h4><i class='bi bi-database-add'></i> Create Table Berkas Digital Apotek</h4>
        </div>
        <div class='card-body'>";

// Eksekusi pembuatan tabel
if ($koneksi->query($sql_create)) {
    echo "<div class='alert alert-success'>
            <h5><i class='bi bi-check-circle'></i> Berhasil!</h5>
            <p>Tabel <code>berkas_digital_apotek</code> berhasil dibuat.</p>
          </div>";
    
    echo "<h5>Struktur Tabel:</h5>
          <pre class='bg-light p-3 rounded'>$sql_create</pre>";
    
    echo "<h5>Kolom-kolom:</h5>
          <table class='table table-bordered table-sm'>
            <thead class='table-light'>
                <tr>
                    <th>Kolom</th>
                    <th>Tipe</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td>INT(11) AUTO_INCREMENT</td>
                    <td>Primary Key</td>
                </tr>
                <tr>
                    <td><code>no_rkm_medis</code></td>
                    <td>VARCHAR(15)</td>
                    <td>Nomor Rekam Medis Pasien</td>
                </tr>
                <tr>
                    <td><code>kode_berkas</code></td>
                    <td>VARCHAR(20)</td>
                    <td>Kode Jenis Berkas (EEG, HBA1C, dll)</td>
                </tr>
                <tr>
                    <td><code>nama_berkas</code></td>
                    <td>VARCHAR(100)</td>
                    <td>Nama Jenis Berkas</td>
                </tr>
                <tr>
                    <td><code>lokasi_file</code></td>
                    <td>TEXT</td>
                    <td>Path lokasi file</td>
                </tr>
                <tr>
                    <td><code>tgl_upload</code></td>
                    <td>DATETIME</td>
                    <td>Tanggal & Waktu Upload</td>
                </tr>
            </tbody>
          </table>";
    
} else {
    echo "<div class='alert alert-danger'>
            <h5><i class='bi bi-x-circle'></i> Gagal!</h5>
            <p>Error: " . $koneksi->error . "</p>
          </div>";
}

echo "      <div class='text-center mt-4'>
                <a href='previewriwayat.php?no_rkm_medis=' class='btn btn-primary'>
                    <i class='bi bi-arrow-left'></i> Kembali ke Preview
                </a>
                <a href='create_folders.php' class='btn btn-warning'>
                    <i class='bi bi-folder-plus'></i> Buat Folder
                </a>
                <a href='dokumentasi_upload.html' class='btn btn-info'>
                    <i class='bi bi-book'></i> Dokumentasi
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>";

$koneksi->close();
?>
