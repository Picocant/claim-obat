<?php
session_start();
header('Content-Type: application/json');

$host = "localhost"; // Ganti dengan host database Anda
$user = "root"; // Ganti dengan username database Anda
$password = ""; // Ganti dengan password database Anda
$database = "rsar"; // Ganti dengan nama database Anda

// Membuat koneksi
$konektor = new mysqli($host, $user, $password, $database);

// Periksa koneksi
if ($konektor->connect_error) {
    die("Koneksi gagal: " . $konektor->connect_error);
}


if (isset($_GET['act']) && $_GET['act'] == "excel") {
    if (isset($_FILES['filepegawai']) && $_FILES['filepegawai']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['filepegawai']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        $target_dir = "../upload_excel/" . basename($_FILES['filepegawai']['name']);
        if (move_uploaded_file($_FILES['filepegawai']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                                require_once('spreadsheet-reader-master/SpreadsheetReader.php');
                function sanitizeValue($value, $konektor) {
                    $trimmed = trim($value);
                    if ($trimmed === '' || is_null($trimmed)) {
                        return '-';
                    }
                    return $konektor->real_escape_string($trimmed);
                }

                     function convertDate($dateStr) {
    $dateStr = trim($dateStr);
    if ($dateStr === '') {
        return '0000-00-00';
    }

    // Jika hanya digit (numeric) → Excel serial date
    if (is_numeric($dateStr)) {
        $serial = (int)$dateStr;
        if ($serial <= 0) return '0000-00-00';

        $unixDate = ($serial - 25569) * 86400;
        $date = gmdate('Y-m-d', $unixDate);

        $year = (int)substr($date, 0, 4);
        if ($year < 1900 || $year > 2100) {
            return '0000-00-00';
        }

        return $date;
    }

    // Jika ada tanda slash → coba parse MM/DD/YYYY
    if (strpos($dateStr, '/') !== false) {
        $parts = explode('/', $dateStr);
        if (count($parts) == 3) {
            $month = (int)$parts[0];
            $day = (int)$parts[1];
            $year = (int)$parts[2];

            if ($year < 1900 || $year > 2100) {
                return '0000-00-00';
            }

            if (!checkdate($month, $day, $year)) {
                return '0000-00-00';
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    // Format tidak dikenali
    return '0000-00-00';
}

                
                $Reader = new SpreadsheetReader($target_dir);
                
                $id= 0;
                $id_jadi=$id++;
                foreach ($Reader as $Key => $Row) {
                    if ($Key < 1) continue;

                  $nip = sanitizeValue($Row[0], $konektor);
                    $nama = sanitizeValue($Row[1], $konektor);
                    $jk = sanitizeValue($Row[2], $konektor);
                    $jbtn = sanitizeValue($Row[3], $konektor);
                    $jnj_jabatan = sanitizeValue($Row[4], $konektor);
                    $kode_kelompok = sanitizeValue($Row[5], $konektor);
                    $kode_resiko = sanitizeValue($Row[6], $konektor);
                    $kode_emergency = sanitizeValue($Row[7], $konektor);
                    $departemen = sanitizeValue($Row[8], $konektor);
                    $bidang = sanitizeValue($Row[9], $konektor);
                    $stts_wp = sanitizeValue($Row[10], $konektor);
                    $stts_kerja = sanitizeValue($Row[11], $konektor);
                    $npwp = sanitizeValue($Row[12], $konektor);
                    $pendidikan = sanitizeValue($Row[13], $konektor);
                    $gapok = sanitizeValue($Row[14], $konektor);
                    $tmp_lahir = sanitizeValue($Row[15], $konektor);
                    $tgl_lahir = convertDate(trim($Row[16]));
                    $alamat = sanitizeValue($Row[17], $konektor);
                    $kota = sanitizeValue($Row[18], $konektor);
                    $mulai_kerja = convertDate(trim($Row[19]));
                    $ms_kerja = sanitizeValue($Row[20], $konektor);
                    $indexins = sanitizeValue($Row[21], $konektor);
                    $bpd = sanitizeValue($Row[22], $konektor);
                    $rekening = sanitizeValue($Row[23], $konektor);
                    $stts_aktif = sanitizeValue($Row[24], $konektor);
                    $wajibmasuk = sanitizeValue($Row[25], $konektor);
                    $pengurang = sanitizeValue($Row[26], $konektor);
                    $indek = sanitizeValue($Row[27], $konektor);
                    $mulai_kontrak = convertDate(trim($Row[28]));
                    $cuti_diambil = sanitizeValue($Row[29], $konektor);
                    $dankes = sanitizeValue($Row[30], $konektor);
                    $photo = sanitizeValue($Row[31], $konektor);
                    $no_ktp = sanitizeValue($Row[32], $konektor);

                    $sql = "INSERT INTO pegawai(id,nik, nama, jk, jbtn, jnj_jabatan, kode_kelompok, kode_resiko, kode_emergency, departemen, bidang, stts_wp, stts_kerja, npwp, pendidikan, gapok, tmp_lahir, tgl_lahir, alamat, kota, mulai_kerja, ms_kerja, indexins, bpd, rekening, stts_aktif, wajibmasuk, pengurang, indek, mulai_kontrak, cuti_diambil, dankes, photo, no_ktp) 
                            VALUES ('$id_jadi','$nip','$nama','$jk','$jbtn','$jnj_jabatan','$kode_kelompok','$kode_resiko','$kode_emergency','$departemen','$bidang','$stts_wp','$stts_kerja','$npwp','$pendidikan','$gapok','$tmp_lahir','$tgl_lahir','$alamat','$kota','$mulai_kerja','$ms_kerja','$indexins','$bpd','$rekening','$stts_aktif','$wajibmasuk','$pengurang','$indek','$mulai_kontrak','$cuti_diambil','$dankes','$photo','$no_ktp')";

                    if (!$konektor->query($sql)) {
                        echo json_encode(['success' => false, 'message' => 'Error pada baris ' . ($Key + 1) . ': ' . $konektor->error]);
                        exit();
                    }
                }

                $konektor->close();
                echo json_encode(['success' => true, 'message' => 'Data berhasil diimpor.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error membaca file Excel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
}

if (isset($_GET['act']) && $_GET['act'] == "update") {
    if (isset($_FILES['filepegawai']) && $_FILES['filepegawai']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['filepegawai']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        $target_dir = "../upload_excel/" . basename($_FILES['filepegawai']['name']);
        if (move_uploaded_file($_FILES['filepegawai']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);

                foreach ($Reader as $Key => $Row) {
                    if ($Key < 1) continue;

                    // Ambil ID dari kolom pertama sebagai acuan UPDATE
                    $id = $konektor->real_escape_string(trim($Row[0]));
                    $jbtn = $konektor->real_escape_string(trim($Row[1]));
                

                    // Periksa apakah ID ada di database
                    $cek_sql = "SELECT id FROM pegawai WHERE id = '$id'";
                    $cek_result = $konektor->query($cek_sql);

                    if ($cek_result->num_rows > 0) {
                        // Lakukan UPDATE jika ID sudah ada
                        $sql = "UPDATE pegawai SET 
                             jbtn='$jbtn' WHERE id = '$id'";
                    } else {
                        // Jika ID tidak ditemukan, skip (atau bisa juga ditambahkan sebagai INSERT)
                        continue;
                    }

                    if (!$konektor->query($sql)) {
                        echo json_encode(['success' => false, 'message' => 'Error pada baris ' . ($Key + 1) . ': ' . $konektor->error]);
                        exit();
                    }
                }

                $konektor->close();
                echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error membaca file Excel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
}