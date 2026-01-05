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
    if (isset($_FILES['fileprov']) && $_FILES['fileprov']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = pathinfo($_FILES['fileprov']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['fileprov']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['fileprov']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);

                foreach ($Reader as $Key => $Row) {
                    // Lewati baris header
                    if ($Key < 1) continue;
                    // Escape data sebelum dimasukkan ke database
                    $nm_prop = $konektor->real_escape_string(trim($Row[0]));

                    // Query SQL
                    $sql = "INSERT INTO propinsi (nm_prop) VALUES ('$nm_prop')";

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
