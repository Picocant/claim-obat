<?php
/************************************************
 * SCRIPT INSERT MASTER BERKAS DIGITAL
 * Jalankan script ini untuk membuat data master
 * berkas digital yang diperlukan
 ************************************************/

require_once '../../conf/conf.php';

// Cek apakah user sudah konfirmasi
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

if (!$confirmed) {
    // Tampilkan halaman konfirmasi
    showConfirmationPage();
    exit;
}

$koneksi = bukakoneksi();

if (!$koneksi) {
    die("Koneksi database gagal");
}

function showConfirmationPage() {
    ?>
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Konfirmasi Insert Master Berkas</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
    </head>
    <body>
        <div class='modal fade show d-block' style='background: rgba(0,0,0,0.5);' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                    <div class='modal-header bg-warning'>
                        <h5 class='modal-title'>
                            <i class='bi bi-exclamation-triangle-fill'></i> Konfirmasi Insert Data
                        </h5>
                    </div>
                    <div class='modal-body'>
                        <p class='mb-3'>Apakah Anda yakin ingin menjalankan proses insert master berkas digital?</p>
                        <div class='alert alert-info mb-0'>
                            <small>
                                <i class='bi bi-info-circle'></i> 
                                Proses ini akan menambahkan 16 data master berkas digital ke database.
                            </small>
                        </div>
                    </div>
                    <div class='modal-footer'>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='confirm' value='no'>
                            <button type='submit' class='btn btn-secondary'>
                                <i class='bi bi-x-circle'></i> Batal
                            </button>
                        </form>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='confirm' value='yes'>
                            <button type='submit' class='btn btn-primary'>
                                <i class='bi bi-check-circle'></i> Ya, Lanjutkan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Data master berkas
$master_berkas = [
    ['kode' => 'EEG', 'nama' => 'Electroencephalography (EEG)'],
    ['kode' => 'HBA1C', 'nama' => 'Hemoglobin A1c (HbA1c)'],
    ['kode' => 'MMSE', 'nama' => 'Mini-Mental State Examination (MMSE)'],
    ['kode' => 'ECHO', 'nama' => 'Ekokardiografi (ECHO)'],
    ['kode' => 'ECHOPED', 'nama' => 'ECHO Pediatrik'],
    ['kode' => 'EKG', 'nama' => 'Elektrokardiogram (EKG)'],
    ['kode' => 'OCT', 'nama' => 'Optical Coherence Tomography (OCT)'],
    ['kode' => 'SLITLAMP', 'nama' => 'Slit Lamp'],
    ['kode' => 'TREADMILL', 'nama' => 'Treadmill'],
    ['kode' => 'USG', 'nama' => 'USG'],
    ['kode' => 'USGGYN', 'nama' => 'USG Gynecologi'],
    ['kode' => 'USGNEO', 'nama' => 'USG Neonatus'],
    ['kode' => 'USGURO', 'nama' => 'USG Urologi'],
    ['kode' => 'ENDOFAR', 'nama' => 'Endoskopi Faring Laring'],
    ['kode' => 'ENDOHID', 'nama' => 'Endoskopi Hidung'],
    ['kode' => 'ENDOTEL', 'nama' => 'Endoskopi Telinga']
];

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Insert Master Berkas</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
</head>
<body>
<div class='container my-5'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h4><i class='bi bi-database-fill-add'></i> Insert Master Berkas Digital</h4>
        </div>
        <div class='card-body'>
            <table class='table table-bordered table-sm'>
                <thead class='table-light'>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Berkas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";

$no = 1;
$success = 0;
$skip = 0;
$error = 0;

foreach ($master_berkas as $berkas) {
    echo "<tr>";
    echo "<td>$no</td>";
    echo "<td><code>{$berkas['kode']}</code></td>";
    echo "<td>{$berkas['nama']}</td>";
    
    // Cek apakah sudah ada
    $check_sql = "SELECT COUNT(*) as total FROM master_berkas_digital WHERE kode = ?";
    $check_stmt = $koneksi->prepare($check_sql);
    $check_stmt->bind_param("s", $berkas['kode']);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        echo "<td><span class='badge bg-info'>Sudah Ada</span></td>";
        $skip++;
    } else {
        // Insert
        $insert_sql = "INSERT INTO master_berkas_digital (kode, nama) VALUES (?, ?)";
        $insert_stmt = $koneksi->prepare($insert_sql);
        $insert_stmt->bind_param("ss", $berkas['kode'], $berkas['nama']);
        
        if ($insert_stmt->execute()) {
            echo "<td><span class='badge bg-success'><i class='bi bi-check-circle'></i> Berhasil</span></td>";
            $success++;
        } else {
            echo "<td><span class='badge bg-danger'><i class='bi bi-x-circle'></i> Gagal: {$koneksi->error}</span></td>";
            $error++;
        }
    }
    
    echo "</tr>";
    $no++;
}

echo "      </tbody>
            </table>
            
            <div class='alert " . ($error > 0 ? 'alert-warning' : 'alert-success') . "'>
                <h5><i class='bi bi-info-circle'></i> Ringkasan Proses:</h5>
                <ul class='mb-0'>
                    <li><strong>Berhasil Insert:</strong> <span class='badge bg-success'>$success</span></li>
                    <li><strong>Sudah Ada (Skip):</strong> <span class='badge bg-info'>$skip</span></li>
                    <li><strong>Error:</strong> <span class='badge bg-danger'>$error</span></li>
                    <li><strong>Total Diproses:</strong> <span class='badge bg-dark'>" . count($master_berkas) . "</span></li>
                </ul>
            </div>
            
            <div class='text-center mt-3'>
                <a href='previewriwayat.php?no_rkm_medis=' class='btn btn-primary'>
                    <i class='bi bi-arrow-left'></i> Kembali ke Preview
                </a>
                <a href='dokumentasi_upload.html' class='btn btn-info'>
                    <i class='bi bi-book'></i> Dokumentasi
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Notifikasi Hasil -->
<div class='modal fade' id='resultModal' tabindex='-1'>
    <div class='modal-dialog modal-dialog-centered'>
        <div class='modal-content'>
            <div class='modal-header " . ($error > 0 ? 'bg-warning' : 'bg-success') . " text-white'>
                <h5 class='modal-title'>
                    <i class='bi bi-" . ($error > 0 ? 'exclamation-triangle' : 'check-circle') . "-fill'></i> 
                    Proses Selesai
                </h5>
            </div>
            <div class='modal-body'>
                <div class='text-center mb-3'>
                    <i class='bi bi-" . ($error > 0 ? 'exclamation-triangle' : 'check-circle') . "-fill' style='font-size: 4rem; color: " . ($error > 0 ? '#ffc107' : '#198754') . ";'></i>
                </div>
                <p class='text-center mb-3'><strong>" . ($error > 0 ? 'Proses selesai dengan beberapa error' : 'Semua data berhasil diproses!') . "</strong></p>
                <table class='table table-sm table-bordered'>
                    <tr>
                        <td>Berhasil Insert</td>
                        <td class='text-end'><span class='badge bg-success'>$success</span></td>
                    </tr>
                    <tr>
                        <td>Sudah Ada (Skip)</td>
                        <td class='text-end'><span class='badge bg-info'>$skip</span></td>
                    </tr>
                    <tr>
                        <td>Error</td>
                        <td class='text-end'><span class='badge bg-danger'>$error</span></td>
                    </tr>
                    <tr class='table-light'>
                        <td><strong>Total</strong></td>
                        <td class='text-end'><span class='badge bg-dark'>" . count($master_berkas) . "</span></td>
                    </tr>
                </table>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-primary' data-bs-dismiss='modal'>
                    <i class='bi bi-check-lg'></i> OK
                </button>
            </div>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>
<script>
    // Auto show modal hasil
    window.addEventListener('DOMContentLoaded', function() {
        var resultModal = new bootstrap.Modal(document.getElementById('resultModal'));
        resultModal.show();
    });
</script>
</body>
</html>";

$koneksi->close();
?>
