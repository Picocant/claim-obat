<?php
ini_set('max_execution_time', 0);
set_time_limit(0);
session_start();
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$database = "br_rsar";

// Membuat koneksi
$konektor = new mysqli($host, $user, $password, $database);

// Periksa koneksi
if ($konektor->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Koneksi gagal: ' . $konektor->connect_error]));
}

if (isset($_GET['act']) && $_GET['act'] == "excel") {
    if (isset($_FILES['filepasienbr']) && $_FILES['filepasienbr']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = pathinfo($_FILES['filepasienbr']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Hanya file Excel yang diperbolehkan.']);
            exit();
        }

        $target_dir = "../upload_excel/" . basename($_FILES['filepasienbr']['name']);

        if (move_uploaded_file($_FILES['filepasienbr']['tmp_name'], $target_dir)) {
            try {
                require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
                require_once('spreadsheet-reader-master/SpreadsheetReader.php');

                $Reader = new SpreadsheetReader($target_dir);

                $totalInsert = 0;
                $totalSkip = 0;

                foreach ($Reader as $Key => $Row) {
                    if ($Key < 1) continue; // Lewati header

                    // Ambil data dan bersihkan
                $no_rm     = isset($Row[1]) ? $konektor->real_escape_string(trim($Row[1])) : '';
                $nama      = isset($Row[2]) ? $konektor->real_escape_string(trim($Row[2])) : '';
                $tgl_lahir = isset($Row[3]) ? $konektor->real_escape_string(trim($Row[3])) : '';
                $umur      = isset($Row[4]) ? $konektor->real_escape_string(trim($Row[4])) : '';
                $jk        = isset($Row[5]) ? $konektor->real_escape_string(trim($Row[5])) : '';
                $alamat    = isset($Row[6]) ? $konektor->real_escape_string(trim($Row[6])) : '';
                $nik       = isset($Row[7]) ? $konektor->real_escape_string(trim($Row[7])) : '';
                $no_asur   = isset($Row[8]) ? $konektor->real_escape_string(trim($Row[8])) : '';


                    // Isi default jika kosong
                    if ($nik == '' || $nik == '-') $nik = $no_rm;
                    if ($nama == '') $nama = '-';
                    if ($umur == '') $umur = '-';
                    if ($jk == '') $jk = '-';
                    if ($alamat == '') $alamat = '-';
                    if ($no_asur == '') $no_asur = '-';

                    // Format tanggal (dd-mm-yyyy → yyyy-mm-dd)
                    if (!empty($tgl_lahir) && preg_match('/^\d{2}-\d{2}-\d{4}$/', $tgl_lahir)) {
                        $parts = explode('-', $tgl_lahir);
                        $tgl_lahir = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                    } else {
                        $tgl_lahir = '0000-00-00';
                    }

                    // Cek duplikat berdasarkan NIK atau NO RM
                    $cekQuery = "SELECT COUNT(*) AS ada FROM pasien_br WHERE mr_pasien_br = '$no_rm' OR nik_pasien_br = '$nik'";
                    $cek = $konektor->query($cekQuery);
                    $ada = $cek->fetch_assoc()['ada'];

                    if ($ada > 0) {
                        $totalSkip++;
                        continue;
                    }

                    // Insert data baru
                    $sql = "INSERT INTO pasien_br 
                            (`mr_pasien_br`, `nik_pasien_br`, `no_a_pasien_br`, `alamat_pasien_br`, 
                             `umur_pasien_br`, `tgl_pasien_br`, `nama_pasien_br`, `jk_pasien_br`)
                            VALUES 
                            ('$no_rm', '$nik', '$no_asur', '$alamat', '$umur', '$tgl_lahir', '$nama', '$jk')";

                    if ($konektor->query($sql)) {
                        $totalInsert++;
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Error pada baris ' . ($Key + 1) . ': ' . $konektor->error
                        ]);
                        exit();
                    }
                }

                $konektor->close();
                echo json_encode([
                    'success' => true,
                    'message' => "Import selesai. $totalInsert data berhasil ditambahkan, $totalSkip data duplikat dilewati."
                ]);

            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error membaca file Excel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file ke direktori upload.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File tidak diunggah dengan benar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
}
?>