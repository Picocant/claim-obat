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
    if (isset($_FILES['filetaskid']) && $_FILES['filetaskid']['error'] == UPLOAD_ERR_OK) {
        // Validasi tipe file (hanya Excel)
        $allowedExtensions = ['xls', 'xlsx'];
        $fileExtension = pathinfo($_FILES['filetaskid']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        // Tentukan target direktori dan file path
        $target_dir = "../upload_excel/" . basename($_FILES['filetaskid']['name']);

        // Pindahkan file yang diunggah ke target direktori
        if (move_uploaded_file($_FILES['filetaskid']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);
                
                
                    foreach ($Reader as $Key => $Row) {
                        // Lewati baris header
                        if ($Key < 1) continue;
                    
                        // Escape data
                        $no_rawat = $konektor->real_escape_string(trim($Row[0]));
                        $taskid   = $konektor->real_escape_string(trim($Row[1]));
                        $waktu    = $konektor->real_escape_string(trim($Row[2]));
                    
                        // --- Cek apakah data sudah ada ---
                        $cek_sql = "SELECT COUNT(*) AS jumlah FROM referensi_mobilejkn_bpjs_taskid 
                                    WHERE no_rawat = '$no_rawat' AND taskid = '$taskid'";
                        $cek_result = $konektor->query($cek_sql);
                        $exist = ($cek_result && $cek_result->fetch_assoc()['jumlah'] > 0);
                    
                        if ($exist) {
                            // --- Jika ada → update waktu ---
                            $update_sql = "UPDATE referensi_mobilejkn_bpjs_taskid 
                                           SET waktu = '$waktu' 
                                           WHERE no_rawat = '$no_rawat' AND taskid = '$taskid'";
                            if (!$konektor->query($update_sql)) {
                                echo json_encode([
                                    'success' => false,
                                    'message' => 'Gagal update pada baris ' . ($Key + 1) . ': ' . $konektor->error
                                ]);
                                exit();
                            }
                        } else {
                            // --- Jika belum ada → insert baru ---
                            $insert_sql = "INSERT INTO referensi_mobilejkn_bpjs_taskid (no_rawat, taskid, waktu)
                                           VALUES ('$no_rawat', '$taskid', '$waktu')";
                            if (!$konektor->query($insert_sql)) {
                                echo json_encode([
                                    'success' => false,
                                    'message' => 'Gagal insert pada baris ' . ($Key + 1) . ': ' . $konektor->error
                                ]);
                                exit();
                            }
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