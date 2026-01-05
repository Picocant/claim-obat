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
    // Periksa apakah file diunggah dengan benar
    if (isset($_FILES['filekamar']) && $_FILES['filekamar']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['filekamar']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['filekamar']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['filekamar']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);

                foreach ($Reader as $Key => $Row) {
                    // Lewati baris header
                    if ($Key < 1) continue;
                    // Escape data sebelum dimasukkan ke database
                    $kd_kamar    = $konektor->real_escape_string(trim($Row[0]));
                    $kd_bangsal  = $konektor->real_escape_string(trim($Row[1]));
                    $nama_ruangan= $konektor->real_escape_string(trim($Row[2]));
                    $trf_kamar   = $konektor->real_escape_string(trim($Row[3]));
                    $status      = $konektor->real_escape_string(trim($Row[4]));
                    $kelas       = $konektor->real_escape_string(trim($Row[5]));
                    $statusdata  = $konektor->real_escape_string(trim($Row[6]));


                    // Query SQL
                   $sql = "INSERT INTO kamar (`kd_kamar`, `kd_bangsal`, `trf_kamar`, `status`, `kelas`, `statusdata`) 
                    VALUES ('$kd_kamar', '$kd_bangsal', '$trf_kamar', '$status', '$kelas', '$statusdata')";
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