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
    if (isset($_FILES['filedokter']) && $_FILES['filedokter']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['filedokter']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        $target_dir = "../upload_excel/" . basename($_FILES['filedokter']['name']);
        if (move_uploaded_file($_FILES['filedokter']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

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

                
                function getKodeSpesialis($namaSpesialis, $konektor) {
                    $namaSpesialisEscaped = $konektor->real_escape_string($namaSpesialis);
                    $result = $konektor->query("SELECT kd_sps FROM spesialis WHERE nm_sps = '$namaSpesialisEscaped' LIMIT 1");
                    if ($result && $row = $result->fetch_assoc()) {
                        return $row['kd_sps'];
                    }
                    return null;
                }

                
                $Reader = new SpreadsheetReader($target_dir);

              foreach ($Reader as $Key => $Row) {
                    if ($Key < 1) continue;
                
                    $kd_dokter = $konektor->real_escape_string(trim($Row[0])) ?: '-';
                    $nm_dokter = $konektor->real_escape_string(trim($Row[1])) ?: '-';
                    $jk = $konektor->real_escape_string(trim($Row[2])) ?: '-';
                    $tmp_lahir = $konektor->real_escape_string(trim($Row[3])) ?: '-';
                    $tgl_lahir = convertDate(trim($Row[4]));
                    $gol_drh = $konektor->real_escape_string(trim($Row[5])) ?: '-';
                    $agama = $konektor->real_escape_string(trim($Row[6])) ?: '-';
                    $almt_tgl = $konektor->real_escape_string(trim($Row[7])) ?: '-';
                    $no_telp = $konektor->real_escape_string(trim($Row[8])) ?: '-';
                    $email = $konektor->real_escape_string(trim($Row[9])) ?: '-';
                    $stts_nikah = $konektor->real_escape_string(trim($Row[10])) ?: '-';
                    $nm_sps = $konektor->real_escape_string(trim($Row[11])) ?: '-';
                    $alumni = $konektor->real_escape_string(trim($Row[12])) ?: '-';
                    $no_ijn_praktek = $konektor->real_escape_string(trim($Row[13])) ?: '-';
                    $status = $konektor->real_escape_string(trim($Row[14])) ?: '-';
                
                    // 🔎 Cari kode spesialis
                    $kd_sps = getKodeSpesialis($nm_sps, $konektor);
                    if (!$kd_sps) {
                        // Kalau tidak ketemu bisa:
                        // 1. Skip baris
                        // continue;
                
                        // 2. Atau pakai nilai default
                        $kd_sps = '-';
                    }
                
                    $sql = "INSERT INTO `dokter` (
                        `kd_dokter`, `nm_dokter`, `jk`, `tmp_lahir`, `tgl_lahir`, `gol_drh`, `agama`, 
                        `almt_tgl`, `no_telp`, `email`, `stts_nikah`, `kd_sps`, `alumni`, `no_ijn_praktek`, `status`
                    ) VALUES (
                        '$kd_dokter', '$nm_dokter', '$jk', '$tmp_lahir', '$tgl_lahir', '$gol_drh', '$agama',
                        '$almt_tgl', '$no_telp', '$email', '$stts_nikah', '$kd_sps', '$alumni', '$no_ijn_praktek', '$status'
                    )";
                
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