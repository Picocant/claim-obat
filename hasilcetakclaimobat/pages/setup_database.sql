-- ================================================
-- SETUP DATABASE BERKAS DIGITAL APOTEK
-- Jalankan SQL ini di phpMyAdmin atau MySQL client
-- ================================================

-- Buat tabel berkas_digital_apotek
CREATE TABLE IF NOT EXISTS `berkas_digital_apotek` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `no_rkm_medis` VARCHAR(15) NOT NULL COMMENT 'Nomor Rekam Medis Pasien',
  `kode_berkas` VARCHAR(20) NOT NULL COMMENT 'Kode Jenis Berkas (EEG, HBA1C, dll)',
  `nama_berkas` VARCHAR(100) NOT NULL COMMENT 'Nama Jenis Berkas',
  `lokasi_file` TEXT NOT NULL COMMENT 'Path lokasi file',
  `tgl_upload` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Tanggal & Waktu Upload',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_berkas` (`no_rkm_medis`, `kode_berkas`),
  KEY `idx_no_rkm_medis` (`no_rkm_medis`),
  KEY `idx_kode_berkas` (`kode_berkas`),
  KEY `idx_tgl_upload` (`tgl_upload`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tabel penyimpanan berkas digital pemeriksaan';

-- Cek hasil
SELECT 'Tabel berkas_digital_apotek berhasil dibuat!' AS status;

-- Tampilkan struktur tabel
DESCRIBE berkas_digital_apotek;

-- Tampilkan jumlah data (awal pasti 0)
SELECT COUNT(*) AS jumlah_data FROM berkas_digital_apotek;
