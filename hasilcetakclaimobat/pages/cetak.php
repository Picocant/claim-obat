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

<style>
body { font-size: 11px; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #000; padding: 5px; vertical-align: top; }
th { background: #f2f2f2; }
h4 { margin: 8px 0 4px; }
</style>

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

<br>


<br>

<!-- =======================
     2. DATA REGISTRASI
     ======================= -->
<h4>2. Data Registrasi</h4>

<?php
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
<div style="page-break-after:always;"></div>

<!-- =======================
     4. TEMPLATE RESEP
     ======================= -->
     <h4>4. RESEP</h4>
<?php
// WAJIB: pastikan file ini ADA:
// D:\xampp\htdocs\webapps\hasilcetakclaimobat\pages\phpqrcode\qrlib.php
require_once __DIR__ . '/../phpqrcode/qrlib.php';

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
    ORDER BY ro.no_resep DESC
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

$qSerah = $koneksi->query("
  SELECT photo 
  FROM bukti_penyerahan_resep_obat
  WHERE no_resep = '{$r['no_resep']}'
  ORDER BY no_resep DESC
  LIMIT 1
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



<?php
/* =====================
   TEMPLATE NOTA OBAT
   ===================== */
$q_nota = $koneksi->query("
    SELECT
        rp.no_rkm_medis,
        dpo.no_rawat,
        p.nm_pasien,
        pj.png_jawab AS penanggung,
        d.nm_dokter AS pemberi_resep,
        MIN(ro.no_resep) AS no_resep,
        DATE(dpo.tgl_perawatan) AS tanggal,
        dpo.jam
    FROM detail_pemberian_obat dpo
    JOIN reg_periksa rp ON rp.no_rawat = dpo.no_rawat
    JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
    LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
    LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
    LEFT JOIN resep_obat ro ON ro.no_rawat = dpo.no_rawat
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    GROUP BY dpo.no_rawat, dpo.tgl_perawatan, dpo.jam
    ORDER BY dpo.tgl_perawatan DESC, dpo.jam DESC
");

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
<div style="page-break-after:always;"></div>
<?php endwhile; ?>
<?php endif; ?>
<!-- =======================
     4. TEMPLATE BERKAS DIGITAL
     ======================= -->
<h4>5. Berkas Digital</h4>

<style>
  .tbl-berkas { width:100%; border-collapse:collapse; }
  .tbl-berkas th, .tbl-berkas td { border:1px solid #000; padding:6px; vertical-align:top; }
  .tbl-berkas th { background:#f2f2f2; }

  .berkas-preview-img{
    max-width:260px;
    max-height:180px;
    object-fit:contain;
    display:block;
  }
</style>

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



<div style="page-break-after:always;"></div>
<!-- =======================
     5. RESUME PASIEN
     ======================= -->
<h4>5. Resume Pasien</h4>

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
<table border="1" width="100%" cellpadding="5">
<tr>
  <th>No</th>
  <th>No Rawat</th>
  <th>No Registrasi</th>
  <th>Tanggal</th>
  <th>Umur</th>
  <th>Poliklinik</th>
  <th>Dokter</th>
  <th>Status</th>
  <th>Keluhan</th>
  <th>Diagnosa</th>
  <th>Kondisi Pulang</th>
  <th>Obat Pulang</th>
</tr>
<?php
$no = 1;
while ($r = $rj->fetch_assoc()) {
?>
<tr>
  <td><?= $no++ ?></td>
  <td><?= $r['no_rawat'] ?></td>
  <td><?= $r['no_reg'] ?></td>
  <td><?= $r['tgl_registrasi'] ?></td>
  <td><?= $r['umurdaftar'] ?></td>
  <td><?= $r['nm_poli'] ?></td>
  <td><?= $r['nm_dokter'] ?></td>
  <td><?= $r['status_lanjut'] ?></td>
  <td><?= nl2br($r['keluhan_utama']) ?></td>
  <td><?= $r['diagnosa_utama'] ?></td>
  <td><?= $r['kondisi_pulang'] ?></td>
  <td><?= nl2br($r['obat_pulang']) ?></td>
</tr>
<?php } ?>
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
<table border="1" width="100%" cellpadding="5">
<tr>
  <th>No</th>
  <th>No Rawat</th>
  <th>No Registrasi</th>
  <th>Tanggal</th>
  <th>Umur</th>
  <th>Dokter</th>
  <th>Diagnosa Awal</th>
  <th>Keluhan</th>
  <th>Diagnosa Utama</th>
  <th>Keadaan</th>
  <th>Cara Keluar</th>
  <th>Obat Pulang</th>
</tr>
<?php
$no = 1;
while ($r = $ri->fetch_assoc()) {
?>
<tr>
  <td><?= $no++ ?></td>
  <td><?= $r['no_rawat'] ?></td>
  <td><?= $r['no_reg'] ?></td>
  <td><?= $r['tgl_registrasi'] ?></td>
  <td><?= $r['umurdaftar'] ?></td>
  <td><?= $r['nm_dokter'] ?></td>
  <td><?= $r['diagnosa_awal'] ?></td>
  <td><?= nl2br($r['keluhan_utama']) ?></td>
  <td><?= $r['diagnosa_utama'] ?></td>
  <td><?= $r['keadaan'] ?></td>
  <td><?= $r['cara_keluar'] ?></td>
  <td><?= nl2br($r['obat_pulang']) ?></td>
</tr>
<?php } } 

if (!$ada_resume) {
    echo '<p><em>Tidak ada data resume untuk pasien ini.</em></p>';
}
?>


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