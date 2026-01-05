<?php
session_start();
header('Content-Type: application/json');

$host = "localhost"; // Ganti dengan host database Anda
$user = "root"; // Ganti dengan username database Anda
$password = ""; // Ganti dengan password database Anda
$database = "br_rsar"; // Ganti dengan nama database Anda

// Membuat koneksi
$konektor = new mysqli($host, $user, $password, $database);

// Periksa koneksi
if ($konektor->connect_error) {
    die("Koneksi gagal: " . $konektor->connect_error);
}



if (isset($_GET['act']) && $_GET['act'] == "excel") {
    // Periksa apakah file diunggah dengan benar
    if (isset($_FILES['filepasien']) && $_FILES['filepasien']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['filepasien']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['filepasien']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['filepasien']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);
                
                
                function convertDate($dateStr) {
                    if (empty($dateStr)) return null;
                
                    // Jika format numeric (Excel serial date)
                    if (is_numeric($dateStr)) {
                        // Excel starts counting from 1900-01-01, but incorrectly assumes 1900 is a leap year
                        $unixDate = ($dateStr - 25569) * 86400; // 25569 = days between 1900-01-01 and 1970-01-01
                        return gmdate("Y-m-d", $unixDate);
                    }
                
                    // Jika format teks dd/mm/yyyy
                    $parts = explode('/', $dateStr);
                    if (count($parts) == 3) {
                        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                    }
                
                    return null; // Jika format tidak dikenali
                }


                foreach ($Reader as $Key => $Row) {
                    // Lewati baris header
                    if ($Key < 1) continue;
                    // Escape data sebelum dimasukkan ke database
                $no_rkm_medis  = $konektor->real_escape_string(trim($Row[0]));
                $no_ktp        = $konektor->real_escape_string(trim($Row[1]));
                $nm_pasien     = $konektor->real_escape_string(trim($Row[2]));
                $jk            = $konektor->real_escape_string(trim($Row[3]));
                $tmp_lahir     = $konektor->real_escape_string(trim($Row[4]));
                $tgl_lahir_raw = convertDate(trim($Row[5]));
                $rawAlamat = trim($Row[6]);
                $alamat = empty($rawAlamat) || strtolower($rawAlamat) === "none" ? "-" : $konektor->real_escape_string($rawAlamat);

                $umur_raw      = $konektor->real_escape_string(trim($Row[7]));
                
                    
             if ($umur_raw === '-' || trim($umur_raw) === '') {
                try {
                    $lahir = new DateTime($tgl_lahir_raw);
                    $today = new DateTime();
                    $selisih = $today->diff($lahir);
                    $umur = $selisih->y;
                } catch (Exception $e) {
                    $umur = 0;
                }
            } else {
                $umur = (int) $umur_raw;
            }
            $umur = $konektor->real_escape_string($umur);

                
                
                $kelurahanpj   = $konektor->real_escape_string(trim($Row[8]));
                $kecamatanpj   = $konektor->real_escape_string(trim($Row[9]));
                $kabupatenpj   = $konektor->real_escape_string(trim($Row[10]));
                $propinsipj    = $konektor->real_escape_string(trim($Row[11]));
                $no_tlp        = $konektor->real_escape_string(trim($Row[12]));
                $agama         = $konektor->real_escape_string(trim($Row[13]));
                $stts_nikah    = $konektor->real_escape_string(trim($Row[14]));
                $no_peserta    = $konektor->real_escape_string(trim($Row[15]));
                $kd_pj_raw = trim($Row[16]);
                $kd_pj = $konektor->real_escape_string($kd_pj_raw !== "" ? $kd_pj_raw : "-");
                
                
                // Kolom tambahan yang tidak ada di Excel, isi default '-'
                $nm_ibu            = '-';
                $gol_darah         = '-';
                $pekerjaan         = '-';
                $tgl_daftar = date('Y-m-d H:i:s'); // Format lengkap: 2024-08-31 14:55:30
                $pnd               = '-';
                $keluarga          = 'LAIN-LAIN';
                $namakeluarga      = '-';
                $pekerjaanpj       = '-';
                $perusahaan_pasien = '-';
                $suku_bangsa       = '1';
                $bahasa_pasien     = '2';
                $cacat_fisik       = '1';
                $email             = '-';
                $nip               = '-';


            
                
                
    
                    $sql = "INSERT INTO pasien (no_rkm_medis, nm_pasien, no_ktp, jk, tmp_lahir, tgl_lahir, nm_ibu, alamat, gol_darah,
                        pekerjaan, stts_nikah, agama, tgl_daftar, no_tlp, umur, pnd, keluarga, namakeluarga,
                        kd_pj, no_peserta, kd_kel, kd_kec, kd_kab, pekerjaanpj, alamatpj, kelurahanpj, kecamatanpj,
                        kabupatenpj, perusahaan_pasien, suku_bangsa, bahasa_pasien, cacat_fisik, email, nip,
                        kd_prop, propinsipj) VALUES (
                        '$no_rkm_medis', '$nm_pasien', '$no_ktp', '$jk', '$tmp_lahir', '$tgl_lahir_raw', '$nm_ibu', '$alamat', '$gol_darah',
                        '$pekerjaan', '$stts_nikah', '$agama', '$tgl_daftar', '$no_tlp', '$umur', '$pnd', '$keluarga', '$namakeluarga',
                        '$kd_pj', '$no_peserta', '$kelurahanpj', '$kecamatanpj', '$kabupatenpj', '$pekerjaanpj', '$alamat', '$kelurahanpj', '$kecamatanpj',
                        '$kabupatenpj', '$perusahaan_pasien', '$suku_bangsa', '$bahasa_pasien', '$cacat_fisik', '$email', '$nip',
                        '$propinsipj', '$propinsipj')";

                    if (!$konektor->query($sql)) {
                        echo json_encode(['success' => false, 'message' => 'Error pada baris ' . ($Key + 1) . ': ' . $konektor->error]);
                        exit();
                    }
                }

                // Tutup koneksi database
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