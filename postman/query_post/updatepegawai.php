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
                    
                    $id    = $konektor->real_escape_string(trim($Row[0]));
                    $npwp  = $konektor->real_escape_string(trim($Row[1]));
                    $bpd  = $konektor->real_escape_string(trim($Row[2]));
                    $rekening  = $konektor->real_escape_string(trim($Row[2]));
                    //$mulai_kontrak = convertDate(trim($Row[2])); 
                    //$mulai_kerja = convertDate(trim($Row[2]));
                   
                   $sql = "UPDATE pegawai SET npwp='$npwp', bpd='$bpd', rekening='$rekening' WHERE id='$id'";

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