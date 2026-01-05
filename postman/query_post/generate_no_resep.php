<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

// === 1. Koneksi database ===
$host = "192.168.0.100"; // Ganti dengan host database Anda
$user = "rsar"; // Ganti dengan username database Anda
$password = "Stbkhanza2025"; // Ganti dengan password database Anda
$database = "br_rsar"; // Ganti dengan nama database Anda

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Koneksi gagal: " . $conn->connect_error
    ]));
}

// === 2. Ambil tanggal dari input POST ===
$input = json_decode(file_get_contents("php://input"), true);

// Ambil parameter tanggal, bisa dari JSON atau dari form POST
$tanggal = isset($input['tanggal']) ? $input['tanggal'] : ($_POST['tanggal'] ?? null);

if (!$tanggal) {
    echo json_encode([
        "status" => "error",
        "message" => "Harap kirim parameter 'tanggal' (YYYY-MM-DD)"
    ]);
    exit;
}

// === 3. Ambil semua data dengan tanggal peresepan itu (hiraukan no_rawat dan jam) ===
$sql_resep = "SELECT no_resep
    FROM resep_obat
    WHERE DATE(tgl_peresepan) = '$tanggal'
    ORDER BY no_resep ASC
";
$result = $conn->query($sql_resep);

if (!$result || $result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Tidak ada data untuk tanggal $tanggal"]);
    exit;
}

// === 4. Buat prefix dan nomor urut baru ===
$prefix = date('Ymd', strtotime($tanggal));
$urut = 1;
$total_updated = 0;
$updated_list = [];

// === 5. Update semua baris yang tanggal peresepannya sama ===
while ($row = $result->fetch_assoc()) {
    $no_lama = $row['no_resep'];
    $no_baru = $prefix . str_pad($urut, 4, "0", STR_PAD_LEFT); // 202510300001 dst
    $urut++;

    // Update semua baris dengan no_resep lama ini dan tanggal yang sama
    $update_sql = "UPDATE resep_obat 
        SET no_resep = '$no_baru' 
        WHERE no_resep = '$no_lama' 
        AND DATE(tgl_peresepan) = '$tanggal'
    ";
    $conn->query($update_sql);

    $updated_list[] = [
        "no_resep_lama" => $no_lama,
        "no_resep_baru" => $no_baru
    ];
    $total_updated++;
}

// === 6. Kirim hasil JSON ===
echo json_encode([
    "status" => "success",
    "tanggal" => $tanggal,
    "prefix" => $prefix,
    "total_diubah" => $total_updated,
    "data" => $updated_list
], JSON_PRETTY_PRINT);

$conn->close();
?>