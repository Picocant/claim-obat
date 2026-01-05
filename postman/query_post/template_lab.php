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
    if (isset($_FILES['file_template_lab']) && $_FILES['file_template_lab']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = pathinfo($_FILES['file_template_lab']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['file_template_lab']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['file_template_lab']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);
                
                foreach ($Reader as $Key => $Row) {
    // Lewati header
    if ($Key < 1) continue;

    // Escape & mapping data Excel ke kolom
    $id_template    = $konektor->real_escape_string(trim($Row[0]));
    $bagian_rs      = $konektor->real_escape_string(trim($Row[1]));
    $bagian_dokter  = $konektor->real_escape_string(trim($Row[2]));
    $bagian_laborat = $konektor->real_escape_string(trim($Row[3]));
    $kso            = $konektor->real_escape_string(trim($Row[4]));
    $menejemen      = $konektor->real_escape_string(trim($Row[5]));
    $biaya_item     = $konektor->real_escape_string(trim($Row[6]));

    // Query Update langsung
    $update_sql = "UPDATE template_laboratorium SET 
                        bagian_rs = '$bagian_rs',
                        bagian_dokter = '$bagian_dokter',
                        bagian_laborat = '$bagian_laborat',
                        kso = '$kso',
                        menejemen = '$menejemen',
                        biaya_item = '$biaya_item'
                    WHERE id_template = '$id_template'";

    if (!$konektor->query($update_sql)) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal update pada baris ' . ($Key + 1) . ': ' . $konektor->error
        ]);
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