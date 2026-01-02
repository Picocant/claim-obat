<?php
/************************************************
 * PREVIEW RIWAYAT KLAIM PASIEN - SIMRS KHANZA
 ************************************************/

require_once '../../conf/conf.php';

/* =======================
   KONEKSI DATABASE KHANZA
   ======================= */
$koneksi = bukakoneksi();

if (!$koneksi) {
    die("Koneksi database gagal");
}

/* =======================
   PARAMETER
   ======================= */
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
$no_rkm_medis = trim($no_rkm_medis);

$pasien = [];
$registrasi = [];

function formatJK($jk){
    if ($jk === 'L') return 'Laki-Laki';
    if ($jk === 'P') return 'Perempuan';
    return '-';
}
/* =======================
   AMBIL DATA PASIEN LANGSUNG DENGAN NO_RKM_MEDIS
   ======================= */
if ($no_rkm_medis !== '') {
    $sql = "
    SELECT 
        p.no_rkm_medis,
        p.nm_pasien,
        p.jk,
        p.tmp_lahir,
        p.tgl_lahir,
        p.alamat,
        p.agama,
        p.pnd,
        p.nm_ibu,
        p.gol_darah,
        p.stts_nikah,
        bp.nama_bahasa,
        cf.nama_cacat
    FROM pasien p
    LEFT JOIN bahasa_pasien bp ON p.bahasa_pasien = bp.id
    LEFT JOIN cacat_fisik cf ON p.cacat_fisik = cf.id
    WHERE p.no_rkm_medis = ?
    LIMIT 1
";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) die("Prepare pasien gagal: " . $koneksi->error);
    $stmt->bind_param("s", $no_rkm_medis);
    $stmt->execute();
    $pasien = $stmt->get_result()->fetch_assoc();
}
/* =======================
   AMBIL DATA REGISTRASI TERBARU DARI PASIEN
   ======================= */
if ($no_rkm_medis !== '') {
    $sql2 = "
    SELECT
    rp.no_rawat,
    rp.no_reg,
    rp.tgl_registrasi,
    rp.jam_reg,
    rp.umurdaftar,

    p.nm_poli,
    d.nm_dokter,

    pj.png_jawab AS cara_bayar,

    rp.p_jawab,
    rp.almt_pj,
    rp.hubunganpj,
    rp.stts,

    IFNULL(r.rujuk_ke,'-') AS rujukan_eksternal,

    IF(ki.no_rawat IS NULL, '-', 
       CONCAT(ki.kd_kamar,' - ',b.nm_bangsal)
    ) AS rawat_inap

FROM reg_periksa rp
LEFT JOIN poliklinik p ON rp.kd_poli = p.kd_poli
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj

LEFT JOIN rujuk r ON rp.no_rawat = r.no_rawat

LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat
LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal

WHERE rp.no_rkm_medis = ?
ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC, rp.no_rawat DESC

";

$stmt = $koneksi->prepare($sql2);
$stmt = $koneksi->prepare($sql2);
if (!$stmt) {
    die("SQL ERROR: " . $koneksi->error);
}

$stmt->bind_param("s", $no_rkm_medis);
$stmt->execute();
$result = $stmt->get_result();
$d = $result->fetch_assoc(); // ambil datanya

}
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Preview Klaim Obat</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@page { size: A4; margin: 2cm; }
body { font-size: 12px; background: #f4f6f9; }

.card { box-shadow: 0 2px 8px rgba(0,0,0,.08); border: none; }
.card-header { background: #0d6efd; color: #fff; font-weight: 600; }

table th { background: #f1f3f5; white-space: nowrap; }
table td, table th { vertical-align: middle; }

details summary {
  cursor: pointer;
  font-weight: 600;
  padding: 6px;
  background: #eef3ff;
  border: 1px solid #cfe2ff;
  border-radius: 4px;
}

.indent { margin-left: 20px; }
.indent-2 { margin-left: 40px; }

@media print {
  body { background: #fff; font-size: 11px; }
  .no-print, input, summary, .filter { display: none !important; }
  details { display: block; }
  .card { box-shadow: none; }
}
</style>
</head>

<body>
<div class="container my-4">

<div class="text-center mb-3">
  <h4 class="mb-0">PREVIEW KLAIM PASIEN</h4>
  <small class="text-muted">SIMRS KHANZA</small>
</div>
<!-- 1. DATA PASIEN -->
<div class="card mb-3">
  <div class="card-header">1. Data Pasien</div>
  <div class="card-body p-0">
    <table class="table table-sm table-bordered mb-0">
      <tr>
        <th>No.</th>
        <th>Field</th>
        <th>:</th>
        <th>Isi</th>
      </tr>
      <?php
if (empty($pasien)) {
    echo "<tr>
            <td colspan='4' class='text-center text-muted'>
              Tidak ada data pasien
            </td>
          </tr>";
} else {
      $no = 1;
      $fields = [
          'No. Rekam Medis' => $pasien['no_rkm_medis'] ?? '-',
          'Nama Pasien' => $pasien['nm_pasien'] ?? '-',
          'Alamat' => $pasien['alamat'] ?? '-',
          'Jenis Kelamin' => formatJK($pasien['jk'] ?? ''),
          'Tempat, Tgl Lahir' => ($pasien['tmp_lahir'] ?? '-') . ', ' . ($pasien['tgl_lahir'] ?? '-'),        
          'Ibu Kandung' => $pasien['nm_ibu'] ?? '-',
          'Golongan Darah' => $pasien['gol_darah'] ?? '-',
          'Status Nikah' => $pasien['stts_nikah'] ?? '-',
          'Agama' => $pasien['agama'] ?? '-',
          'Pendidikan Terakhir' => $pasien['pnd'] ?? '-',
          'Bahasa' => $pasien['nama_bahasa'] ?? '-',
          'Cacat Fisik' => $pasien['nama_cacat'] ?? '-',
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
  </div>
</div>

<!-- FILTER RIWAYAT -->
<div class="card mb-3 filter">
  <div class="card-body">
    <label class="form-label fw-semibold mb-2">Filter Riwayat Kunjungan</label>

    <!-- Semua riwayat -->
    <div class="form-check mb-2">
      <input class="form-check-input" type="radio" name="filterRiwayat"
             id="riwayatAll" value="all" checked>
      <label class="form-check-label" for="riwayatAll">
        Tampilkan semua riwayat
      </label>
    </div>

    <!-- Riwayat terakhir (jumlah bebas) -->
    <div class="form-check d-flex align-items-center gap-2">
      <input class="form-check-input" type="radio" name="filterRiwayat"
             id="riwayatLast" value="last">
      <label class="form-check-label" for="riwayatLast">
        Tampilkan
      </label>

      <input type="number" id="jumlahRiwayat"
             class="form-control form-control-sm"
             style="width: 80px;"
             min="1" value="5" disabled>

      <span>riwayat terakhir</span>
    </div>
  </div>
</div>


<!-- 2. DATA REGISTRASI --> 
<div class="card mb-3">
  <div class="card-header">2. Data Registrasi</div>
  <div class="card-body p-0">
    <?php
// Ambil semua no_rawat pasien tertentu
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

if ($query_rawat === false) {
    echo "<div class='p-3 text-danger'>SQL Error: {$koneksi->error}</div>";
} elseif ($query_rawat->num_rows == 0) {

    // ===== TIDAK ADA REGISTRASI =====
    echo "<div class='p-3 text-center text-muted'>
            Tidak ada data registrasi
          </div>";

} else {

    // ===== ADA REGISTRASI =====
    while ($row_rawat = $query_rawat->fetch_assoc()) {

        $no_rawat = $row_rawat['no_rawat'];
        $poli     = $row_rawat['nm_poli'] ?? '-';
        $status   = $row_rawat['stts'] ?? '-';

        // Ambil detail registrasi (1 no_rawat = 1 baris)
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

                /* Rujukan internal terakhir */
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

                /* Rujukan eksternal terakhir */
                (
                  SELECT CONVERT(rj.rujuk_ke USING utf8mb4)
                  FROM rujuk rj
                  WHERE rj.no_rawat = rp.no_rawat
                  LIMIT 1
                ) AS rujukan_eksternal,

                /* Rawat inap terakhir */
                (
                  SELECT CONCAT(
                    CONVERT(ki.kd_kamar USING utf8mb4),
                    ' - ',
                    CONVERT(b.nm_bangsal USING utf8mb4)
                  )
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
            WHERE rp.no_rawat = '$no_rawat'
        ");

        if ($res_detail === false) {
            echo "<div class='p-2 text-danger'>SQL Error: {$koneksi->error}</div>";
            continue;
        }

        $d = $res_detail->fetch_assoc(); // ← PASTI 1 BARIS

        echo "<details class='mb-2 riwayat-row'>
              <summary>
                {$no_rawat} | {$poli}
                <span class='badge bg-success'>{$status}</span>
              </summary>

              <table class='table table-sm table-bordered mt-2'>
                <tbody>";

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

        echo "   </tbody>
              </table>
            </details>";
    }
}
?>

<!-- 3. KUNJUNGAN -->
<div class="card mb-3">
  <div class="card-header">3. Kunjungan Pasien</div>
  <div class="card-body">

    <!-- NAV TABS -->
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sep">SEP</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#resep">Resep Obat</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#berkas">Berkas Digital</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#resume">Resume</button></li> 
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#echo">Ekokardiografi (ECHO)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#eeg">Electroencephalography (EEG)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#hba1c">Hemoglobin A1c (HbA1c)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mmse">Mini-Mental State Examination (MMSE)</button></li>
    </ul>
    <!-- TAB CONTENT -->
    <div class="tab-content border p-3">
<!-- TAB SEP -->
<?php
// =======================
// LOGIKA FILTER
// =======================
$filter = $_GET['filter'] ?? 'all';
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$limitSql = '';
if ($filter === 'last' && $limit > 0) {
    $limitSql = "LIMIT $limit";
}
?>

<div class="tab-pane fade show active" id="sep">
  <table class="table table-sm table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th width="8%" class="text-center">
          <label class="form-check-label small">
            <input type="checkbox" class="check-all-tab me-1">
            Pilih semua
          </label>
        </th>
        <th width="5%">No</th>
        <th width="25%">Nomor Rawat</th>
        <th>File SEP</th>
      </tr>
    </thead>
    <tbody>

<?php
$no = 1;

// =======================
// QUERY SEP (TERFILTER)
// =======================
$qSep = $koneksi->query("
    SELECT 
        rp.no_rawat,
        bdp.lokasi_file
    FROM reg_periksa rp
    INNER JOIN pasien p 
        ON p.no_rkm_medis = rp.no_rkm_medis
    INNER JOIN berkas_digital_perawatan bdp
        ON bdp.no_rawat = rp.no_rawat
       AND bdp.kode = '001'
    WHERE p.no_rkm_medis = '$no_rkm_medis'
    ORDER BY rp.no_rawat DESC
    $limitSql
");

if (!$qSep || $qSep->num_rows == 0) {
    echo "
    <tr>
      <td colspan='4' class='text-center text-muted'>
        File SEP tidak ditemukan
      </td>
    </tr>";
} else {

    while ($row = $qSep->fetch_assoc()) {

        $file_db = $row['lokasi_file'];
        $url_sep = "http://192.168.0.100/webapps/berkasrawat/" . $file_db;
        $ext     = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
?>
<tr class="riwayat-row">
  <td class="text-center">
    <input type="checkbox"
       class="row-check"
       name="sep_id[]"
       value="<?= htmlspecialchars($row['no_rawat']) ?>">
  </td>
  <td><?= $no++ ?></td>
  <td><?= htmlspecialchars($row['no_rawat']) ?></td>
  <td>

<?php if (!empty($file_db)) { ?>

    <?php if (in_array($ext, ['jpg','jpeg','png','webp'])) { ?>

        <!-- TAMPILKAN GAMBAR -->
        <img 
          src="<?= $url_sep ?>"
          class="img-fluid"
          style="max-height:600px;border:1px solid #ccc;border-radius:6px;"
        >

    <?php } elseif ($ext === 'pdf') { ?>

        <!-- TAMPILKAN PDF TANPA TOOLBAR -->
        <iframe 
          src="<?= $url_sep ?>#toolbar=0&navpanes=0&scrollbar=0"
          width="100%" 
          height="600"
          style="border:1px solid #ccc;border-radius:6px;">
        </iframe>

    <?php } else { ?>

        <a href="<?= $url_sep ?>" target="_blank">
          <?= basename($file_db) ?>
        </a>

    <?php } ?>

<?php } else { ?>
    <span class="text-danger">File tidak ditemukan</span>
<?php } ?>
  </td>
</tr>
<?php
    }
}
?>
    </tbody>
  </table>
</div>

<!-- TAB RESEP -->
<?php
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';

$q_resep = mysqli_query($koneksi,"
    SELECT 
        ro.no_resep,
        ro.no_rawat,
        pj.png_jawab AS penanggung,
        ro.status AS jenis_resep,
        d.nm_dokter
    FROM resep_obat ro
    JOIN reg_periksa rp 
        ON ro.no_rawat = rp.no_rawat
    LEFT JOIN dokter d 
        ON d.kd_dokter = rp.kd_dokter
    LEFT JOIN penjab pj 
        ON pj.kd_pj = rp.kd_pj
    WHERE rp.no_rkm_medis = '$no_rkm_medis'
    ORDER BY ro.no_resep DESC
");

?>

<div class="tab-pane fade" id="resep" role="tabpanel">
  <h6 class="mt-1"><strong>Resep</strong></h6>

<table class="table table-sm table-bordered">
<thead class="table-light">
<tr>
  <th width="8%" class="text-center">
    <label class="form-check-label small">
      <input type="checkbox" class="check-all-tab me-1">
      Pilih semua
    </label>
  </th>
  <th width="5%">No</th>
  <th width="20%">Nomor Rawat</th>
  <th>Jenis Pasien</th>
  <th>Jenis Resep</th>
  <th>Pemberi Resep</th>
  <th>No. Resep</th>
  <th>Hasil (Isi Resep)</th>
</tr>
</thead>
<tbody>

<?php
$no = 1;
while ($r = mysqli_fetch_assoc($q_resep)) {

  // ambil isi resep
 $q_detail = mysqli_query($koneksi,"
      SELECT 
          b.nama_brng, 
          rd.jml, 
          rd.aturan_pakai
      FROM resep_dokter rd
      JOIN databarang b 
          ON rd.kode_brng = b.kode_brng
      WHERE rd.no_resep = '{$r['no_resep']}'
  ");
?>
<tr>
  <td class="text-center">
    <input type="checkbox" class="row-check">
  </td>
  <td><?= $no++ ?></td>
  <td><?= $r['no_rawat'] ?></td>

  <!-- JENIS PASIEN = PENANGGUNG -->
  <td><?= $r['penanggung'] ?? '-' ?></td>

  <!-- JENIS RESEP -->
  <td>
<?php if ($r['jenis_resep'] == 'ralan') { ?>
  <span class="badge bg-info">Rawat Jalan</span>
<?php } else { ?>
  <span class="badge bg-warning text-dark">Rawat Inap</span>
<?php } ?>
</td>


  <!-- PEMBERI RESEP -->
  <td><?= $r['nm_dokter'] ?? '-' ?></td>

  <!-- NO RESEP -->
  <td><?= $r['no_resep'] ?></td>

  <!-- ISI RESEP -->
  <td>
    <?php
    if (mysqli_num_rows($q_detail) > 0) {
      echo "<ul class='mb-0'>";
      while ($d = mysqli_fetch_assoc($q_detail)) {
        echo "<li>
                {$d['nama_brng']} ({$d['jml']})<br>
                <small class='text-muted'>{$d['aturan_pakai']}</small>
              </li>";
      }
      echo "</ul>";
    } else {
      echo "<span class='text-muted'>Tidak ada detail obat</span>";
    }
    ?>
  </td>
</tr>
<?php } ?>
</tbody>
</table>

<!-- ================= TABEL NOTA OBAT ================= -->
<?php
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';

$q_nota_obat = mysqli_query($koneksi, "
    SELECT
        dpo.no_rawat,
        dpo.status,
        dpo.tgl_perawatan AS tanggal,
        dpo.jam,

        pj.png_jawab AS penanggung,
        d.nm_dokter AS pemberi_resep,

        -- ambil salah satu no_resep yg terkait
        MIN(ro.no_resep) AS no_resep

    FROM detail_pemberian_obat dpo
    JOIN reg_periksa rp 
        ON rp.no_rawat = dpo.no_rawat
    LEFT JOIN penjab pj 
        ON pj.kd_pj = rp.kd_pj
    LEFT JOIN dokter d 
        ON d.kd_dokter = rp.kd_dokter
    LEFT JOIN resep_obat ro 
        ON ro.no_rawat = dpo.no_rawat

    WHERE rp.no_rkm_medis = '$no_rkm_medis'

    GROUP BY 
        dpo.no_rawat,
        dpo.tgl_perawatan,
        dpo.jam

    ORDER BY dpo.tgl_perawatan DESC, dpo.jam DESC
");
?>

<h6 class="mt-4"><strong>Nota Obat</strong></h6>

<table class="table table-sm table-bordered">
<thead class="table-light">
<tr>
  <th width="8%" class="text-center">
    <label class="form-check-label small">
      <input type="checkbox" class="check-all-tab me-1">
      Pilih semua
    </label>
  </th>
  <th width="5%">No</th>
  <th>Nomor Rawat</th>
  <th>Jenis</th>
  <th>No. Nota</th>
  <th>Tanggal</th>
  <th>Jam</th>

  <!-- TAMBAHAN KOLOM -->
  <th>Penanggung</th>
  <th>Pemberi Resep</th>
  <th>No. Resep</th>
</tr>
</thead>
<tbody>

<?php
if (mysqli_num_rows($q_nota_obat) > 0) {
    $no = 1;
    while ($n = mysqli_fetch_assoc($q_nota_obat)) {
?>
<tr>
  <td class="text-center">
    <input type="checkbox"
       class="row-check"
       name="sep_id[]"
       value="<?= htmlspecialchars($row['no_rawat']) ?>">
  </td>
  <td><?= $no++ ?></td>
  <td><?= $n['no_rawat'] ?></td>
  <td>
    <?php if ($n['status'] == 'Ralan') { ?>
        <span class="badge bg-info">Rawat Jalan</span>
    <?php } else { ?>
        <span class="badge bg-warning text-dark">Rawat Inap</span>
    <?php } ?>
  </td>
  <td><?= $n['no_rawat'] ?></td>
  <td><?= $n['tanggal'] ?></td>
  <td><?= $n['jam'] ?></td>

  <!-- ISI SESUAI NOTA -->
  <td><?= $n['penanggung'] ?? '-' ?></td>
  <td><?= $n['pemberi_resep'] ?? '-' ?></td>
  <td><?= $n['no_resep'] ?? '-' ?></td>
</tr>
<?php
    }
} else {
?>
<tr>
  <td colspan="10" class="text-center text-muted">
    Tidak ada nota obat
  </td>
</tr>
<?php } ?>

</tbody>
</table>


<!-- ================= TABEL PENYERAHAN ================= -->
<?php
$sql_serah = "
SELECT 
  rp.no_rawat,
  bo.no_resep,
  bo.photo
FROM bukti_penyerahan_resep_obat bo
INNER JOIN resep_obat ro ON bo.no_resep = ro.no_resep
INNER JOIN reg_periksa rp ON ro.no_rawat = rp.no_rawat
WHERE rp.no_rkm_medis = ?
ORDER BY bo.no_resep DESC
";

$stmt = $koneksi->prepare($sql_serah);
$stmt->bind_param("s", $no_rkm_medis);
$stmt->execute();
$serah = $stmt->get_result();
?>

<h6 class="mt-4"><strong>Penyerahan Obat</strong></h6>
<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr>
      <th width="8%" class="text-center">
        <label class="form-check-label small">
          <input type="checkbox" class="check-all-tab me-1">
          Pilih semua
        </label>
      </th>
      <th width="5%">No</th>
      <th width="25%">Nomor Rawat</th>
      <th width="25%">No. Resep</th>
      <th>Bukti Penyerahan</th>
    </tr>
  </thead>
  <tbody>
<?php
$no = 1;
$ada_serah = false;

while($r = $serah->fetch_assoc()){
  $ada_serah = true;

  // ===== FIX PATH FOTO =====
  $foto = "http://localhost/webapps/penyerahanresep/pages/upload/" . basename($r['photo']);
?>
<tr>
  <td class="text-center">
    <input type="checkbox" class="row-check">
  </td>
  <td><?= $no++ ?></td>
  <td><?= $r['no_rawat'] ?></td>
  <td><?= $r['no_resep'] ?></td>
  <td class="text-center">
    <a href="<?= $foto ?>" target="_blank">
      <img src="<?= $foto ?>" 
           class="img-thumbnail" 
           style="max-height:120px">
    </a>
  </td>
</tr>
<?php } ?>

<?php if(!$ada_serah){ ?>
<tr>
  <td colspan="5" class="text-center text-muted py-3">
    <em>Tidak ada data penyerahan obat.</em>
  </td>
</tr>
<?php } ?>
  </tbody>
</table>

</div>


<!-- TAB BERKAS DIGITAL -->
<?php
require_once '../../conf/conf.php';
$koneksi = bukakoneksi();

// =======================
// PARAMETER
// =======================
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// =======================
// LOGIKA LIMIT
// =======================
$limitSql = '';
if ($filter === 'last' && $limit > 0) {
    $limitSql = "LIMIT $limit";
}

// =======================
// QUERY BERKAS DIGITAL (TERFILTER)
// =======================
$query = "
    SELECT 
        b.no_rawat,
        b.kode,
        m.nama AS jenis_berkas,
        b.lokasi_file
    FROM berkas_digital_perawatan b
    JOIN reg_periksa r 
        ON b.no_rawat = r.no_rawat
    LEFT JOIN master_berkas_digital m
        ON b.kode = m.kode
    WHERE r.no_rkm_medis = '$no_rkm_medis'
    ORDER BY b.no_rawat DESC
    $limitSql
";

$result = mysqli_query($koneksi, $query);
?>

<div class="tab-pane fade" id="berkas">
    <table class="table table-sm table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th width="8%" class="text-center">
                    <label class="form-check-label small">
                        <input type="checkbox" class="check-all-tab me-1">
                        Pilih semua
                    </label>
                </th>
                <th width="5%">No</th>
                <th width="20%">Nomor Rawat</th>
                <th width="20%">Jenis</th>
                <th>File</th>
            </tr>
        </thead>
        <tbody>

<?php 
if ($result && mysqli_num_rows($result) > 0) {
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {

        // =======================
        // PATH FILE
        // =======================
        $file_db  = $row['lokasi_file'];
        $file_fs  = $_SERVER['DOCUMENT_ROOT'] . "/webapps/berkasrawat/" . $file_db;
        $file_url = "http://localhost/webapps/berkasrawat/" . $file_db;
        $ext      = strtolower(pathinfo($file_db, PATHINFO_EXTENSION));
?>
        <tr class="riwayat-row">
            <td class="text-center">
                <input type="checkbox" class="row-check">
            </td>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['no_rawat']) ?></td>
            <td>
                <?= $row['jenis_berkas']
                    ? htmlspecialchars($row['jenis_berkas'])
                    : '<span class="text-muted">-</span>' ?>
            </td>
            <td>

<?php if (!empty($file_db) && file_exists($file_fs)) { ?>

    <?php if (in_array($ext, ['jpg','jpeg','png','webp'])) { ?>
        <a href="<?= $file_url ?>" target="_blank">
            <img src="<?= $file_url ?>"
                 class="img-thumbnail"
                 style="max-height:120px">
        </a>

    <?php } elseif ($ext === 'pdf') { ?>
        <iframe src="<?= $file_url ?>"
                width="100%"
                height="400"
                style="border:1px solid #ccc;border-radius:6px">
        </iframe>

    <?php } else { ?>
        <a href="<?= $file_url ?>" target="_blank">
            <?= basename($file_db) ?>
        </a>
    <?php } ?>

<?php } else { ?>
    <span class="text-danger">File tidak ditemukan</span>
<?php } ?>

            </td>
        </tr>
<?php
    }
} else {
?>
        <tr>
            <td colspan="5" class="text-center text-muted">
                Tidak ada berkas digital
            </td>
        </tr>
<?php } ?>

        </tbody>
    </table>
</div>



<!-- TAB ECHO -->
<div class="tab-pane fade" id="echo">
  <table class="table table-sm table-bordered">
    <thead class="table-light">
      <tr>
        <th width="8%" class="text-center">
  <label class="form-check-label small">
    <input type="checkbox" class="check-all-tab me-1">
    Pilih semua
  </label>
</th>
        <th width="5%">No</th>
        <th width="30%">Nomor Rawat</th>
        <th width="20%">Jenis</th>
        <th>Hasil</th>
      </tr>
    </thead>
    <tbody>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>1</td>
        <td>2025/06/18/00001</td>
        <td><span class="badge bg-info">Pediatrik</span></td>
        <td>ECHO Jantung Anak</td>
      </tr>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>2</td>
        <td>2025/06/18/00002</td>
        <td><span class="badge bg-warning text-dark">Biasa</span></td>
        <td>ECHO Jantung Dewasa</td>
      </tr>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>3</td>
        <td>2025/06/18/00003</td>
        <td><span class="badge bg-info">Pediatrik</span></td>
        <td>ECHO Jantung Anak</td>
      </tr>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>4</td>
        <td>2025/06/18/00004</td>
        <td><span class="badge bg-warning text-dark">Biasa</span></td>
        <td>ECHO Jantung Dewasa</td>
      </tr>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>5</td>
        <td>2025/06/18/00005</td>
        <td><span class="badge bg-info">Pediatrik</span></td>
        <td>ECHO Jantung Anak</td>
      </tr>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>6</td>
        <td>2025/06/18/00006</td>
        <td><span class="badge bg-warning text-dark">Biasa</span></td>
        <td>ECHO Jantung Dewasa</td>
      </tr>
      <tr class="riwayat-row">
        <td><input type="checkbox" class="row-check"></td>
        <td>7</td>
        <td>2025/06/18/00007</td>
        <td><span class="badge bg-info">Pediatrik</span></td>
        <td>ECHO Jantung Anak</td>
      </tr>
    </tbody>
  </table>
</div>

<!-- TAB RESUME -->
<?php
// =======================
// PARAMETER FILTER
// =======================
$no_rkm_medis = $_GET['no_rkm_medis'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// =======================
// LOGIKA LIMIT
// =======================
$limitSql = '';
if ($filter === 'last' && $limit > 0) {
    $limitSql = "LIMIT $limit";
}
?>

<div class="tab-pane fade" id="resume">
  <table class="table table-sm table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th width="8%" class="text-center">
          <label class="form-check-label small">
            <input type="checkbox" class="check-all-tab me-1">
            Pilih semua
          </label>
        </th>
        <th width="5%">No</th>
        <th width="30%">Nomor Rawat</th>
        <th width="20%">Jenis</th>
        <th>Hasil</th>
      </tr>
    </thead>
    <tbody>

<?php
$no = 1;
$ada_resume = false;

/* =====================
   RESUME RAWAT JALAN
   ===================== */
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
WHERE rp.no_rkm_medis = ?
ORDER BY rp.tgl_registrasi DESC
$limitSql
";

$stmt = $koneksi->prepare($sql_rj);
$stmt->bind_param("s", $no_rkm_medis);
$stmt->execute();
$rj = $stmt->get_result();

while ($r = $rj->fetch_assoc()) {
  $ada_resume = true;
?>
<tr class="riwayat-row">
  <td class="text-center"><input type="checkbox" class="row-check"></td>
  <td><?= $no++ ?></td>
  <td><?= htmlspecialchars($r['no_rawat']) ?></td>
  <td><span class="badge bg-success">Rawat Jalan</span></td>
  <td>
    <strong>No. Rawat:</strong> <?= $r['no_rawat'] ?><br>
    <strong>No. Registrasi:</strong> <?= $r['no_reg'] ?><br>
    <strong>Tanggal:</strong> <?= $r['tgl_registrasi'] ?><br>
    <strong>Umur:</strong> <?= $r['umurdaftar'] ?><br>
    <strong>Poliklinik:</strong> <?= $r['nm_poli'] ?><br>
    <strong>Dokter:</strong> <?= $r['nm_dokter'] ?><br>
    <strong>Status:</strong> <?= $r['status_lanjut'] ?><br>
    <strong>Keluhan:</strong> <?= nl2br($r['keluhan_utama']) ?><br>
    <strong>Diagnosa:</strong> <?= $r['diagnosa_utama'] ?><br>
    <strong>Kondisi Pulang:</strong> <?= $r['kondisi_pulang'] ?><br>
    <strong>Obat Pulang:</strong> <?= nl2br($r['obat_pulang']) ?>
  </td>
</tr>
<?php } ?>


<?php
/* =====================
   RESUME RAWAT INAP
   ===================== */
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
WHERE rp.no_rkm_medis = ?
ORDER BY rp.tgl_registrasi DESC
$limitSql
";

$stmt = $koneksi->prepare($sql_ri);
$stmt->bind_param("s", $no_rkm_medis);
$stmt->execute();
$ri = $stmt->get_result();

while ($r = $ri->fetch_assoc()) {
  $ada_resume = true;
?>
<tr class="riwayat-row">
  <td class="text-center"><input type="checkbox" class="row-check"></td>
  <td><?= $no++ ?></td>
  <td><?= htmlspecialchars($r['no_rawat']) ?></td>
  <td><span class="badge bg-danger">Rawat Inap</span></td>
  <td>
    <strong>No. Rawat:</strong> <?= $r['no_rawat'] ?><br>
    <strong>No. Registrasi:</strong> <?= $r['no_reg'] ?><br>
    <strong>Tanggal:</strong> <?= $r['tgl_registrasi'] ?><br>
    <strong>Umur:</strong> <?= $r['umurdaftar'] ?><br>
    <strong>Dokter:</strong> <?= $r['nm_dokter'] ?><br>
    <strong>Diagnosa Awal:</strong> <?= $r['diagnosa_awal'] ?><br>
    <strong>Keluhan:</strong> <?= nl2br($r['keluhan_utama']) ?><br>
    <strong>Diagnosa Utama:</strong> <?= $r['diagnosa_utama'] ?><br>
    <strong>Keadaan:</strong> <?= $r['keadaan'] ?><br>
    <strong>Cara Keluar:</strong> <?= $r['cara_keluar'] ?><br>
    <strong>Obat Pulang:</strong> <?= nl2br($r['obat_pulang']) ?>
  </td>
</tr>
<?php } ?>

<?php if (!$ada_resume) { ?>
<tr>
  <td colspan="5" class="text-center text-muted py-4">
    <em>Tidak ada data resume untuk pasien ini.</em>
  </td>
</tr>
<?php } ?>

    </tbody>
  </table>
</div>


<!-- TAB EEG -->
<div class="tab-pane fade" id="eeg">
<table class="table table-sm table-bordered">
<thead class="table-light">
<tr>
  <th width="8%" class="text-center">
  <label class="form-check-label small">
    <input type="checkbox" class="check-all-tab me-1">
    Pilih semua
  </label>
</th>
  <th width="5%">No</th>
  <th width="30%">Nomor Rawat</th>
  <th>Hasil</th>
</tr>
</thead>
<tbody>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>1</td><td>2025/06/18/00001</td><td>EEG Normal</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>2</td><td>2025/06/18/00002</td><td>EEG Abnormal</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>3</td><td>2025/06/18/00003</td><td>EEG Normal</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>4</td><td>2025/06/18/00004</td><td>EEG Normal</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>5</td><td>2025/06/18/00005</td><td>EEG Abnormal</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>6</td><td>2025/06/18/00006</td><td>EEG Normal</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>7</td><td>2025/06/18/00007</td><td>EEG Normal</td></tr>
</tbody>
</table>
</div>

<!-- TAB HBA1C -->
<div class="tab-pane fade" id="hba1c">
<table class="table table-sm table-bordered">
<thead class="table-light">
<tr>
  <th width="8%" class="text-center">
  <label class="form-check-label small">
    <input type="checkbox" class="check-all-tab me-1">
    Pilih semua
  </label>
</th>
  <th width="5%">No</th>
  <th width="30%">Nomor Rawat</th>
  <th>Hasil</th>
</tr>
</thead>
<tbody>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>1</td><td>2025/06/18/00001</td><td>6.2 %</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>2</td><td>2025/06/18/00002</td><td>7.1 %</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>3</td><td>2025/06/18/00003</td><td>6.8 %</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>4</td><td>2025/06/18/00004</td><td>7.5 %</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>5</td><td>2025/06/18/00005</td><td>6.0 %</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>6</td><td>2025/06/18/00006</td><td>7.0 %</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>7</td><td>2025/06/18/00007</td><td>6.5 %</td></tr>
</tbody>
</table>
</div>


<!-- TAB MMSE -->
<div class="tab-pane fade" id="mmse">
<table class="table table-sm table-bordered">
<thead class="table-light">
<tr>
  <th width="8%" class="text-center">
  <label class="form-check-label small">
    <input type="checkbox" class="check-all-tab me-1">
    Pilih semua
  </label>
</th>
  <th width="5%">No</th>
  <th width="30%">Nomor Rawat</th>
  <th>Hasil</th>
</tr>
</thead>
<tbody>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>1</td><td>2025/06/18/00001</td><td>Skor 28 (Normal)</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>2</td><td>2025/06/18/00002</td><td>Skor 24 (Gangguan Ringan)</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>3</td><td>2025/06/18/00003</td><td>Skor 26</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>4</td><td>2025/06/18/00004</td><td>Skor 23</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>5</td><td>2025/06/18/00005</td><td>Skor 27</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>6</td><td>2025/06/18/00006</td><td>Skor 25</td></tr>
<tr class="riwayat-row"><td><input type="checkbox" class="row-check"></td><td>7</td><td>2025/06/18/00007</td><td>Skor 29</td></tr>
</tbody>
</table>
</div>
    </div>
  </div>
</div>
</div>
</div>


<form action="cetak.php" method="post" target="_blank" class="text-center no-print">
  <input type="hidden" name="no_rkm_medis"
         value="<?php echo isset($pasien['no_rkm_medis']) ? $pasien['no_rkm_medis'] : ''; ?>">
  <input type="hidden" name="no_rawat"
         value="<?php echo isset($registrasi['no_rawat']) ? $registrasi['no_rawat'] : ''; ?>">
  <button type="submit" class="btn btn-primary btn-sm">
    CETAK PDF
  </button>
</form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  const inputJumlah = document.getElementById('jumlahRiwayat');
  const radioAll    = document.getElementById('riwayatAll');
  const radioLast   = document.getElementById('riwayatLast');

  function filterRiwayat() {
    const mode  = document.querySelector('input[name="filterRiwayat"]:checked').value;
    const limit = parseInt(inputJumlah.value || 0);

    document.querySelectorAll('.riwayat-row').forEach((row, index) => {
      if (mode === 'last') {
        row.style.display = index < limit ? '' : 'none';
      } else {
        row.style.display = '';
      }
    });
  }

  /* klik radio */
  radioAll.addEventListener('change', () => {
    inputJumlah.disabled = true;
    filterRiwayat();
  });

  radioLast.addEventListener('change', () => {
    inputJumlah.disabled = false;
    inputJumlah.focus();
    filterRiwayat();
  });

  /* klik / fokus input = auto aktif */
  inputJumlah.addEventListener('focus', () => {
    radioLast.checked = true;
    inputJumlah.disabled = false;
    filterRiwayat();
  });

  /* ketik angka = langsung jalan */
  inputJumlah.addEventListener('input', filterRiwayat);

  /* initial state */
  filterRiwayat();

});
</script>

<script>
document.addEventListener('change', function (e) {

  /* ===== PILIH SEMUA PER TABEL ===== */
  if (e.target.classList.contains('check-all-tab')) {
    const table = e.target.closest('table');
    if (!table) return;

    table.querySelectorAll('.row-check')
         .forEach(cb => cb.checked = e.target.checked);
  }

  /* ===== AUTO UPDATE CHECK ALL ===== */
  if (e.target.classList.contains('row-check')) {
    const table = e.target.closest('table');
    if (!table) return;

    const allCheck = table.querySelector('.check-all-tab');
    const checks = table.querySelectorAll('.row-check');

    allCheck.checked = [...checks].every(cb => cb.checked);
  }

});
</script>
</body>
</html>