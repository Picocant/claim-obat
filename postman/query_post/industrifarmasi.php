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
    if (isset($_FILES['fileinsfarmasi']) && $_FILES['fileinsfarmasi']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['fileinsfarmasi']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['fileinsfarmasi']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['fileinsfarmasi']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);

                foreach ($Reader as $Key => $Row) {
                    // Lewati baris header
                    if ($Key < 1) continue;
                    // Escape data sebelum dimasukkan ke database
                    $kd_industri = $konektor->real_escape_string(trim($Row[0]));
                    $nm_supplier = $konektor->real_escape_string(trim($Row[1]));
                    $alamat = $konektor->real_escape_string(trim($Row[2]));
                    $kota = $konektor->real_escape_string(trim($Row[3]));
                    $no_telp = $konektor->real_escape_string(trim($Row[4]));

                    // Query SQL
                    $sql = "INSERT INTO industrifarmasi (kode_industri, nama_industri, alamat, kota, no_telp)
                    VALUES ('$kd_industri', '$nm_supplier', '$alamat', '$kota', '$no_telp')";

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