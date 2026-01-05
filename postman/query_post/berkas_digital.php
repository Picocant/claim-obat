<?php
session_start();
header('Content-Type: application/json');

// Koneksi database
$host = "192.168.0.100";
$user = "rsar";
$password = "Stbkhanza2025";
$database = "br_rsar";

$konektor = new mysqli($host, $user, $password, $database);
if ($konektor->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Koneksi gagal: ' . $konektor->connect_error]));
}

if (isset($_GET['act']) && $_GET['act'] == "excel") {
    if (isset($_FILES['fileberkasklaim']) && $_FILES['fileberkasklaim']['error'] == UPLOAD_ERR_OK) {

        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['fileberkasklaim']['name'], PATHINFO_EXTENSION);

        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        $target_dir = "../upload_excel/" . basename($_FILES['fileberkasklaim']['name']);

        if (move_uploaded_file($_FILES['fileberkasklaim']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);

                $inserted = 0;
                $skipped = 0;
                $not_found = 0;

                foreach ($Reader as $Key => $Row) {
                    // Lewati baris header
                    if ($Key < 1) continue;

                    // Ambil nama file dari kolom pertama Excel
                    $nama_file = trim($Row[0]);
                    if ($nama_file == '') continue;

                    // Hilangkan ekstensi .pdf jika ada
                    $no_sep1 = pathinfo($nama_file, PATHINFO_FILENAME);
                    $no_sep = $konektor->real_escape_string($no_sep1);

                    // Cek no_sep di tabel bridging_sep
                    $query_sep = "SELECT no_rawat FROM bridging_sep WHERE no_sep = '$no_sep'";
                    $hasil_sep = $konektor->query($query_sep);

                    if ($hasil_sep && $hasil_sep->num_rows > 0) {
                        $data_sep = $hasil_sep->fetch_assoc();
                        $no_rawat = $data_sep['no_rawat'];

                        // Lokasi file tetap disimpan, tapi tanpa dicek keberadaannya
                        $lokasi_file = "pages/upload/" . $nama_file;

                        // ✅ Cek apakah data sudah ada
                        $cek_sql = "SELECT 1 FROM berkas_digital_perawatan WHERE no_rawat='$no_rawat' AND kode='015' AND lokasi_file='$lokasi_file' LIMIT 1";
                        $cek_data = $konektor->query($cek_sql);

                        if ($cek_data && $cek_data->num_rows > 0) {
                            $skipped++; // sudah ada, lanjut ke baris berikutnya
                            continue;
                        }

                        // Insert ke tabel berkas_digital_perawatan
                        $sql = "INSERT INTO berkas_digital_perawatan (no_rawat, kode, lokasi_file)
                                VALUES ('$no_rawat', '015', '$lokasi_file')";

                        if ($konektor->query($sql)) {
                            $inserted++;
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Gagal insert baris ke-' . ($row + 1) . ': ' . $konektor->error
                            ]);
                            exit();
                        }
                    } else {
                        $not_found++;
                    }
                }

                $konektor->close();

                echo json_encode([
                    'success' => true,
                    'message' => "Import selesai.<br>
                        Berhasil insert: $inserted data<br>
                        Data duplikat dilewati: $skipped<br>
                        No. SEP tidak ditemukan di bridging_sep: $not_found"
                ]);

            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error membaca file Excel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file Excel.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
}