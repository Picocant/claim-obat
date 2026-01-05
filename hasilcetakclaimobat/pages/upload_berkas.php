<?php
/************************************************
 * UPLOAD BERKAS PEMERIKSAAN - SIMRS KHANZA
 ************************************************/

require_once '../../conf/conf.php';
$koneksi = bukakoneksi();

if (!$koneksi) {
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Method tidak diizinkan']));
}

// Ambil data dari form
$no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
$jenis_berkas = $_POST['jenis_berkas'] ?? '';

// Validasi input
if (empty($no_rkm_medis) || empty($jenis_berkas)) {
    die(json_encode(['success' => false, 'message' => 'No rekam medis dan jenis berkas harus diisi']));
}

// Validasi file upload
if (!isset($_FILES['file_berkas']) || $_FILES['file_berkas']['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(['success' => false, 'message' => 'File berkas harus diupload']));
}

$file = $_FILES['file_berkas'];

// Mapping jenis berkas ke folder dan kode
$mapping_berkas = [
    'eeg' => [
        'folder' => 'hasilpemeriksaaneeg',
        'kode' => 'EEG',
        'nama' => 'Electroencephalography (EEG)'
    ],
    'hba1c' => [
        'folder' => 'hasilpemeriksaanhba1c',
        'kode' => 'HBA1C',
        'nama' => 'Hemoglobin A1c (HbA1c)'
    ],
    'mmse' => [
        'folder' => 'hasilpemeriksaanmmse',
        'kode' => 'MMSE',
        'nama' => 'Mini-Mental State Examination (MMSE)'
    ],
    'echo' => [
        'folder' => 'hasilpemeriksaanecho',
        'kode' => 'ECHO',
        'nama' => 'Ekokardiografi (ECHO)'
    ],
    'echo_pediatrik' => [
        'folder' => 'hasilpemeriksaanechopediatrik',
        'kode' => 'ECHOPED',
        'nama' => 'ECHO Pediatrik'
    ],
    'ekg' => [
        'folder' => 'hasilpemeriksaanekg',
        'kode' => 'EKG',
        'nama' => 'Elektrokardiogram (EKG)'
    ],
    'oct' => [
        'folder' => 'hasilpemeriksaanoct',
        'kode' => 'OCT',
        'nama' => 'Optical Coherence Tomography (OCT)'
    ],
    'slitlamp' => [
        'folder' => 'hasilpemeriksaanslitlamp',
        'kode' => 'SLITLAMP',
        'nama' => 'Slit Lamp'
    ],
    'treadmill' => [
        'folder' => 'hasilpemeriksaantreadmill',
        'kode' => 'TREADMILL',
        'nama' => 'Treadmill'
    ],
    'usg' => [
        'folder' => 'hasilpemeriksaanusg',
        'kode' => 'USG',
        'nama' => 'USG'
    ],
    'usg_gynecologi' => [
        'folder' => 'hasilpemeriksaanusggynecologi',
        'kode' => 'USGGYN',
        'nama' => 'USG Gynecologi'
    ],
    'usg_neonatus' => [
        'folder' => 'hasilpemeriksaanusgneonatus',
        'kode' => 'USGNEO',
        'nama' => 'USG Neonatus'
    ],
    'usg_urologi' => [
        'folder' => 'hasilpemeriksaanusgurologi',
        'kode' => 'USGURO',
        'nama' => 'USG Urologi'
    ],
    'endoskopi_faring' => [
        'folder' => 'hasilpemeriksaanendoskopifaringlaring',
        'kode' => 'ENDOFAR',
        'nama' => 'Endoskopi Faring Laring'
    ],
    'endoskopi_hidung' => [
        'folder' => 'hasilpemeriksaanendoskopihidung',
        'kode' => 'ENDOHID',
        'nama' => 'Endoskopi Hidung'
    ],
    'endoskopi_telinga' => [
        'folder' => 'hasilpemeriksaanendoskopitelinga',
        'kode' => 'ENDOTEL',
        'nama' => 'Endoskopi Telinga'
    ]
];

// Validasi jenis berkas
if (!isset($mapping_berkas[$jenis_berkas])) {
    die(json_encode(['success' => false, 'message' => 'Jenis berkas tidak valid']));
}

$config = $mapping_berkas[$jenis_berkas];

// Path folder tujuan
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/webapps/' . $config['folder'] . '/';

// Buat folder jika belum ada
if (!file_exists($base_path)) {
    mkdir($base_path, 0777, true);
}

// Generate nama file unik
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

// Validasi ekstensi file
if (!in_array($file_ext, $allowed_ext)) {
    die(json_encode(['success' => false, 'message' => 'Ekstensi file tidak diizinkan. Hanya: ' . implode(', ', $allowed_ext)]));
}

// Generate nama file: no_rkm_medis_timestamp.ext
$timestamp = date('YmdHis');
$new_filename = str_replace(['/', '.'], '_', $no_rkm_medis) . '_' . $timestamp . '.' . $file_ext;
$target_path = $base_path . $new_filename;

// Upload file
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    die(json_encode(['success' => false, 'message' => 'Gagal mengupload file']));
}

// Simpan ke database berkas_digital_apotek (tabel baru yang sederhana)
$lokasi_file = $config['folder'] . '/' . $new_filename;

// Cek apakah sudah ada berkas dengan kode yang sama untuk no_rkm_medis ini
$check_sql = "SELECT id FROM berkas_digital_apotek 
              WHERE no_rkm_medis = ? AND kode_berkas = ?";
$check_stmt = $koneksi->prepare($check_sql);
$check_stmt->bind_param("ss", $no_rkm_medis, $config['kode']);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update jika sudah ada
    $update_sql = "UPDATE berkas_digital_apotek 
                   SET lokasi_file = ?, 
                       nama_berkas = ?,
                       tgl_upload = NOW()
                   WHERE no_rkm_medis = ? AND kode_berkas = ?";
    $update_stmt = $koneksi->prepare($update_sql);
    $update_stmt->bind_param("ssss", $lokasi_file, $config['nama'], $no_rkm_medis, $config['kode']);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Berkas berhasil diupdate',
            'jenis' => $config['nama']
        ]);
    } else {
        // Hapus file jika gagal update database
        unlink($target_path);
        echo json_encode(['success' => false, 'message' => 'Gagal update database: ' . $koneksi->error]);
    }
} else {
    // Insert jika belum ada
    $insert_sql = "INSERT INTO berkas_digital_apotek 
                   (no_rkm_medis, kode_berkas, nama_berkas, lokasi_file) 
                   VALUES (?, ?, ?, ?)";
    $insert_stmt = $koneksi->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $no_rkm_medis, $config['kode'], $config['nama'], $lokasi_file);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Berkas berhasil diupload',
            'jenis' => $config['nama']
        ]);
    } else {
        // Hapus file jika gagal insert ke database
        unlink($target_path);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $koneksi->error]);
    }
}

$koneksi->close();
