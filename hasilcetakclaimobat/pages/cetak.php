<?php
/************************************************
 * CETAK PDF KLAIM BPJS
 ************************************************/
session_start();
require_once '../../conf/conf.php';
require_once __DIR__ . '/../dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* =========================
   KONEKSI
   ========================= */
$koneksi = bukakoneksi();
if (!$koneksi) {
    die('Koneksi database gagal');
}
/* =========================
   DATA WAJIB
   ========================= */
$no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
// =====================================
// PASTIKAN RAWAT LIST SELALU ADA
// =====================================
$rawatList = [];

// Ambil filter dari form (kalau ada)
$filter = $_POST['filter'] ?? 'all';
$limit  = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;

// Ambil daftar no_rawat sesuai filter
$sql = "SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = ?";
if ($filter === 'last') {
    $sql .= " ORDER BY no_rawat DESC LIMIT $limit";
}

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $no_rkm_medis);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $rawatList[] = $row['no_rawat'];
}
$stmt->close();

// JIKA MASIH KOSONG, JANGAN ERROR — STOP DENGAN PESAN
if (empty($rawatList)) {
    echo "<p><b>Tidak ada data kunjungan.</b></p>";
    return;
}

$no_rawat    = $_POST['no_rawat'] ?? '';

if ($no_rkm_medis === '') {
    die('Data pasien tidak valid');
}

/* 🔥 JIKA no_rawat TIDAK DIKIRIM → AMBIL TERBARU */
if ($no_rawat === '') {
    $q_last = $koneksi->query("
        SELECT no_rawat
        FROM reg_periksa
        WHERE no_rkm_medis = '$no_rkm_medis'
        ORDER BY no_rawat DESC
        LIMIT 1
    ");

    if ($q_last && $q_last->num_rows > 0) {
        $no_rawat = $q_last->fetch_assoc()['no_rawat'];
    } else {
        die('Registrasi tidak ditemukan');
    }
}

/* =========================
   DATA CHECKBOX
   ========================= */
$sep_ids     = $_POST['sep_id']     ?? [];
$resep_ids   = $_POST['resep_id']   ?? [];
$berkas_ids  = $_POST['berkas_id']  ?? [];
$resume_ids  = $_POST['resume_id']  ?? [];
$echo_ids    = $_POST['echo_id']    ?? [];
$eeg_ids     = $_POST['eeg_id']     ?? [];
$nota_ids    = $_POST['nota_id']    ?? [];
$penyerahan_ids = $_POST['penyerahan_id'] ?? [];
$hba1c_ids   = $_POST['hba1c_id']   ?? [];
$mmse_ids    = $_POST['mmse_id']    ?? [];
$ekg_ids     = $_POST['ekg_id']     ?? [];

// Debug: uncomment untuk test
// echo "Nota IDs: " . count($nota_ids) . "<br>";
// echo "Penyerahan IDs: " . count($penyerahan_ids) . "<br>";
// var_dump($nota_ids);
// var_dump($penyerahan_ids);
// exit;

/* =========================
   FILTER REGISTRASI
   ========================= */
$filter_registrasi_mode  = $_POST['filter_registrasi_mode']  ?? 'all';
$filter_registrasi_limit = isset($_POST['filter_registrasi_limit']) ? (int)$_POST['filter_registrasi_limit'] : 5;

/* =========================
   FILTER RESEP
   ========================= */
$filter_resep_status = $_POST['filter_resep_status'] ?? 'semua';
$filter_resep_tanggal_dari = $_POST['filter_resep_tanggal_dari'] ?? '';
$filter_resep_tanggal_sampai = $_POST['filter_resep_tanggal_sampai'] ?? '';
$filter_resep_limit = isset($_POST['filter_resep_limit']) ? (int)$_POST['filter_resep_limit'] : 0;
$filter_resep_urutan = $_POST['filter_resep_urutan'] ?? 'DESC';

/* =========================
   CEK APAKAH ADA YANG DIPILIH
   ========================= */
$ada_pilihan = !empty($sep_ids) || !empty($resep_ids) || !empty($berkas_ids) || 
               !empty($resume_ids) || !empty($echo_ids) || !empty($eeg_ids) || 
               !empty($nota_ids) || !empty($penyerahan_ids) || !empty($hba1c_ids) || 
               !empty($mmse_ids) || !empty($ekg_ids);

if (!$ada_pilihan) {
    ?>
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Peringatan</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
        <style>
            .modal-backdrop-custom {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                z-index: 1040;
            }
            .modal-custom {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 1050;
                width: 90%;
                max-width: 500px;
            }
        </style>
    </head>
    <body>
        <div class='modal-backdrop-custom'></div>
        <div class='modal-custom'>
            <div class='modal-content'>
                <div class='modal-header bg-danger text-white'>
                    <h5 class='modal-title'>
                        <i class='bi bi-exclamation-triangle-fill'></i> Tidak Ada Data Yang Dipilih
                    </h5>
                </div>
                <div class='modal-body text-center py-4'>
                    <i class='bi bi-inbox' style='font-size: 4rem; color: #dc3545;'></i>
                    <p class='mt-3 mb-0' style='font-size: 16px; color: #666;'>
                        Silakan pilih minimal satu data untuk dicetak.
                    </p>
                </div>
                <div class='modal-footer justify-content-center'>
                    <a href='previewriwayat.php?no_rkm_medis=<?= htmlspecialchars($no_rkm_medis) ?>' class='btn btn-primary btn-lg'>
                        <i class='bi bi-arrow-left'></i> Kembali ke Preview
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/* =========================
   AMBIL DATA PASIEN
   ========================= */
$q_pasien = $koneksi->query("
    SELECT p.*
    FROM pasien p
    WHERE p.no_rkm_medis = '$no_rkm_medis'
    LIMIT 1
");
$pasien = $q_pasien->fetch_assoc();

/* =========================
   AMBIL DATA REGISTRASI
   ========================= */
$q_reg = $koneksi->query("
    SELECT r.*, pl.nm_poli, d.nm_dokter, pj.png_jawab AS cara_bayar
    FROM reg_periksa r
    LEFT JOIN poliklinik pl ON pl.kd_poli = r.kd_poli
    LEFT JOIN dokter d ON d.kd_dokter = r.kd_dokter
    LEFT JOIN penjab pj ON pj.kd_pj = r.kd_pj
    WHERE r.no_rawat = '$no_rawat'
    LIMIT 1
");
$registrasi = $q_reg->fetch_assoc();

/* =========================
   DOMPDF
   ========================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

@page {
    margin: 15mm 20mm;
}

body { 
    font-family: 'Helvetica', 'Arial', sans-serif;
    font-size: 10px;
    line-height: 1.2;
    color: #333;
    padding: 15px 20px;
    margin: 0 auto;
    max-width: 210mm;
}

h4 { 
    font-size: 13px;
    font-weight: bold;
    margin: 12px 0 6px 0;
    padding-bottom: 4px;
    border-bottom: 2px solid #333;
    text-transform: uppercase;
    color: #000;
}

h5 {
    font-size: 11px;
    font-weight: bold;
    margin: 8px 0 5px 0;
    color: #444;
}

table { 
    border-collapse: collapse; 
    width: 100%;
    margin-bottom: 8px;
}

th, td { 
    border: 1px solid #666;
    padding: 4px 6px;
    vertical-align: top;
    font-size: 10px;
    line-height: 1.2;
}

th { 
    background: #e8e8e8;
    font-weight: bold;
    text-align: left;
    color: #000;
}

td {
    background: #fff;
}

tr:nth-child(even) td {
    background: #f9f9f9;
}

p {
    margin: 5px 0;
    line-height: 1.3;
}

em {
    color: #666;
    font-style: italic;
}

strong {
    font-weight: bold;
    color: #000;
}

/* Styling untuk tabel berkas */
.tbl-berkas { 
    width: 100%; 
    border-collapse: collapse;
    margin-bottom: 8px;
    page-break-inside: avoid;
}

.tbl-berkas th, .tbl-berkas td { 
    border: 1px solid #666;
    padding: 5px 6px;
    vertical-align: top;
}

.tbl-berkas th { 
    background: #e8e8e8;
    font-weight: bold;
    text-align: center;
}

/* Styling untuk tabel resume */
.tbl-resume {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-size: 9px;
    page-break-inside: avoid;
}

.tbl-resume thead th {
    background: #d0d0d0;
    border: 1px solid #666;
    padding: 4px 3px;
    font-size: 9px;
    font-weight: bold;
    text-align: center;
    vertical-align: middle;
}

.tbl-resume tbody td {
    border: 1px solid #999;
    padding: 3px 4px;
    vertical-align: top;
    word-wrap: break-word;
    line-height: 1.2;
}

.tbl-resume tbody tr:hover {
    background: #f5f5f5;
}

.berkas-preview-img {
    max-width: 260px;
    max-height: 180px;
    object-fit: contain;
    display: block;
    margin: 5px auto;
    border: 1px solid #dddddd;
}

/* Page break */
.page-break {
    page-break-after: always;
}

/* Spacing helper */
.mb-10 { margin-bottom: 10px; }
.mb-15 { margin-bottom: 15px; }
.mt-10 { margin-top: 10px; }
.mt-15 { margin-top: 15px; }

/* Kop Berkas */
.kop-berkas {
    width: 100%;
    margin-bottom: 20px;
}
.kop-berkas table {
    width: 100%;
    border-collapse: collapse;
    border: none;
}
.kop-berkas table td {
    border: none;
}
.kop-berkas .logo-cell {
    width: 90px;
    vertical-align: top;
}
.kop-berkas .logo-cell img {
    width: 70px;
    height: auto;
    display: block;
    margin-top: 2px;
}
.kop-berkas .title-cell {
    text-align: center;
    vertical-align: top;
    line-height: 1.15;
}
.kop-berkas .title-cell .t1 {
    font-size: 20px;
    font-weight: normal;
    letter-spacing: .5px;
    white-space: nowrap;
}
.kop-berkas .title-cell .t2,
.kop-berkas .title-cell .t3,
.kop-berkas .title-cell .t4 {
    font-size: 12px;
}
.kop-berkas .spacer-cell {
    width: 90px;
}
</style>
</head>
<body>

<!-- =======================
     KOP BERKAS
     ======================= -->
<div class="kop-berkas">
    <table>
        <tr>
            <td class="logo-cell">
                <img src="http://localhost/webapps/hasilcetakclaimobat/pages/assets/logorsar.png" alt="logo">
            </td>
            <td class="title-cell">
                <div class="t1">RUMAH SAKIT UMUM DAERAH dr. ABDOER RAHEM</div>
                <div class="t2">Jl. Anggrek No. 68, Kelurahan. Patokan , Kecamatan. Situbondo,</div>
                <div class="t3">0338-671028</div>
                <div class="t4">E-mail : rsu.situbondo@yahoo.com</div>
            </td>
            <td class="spacer-cell"></td>
        </tr>
    </table>
</div>

<!-- =======================
     1. DATA PASIEN
     ======================= -->
<h4>1. Data Pasien</h4>

<table width="100%" cellpadding="4" cellspacing="0" border="1">
  <tr style="background:#eee">
    <th width="5%">No.</th>
    <th width="30%">Field</th>
    <th width="3%">:</th>
    <th>Isi</th>
  </tr>

<?php
if (empty($pasien)) {
    echo "<tr>
            <td colspan='4' align='center'>
              Tidak ada data pasien
            </td>
          </tr>";
} else {

    $jk = $pasien['jk'] ?? '';
    $jenis_kelamin = ($jk === 'L') ? 'Laki-Laki' : (($jk === 'P') ? 'Perempuan' : '-');

    $no = 1;
    $fields = [
        'No. Rekam Medis'       => $pasien['no_rkm_medis'] ?? '-',
        'Nama Pasien'          => $pasien['nm_pasien'] ?? '-',
        'Alamat'               => $pasien['alamat'] ?? '-',
        'Jenis Kelamin'        => $jenis_kelamin,
        'Tempat, Tgl Lahir'    => ($pasien['tmp_lahir'] ?? '-') . ', ' . ($pasien['tgl_lahir'] ?? '-'),
        'Ibu Kandung'          => $pasien['nm_ibu'] ?? '-',
        'Golongan Darah'       => $pasien['gol_darah'] ?? '-',
        'Status Nikah'         => $pasien['stts_nikah'] ?? '-',
        'Agama'                => $pasien['agama'] ?? '-',
        'Pendidikan Terakhir'  => $pasien['pnd'] ?? '-',
        'Bahasa'               => $pasien['nama_bahasa'] ?? '-',
        'Cacat Fisik'          => $pasien['nama_cacat'] ?? '-',
    ];

    foreach ($fields as $field => $value) {
        echo "<tr>
                <td>{$no}</td>
                <td>{$field}</td>
                <td>:</td>
                <td>{$value}</td>
              </tr>";
        $no++;
    }
}
?>
</table>

<!-- =======================
     2. DATA REGISTRASI
     ======================= -->
<h4>2. Data Registrasi</h4>

<?php
// Terapkan filter registrasi pada query
$limitSql_registrasi = '';
if ($filter_registrasi_mode === 'last' && $filter_registrasi_limit > 0) {
    $limitSql_registrasi = "LIMIT $filter_registrasi_limit";
}

$query_rawat = $koneksi->query("
    SELECT 
        rp.no_rawat,
        rp.tgl_registrasi,
        p.nm_poli,
        rp.stts
    FROM reg_periksa rp
    LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    ORDER BY rp.no_rawat DESC
    $limitSql_registrasi
");

if (!$query_rawat || $query_rawat->num_rows == 0) {

    echo "<p><em>Tidak ada data registrasi</em></p>";

} else {

    while ($row_rawat = $query_rawat->fetch_assoc()) {

        $no_rawat_loop = $row_rawat['no_rawat']; // ✅ DIPISAH
        $poli     = $row_rawat['nm_poli'] ?? '-';
        $status   = $row_rawat['stts'] ?? '-';

        $res_detail = $koneksi->query("
    SELECT
        rp.no_rawat,
        rp.no_reg,
        rp.tgl_registrasi,
        rp.umurdaftar,
        p.nm_poli,
        d.nm_dokter,
        pj.png_jawab AS cara_bayar,
        rp.p_jawab,
        rp.almt_pj,
        rp.hubunganpj,
        rp.stts,
        (
            SELECT CONCAT(
                CONVERT(d2.nm_dokter USING utf8mb4),
                ' → Poli ',
                CONVERT(p2.nm_poli USING utf8mb4)
            )
            FROM rujukan_internal_poli ri
            LEFT JOIN dokter d2 ON ri.kd_dokter = d2.kd_dokter
            LEFT JOIN poliklinik p2 ON ri.kd_poli = p2.kd_poli
            WHERE ri.no_rawat = rp.no_rawat
            LIMIT 1
            ) AS rujukan_internal,
        (
          SELECT rj.rujuk_ke
          FROM rujuk rj
          WHERE rj.no_rawat = rp.no_rawat
          LIMIT 1
        ) AS rujukan_eksternal,

        (
          SELECT CONCAT(ki.kd_kamar,' - ',b.nm_bangsal)
          FROM kamar_inap ki
          LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
          LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
          WHERE ki.no_rawat = rp.no_rawat
          LIMIT 1
        ) AS rawat_inap

    FROM reg_periksa rp
    LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
    LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    WHERE rp.no_rawat = '$no_rawat_loop'
");

/* ======================
   CEK SQL ERROR
   ====================== */
if ($res_detail === false) {
    echo "<p style='color:red'>
            <strong>SQL ERROR:</strong><br>
            {$koneksi->error}
          </p>";
    continue; // LANJUT no_rawat berikutnya
}

$d = $res_detail->fetch_assoc();

if (!$d) {
    echo "<p><em>Data registrasi tidak ditemukan.</em></p>";
    continue;
}
        //echo "<strong>{$no_rawat} | {$poli} | {$status}</strong>";

        echo "<table width='100%' cellpadding='4' cellspacing='0' border='1' style='margin-top:5px;margin-bottom:15px'>
                <tr style='background:#eee'>
                  <th width='5%'>No</th>
                  <th width='30%'>Field</th>
                  <th width='3%'>:</th>
                  <th>Isi</th>
                </tr>";

        $no = 1;
        $rows = [
            'No. Rawat'          => $d['no_rawat'],
            'No. Registrasi'     => $d['no_reg'],
            'Tanggal Registrasi' => $d['tgl_registrasi'],
            'Umur Saat Daftar'   => ($d['umurdaftar'] ? $d['umurdaftar'].' th' : '-'),
            'Unit/Poliklinik'    => $d['nm_poli'],
            'Dokter Poli'        => $d['nm_dokter'],
            'Cara Bayar'         => $d['cara_bayar'],
            'Penanggung Jawab'   => $d['p_jawab'],
            'Alamat P.J.'        => $d['almt_pj'],
            'Hubungan P.J.'      => $d['hubunganpj'],
            'Status'             => $d['stts'],
            'Rujukan Internal'   => $d['rujukan_internal'] ?: '-',
            'Rujukan Eksternal'  => $d['rujukan_eksternal'] ?: '-',
            'Rawat Inap'         => $d['rawat_inap'] ?: '-',
        ];

        foreach ($rows as $label => $value) {
            echo "<tr>
                    <td>{$no}</td>
                    <td>{$label}</td>
                    <td>:</td>
                    <td>{$value}</td>
                  </tr>";
            $no++;
        }
        echo "</table>";
    }
}
?>
<div style="page-break-after:always;"></div>

<!-- =======================
     TEMPLATE SEP
     ======================= -->
<h4 style="margin-bottom:4px;">3. SEP</h4>
<style>
  /* HILANGKAN JARAK ATAS */
  h4 {
    margin-top: 5px !important;
    margin-bottom: 6px !important;
  }

  /* Wadah utama SEP */
  .sep-card{
    margin: 4px 0 10px 0;
    padding: 6px;
    border: 1px solid #ccc;
    page-break-inside: avoid;
    break-inside: avoid;
  }

  /* gambar sep */
  .sep-img{
    display: block;
    width: 100%;
    max-width: 720px;
    height: auto;
    max-height: 230mm;
    margin: 0 auto;
  }

  /* cegah browser lompat halaman aneh */
  @media print {
    body { margin: 10mm; }

    .sep-card {
      page-break-inside: avoid;
      break-inside: avoid;
    }
  }
</style>

<?php
// === fungsi konversi PDF -> PNG (halaman 1) jadi DataURI untuk Dompdf
function pdfUrlToPngDataUri($pdfUrl) {

    // Ghostscript path (punya kamu)
    putenv('MAGICK_GHOSTSCRIPT_PATH=C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe');
    putenv('PATH=C:\Program Files\gs\gs10.06.0\bin;' . getenv('PATH'));

    if (!extension_loaded('imagick')) {
        return ['ok'=>false, 'err'=>'Imagick belum aktif'];
    }

    // ====== 1) Download PDF ======
    $tmpPdf = tempnam(sys_get_temp_dir(), 'sep_') . '.pdf';

    $bin = @file_get_contents($pdfUrl);

    // fallback: kalau allow_url_fopen mati / akses URL gagal, pakai cURL
    if ($bin === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($pdfUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
            ]);
            $bin = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($bin === false || $http >= 400) {
                $last = error_get_last();
                return ['ok'=>false, 'err'=>'Gagal download PDF. HTTP='.$http.' CURL='.$cerr.' PHPerr='.( $last['message'] ?? '-' )];
            }
        } else {
            $last = error_get_last();
            return ['ok'=>false, 'err'=>'file_get_contents gagal & cURL tidak tersedia. PHPerr='.( $last['message'] ?? '-' )];
        }
    }

    file_put_contents($tmpPdf, $bin);

    // ====== 2) Convert PDF -> PNG (halaman 1) ======
    try {
        $im = new Imagick();
        $im->setResolution(160, 160);
        $im->readImage($tmpPdf . '[0]');
        $im->setImageFormat('png');

        $png = $im->getImageBlob();

        $im->clear();
        $im->destroy();
        @unlink($tmpPdf);

        return ['ok'=>true, 'data'=>'data:image/png;base64,' . base64_encode($png)];
    } catch (Exception $e) {
        @unlink($tmpPdf);
        return ['ok'=>false, 'err'=>'Convert gagal: '.$e->getMessage()];
    }
}

$qSep = $koneksi->query("
    SELECT rp.no_rawat, bdp.lokasi_file
    FROM reg_periksa rp
    INNER JOIN berkas_digital_perawatan bdp
      ON bdp.no_rawat = rp.no_rawat
     AND bdp.kode = '001'
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    ORDER BY rp.no_rawat DESC
");

$BASE_BERKASRAWAT = "http://192.168.0.100/webapps/berkasrawat/";

if (!$qSep || $qSep->num_rows == 0) {
    echo '<p><em>Tidak ada data SEP.</em></p>';
} else {
    while ($sep = $qSep->fetch_assoc()) {
        $file_db = $sep['lokasi_file'] ?? '';
        if (!$file_db) continue;

        $url_sep = $BASE_BERKASRAWAT . $file_db;
        $ext     = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));

        echo '<div class="sep-card">';
        echo '<div style="font-weight:bold; margin-bottom:8px;">No Rawat: '.htmlspecialchars($sep['no_rawat']).'</div>';

        // === kalau file gambar: langsung cetak
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            echo '<img src="'.$url_sep.'" class="sep-img">';
        }

        // === kalau PDF: konversi jadi gambar dulu
        elseif ($ext === 'pdf') {
            $res = pdfUrlToPngDataUri($url_sep);

            if ($res['ok']) {
                echo '<img src="'.$res['data'].'" class="sep-img">';
            } else {
                echo '<div><strong>Gagal tampilkan PDF.</strong></div>';
                echo '<div style="font-size:12px;">Imagick: '.(extension_loaded("imagick") ? "ON" : "OFF").'</div>';
                echo '<div style="font-size:12px;">File: '.htmlspecialchars(basename($file_db)).'</div>';
                echo '<pre style="font-size:12px; white-space:pre-wrap;">'.$res['err'].'</pre>';
            }

        }
        else {
            echo '<div><em>Format tidak didukung untuk cetak:</em> '.htmlspecialchars(basename($file_db)).'</div>';
        }

        echo '</div>';
    }
}
?>

<?php if (!empty($eeg_ids)): ?>
<!-- =======================
     4. ELECTROENCEPHALOGRAPHY (EEG)
     ======================= -->
<h4>4. Electroencephalography (EEG)</h4>

<?php
$q_eeg = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'EEG'
    ORDER BY b.tgl_upload DESC
");

if ($q_eeg && $q_eeg->num_rows > 0) {
?>
<table class="tbl-berkas" style="page-break-inside:avoid;">
  <tr>
    <th width="5%">No</th>
    <th width="20%">No RM</th>
    <th width="20%">Tanggal Upload</th>
    <th>Preview</th>
  </tr>
  <?php
  $no = 1;
  while ($row = $q_eeg->fetch_assoc()) {
      // Filter berdasarkan checkbox
      if (!empty($eeg_ids) && !in_array($row['id'], $eeg_ids)) {
          continue;
      }
      
      $file_db = $row['lokasi_file'] ?? '';
      if (!$file_db) continue;

      $url_file = "http://localhost/webapps/" . $file_db;
      $ext = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
  ?>
  <tr>
    <td style="text-align:center;"><?= $no++ ?></td>
    <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></td>
    <td style="text-align:center;">
      <?php
      if (in_array($ext, ['jpg','jpeg','png','webp'])) {
          echo '<img class="berkas-preview-img" src="'.$url_file.'" alt="EEG">';
      }
      elseif ($ext === 'pdf') {
          $res = pdfUrlToPngDataUri($url_file);
          if ($res['ok']) {
              echo '<img class="berkas-preview-img" src="'.$res['data'].'" alt="EEG PDF">';
          } else {
              echo '<em>Gagal tampilkan PDF: '.htmlspecialchars(basename($file_db)).'</em>';
          }
      } else {
          echo '<em>Format tidak didukung: '.htmlspecialchars(basename($file_db)).'</em>';
      }
      ?>
    </td>
  </tr>
  <?php } ?>
</table>
<?php
} else {
    echo '<p><em>Tidak ada data EEG untuk pasien ini.</em></p>';
}
?>
<?php endif; // end EEG ?>

<?php if (!empty($ekg_ids)): ?>
<!-- =======================
     5. ELEKTROKARDIOGRAM (EKG)
     ======================= -->
<h4>5. Elektrokardiogram (EKG)</h4>

<?php
$q_ekg = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'EKG'
    ORDER BY b.tgl_upload DESC
");

if ($q_ekg && $q_ekg->num_rows > 0) {
?>
<table class="tbl-berkas" style="page-break-inside:avoid;">
  <tr>
    <th width="5%">No</th>
    <th width="20%">No RM</th>
    <th width="20%">Tanggal Upload</th>
    <th>Preview</th>
  </tr>
  <?php
  $no = 1;
  while ($row = $q_ekg->fetch_assoc()) {
      // Filter berdasarkan checkbox
      if (!empty($ekg_ids) && !in_array($row['id'], $ekg_ids)) {
          continue;
      }
      
      $file_db = $row['lokasi_file'] ?? '';
      if (!$file_db) continue;

      $url_file = "http://localhost/webapps/" . $file_db;
      $ext = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
  ?>
  <tr>
    <td style="text-align:center;"><?= $no++ ?></td>
    <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></td>
    <td style="text-align:center;">
      <?php
      if (in_array($ext, ['jpg','jpeg','png','webp'])) {
          echo '<img class="berkas-preview-img" src="'.$url_file.'" alt="EKG">';
      }
      elseif ($ext === 'pdf') {
          $res = pdfUrlToPngDataUri($url_file);
          if ($res['ok']) {
              echo '<img class="berkas-preview-img" src="'.$res['data'].'" alt="EKG PDF">';
          } else {
              echo '<em>Gagal tampilkan PDF: '.htmlspecialchars(basename($file_db)).'</em>';
          }
      } else {
          echo '<em>Format tidak didukung: '.htmlspecialchars(basename($file_db)).'</em>';
      }
      ?>
    </td>
  </tr>
  <?php } ?>
</table>
<?php
} else {
    echo '<p><em>Tidak ada data EKG untuk pasien ini.</em></p>';
}
?>
<?php endif; // end EKG ?>

<?php if (!empty($hba1c_ids)): ?>
<!-- =======================
     6. HEMOGLOBIN A1C (HBA1C)
     ======================= -->
<h4>6. Hemoglobin A1c (HbA1c)</h4>

<?php
$q_hba1c = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'HBA1C'
    ORDER BY b.tgl_upload DESC
");

if ($q_hba1c && $q_hba1c->num_rows > 0) {
?>
<table class="tbl-berkas" style="page-break-inside:avoid;">
  <tr>
    <th width="5%">No</th>
    <th width="20%">No RM</th>
    <th width="20%">Tanggal Upload</th>
    <th>Preview</th>
  </tr>
  <?php
  $no = 1;
  while ($row = $q_hba1c->fetch_assoc()) {
      // Filter berdasarkan checkbox
      if (!empty($hba1c_ids) && !in_array($row['id'], $hba1c_ids)) {
          continue;
      }
      
      $file_db = $row['lokasi_file'] ?? '';
      if (!$file_db) continue;

      $url_file = "http://localhost/webapps/" . $file_db;
      $ext = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
  ?>
  <tr>
    <td style="text-align:center;"><?= $no++ ?></td>
    <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></td>
    <td style="text-align:center;">
      <?php
      if (in_array($ext, ['jpg','jpeg','png','webp'])) {
          echo '<img class="berkas-preview-img" src="'.$url_file.'" alt="HbA1c">';
      }
      elseif ($ext === 'pdf') {
          $res = pdfUrlToPngDataUri($url_file);
          if ($res['ok']) {
              echo '<img class="berkas-preview-img" src="'.$res['data'].'" alt="HbA1c PDF">';
          } else {
              echo '<em>Gagal tampilkan PDF: '.htmlspecialchars(basename($file_db)).'</em>';
          }
      } else {
          echo '<em>Format tidak didukung: '.htmlspecialchars(basename($file_db)).'</em>';
      }
      ?>
    </td>
  </tr>
  <?php } ?>
</table>
<?php
} else {
    echo '<p><em>Tidak ada data HbA1c untuk pasien ini.</em></p>';
}
?>
<?php endif; // end HbA1c ?>

<?php if (!empty($mmse_ids)): ?>
<!-- =======================
     7. MINI-MENTAL STATE EXAMINATION (MMSE)
     ======================= -->
<h4>7. Mini-Mental State Examination (MMSE)</h4>

<?php
$q_mmse = $koneksi->query("
    SELECT 
        b.id,
        b.no_rkm_medis,
        b.kode_berkas,
        b.nama_berkas,
        b.lokasi_file,
        b.tgl_upload
    FROM berkas_digital_apotek b
    WHERE b.no_rkm_medis = '$no_rkm_medis'
      AND b.kode_berkas = 'MMSE'
    ORDER BY b.tgl_upload DESC
");

if ($q_mmse && $q_mmse->num_rows > 0) {
?>
<table class="tbl-berkas" style="page-break-inside:avoid;">
  <tr>
    <th width="5%">No</th>
    <th width="20%">No RM</th>
    <th width="20%">Tanggal Upload</th>
    <th>Preview</th>
  </tr>
  <?php
  $no = 1;
  while ($row = $q_mmse->fetch_assoc()) {
      // Filter berdasarkan checkbox
      if (!empty($mmse_ids) && !in_array($row['id'], $mmse_ids)) {
          continue;
      }
      
      $file_db = $row['lokasi_file'] ?? '';
      if (!$file_db) continue;

      $url_file = "http://localhost/webapps/" . $file_db;
      $ext = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
  ?>
  <tr>
    <td style="text-align:center;"><?= $no++ ?></td>
    <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></td>
    <td style="text-align:center;">
      <?php
      if (in_array($ext, ['jpg','jpeg','png','webp'])) {
          echo '<img class="berkas-preview-img" src="'.$url_file.'" alt="MMSE">';
      }
      elseif ($ext === 'pdf') {
          $res = pdfUrlToPngDataUri($url_file);
          if ($res['ok']) {
              echo '<img class="berkas-preview-img" src="'.$res['data'].'" alt="MMSE PDF">';
          } else {
              echo '<em>Gagal tampilkan PDF: '.htmlspecialchars(basename($file_db)).'</em>';
          }
      } else {
          echo '<em>Format tidak didukung: '.htmlspecialchars(basename($file_db)).'</em>';
      }
      ?>
    </td>
  </tr>
  <?php } ?>
</table>
<?php
} else {
    echo '<p><em>Tidak ada data MMSE untuk pasien ini.</em></p>';
}
?>
<?php endif; // end MMSE ?>

<div style="page-break-after:always;"></div>

<?php if (!empty($resep_ids)): ?>
<!-- =======================
     8. TEMPLATE RESEP
     ======================= -->
<h4>8. RESEP</h4>
<?php
// WAJIB: pastikan file ini ADA:
// D:\xampp\htdocs\webapps\hasilcetakclaimobat\pages\phpqrcode\qrlib.php
require_once __DIR__ . '/../phpqrcode/qrlib.php';

// Buat kondisi WHERE tambahan untuk filter status
$whereResepStatus = '';
if ($filter_resep_status === 'ralan') {
    $whereResepStatus = "AND ro.status = 'ralan'";
} elseif ($filter_resep_status === 'ranap') {
    $whereResepStatus = "AND ro.status = 'ranap'";
}
// jika 'semua', tidak ada filter tambahan

// Buat kondisi WHERE untuk filter tanggal
$whereResepTanggal = '';
if (!empty($filter_resep_tanggal_dari)) {
    $whereResepTanggal .= " AND DATE(ro.tgl_peresepan) >= '$filter_resep_tanggal_dari'";
}
if (!empty($filter_resep_tanggal_sampai)) {
    $whereResepTanggal .= " AND DATE(ro.tgl_peresepan) <= '$filter_resep_tanggal_sampai'";
}

// Buat LIMIT untuk filter jumlah terbaru
$limitResep = '';
if ($filter_resep_limit > 0) {
    $limitResep = "LIMIT $filter_resep_limit";
}

// Validasi urutan (hanya terima DESC atau ASC)
$orderResep = ($filter_resep_urutan === 'ASC') ? 'ASC' : 'DESC';

// ambil semua resep pasien
$qResep = $koneksi->query("
    SELECT
        ro.no_resep,
        ro.no_rawat,
        DATE(ro.tgl_peresepan) AS tanggal_resep,
        p.nm_pasien,
        rp.no_rkm_medis,
        rp.kd_dokter, 
        pj.png_jawab AS penanggung,
        d.nm_dokter
    FROM resep_obat ro
    INNER JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
    INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
    LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    $whereResepStatus
    $whereResepTanggal
    ORDER BY ro.no_resep $orderResep
    $limitResep
");
?>

<?php if (!$qResep || $qResep->num_rows == 0): ?>
  <p><em>Tidak ada data resep obat.</em></p>
<?php else: ?>

<style>
/* ===== SCOPED: hanya untuk resep ===== */
.resep-wrap{ font-family: Arial, Helvetica, sans-serif; font-size:12px; color:#000; width:100%; }
.resep-header{ width:100%; border-collapse:collapse; }
.resep-header td{ vertical-align:top; }
.resep-logo{ width:90px; }
.resep-logo img{ width:70px; height:auto; display:block; margin-top:2px; }

.resep-title{
  text-align:center;
  line-height:1.15;
  white-space:nowrap;      /* paksa 1 baris */
}
.resep-title .t1{
  font-size:20px;
  font-weight:normal;
  letter-spacing:.5px;
  white-space:nowrap;      /* paksa 1 baris */
}
.resep-title .t2,.resep-title .t3,.resep-title .t4{ font-size:12px; }

.resep-line{
  height:1px;
  border-top:2px solid #333;
  border-bottom:1px solid #333;
  margin:3px 0 10px 0;
}


/* identitas */
.resep-info{ width:100%; border-collapse:collapse; margin-bottom:6px; }
.resep-info td{ padding:1px 0; }
.resep-info .lbl{ width:140px; }
.resep-info .sep{ width:12px; text-align:center; }
.resep-info .val{ width:auto; }

/* garis tipis pemisah (mirip foto) */
.resep-divider{ border-top:1px solid #333; margin:8px 0; }

/* judul RESEP tengah */
.resep-judul{
  text-align:center;
  font-size:18px;
  font-weight:bold;
  letter-spacing:1px;
  margin:4px 0 6px 0;
}

/* daftar item resep */
.resep-list{ width:100%; border-collapse:collapse; }
.resep-list td{ padding:2px 0; vertical-align:top; }

.col-r{ width:40px; }              /* "R/" */
.col-nama{ width:auto; padding-left:10px; }
.col-jml{ width:140px; text-align:right; white-space:nowrap; } /* "16 Kaplet" */

/* baris aturan pakai */
.aturan-row td{ padding-top:0; padding-bottom:6px; }
.aturan-wrap{ padding-left:50px; } /* geser biar sejajar dengan nama obat */
.aturan-s{ width:22px; display:inline-block; }
.dash{
  display:inline-block;
  width:60px;
  border-bottom:2px solid #333;
  transform: translateY(-3px);
  margin:0 8px 0 6px;
}
.aturan-text{ display:inline-block; }
/* garis putus-putus sejajar dengan S */
.s-dash{
  display:inline-block;
  width:80px;                 /* panjang putus-putus (boleh kamu adjust) */
  border-bottom:2px dashed #333;
  margin:0 8px 0 6px;
  transform: translateY(-3px);
}

/* garis panjang di baris bawah S (full sampai kanan) */
.line-full{
  border-bottom:2px solid #333;
  height:0;
  width:100%;
  margin-top:6px;
  position: relative;
  top: -5px;   /* NAikin garis 3px */
}


/* cell khusus untuk garis panjang biar mulai dari posisi yang pas */
.line-cell{
  padding-left:50px;          /* sejajar sama aturan-wrap */
  padding-right:0;
}

/* garis bawah tiap item (seperti foto) */
.item-line{ border-bottom:1px solid #333; height:1px; }

/* footer bawah (dibuat rata kanan seperti contoh gambar) */
/* blok kanan */
/* paksa isi blok kanan benar-benar rata tengah */
.resep-bawah{
  width:260px;
  margin-left:auto;
  text-align:center;
}

.resep-bawah *{
  text-align:center !important;
}

.resep-qr{
  display:flex;
  justify-content:center;   /* QR tepat tengah */
}

.resep-qr img{
  display:block;
  margin:0 auto;            /* jaga-jaga kalau flex tidak kebaca */
  width:90px;
  height:90px;
}
.resep-tgl, .resep-dokter, .resep-qr, .resep-serah{
  display:block;
  width:100%;
  text-align:center !important;
}


/* nama dokter */
.resep-dokter{
  margin-top:6px;
  font-size:13px;
}

/* diserahkan */
.resep-serah{
  margin-top:6px;
  font-size:13px;
}

/* FOTO BUKTI PENYERAHAN */
.serah-img{
  width:180px;      /* atur kecil-besar */
  height:auto;
  max-height:130px;
  object-fit:cover;
}

/* ===== HAPUS BORDER / KOTAK KHUSUS AREA RESEP SAJA ===== */
.resep-wrap table,
.resep-wrap tr,
.resep-wrap td,
.resep-wrap th{
  border: none !important;
}
</style>

<?php while ($r = $qResep->fetch_assoc()): ?>
  <?php
    // Filter checkbox
    if (!empty($resep_ids) && !in_array($r['no_resep'], $resep_ids)) continue;

    // fallback tanggal kalau null
    $tgl_resep = $r['tanggal_resep'] ?: date('Y-m-d');

    // === QR TEXT (yang kamu mau, template & kalimat sudah bener katanya) ===
    $qr_text =
      "RSUD dr. Abdoer Rahem\n" .
      "Nama Dokter: " . ($r['nm_dokter'] ?? '-') . "\n" .
      "No Resep: " . ($r['no_resep'] ?? '-') . "\n" .
      "Tanggal: " . $tgl_resep;

    // === GENERATE QR BASE64 (PASTI MUNCUL, TANPA INTERNET) ===
    ob_start();
    QRcode::png($qr_text, null, QR_ECLEVEL_L, 4, 1); // size 4, margin 1
    $qr_base64 = base64_encode(ob_get_clean());
    // === AMBIL FOTO BUKTI PENYERAHAN (BERDASARKAN NO RESEP) ===
$base64_serah = null;

// ===== APPLY FILTER RESEP KE PENYERAHAN OBAT =====
$whereSerahStatus = '';
if ($filter_resep_status && $filter_resep_status !== 'semua') {
    $whereSerahStatus = " AND ro.status = '" . ($filter_resep_status === 'ralan' ? 'Ralan' : 'Ranap') . "'";
}

$whereSerahTanggal = '';
if (!empty($filter_resep_tanggal_dari) && !empty($filter_resep_tanggal_sampai)) {
    $whereSerahTanggal = " AND DATE(ro.tgl_peresepan) BETWEEN '$filter_resep_tanggal_dari' AND '$filter_resep_tanggal_sampai'";
}

$orderSerah = ($filter_resep_urutan === 'ASC') ? 'ASC' : 'DESC';
$limitSerah = '';
if (!empty($filter_resep_limit) && intval($filter_resep_limit) > 0) {
    $limitSerah = " LIMIT " . intval($filter_resep_limit);
}

$qSerah = $koneksi->query("
  SELECT photo 
  FROM bukti_penyerahan_resep_obat bpo
  INNER JOIN resep_obat ro ON bpo.no_resep = ro.no_resep
  WHERE bpo.no_resep = '{$r['no_resep']}'
  $whereSerahStatus
  $whereSerahTanggal
  ORDER BY bpo.no_resep $orderSerah
  $limitSerah
");

if ($qSerah && $qSerah->num_rows > 0) {
    $rowSerah = $qSerah->fetch_assoc();
    $foto_path = $_SERVER['DOCUMENT_ROOT'] . "/webapps/penyerahanresep/pages/upload/" . basename($rowSerah['photo']);

    if (file_exists($foto_path)) {
        $ext = strtolower(pathinfo($foto_path, PATHINFO_EXTENSION));
        $base64_serah = 'data:image/'.$ext.';base64,' . base64_encode(file_get_contents($foto_path));
    }
}


    // ambil detail obat per resep
    $qDetail = $koneksi->query("
        SELECT
            b.nama_brng,
            IFNULL(b.kode_sat,'') AS kode_sat,
            rd.jml,
            rd.aturan_pakai
        FROM resep_dokter rd
        INNER JOIN databarang b ON rd.kode_brng = b.kode_brng
        WHERE rd.no_resep = '{$r['no_resep']}'
        ORDER BY b.nama_brng
    ");
  ?>

  <div class="resep-wrap">

    <!-- KOP -->
    <table class="resep-header">
      <tr>
        <td class="resep-logo">
          <img src="http://localhost/webapps/hasilcetakclaimobat/pages/assets/logorsar.png" alt="logo">
        </td>
        <td class="resep-title">
          <div class="t1">RUMAH SAKIT UMUM DAERAH dr. ABDOER RAHEM</div>
          <div class="t2">Jl. Anggrek No. 68, Kelurahan. Patokan , Kecamatan. Situbondo,</div>
          <div class="t3">0338-671028</div>
          <div class="t4">E-mail : rsu.situbondo@yahoo.com</div>
        </td>
        <td style="width:90px;"></td>
      </tr>
    </table>

    <div class="resep-line"></div>

    <!-- IDENTITAS (sesuai foto) -->
    <table class="resep-info">
      <tr><td class="lbl">Nama Pasien</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($r['nm_pasien']) ?></td></tr>
      <tr><td class="lbl">No. R.M.</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($r['no_rkm_medis']) ?></td></tr>
      <tr><td class="lbl">No. Rawat</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($r['no_rawat']) ?></td></tr>
      <tr><td class="lbl">Jenis Pasien</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($r['penanggung'] ?: '-') ?></td></tr>
      <tr><td class="lbl">Pemberi Resep</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($r['nm_dokter'] ?: '-') ?></td></tr>
      <tr><td class="lbl">No. Resep</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($r['no_resep']) ?></td></tr>
    </table>
    <div class="resep-line"></div>
    <div class="resep-judul">RESEP</div>

    <!-- LIST RESEP -->
    <table class="resep-list">
      <?php if ($qDetail && $qDetail->num_rows > 0): ?>
        <?php while ($d = $qDetail->fetch_assoc()): ?>
          <?php
            $nama = strtoupper($d['nama_brng']);
            $sat  = trim($d['kode_sat']);
            $jml  = rtrim(rtrim(number_format((float)$d['jml'], 1, '.', ''), '0'), '.');
            $qtyText = $jml . ($sat ? " " . $sat : "");
            $aturan = trim($d['aturan_pakai'] ?? '');
          ?>
          <tr>
            <td class="col-r">R/</td>
            <td class="col-nama"><?= htmlspecialchars($nama) ?></td>
            <td class="col-jml"><?= htmlspecialchars($qtyText) ?></td>
          </tr>
          <tr class="aturan-row">
  <td></td>
  <td colspan="2" class="aturan-wrap">
    <span class="aturan-s">S</span>
    <span class="s-dash"></span>
    <span class="aturan-text"><?= htmlspecialchars($aturan) ?></span>
  </td>
</tr>

<!-- GARIS PANJANG DI BARIS BAWAH "S" (INI YANG KAMU MAU) -->
<tr class="line-row">
  <td></td>
  <td colspan="2" class="line-cell">
    <div class="line-full"></div>
  </td>
</tr>


        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="3"><em>Tidak ada detail obat</em></td></tr>
      <?php endif; ?>
    </table>
<!-- BAWAH: FOTO SERAH (KIRI) + QR (KANAN) -->
<table class="resep-bawah-row">
  <tr>
    <!-- FOTO BUKTI PENYERAHAN -->
    <td class="serah-col">
      <?php if ($base64_serah): ?>
        <img src="<?= $base64_serah ?>" class="serah-img">
      <?php else: ?>
        <div style="font-size:12px;"><em>Foto bukti penyerahan belum ada</em></div>
      <?php endif; ?>
    </td>

    <!-- QR & INFO -->
    <td class="qr-col">
      <div class="qr-box">
        <div class="resep-tgl">Situbondo, <?= htmlspecialchars($tgl_resep) ?></div>

        <div class="resep-qr">
          <img src="data:image/png;base64,<?= $qr_base64 ?>">
        </div>

        <div class="resep-dokter"><?= htmlspecialchars($r['nm_dokter']) ?></div>
        <div class="resep-serah">Diserahkan : Petugas Farmasi</div>
      </div>
    </td>
  </tr>
</table>


  <div style="page-break-after:always;"></div>

<?php endwhile; ?>
<?php endif; ?>
<?php endif; // end resep_ids ?>




<?php if (!empty($nota_ids)): ?>
<!-- =======================
     9. NOTA OBAT
     ======================= -->
<h4>9. NOTA OBAT</h4>

<?php
/* =====================
   TEMPLATE NOTA OBAT
   ===================== */
// ===== APPLY FILTER RESEP KE NOTA OBAT =====
$whereNotaStatus = '';
if ($filter_resep_status && $filter_resep_status !== 'semua') {
    $whereNotaStatus = " AND dpo.status = '" . ($filter_resep_status === 'ralan' ? 'Ralan' : 'Ranap') . "'";
}

$whereNotaTanggal = '';
if (!empty($filter_resep_tanggal_dari) && !empty($filter_resep_tanggal_sampai)) {
    $whereNotaTanggal = " AND DATE(dpo.tgl_perawatan) BETWEEN '$filter_resep_tanggal_dari' AND '$filter_resep_tanggal_sampai'";
}

$orderNota = ($filter_resep_urutan === 'ASC') ? 'ASC' : 'DESC';
$limitNota = '';
if (!empty($filter_resep_limit) && intval($filter_resep_limit) > 0) {
    $limitNota = " LIMIT " . intval($filter_resep_limit);
}

// Filter berdasarkan checkbox yang dipilih
$whereNotaIds = '';
if (!empty($nota_ids)) {
    $notaConditions = [];
    foreach ($nota_ids as $id) {
        $parts = explode('|', $id);
        if (count($parts) === 3) {
            $no_rawat_safe = $koneksi->real_escape_string($parts[0]);
            $tanggal_safe = $koneksi->real_escape_string($parts[1]);
            $jam_safe = $koneksi->real_escape_string($parts[2]);
            $notaConditions[] = "(dpo.no_rawat = '$no_rawat_safe' AND DATE(dpo.tgl_perawatan) = '$tanggal_safe' AND dpo.jam = '$jam_safe')";
        }
    }
    if (!empty($notaConditions)) {
        $whereNotaIds = " AND (" . implode(' OR ', $notaConditions) . ")";
    }
}

$q_nota = $koneksi->query("
    SELECT
        rp.no_rkm_medis,
        dpo.no_rawat,
        p.nm_pasien,
        pj.png_jawab AS penanggung,
        d.nm_dokter AS pemberi_resep,
        MIN(ro.no_resep) AS no_resep,
        DATE(dpo.tgl_perawatan) AS tanggal,
        dpo.jam,
        dpo.status
    FROM detail_pemberian_obat dpo
    JOIN reg_periksa rp ON rp.no_rawat = dpo.no_rawat
    JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
    LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
    LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
    LEFT JOIN resep_obat ro ON ro.no_rawat = dpo.no_rawat
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    $whereNotaIds
    $whereNotaStatus
    $whereNotaTanggal
    GROUP BY dpo.no_rawat, dpo.tgl_perawatan, dpo.jam
    ORDER BY dpo.tgl_perawatan $orderNota, dpo.jam $orderNota
    $limitNota
");

// Debug query nota
if (!$q_nota) {
    echo "<p style='color:red;'>Error Nota Query: " . $koneksi->error . "</p>";
}

/* helper format uang seperti di foto: 33,600.00 */
function rp_foto($angka) {
    return 'Rp ' . number_format((float)$angka, 2, '.', ',');
}
?>

<?php if (!$q_nota || $q_nota->num_rows == 0): ?>
  <p><em>Data nota obat tidak ditemukan.</em></p>
<?php else: ?>

<style>
  
.resep-line{
  height:1px;
  border-top:2px solid #333;
  border-bottom:1px solid #333;
  margin:3px 0 10px 0;
}

  /* RESET BORDER KHUSUS NOTA (tidak ganggu tabel lain) */
.nota-wrap table,
.nota-wrap tr,
.nota-wrap td {
  border: none !important;
}


/* =============================
   FONT & LAYOUT UTAMA
============================= */
.nota-wrap{
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
  color: #000;
  width: 100%;
}

/* =============================
   HEADER
============================= */
.nota-header{
  width:100%;
  border-collapse: collapse;
}

.logo img{
  width:70px;
  height:auto;
}

.rs-title{
  text-align:center;
  line-height:1.2;
}

.rs-title .t1{
  font-size:20px;
  font-weight:normal;
}

.rs-title .t2,
.rs-title .t3,
.rs-title .t4{
  font-size:12px;
}

/* === SATU-SATUNYA GARIS === */
.double-line{
  border-top:2px solid #000;
  border-bottom:1px solid #000;
  height:6px;
  margin:6px 0 10px 0;
}

/* =============================
   DATA PASIEN
============================= */
.info td{
  padding:2px 0;
}

.info .lbl{ width:140px; }
.info .sep{ width:10px; text-align:center; }
.info .val{ width:auto; }

/* =============================
   DAFTAR OBAT (TANPA GARIS)
============================= */
.obat{
  width:100%;
  border-collapse:collapse;
}

.obat td{
  padding:2px 0;
  border:none !important;
}

.obat .no{ width:24px; }
.obat .nama{ padding-left:6px; }
.obat .jml{
  width:120px;
  text-align:right;
  white-space:nowrap;
}
.obat .sub{
  width:140px;
  text-align:right;
  white-space:nowrap;
}

/* =============================
   FOOTER
============================= */
.footer{
  margin-top:25px;
}

.footer .tgl{
  text-align:right;
}

.footer .petugas{
  margin-top:25px;
  text-align:right;
  font-size:13px;
}
</style>

<?php while ($h = $q_nota->fetch_assoc()): ?>

<div class="nota-wrap">

  <!-- HEADER -->
  <table class="nota-header">
    <tr>
      <td class="logo">
      <img src="http://localhost/webapps/hasilcetakclaimobat/pages/assets/logorsar.png" style="width:70px;">
      </td>
      <td class="rs-title">
        <div class="t1">RUMAH SAKIT UMUM DAERAH dr. ABDOER RAHEM</div>
        <div class="t2">Jl. Anggrek No. 68, Kelurahan. Patokan , Kecamatan. Situbondo,</div>
        <div class="t3">0338-671028</div>
        <div class="t4">E-mail : rsu.situbondo@yahoo.com</div>
      </td>
      <td style="width:90px;"></td>
    </tr>
  </table>

  <div class="resep-line"></div>

  <!-- INFO PASIEN -->
  <table class="info">
    <tr><td class="lbl">Nama Pasien</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($h['nm_pasien']) ?></td></tr>
    <tr><td class="lbl">No. R.M.</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($h['no_rkm_medis']) ?></td></tr>
    <tr><td class="lbl">No. Rawat</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($h['no_rawat']) ?></td></tr>
    <tr><td class="lbl">Penanggung</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($h['penanggung'] ?: '-') ?></td></tr>
    <tr><td class="lbl">Pemberi Resep</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($h['pemberi_resep'] ?: '-') ?></td></tr>
    <tr><td class="lbl">No. Resep</td><td class="sep">:</td><td class="val"><?= htmlspecialchars($h['no_resep'] ?: '-') ?></td></tr>
  </table>

  <!-- DAFTAR OBAT -->
  <table class="obat">
    <?php
      $q_obat = $koneksi->query("
          SELECT
              b.nama_brng,
              b.kode_sat,          -- pastikan field ini ada (satuan)
              dpo.jml,
              dpo.biaya_obat,
              (dpo.jml * dpo.biaya_obat) AS subtotal
          FROM detail_pemberian_obat dpo
          JOIN databarang b ON b.kode_brng = dpo.kode_brng
          WHERE dpo.no_rawat = '{$h['no_rawat']}'
            AND DATE(dpo.tgl_perawatan) = '{$h['tanggal']}'
            AND dpo.jam = '{$h['jam']}'
      ");

      $no = 1;
      $total = 0;

      if ($q_obat && $q_obat->num_rows > 0) {
          while ($o = $q_obat->fetch_assoc()) {
              $total += (float)$o['subtotal'];
              $nama = strtoupper($o['nama_brng']);
              $sat  = strtoupper($o['kode_sat'] ?? ''); // kalau null, tetap aman
              $jml  = number_format((float)$o['jml'], 1, '.', ''); // 16.0
    ?>
      <tr>
        <td class="no"><?= $no++ ?></td>
        <td class="nama"><?= htmlspecialchars($nama) ?></td>
        <td class="jml"><?= $jml . ($sat ? " $sat" : "") ?></td>
        <td class="sub"><?= rp_foto($o['subtotal']) ?></td>
      </tr>
    <?php
          }
      } else {
    ?>
      <tr>
        <td colspan="4"><em>Tidak ada data obat</em></td>
      </tr>
    <?php } ?>

    <!-- TOTAL -->
    <tr>
      <td class="no"></td>
      <td class="nama" style="padding-left:6px;">TOTAL :</td>
      <td class="jml"></td>
      <td class="sub"><?= rp_foto($total) ?></td>
    </tr>
  </table>

  <!-- FOOTER -->
  <div class="footer">
    <div class="tgl">Situbondo, <?= htmlspecialchars($h['tanggal']) ?></div>
    <div style="height:40px;"></div> <!-- JARAK TANDA TANGAN -->
    <div class="petugas">PETUGAS</div>
  </div>
</div>
<div class="page-break"></div>
<?php endwhile; ?>
<?php endif; ?>
<?php endif; // end nota_ids ?>

<?php if (!empty($penyerahan_ids)): ?>
<!-- =======================
     10. PENYERAHAN OBAT
     ======================= -->
<h4>10. PENYERAHAN OBAT</h4>

<?php
// ===== APPLY FILTER RESEP KE PENYERAHAN OBAT =====
$whereSerahStatus = '';
if ($filter_resep_status && $filter_resep_status !== 'semua') {
    $whereSerahStatus = " AND ro.status = '" . ($filter_resep_status === 'ralan' ? 'Ralan' : 'Ranap') . "'";
}

$whereSerahTanggal = '';
if (!empty($filter_resep_tanggal_dari) && !empty($filter_resep_tanggal_sampai)) {
    $whereSerahTanggal = " AND DATE(ro.tgl_peresepan) BETWEEN '$filter_resep_tanggal_dari' AND '$filter_resep_tanggal_sampai'";
}

$orderSerah = ($filter_resep_urutan === 'ASC') ? 'ASC' : 'DESC';
$limitSerah = '';
if (!empty($filter_resep_limit) && intval($filter_resep_limit) > 0) {
    $limitSerah = " LIMIT " . intval($filter_resep_limit);
}

// Filter berdasarkan checkbox yang dipilih
$whereSerahIds = '';
if (!empty($penyerahan_ids)) {
    $penyerahan_ids_safe = array_map(function($id) use ($koneksi) {
        return "'" . $koneksi->real_escape_string($id) . "'";
    }, $penyerahan_ids);
    $whereSerahIds = " AND bpo.no_resep IN (" . implode(',', $penyerahan_ids_safe) . ")";
}

$q_serah = $koneksi->query("
    SELECT 
        bpo.no_resep,
        bpo.photo,
        ro.status,
        DATE(ro.tgl_peresepan) AS tgl_penyerahan,
        rp.no_rawat
    FROM bukti_penyerahan_resep_obat bpo
    INNER JOIN resep_obat ro ON bpo.no_resep = ro.no_resep
    INNER JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    $whereSerahIds
    $whereSerahStatus
    $whereSerahTanggal
    ORDER BY bpo.no_resep $orderSerah
    $limitSerah
");

// Debug query penyerahan
if (!$q_serah) {
    echo "<p style='color:red;'>Error Penyerahan Query: " . $koneksi->error . "</p>";
}

if (!$q_serah || $q_serah->num_rows == 0) {
    echo '<p><em>Data penyerahan obat tidak ditemukan.</em></p>';
} else {
?>

<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th>No. Rawat</th>
            <th>No. Resep</th>
            <th>Jenis</th>
            <th>Tanggal</th>
            <th>Bukti Penyerahan</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no = 1;
    while ($s = $q_serah->fetch_assoc()) {
        $foto_path = $_SERVER['DOCUMENT_ROOT'] . "/webapps/penyerahanresep/pages/upload/" . basename($s['photo']);
        $base64_serah = null;
        
        if (file_exists($foto_path)) {
            $ext = strtolower(pathinfo($foto_path, PATHINFO_EXTENSION));
            $base64_serah = 'data:image/'.$ext.';base64,' . base64_encode(file_get_contents($foto_path));
        }
        
        $jenis = ($s['status'] === 'Ralan') ? 'Rawat Jalan' : 'Rawat Inap';
    ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($s['no_rawat']) ?></td>
            <td><?= htmlspecialchars($s['no_resep']) ?></td>
            <td><?= $jenis ?></td>
            <td><?= date('d/m/Y', strtotime($s['tgl_penyerahan'])) ?></td>
            <td class="text-center">
                <?php if ($base64_serah): ?>
                    <img src="<?= $base64_serah ?>" style="max-width: 200px; max-height: 150px;">
                <?php else: ?>
                    <em>Foto tidak tersedia</em>
                <?php endif; ?>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<?php } ?>
<?php endif; // end penyerahan_ids ?>

<!-- =======================
     11. BERKAS DIGITAL
     ======================= -->
<h4>11. Berkas Digital</h4>

<?php
// fallback kalau rawatList belum ada
if (!isset($rawatList) || !is_array($rawatList) || count($rawatList) === 0) {
    $rawatList = [];
    if (!empty($no_rawat)) $rawatList[] = $no_rawat;
}

// bikin IN list aman
$inRawat = "'" . implode("','", array_map([$koneksi,'real_escape_string'], $rawatList)) . "'";

// folder upload sesuai yang kamu bilang
$uploadFs = $_SERVER['DOCUMENT_ROOT'] . "/webapps/berkasrawat/pages/upload/";
$uploadUrl = "/webapps/berkasrawat/pages/upload/"; // untuk buka pdf di tab baru

$qBerkas = $koneksi->query("
    SELECT 
        b.no_rawat,
        COALESCE(m.nama,'-') AS jenis_berkas,
        b.lokasi_file
    FROM berkas_digital_perawatan b
    LEFT JOIN master_berkas_digital m ON b.kode = m.kode
    WHERE b.no_rawat IN ($inRawat)
    ORDER BY b.no_rawat DESC
");

if (!$qBerkas || $qBerkas->num_rows == 0) {
    echo '<p><em>Tidak ada berkas digital</em></p>';
} else {
?>
<table class="tbl-berkas">
  <tr>
    <th width="5%">No</th>
    <th width="20%">No Rawat</th>
    <th width="25%">Jenis</th>
    <th>Preview</th>
  </tr>

  <?php
  $no = 1;
  while ($b = $qBerkas->fetch_assoc()) {
      // Filter checkbox
      if (!empty($berkas_ids) && !in_array($b['no_rawat'], $berkas_ids)) continue;

      $dbVal = $b['lokasi_file'] ?? '';
      $fileName = basename($dbVal);
      $fsPath = $uploadFs . $fileName;
      $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
  ?>
  <tr>
    <td><?= $no++ ?></td>
    <td><?= htmlspecialchars($b['no_rawat']) ?></td>
    <td><?= htmlspecialchars($b['jenis_berkas']) ?></td>
    <td>
      <?php if ($fileName && file_exists($fsPath)): ?>

        <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
          <?php
            $mime = ($ext === 'jpg') ? 'jpeg' : $ext;
            $data = base64_encode(file_get_contents($fsPath));
          ?>
          <img class="berkas-preview-img"
               src="data:image/<?= htmlspecialchars($mime) ?>;base64,<?= $data ?>"
               alt="Berkas">

        <?php elseif ($ext === 'pdf'): ?>
          <?php
            // === TAMPILKAN PDF SEBAGAI GAMBAR (PAGE 1) SEPERTI TEMPLATE SEP ===
            // asumsi fungsi pdfUrlToPngDataUri() sudah ada (dipakai di Template SEP)
            $BASE_BERKASRAWAT = "http://192.168.0.100/webapps/berkasrawat/";
            $pdfUrl = $BASE_BERKASRAWAT . ltrim($dbVal, '/');

            // fallback kalau dbVal ternyata cuma nama file
            if ($fileName === $dbVal) {
                $pdfUrl = $BASE_BERKASRAWAT . "pages/upload/" . rawurlencode($fileName);
            }

            $resPdf = pdfUrlToPngDataUri($pdfUrl);
          ?>

          <?php if (isset($resPdf['ok']) && $resPdf['ok']): ?>
            <img class="berkas-preview-img" src="<?= $resPdf['data'] ?>" alt="PDF Preview">
          <?php else: ?>
            <?php $pdfUrlLocal = $uploadUrl . rawurlencode($fileName); ?>
            <a href="<?= htmlspecialchars($pdfUrlLocal) ?>" target="_blank">Buka PDF</a>
          <?php endif; ?>

        <?php else: ?>
          <em>Format tidak didukung: <?= htmlspecialchars($ext) ?></em>

        <?php endif; ?>

      <?php else: ?>
        <em>File tidak ditemukan: <?= htmlspecialchars($fileName ?: '-') ?></em>
      <?php endif; ?>
    </td>
  </tr>
  <?php } ?>
</table>
<?php } ?>

<?php if (!empty($resume_ids)): ?>
<!-- =======================
     12. RESUME PASIEN
     ======================= -->
<h4>12. Resume Pasien</h4>

<?php
$ada_resume = false;

/* ===== RAWAT JALAN ===== */
$sql_rj = "
SELECT 
  rp.no_rawat,
  rp.no_reg,
  rp.tgl_registrasi,
  rp.umurdaftar,
  p.nm_poli,
  d.nm_dokter,
  rp.status_lanjut,
  rj.keluhan_utama,
  rj.diagnosa_utama,
  rj.kondisi_pulang,
  rj.obat_pulang
FROM resume_pasien rj
INNER JOIN reg_periksa rp ON rp.no_rawat = rj.no_rawat
LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
WHERE rp.no_rkm_medis = '$no_rkm_medis'
ORDER BY rp.tgl_registrasi DESC
";

$rj = $koneksi->query($sql_rj);
if ($rj && $rj->num_rows > 0) {
    $ada_resume = true;
?>
<h5>Rawat Jalan</h5>
<table class="tbl-resume">
<thead>
<tr>
  <th width="3%">No</th>
  <th width="12%">Tanggal</th>
  <th width="10%">Poliklinik</th>
  <th width="12%">Dokter</th>
  <th width="18%">Keluhan Utama</th>
  <th width="15%">Diagnosa Utama</th>
  <th width="10%">Kondisi Pulang</th>
  <th width="20%">Obat Pulang</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
while ($r = $rj->fetch_assoc()) {
?>
<tr>
  <td style="text-align:center;"><?= $no++ ?></td>
  <td style="font-size:9px;"><?= date('d/m/Y', strtotime($r['tgl_registrasi'])) ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars($r['nm_poli'] ?? '-') ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars($r['nm_dokter'] ?? '-') ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars(substr($r['keluhan_utama'] ?? '-', 0, 80)) ?><?= strlen($r['keluhan_utama'] ?? '') > 80 ? '...' : '' ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars($r['diagnosa_utama'] ?? '-') ?></td>
  <td style="font-size:9px; text-align:center;"><?= htmlspecialchars($r['kondisi_pulang'] ?? '-') ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars(substr($r['obat_pulang'] ?? '-', 0, 80)) ?><?= strlen($r['obat_pulang'] ?? '') > 80 ? '...' : '' ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } 

/* ===== RAWAT INAP ===== */
$sql_ri = "
SELECT 
  rp.no_rawat,
  rp.no_reg,
  rp.tgl_registrasi,
  rp.umurdaftar,
  d.nm_dokter,
  ri.diagnosa_awal,
  ri.keluhan_utama,
  ri.diagnosa_utama,
  ri.keadaan,
  ri.cara_keluar,
  ri.obat_pulang
FROM resume_pasien_ranap ri
INNER JOIN reg_periksa rp ON rp.no_rawat = ri.no_rawat
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
WHERE rp.no_rkm_medis = '$no_rkm_medis'
ORDER BY rp.tgl_registrasi DESC
";

$ri = $koneksi->query($sql_ri);
if ($ri && $ri->num_rows > 0) {
    $ada_resume = true;
?>
<h5>Rawat Inap</h5>
<table class="tbl-resume">
<thead>
<tr>
  <th width="3%">No</th>
  <th width="12%">Tanggal</th>
  <th width="12%">Dokter</th>
  <th width="15%">Diagnosa Awal</th>
  <th width="18%">Keluhan Utama</th>
  <th width="15%">Diagnosa Utama</th>
  <th width="10%">Keadaan</th>
  <th width="15%">Obat Pulang</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
while ($r = $ri->fetch_assoc()) {
?>
<tr>
  <td style="text-align:center;"><?= $no++ ?></td>
  <td style="font-size:9px;"><?= date('d/m/Y', strtotime($r['tgl_registrasi'])) ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars($r['nm_dokter'] ?? '-') ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars($r['diagnosa_awal'] ?? '-') ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars(substr($r['keluhan_utama'] ?? '-', 0, 80)) ?><?= strlen($r['keluhan_utama'] ?? '') > 80 ? '...' : '' ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars($r['diagnosa_utama'] ?? '-') ?></td>
  <td style="font-size:9px; text-align:center;"><?= htmlspecialchars($r['keadaan'] ?? '-') ?></td>
  <td style="font-size:9px;"><?= htmlspecialchars(substr($r['obat_pulang'] ?? '-', 0, 80)) ?><?= strlen($r['obat_pulang'] ?? '') > 80 ? '...' : '' ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } 

if (!$ada_resume) {
    echo '<p><em>Tidak ada data resume untuk pasien ini.</em></p>';
}
?>
<?php endif; // end RESUME PASIEN ?>

</body>
</html>

<?php
$html = ob_get_clean();
/* =========================
   RENDER
   ========================= */
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream(
    'Klaim_BPJS_' . date('Ymd_His') . '.pdf',
    ['Attachment' => false]
);
exit;
?>