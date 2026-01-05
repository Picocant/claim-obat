<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('Asia/Jakarta');

// === KONFIGURASI DATABASE ===
$conn = new mysqli('192.168.0.100', 'rsar', 'Stbkhanza2025', 'br_rsar');
// $host = "localhost"; // Ganti dengan host database Anda
// $user = "root"; // Ganti dengan username database Anda
// $password = ""; // Ganti dengan password database Anda
// $database = "cek"; // Ganti dengan nama database Anda
//$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error)
    exit(json_encode(["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error]));

// === PARAMETER ===
$input = json_decode(file_get_contents('php://input'), true);
$no_rawat = $input['no_rawat'] ?? $_POST['no_rawat'] ?? $_GET['no_rawat'] ?? null;
if (!$no_rawat)
    exit(json_encode(["status" => "error", "message" => "Parameter no_rawat wajib dikirim."]));

// === DATA REGISTRASI ===
$stmt = $conn->prepare("SELECT no_rawat, no_rkm_medis, tgl_registrasi, biaya_reg FROM reg_periksa WHERE no_rawat=?");
$stmt->bind_param("s", $no_rawat);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data)
    exit(json_encode(["status" => "error", "message" => "Data tidak ditemukan untuk no_rawat: $no_rawat"]));

// === INISIALISASI ===
$tabels = ['rawat_jl_dr', 'rawat_jl_pr', 'rawat_jl_drpr', 'rawat_inap_dr', 'rawat_inap_pr', 'rawat_inap_drpr'];
$kelompok = array_fill_keys(array_merge(['registrasi'], $tabels, [
    'kamar_inap', 'operasi', 'periksa_radiologi', 'periksa_lab',
    'detail_pemberian_obat', 'beri_obat_operasi', 'resep_pulang', 'tambahan_biaya'
]), []);

$total_biaya = $total_kta = $total_konsultasi = 0;
$kategori_list = ['KA', 'KP', 'KTA', 'PJ', 'PB', 'PNB'];
$total_per_kategori = array_fill_keys($kategori_list, 0);

// === QUERY RAWAT (semua tabel rawat) ===
$sql_base = "SELECT r.kd_jenis_prw, j.kd_kategori, r.biaya_rawat AS biaya
             FROM %s r
             LEFT JOIN jns_perawatan j ON j.kd_jenis_prw = r.kd_jenis_prw
             WHERE r.no_rawat = ?";
foreach ($tabels as $tbl) {
    $stmt = $conn->prepare(sprintf($sql_base, $tbl));
    $stmt->bind_param("s", $no_rawat);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $kategori = strtoupper(trim($r['kd_kategori'] ?? '-'));
        $biaya = (float)$r['biaya'];
        $kelompok[$tbl][] = [
            'kd_jns_prw' => $r['kd_jenis_prw'],
            'kategori' => $kategori,
            'biaya' => $biaya
        ];
        $total_biaya += $biaya;
        if ($kategori === 'KTA') $total_kta += $biaya;
        if ($kategori === 'KONSULTASI') $total_konsultasi += $biaya;
        if (isset($total_per_kategori[$kategori])) $total_per_kategori[$kategori] += $biaya;
    }
}

// === KAMAR INAP ===
$q_kamar = $conn->prepare("SELECT ttl_biaya FROM kamar_inap WHERE no_rawat = ?");
$q_kamar->bind_param("s", $no_rawat);
$q_kamar->execute();
$res_kamar = $q_kamar->get_result();
$total_kamar = 0;
while ($r = $res_kamar->fetch_assoc()) {
    $biaya = (float)$r['ttl_biaya'];
    $kelompok['kamar_inap'][] = ['kategori' => 'KAMAR_INAP', 'biaya' => $biaya];
    $total_kamar += $biaya;
}
$total_biaya += $total_kamar;
$total_per_kategori['KA'] += $total_kamar;

// === OPERASI ===
$q_operasi = $conn->prepare("SELECT kode_paket,
        COALESCE(biayaoperator1,0)+COALESCE(biayaoperator2,0)+COALESCE(biayaoperator3,0)+
        COALESCE(biayaasisten_operator1,0)+COALESCE(biayaasisten_operator2,0)+COALESCE(biayaasisten_operator3,0)+
        COALESCE(biayainstrumen,0)+COALESCE(biayadokter_anak,0)+COALESCE(biayaperawaat_resusitas,0)+
        COALESCE(biayadokter_anestesi,0)+COALESCE(biayaasisten_anestesi,0)+COALESCE(biayaasisten_anestesi2,0)+
        COALESCE(biayabidan,0)+COALESCE(biayabidan2,0)+COALESCE(biayabidan3,0)+COALESCE(biayaperawat_luar,0)+
        COALESCE(biayaalat,0)+COALESCE(biayasewaok,0)+COALESCE(akomodasi,0)+COALESCE(bagian_rs,0)+
        COALESCE(biaya_omloop,0)+COALESCE(biaya_omloop2,0)+COALESCE(biaya_omloop3,0)+COALESCE(biaya_omloop4,0)+COALESCE(biaya_omloop5,0)+
        COALESCE(biayasarpras,0)+COALESCE(biaya_dokter_pjanak,0)+COALESCE(biaya_dokter_umum,0) AS total_biaya_operasi,
        biayaoperator1, biayaoperator2, biayaoperator3,
        biayaasisten_operator1, biayaasisten_operator2, biayaasisten_operator3,
        biayainstrumen, biayadokter_anak, biayaperawaat_resusitas,
        biayadokter_anestesi, biayaasisten_anestesi, biayaasisten_anestesi2,
        biayabidan, biayabidan2, biayabidan3, biayaperawat_luar,
        biayaalat, biayasewaok, akomodasi, bagian_rs,
        biaya_omloop, biaya_omloop2, biaya_omloop3, biaya_omloop4, biaya_omloop5,
        biayasarpras, biaya_dokter_pjanak, biaya_dokter_umum
    FROM operasi WHERE no_rawat = ?
");
$q_operasi->bind_param("s", $no_rawat);
$q_operasi->execute();
$res_operasi = $q_operasi->get_result();

$total_operasi = 0;
while ($r = $res_operasi->fetch_assoc()) {
    $biaya_total = (float)$r['total_biaya_operasi'];
    $kelompok['operasi'][] = [
        'kode_paket' => $r['kode_paket'],
        'rincian_biaya' => array_map('floatval', [
            'biayaoperator1' => $r['biayaoperator1'],
            'biayaoperator2' => $r['biayaoperator2'],
            'biayaoperator3' => $r['biayaoperator3'],
            'biayaasisten_operator1' => $r['biayaasisten_operator1'],
            'biayaasisten_operator2' => $r['biayaasisten_operator2'],
            'biayaasisten_operator3' => $r['biayaasisten_operator3'],
            'biayainstrumen' => $r['biayainstrumen'],
            'biayadokter_anak' => $r['biayadokter_anak'],
            'biayaperawaat_resusitas' => $r['biayaperawaat_resusitas'],
            'biayadokter_anestesi' => $r['biayadokter_anestesi'],
            'biayaasisten_anestesi' => $r['biayaasisten_anestesi'],
            'biayaasisten_anestesi2' => $r['biayaasisten_anestesi2'],
            'biayabidan' => $r['biayabidan'],
            'biayabidan2' => $r['biayabidan2'],
            'biayabidan3' => $r['biayabidan3'],
            'biayaperawat_luar' => $r['biayaperawat_luar'],
            'biayaalat' => $r['biayaalat'],
            'biayasewaok' => $r['biayasewaok'],
            'akomodasi' => $r['akomodasi'],
            'bagian_rs' => $r['bagian_rs'],
            'biaya_omloop' => $r['biaya_omloop'],
            'biaya_omloop2' => $r['biaya_omloop2'],
            'biaya_omloop3' => $r['biaya_omloop3'],
            'biaya_omloop4' => $r['biaya_omloop4'],
            'biaya_omloop5' => $r['biaya_omloop5'],
            'biayasarpras' => $r['biayasarpras'],
            'biaya_dokter_pjanak' => $r['biaya_dokter_pjanak'],
            'biaya_dokter_umum' => $r['biaya_dokter_umum']
        ]),
        'total' => $biaya_total
    ];
    $total_operasi += $biaya_total;
}
$total_biaya += $total_operasi;
$total_per_kategori['PB'] += $total_operasi;

// === PERIKSA RADIOLOGI ===
$q_rad = $conn->prepare("SELECT kd_jenis_prw, biaya FROM periksa_radiologi WHERE no_rawat = ?");
$q_rad->bind_param("s", $no_rawat);
$q_rad->execute();
$res_rad = $q_rad->get_result();
$total_rad = 0;
while ($r = $res_rad->fetch_assoc()) {
    $biaya = (float)$r['biaya'];
    $kelompok['periksa_radiologi'][] = ['kd_jns_prw' => $r['kd_jenis_prw'], 'biaya' => $biaya];
    $total_rad += $biaya;
}
$total_biaya += $total_rad;
$total_per_kategori['PJ'] += $total_rad;

// === PERIKSA LAB ===
$q_lab = $conn->prepare("SELECT kd_jenis_prw, biaya FROM periksa_lab WHERE no_rawat = ?");
$q_lab->bind_param("s", $no_rawat);
$q_lab->execute();
$res_lab = $q_lab->get_result();
$total_lab = 0;
while ($r = $res_lab->fetch_assoc()) {
    $biaya = (float)$r['biaya'];
    $kelompok['periksa_lab'][] = ['kd_jns_prw' => $r['kd_jenis_prw'], 'biaya' => $biaya];
    $total_lab += $biaya;
}
$total_biaya += $total_lab;
$total_per_kategori['PJ'] += $total_lab;

// === OBAT & RESEP ===
$queries_obat = [
    'detail_pemberian_obat' => ['SELECT total FROM detail_pemberian_obat WHERE no_rawat = ?', 'total'],
    'beri_obat_operasi' => ['SELECT jumlah FROM beri_obat_operasi WHERE no_rawat = ?', 'jumlah'],
    'resep_pulang' => ['SELECT total FROM resep_pulang WHERE no_rawat = ?', 'total']
];
$total_obat = $total_boo = $total_resep = 0;
foreach ($queries_obat as $key => [$sql, $field]) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $no_rawat);
    $stmt->execute();
    $res = $stmt->get_result();
    $subtotal = 0;
    while ($r = $res->fetch_assoc()) {
        $biaya = (float)$r[$field];
        $kelompok[$key][] = ['biaya' => $biaya];
        $subtotal += $biaya;
    }
    $$key = $subtotal;
    $total_per_kategori['PNB'] += $subtotal;
    $total_biaya += $subtotal;
}

// === TAMBAHAN BIAYA ===
$q_tambahan = $conn->prepare("SELECT besar_biaya FROM tambahan_biaya WHERE no_rawat = ?");
$q_tambahan->bind_param("s", $no_rawat);
$q_tambahan->execute();
$res_tambahan = $q_tambahan->get_result();
$total_tambahan = 0;
while ($r = $res_tambahan->fetch_assoc()) {
    $biaya = (float)$r['besar_biaya'];
    $kelompok['tambahan_biaya'][] = ['biaya' => $biaya];
    $total_tambahan += $biaya;
}
$total_biaya += $total_tambahan;

// === REGISTRASI ===
if ($data['biaya_reg'] > 0) {
    $kelompok['registrasi'][] = [
        'kd_jns_prw' => 'REG',
        'kategori' => 'REGISTRASI',
        'biaya' => (float)$data['biaya_reg']
    ];
    $total_biaya += (float)$data['biaya_reg'];
}

// === REKAP TAMBAHAN ===
$total_rekap = [
    'KTA' => $total_kta,
    'PNB' => $total_per_kategori['PNB'],
    'PB' => $total_per_kategori['PB'],
    'PJ' => $total_per_kategori['PJ'],
    'Lab' => $total_lab,
    'Radio' => $total_rad,
    'obat' => $total_obat + $total_boo + $total_resep,
    'KP' => $total_per_kategori['KP'],
    'KA' => $total_per_kategori['KA'],
    'tambahan' => $total_tambahan
];

// === OUTPUT ===
ksort($total_rekap);
echo json_encode([
    "status" => "success",
    "no_rawat" => $data['no_rawat'],
    "no_rkm_medis" => $data['no_rkm_medis'],
    "tgl_registrasi" => $data['tgl_registrasi'],
    "total_biaya" => $total_biaya,
    "total_per_kategori" => $total_per_kategori,
    "total_rekap" => $total_rekap,
    "kelompok_data" => $kelompok
], JSON_PRETTY_PRINT);

$conn->close();
?>