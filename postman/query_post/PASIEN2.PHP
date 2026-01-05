<?php
session_start();
header('Content-Type: application/json');

$host = "192.168.0.100"; // Ganti dengan host database Anda
$user = "rsar"; // Ganti dengan username database Anda
$password = "Stbkhanza2025"; // Ganti dengan password database Anda
$database = "br_rsar"; // Ganti dengan nama database Anda

// Membuat koneksi
$konektor = new mysqli($host, $user, $password, $database);

// Periksa koneksi
if ($konektor->connect_error) {
    die("Koneksi gagal: " . $konektor->connect_error);
}



if (isset($_GET['act']) && $_GET['act'] == "excel") {
    // Periksa apakah file diunggah dengan benar
    if (isset($_FILES['filepasien2']) && $_FILES['filepasien2']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx',  'csv'];
        $fileExtension = pathinfo($_FILES['filepasien2']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['filepasien2']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['filepasien2']['tmp_name'], $target_dir)) {
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
                // Escape dan siapkan data dari baris $Row
                $no_rkm_medis   = $konektor->real_escape_string(trim($Row[0]));
                $nm_pasien      = $konektor->real_escape_string(trim($Row[1]));
                $no_ktp         = $konektor->real_escape_string(trim($Row[2]));
                $jk             = $konektor->real_escape_string(trim($Row[3]));
                $tmp_lahir      = $konektor->real_escape_string(trim($Row[4]));
                $tgl_lahir      = $konektor->real_escape_string(trim($Row[5]));
                $nm_ibu         = $konektor->real_escape_string(trim($Row[6]));
                $alamat         = $konektor->real_escape_string(trim($Row[7]));
                $gol_darah      = $konektor->real_escape_string(trim($Row[8]));
                $pekerjaan      = $konektor->real_escape_string(trim($Row[9]));
                $stts_nikah     = $konektor->real_escape_string(trim($Row[10]));
                $agama          = $konektor->real_escape_string(trim($Row[11]));
                $tgl_daftar     = $konektor->real_escape_string(trim($Row[12]));
                $no_tlp         = $konektor->real_escape_string(trim($Row[13]));
                $umur           = $konektor->real_escape_string(trim($Row[14]));
                $pnd            = $konektor->real_escape_string(trim($Row[15]));
                $keluarga       = $konektor->real_escape_string(trim($Row[16]));
                $namakeluarga   = $konektor->real_escape_string(trim($Row[17]));
                $kd_pj          = $konektor->real_escape_string(trim($Row[18]));
                $no_peserta     = $konektor->real_escape_string(trim($Row[19]));
                $kd_kel         = $konektor->real_escape_string(trim($Row[20]));
                $kd_kec         = $konektor->real_escape_string(trim($Row[21]));
                $kd_kab         = $konektor->real_escape_string(trim($Row[22]));
                $pekerjaanpj    = $konektor->real_escape_string(trim($Row[23]));
                $alamatpj       = $konektor->real_escape_string(trim($Row[24]));
                $kelurahanpj    = $konektor->real_escape_string(trim($Row[25]));
                $kecamatanpj    = $konektor->real_escape_string(trim($Row[26]));
                $kabupatenpj    = $konektor->real_escape_string(trim($Row[27]));
                $perusahaan     = $konektor->real_escape_string(trim($Row[28]));
                $suku_bangsa    = $konektor->real_escape_string(trim($Row[29]));
                $bahasa         = $konektor->real_escape_string(trim($Row[30]));
                $cacat_fisik    = $konektor->real_escape_string(trim($Row[31]));
                $email          = $konektor->real_escape_string(trim($Row[32]));
                $nip            = $konektor->real_escape_string(trim($Row[33]));
                $kd_prop        = $konektor->real_escape_string(trim($Row[34]));
                $propinsipj     = $konektor->real_escape_string(trim($Row[35]));
                
                // Query Insert
                $sql = "INSERT INTO pasien (
                    no_rkm_medis, nm_pasien, no_ktp, jk, tmp_lahir, tgl_lahir, nm_ibu, alamat,
                    gol_darah, pekerjaan, stts_nikah, agama, tgl_daftar, no_tlp, umur, pnd,
                    keluarga, namakeluarga, kd_pj, no_peserta, kd_kel, kd_kec, kd_kab, pekerjaanpj,
                    alamatpj, kelurahanpj, kecamatanpj, kabupatenpj, perusahaan_pasien, suku_bangsa,
                    bahasa_pasien, cacat_fisik, email, nip, kd_prop, propinsipj
                ) VALUES (
                    '$no_rkm_medis', '$nm_pasien', '$no_ktp', '$jk', '$tmp_lahir', '$tgl_lahir',
                    '$nm_ibu', '$alamat', '$gol_darah', '$pekerjaan', '$stts_nikah', '$agama',
                    '$tgl_daftar', '$no_tlp', '$umur', '$pnd', '$keluarga', '$namakeluarga',
                    '$kd_pj', '$no_peserta', '$kd_kel', '$kd_kec', '$kd_kab', '$pekerjaanpj',
                    '$alamatpj', '$kelurahanpj', '$kecamatanpj', '$kabupatenpj', '$perusahaan',
                    '$suku_bangsa', '$bahasa', '$cacat_fisik', '$email', '$nip', '$kd_prop', '$propinsipj'
                )";



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