# 🚀 SETUP DATABASE - PANDUAN LENGKAP

## Cara 1: Via Browser (Paling Mudah)

Buka di browser:
```
http://localhost/webapps/hasilcetakclaimobat/pages/create_table_berkas.php
```

Script akan otomatis membuat tabel `berkas_digital_apotek`.

---

## Cara 2: Via phpMyAdmin

1. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Pilih database **`sik`** (atau database SIMRS Anda)
3. Klik tab **SQL**
4. Copy-paste isi file `setup_database.sql`
5. Klik **Go/Kirim**

---

## Cara 3: Via MySQL Command Line

```bash
# Masuk ke MySQL
mysql -u root -p

# Pilih database
USE sik;

# Jalankan SQL
SOURCE C:/xampp/htdocs/webapps/hasilcetakclaimobat/pages/setup_database.sql
```

---

## ✅ Verifikasi Setup Berhasil

Jalankan query ini untuk memastikan tabel sudah dibuat:

```sql
SHOW TABLES LIKE 'berkas_digital_apotek';
DESCRIBE berkas_digital_apotek;
```

---

## 📊 Struktur Tabel

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | INT(11) AUTO_INCREMENT | Primary Key |
| `no_rkm_medis` | VARCHAR(15) | Nomor Rekam Medis |
| `no_rawat` | VARCHAR(17) | Nomor Rawat |
| `kode_berkas` | VARCHAR(20) | Kode Jenis (EEG, HBA1C, dll) |
| `nama_berkas` | VARCHAR(100) | Nama Jenis Berkas |
| `lokasi_file` | TEXT | Path file |
| `tgl_upload` | DATETIME | Tanggal upload |

---

## 🔄 Setelah Setup Database

Lanjutkan dengan:

1. ✅ Buat folder: `create_folders.php`
2. ✅ Upload berkas: Gunakan form di halaman preview

---

## ⚠️ Troubleshooting

**Error: Access denied**
- Pastikan user MySQL punya hak CREATE TABLE
- Gunakan user root jika perlu

**Error: Database tidak ada**
- Pastikan database `sik` sudah dibuat
- Ubah nama database di query sesuai sistem Anda

**Error: Table already exists**
- Tabel sudah ada, tidak perlu dibuat lagi
- Langsung lanjut upload berkas
