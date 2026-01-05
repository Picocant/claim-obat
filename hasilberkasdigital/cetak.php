<?php
function convert_pdf_to_jpg($pdf_path, $output_base) {

    // Lokasi Ghostscript di Windows (SESUAIKAN jika beda)
    $gs = '"C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe"';

    // Folder hasil convert
    $out_dir = __DIR__ . "/../berkasrawat_converted/";
    if(!is_dir($out_dir)){
        mkdir($out_dir, 0777, true);
    }

    $output_pattern = $out_dir . $output_base . "-%03d.jpg";

    // Jalankan Ghostscript
    $cmd = "$gs -dNOPAUSE -dBATCH -sDEVICE=jpeg ".
           "-r120 -dJPEGQ=85 ".
           "-sOutputFile=\"$output_pattern\" ".
           "\"$pdf_path\"";

    exec($cmd, $out, $status);

    return ($status === 0);
}

// =============================================
// 0. LOAD DOMPDF
// =============================================
require_once "dompdf/autoload.inc.php";
use Dompdf\Dompdf;

// =============================================
// 1. LOAD FUNGSI SIMRS
// =============================================
include_once "conf/command.php";
require_once('../conf/conf.php');

// =============================================
// 2. GET PARAMETER
// =============================================
$no_rawat = validTeks4($_GET['no_rawat'] ?? "", 20);

// Bersihkan nomor rawat untuk nama file
$no_rawat_clean = preg_replace('/[^A-Za-z0-9]/', '', $no_rawat);

//============================================
//FUNGSI KONEKSI DATABASE
//============================================

class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;
    
    public function __construct() {
        $this->host = 'localhost';
        $this->username = 'username';
        $this->password = 'password';
        $this->database = 'database_name';
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database}", 
                $this->username, 
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function closeConnection() {
        $this->connection = null;
    }
}

// =============================================
// 2 FUNGSI VALIDASI TEKS 
// =============================================

class Validasi {
    public function pindah($event, $from, $to) {
        // Simulasi fungsi pindah focus
        if ($event === 'enter') {
            return $to;
        }
        return $from;
    }
    
    public function textKosong($field, $fieldName) {
        if (empty(trim($field))) {
            throw new Exception("Field $fieldName tidak boleh kosong");
        }
    }
    
    public function MyReport2($reportName, $type, $title, $params) {
        // Fungsi untuk generate report
        $reportData = [
            'report_name' => $reportName,
            'type' => $type,
            'title' => $title,
            'parameters' => $params
        ];
        
        return $this->generatePDF($reportData);
    }
    
    private function generatePDF($data) {
        // Implementasi generate PDF
        return "PDF generated for: " . $data['report_name'];
    }
}

// =============================================
// 3. FUNGSI SEKUEL DATABASE
// =============================================

class Sekuel {
    private $db;
    
    public function __construct() {
        $this->db = (new Database())->getConnection();
    }
    
    public function queryu($sql) {
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function menyimpan($table, $placeholders, $paramCount, $values) {
        $placeholdersStr = implode(',', array_fill(0, $paramCount, '?'));
        $sql = "INSERT INTO $table VALUES ($placeholdersStr)";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }
    
    public function cariIsi($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? reset($result) : null;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function cariGambar($sql) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['logo'] : null;
        } catch (PDOException $e) {
            throw new Exception("Image query failed: " . $e->getMessage());
        }
    }
}

// =============================================
// FUNGSI AKSES
// =============================================
class Akses {
    private $userData;
    
    public function __construct() {
        $this->userData = [
            'namars' => 'Nama Rumah Sakit',
            'alamatrs' => 'Alamat Rumah Sakit',
            'kabupatenrs' => 'Kabupaten',
            'propinsirs' => 'Propinsi',
            'kontakrs' => 'Kontak',
            'emailrs' => 'email@rs.com',
            'kode' => 'user123'
        ];
    }
    
    public function __call($name, $arguments) {
        if (isset($this->userData[$name])) {
            return $this->userData[$name];
        }
        return null;
    }
    
    public function getpasien() {
        // Check if user has access to patient data
        return true;
    }
}

//============================================
// FUNGSI UTAMA DARI CLASS 
//============================================
class RMRiwayatPerawatan {
    private $validasi;
    private $sekuel;
    private $akses;
    private $koneksi;
    
    public function __construct() {
        $this->validasi = new Validasi();
        $this->sekuel = new Sekuel();
        $this->akses = new Akses();
        $this->koneksi = (new Database())->getConnection();
    }
    
    // Fungsi untuk menampilkan data kunjungan
    public function tampilKunjungan($noRM, $jenisFilter, $tgl1 = null, $tgl2 = null, $noRawat = null) {
        $sql = "SELECT 
                    @no := @no + 1 AS no,
                    r.no_rawat,
                    DATE_FORMAT(r.tgl_registrasi, '%d-%m-%Y') AS tanggal,
                    DATE_FORMAT(r.jam_reg, '%H:%i') AS jam,
                    r.kd_dokter,
                    d.nm_dokter,
                    TIMESTAMPDIFF(YEAR, p.tgl_lahir, r.tgl_registrasi) AS umur,
                    COALESCE(p2.nm_poli, k.nm_bangsal) AS ruangan,
                    pj.png_jawab AS jenis_bayar
                FROM reg_periksa r 
                INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis 
                LEFT JOIN dokter d ON r.kd_dokter = d.kd_dokter 
                LEFT JOIN poliklinik p2 ON r.kd_poli = p2.kd_poli 
                LEFT JOIN kamar k ON r.kd_kamar = k.kd_kamar 
                LEFT JOIN penjab pj ON r.kd_pj = pj.kd_pj 
                CROSS JOIN (SELECT @no := 0) AS no_counter
                WHERE r.no_rkm_medis = ?";
        
        $params = [$noRM];
        
        switch ($jenisFilter) {
            case '5_terakhir':
                $sql .= " ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC LIMIT 5";
                break;
            case 'tanggal':
                if ($tgl1 && $tgl2) {
                    $sql .= " AND r.tgl_registrasi BETWEEN ? AND ?";
                    $params[] = $tgl1;
                    $params[] = $tgl2;
                }
                $sql .= " ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC";
                break;
            case 'nomor':
                if ($noRawat) {
                    $sql .= " AND r.no_rawat = ?";
                    $params[] = $noRawat;
                }
                break;
            default:
                $sql .= " ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC";
                break;
        }
        
        try {
            $stmt = $this->koneksi->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    // Fungsi untuk mendapatkan data pasien
    public function isPasien($noRM) {
        $sql = "SELECT 
                    p.no_rkm_medis,
                    p.nm_pasien,
                    p.jk,
                    p.tmp_lahir,
                    DATE_FORMAT(p.tgl_lahir, '%d-%m-%Y') AS tgl_lahir,
                    p.nm_ibu,
                    p.alamat,
                    p.gol_darah,
                    p.stts_nikah,
                    p.agama,
                    p.pnd,
                    p.bahasa,
                    p.cacat_fisik
                FROM pasien p 
                WHERE p.no_rkm_medis = ?";
        
        try {
            $stmt = $this->koneksi->prepare($sql);
            $stmt->execute([$noRM]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    // Fungsi untuk menampilkan riwayat perawatan
    public function tampilPerawatan($noRawat, $filters = []) {
        $htmlContent = new StringBuilder();
        
        // Header HTML
        $htmlContent->append("<html><head><title>Riwayat Perawatan</title></head><body>");
        
        // Tambahkan konten berdasarkan filter yang dipilih
        if ($filters['diagnosa_penyakit'] ?? true) {
            $htmlContent->append($this->getDiagnosaPenyakit($noRawat));
        }
        
        if ($filters['prosedur_tindakan'] ?? true) {
            $htmlContent->append($this->getProsedurTindakan($noRawat));
        }
        
        if ($filters['tindakan_ralan_dokter'] ?? true) {
            $htmlContent->append($this->getTindakanRalanDokter($noRawat));
        }
        
        // ... tambahkan filter lainnya sesuai kebutuhan
        
        $htmlContent->append("</body></html>");
        
        return $htmlContent->toString();
    }
    
    private function getDiagnosaPenyakit($noRawat) {
        $sql = "SELECT d.kd_penyakit, p.nm_penyakit, d.status 
                FROM diagnosa_pasien d 
                INNER JOIN penyakit p ON d.kd_penyakit = p.kd_penyakit 
                WHERE d.no_rawat = ?";
        
        try {
            $stmt = $this->koneksi->prepare($sql);
            $stmt->execute([$noRawat]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = "<h3>Diagnosa/Penyakit</h3><table border='1'>";
            foreach ($results as $row) {
                $html .= "<tr><td>{$row['kd_penyakit']}</td><td>{$row['nm_penyakit']}</td><td>{$row['status']}</td></tr>";
            }
            $html .= "</table>";
            
            return $html;
        } catch (PDOException $e) {
            return "<p>Error loading diagnosa: " . $e->getMessage() . "</p>";
        }
    }
    
    private function getProsedurTindakan($noRawat) {
        $sql = "SELECT p.kd_jenis_pr, j.nm_perawatan, p.status 
                FROM prosedur_pasien p 
                INNER JOIN jns_perawatan j ON p.kd_jenis_pr = j.kd_jenis_pr 
                WHERE p.no_rawat = ?";
        
        try {
            $stmt = $this->koneksi->prepare($sql);
            $stmt->execute([$noRawat]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = "<h3>Prosedur/Tindakan</h3><table border='1'>";
            foreach ($results as $row) {
                $html .= "<tr><td>{$row['kd_jenis_pr']}</td><td>{$row['nm_perawatan']}</td><td>{$row['status']}</td></tr>";
            }
            $html .= "</table>";
            
            return $html;
        } catch (PDOException $e) {
            return "<p>Error loading prosedur: " . $e->getMessage() . "</p>";
        }
    }
    
    private function getTindakanRalanDokter($noRawat) {
        $sql = "SELECT j.nm_perawatan, t.biaya, t.jml, (t.biaya * t.jml) AS total 
                FROM rawat_jl_dr t 
                INNER JOIN jns_perawatan j ON t.kd_jenis_pr = j.kd_jenis_pr 
                WHERE t.no_rawat = ?";
        
        try {
            $stmt = $this->koneksi->prepare($sql);
            $stmt->execute([$noRawat]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = "<h3>Tindakan Ralan Dokter</h3><table border='1'>";
            $totalBiaya = 0;
            foreach ($results as $row) {
                $total = $row['total'];
                $totalBiaya += $total;
                $html .= "<tr><td>{$row['nm_perawatan']}</td><td>" . number_format($row['biaya'], 0, ',', '.') . "</td><td>{$row['jml']}</td><td>" . number_format($total, 0, ',', '.') . "</td></tr>";
            }
            $html .= "<tr><td colspan='3'><strong>Total</strong></td><td><strong>" . number_format($totalBiaya, 0, ',', '.') . "</strong></td></tr>";
            $html .= "</table>";
            
            return $html;
        } catch (PDOException $e) {
            return "<p>Error loading tindakan: " . $e->getMessage() . "</p>";
        }
    }
    
    // Fungsi untuk generate PDF
    public function generatePDF($noRawat, $dataPasien, $htmlContent, $type = 'regular') {
        $filename = "RPP" . str_replace('/', '', $noRawat) . ".pdf";
        
        switch ($type) {
            case 'esign':
                return $this->generatePDFWithESign($filename, $dataPasien, $htmlContent);
            case 'sertisign':
                return $this->generatePDFWithSertiSign($filename, $dataPasien, $htmlContent);
            default:
                return $this->generateRegularPDF($filename, $dataPasien, $htmlContent);
        }
    }
    
    private function generateRegularPDF($filename, $dataPasien, $htmlContent) {
        // Implementasi generate PDF regular
        $pdfData = [
            'filename' => $filename,
            'patient_data' => $dataPasien,
            'content' => $htmlContent
        ];
        
        return $this->createPDFFile($pdfData);
    }
    
    private function generatePDFWithESign($filename, $dataPasien, $htmlContent) {
        // Implementasi generate PDF dengan E-Sign
        $pdfData = [
            'filename' => $filename,
            'patient_data' => $dataPasien,
            'content' => $htmlContent,
            'signature_type' => 'e-sign'
        ];
        
        return $this->createPDFFile($pdfData);
    }
    
    private function generatePDFWithSertiSign($filename, $dataPasien, $htmlContent) {
        // Implementasi generate PDF dengan SertiSign
        $pdfData = [
            'filename' => $filename,
            'patient_data' => $dataPasien,
            'content' => $htmlContent,
            'signature_type' => 'sertisign'
        ];
        
        return $this->createPDFFile($pdfData);
    }
    
    private function createPDFFile($data) {
        // Simulasi pembuatan file PDF
        $filePath = "/tmp/" . $data['filename'];
        file_put_contents($filePath, json_encode($data));
        return $filePath;
    }
    
    // Fungsi untuk memanggil laporan
    public function panggilLaporan($htmlContent) {
        // Process HTML content for reporting
        return [
            'status' => 'success',
            'content' => $htmlContent,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Helper class untuk string building
class StringBuilder {
    private $strings = [];
    
    public function append($string) {
        $this->strings[] = $string;
        return $this;
    }
    
    public function toString() {
        return implode('', $this->strings);
    }
    
    public function clear() {
        $this->strings = [];
        return $this;
    }
}


// =============================================
// QUERY TAMBAHAN RUJUKAN
// =============================================

// Query untuk data rujukan internal poli
$query_rujukan_internal = "
SELECT 
    rujukan_internal_poli.kd_dokter,
    dokter.nm_dokter,
    rujukan_internal_poli.kd_poli,
    poliklinik.nm_poli 
FROM rujukan_internal_poli 
INNER JOIN dokter ON rujukan_internal_poli.kd_dokter = dokter.kd_dokter 
INNER JOIN poliklinik ON rujukan_internal_poli.kd_poli = poliklinik.kd_poli 
WHERE rujukan_internal_poli.no_rawat = ?
";

// Query untuk data kamar inap
$query_kamar_inap = "
SELECT 
    kamar_inap.tgl_masuk,
    kamar_inap.jam_masuk,
    kamar_inap.kd_kamar,
    bangsal.nm_bangsal 
FROM kamar_inap 
INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar 
INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal 
WHERE kamar_inap.no_rawat = ?
";

// Query untuk DPJP Ranap
$query_dpjp_ranap = "
SELECT dpjp_ranap.kd_dokter 
FROM dpjp_ranap 
WHERE dpjp_ranap.no_rawat = ?
";

// Query untuk nama dokter DPJP
$query_nama_dokter = "
SELECT dokter.nm_dokter 
FROM dokter 
WHERE dokter.kd_dokter = ?
";


// =============================================
// QUERY REGISTRASI PERIKSA BERDASARKAN FILTER
// =============================================

// Query untuk R1 (5 data terbaru)
$query_r1 = "
SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg,
       reg_periksa.kd_dokter, dokter.nm_dokter, poliklinik.nm_poli, reg_periksa.p_jawab, reg_periksa.almt_pj,
       reg_periksa.hubunganpj, reg_periksa.biaya_reg, reg_periksa.status_lanjut, penjab.png_jawab,
       reg_periksa.umurdaftar, reg_periksa.sttsumur 
FROM reg_periksa 
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
WHERE reg_periksa.stts <> 'Batal' AND reg_periksa.no_rkm_medis = ? 
ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 5
";

// Query untuk R2 (semua data)
$query_r2 = "
SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg,
       reg_periksa.kd_dokter, dokter.nm_dokter, poliklinik.nm_poli, reg_periksa.p_jawab, reg_periksa.almt_pj,
       reg_periksa.hubunganpj, reg_periksa.biaya_reg, reg_periksa.status_lanjut, penjab.png_jawab,
       reg_periksa.umurdaftar, reg_periksa.sttsumur 
FROM reg_periksa 
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
WHERE reg_periksa.stts <> 'Batal' AND reg_periksa.no_rkm_medis = ? 
ORDER BY reg_periksa.tgl_registrasi
";

// Query untuk R3 (berdasarkan tanggal)
$query_r3 = "
SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg,
       reg_periksa.kd_dokter, dokter.nm_dokter, poliklinik.nm_poli, reg_periksa.p_jawab, reg_periksa.almt_pj,
       reg_periksa.hubunganpj, reg_periksa.biaya_reg, reg_periksa.status_lanjut, penjab.png_jawab,
       reg_periksa.umurdaftar, reg_periksa.sttsumur 
FROM reg_periksa 
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
WHERE reg_periksa.stts <> 'Batal' AND reg_periksa.no_rkm_medis = ? 
AND reg_periksa.tgl_registrasi BETWEEN ? AND ? 
ORDER BY reg_periksa.tgl_registrasi
";

// Query untuk R4 (berdasarkan no_rawat spesifik)
$query_r4 = "
SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg,
       reg_periksa.kd_dokter, dokter.nm_dokter, poliklinik.nm_poli, reg_periksa.p_jawab, reg_periksa.almt_pj,
       reg_periksa.hubunganpj, reg_periksa.biaya_reg, reg_periksa.status_lanjut, penjab.png_jawab,
       reg_periksa.umurdaftar, reg_periksa.sttsumur 
FROM reg_periksa 
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
WHERE reg_periksa.stts <> 'Batal' AND reg_periksa.no_rkm_medis = ? AND reg_periksa.no_rawat = ?
";


// =============================================
// QUERY RUJUKAN INTERNAL
//  ============================================

$query_rujukan_internal = "
SELECT poliklinik.nm_poli, dokter.nm_dokter 
FROM rujukan_internal_poli 
INNER JOIN poliklinik ON rujukan_internal_poli.kd_poli = poliklinik.kd_poli 
INNER JOIN dokter ON rujukan_internal_poli.kd_dokter = dokter.kd_dokter 
WHERE no_rawat = ?
";

// =============================================
// QUERY DPJP RANAP
// =============================================

$query_dpjp_ranap = "
SELECT dokter.nm_dokter
FROM dpjp_ranap
INNER JOIN dokter ON dpjp_ranap.kd_dokter = dokter.kd_dokter
WHERE dpjp_ranap.no_rawat = ?
";

// =============================================
// QUERY CATATAN PERAWATAN
// =============================================

$query_catatan_perawatan = "
SELECT catatan_perawatan.tanggal, catatan_perawatan.jam, catatan_perawatan.kd_dokter, dokter.nm_dokter,
       catatan_perawatan.catatan

FROM catatan_perawatan
INNER JOIN dokter ON catatan_perawatan.kd_dokter = dokter.kd_dokter
WHERE catatan_perawatan.no_rawat = ?
ORDER BY catatan_perawatan.tanggal, catatan_perawatan.jam
";


// =============================================
// QUERY TINDAKAN RAWAT JALAN
// =============================================

// Tindakan Rawat Jalan Dokter
$query_tindakan_ralan_dokter = "
SELECT rawat_jl_dr.kd_jenis_prw, jns_perawatan.nm_perawatan, dokter.nm_dokter, rawat_jl_dr.biaya_rawat,
       rawat_jl_dr.tgl_perawatan, rawat_jl_dr.jam_rawat 
FROM rawat_jl_dr 
INNER JOIN jns_perawatan ON rawat_jl_dr.kd_jenis_prw = jns_perawatan.kd_jenis_prw 
INNER JOIN dokter ON rawat_jl_dr.kd_dokter = dokter.kd_dokter 
WHERE rawat_jl_dr.no_rawat = ? 
ORDER BY rawat_jl_dr.tgl_perawatan, rawat_jl_dr.jam_rawat
";

// Tindakan Rawat Jalan Paramedis
$query_tindakan_ralan_paramedis = "
SELECT rawat_jl_pr.kd_jenis_prw, jns_perawatan.nm_perawatan, petugas.nama, rawat_jl_pr.biaya_rawat,
       rawat_jl_pr.tgl_perawatan, rawat_jl_pr.jam_rawat 
FROM rawat_jl_pr 
INNER JOIN jns_perawatan ON rawat_jl_pr.kd_jenis_prw = jns_perawatan.kd_jenis_prw 
INNER JOIN petugas ON rawat_jl_pr.nip = petugas.nip 
WHERE rawat_jl_pr.no_rawat = ? 
ORDER BY rawat_jl_pr.tgl_perawatan, rawat_jl_pr.jam_rawat
";

// Tindakan Rawat Jalan Dokter & Paramedis
$query_tindakan_ralan_dokter_paramedis = "
SELECT rawat_jl_drpr.kd_jenis_prw, jns_perawatan.nm_perawatan, dokter.nm_dokter, petugas.nama, rawat_jl_drpr.biaya_rawat,
       rawat_jl_drpr.tgl_perawatan, rawat_jl_drpr.jam_rawat 
FROM rawat_jl_drpr 
INNER JOIN jns_perawatan ON rawat_jl_drpr.kd_jenis_prw = jns_perawatan.kd_jenis_prw 
INNER JOIN dokter ON rawat_jl_drpr.kd_dokter = dokter.kd_dokter 
INNER JOIN petugas ON rawat_jl_drpr.nip = petugas.nip 
WHERE rawat_jl_drpr.no_rawat = ? 
ORDER BY rawat_jl_drpr.tgl_perawatan, rawat_jl_drpr.jam_rawat
";


// =============================================
// QUERY TINDAKAN RAWAT INAP
// =============================================

// Tindakan Rawat Inap Dokter
$query_tindakan_ranap_dokter = "
SELECT rawat_inap_dr.tgl_perawatan, rawat_inap_dr.jam_rawat, rawat_inap_dr.kd_jenis_prw, jns_perawatan_inap.nm_perawatan,
       dokter.nm_dokter, rawat_inap_dr.biaya_rawat 
FROM rawat_inap_dr 
INNER JOIN jns_perawatan_inap ON rawat_inap_dr.kd_jenis_prw = jns_perawatan_inap.kd_jenis_prw 
INNER JOIN dokter ON rawat_inap_dr.kd_dokter = dokter.kd_dokter 
WHERE rawat_inap_dr.no_rawat = ? 
ORDER BY rawat_inap_dr.tgl_perawatan, rawat_inap_dr.jam_rawat
";

// Tindakan Rawat Inap Paramedis
$query_tindakan_ranap_paramedis = "
SELECT rawat_inap_pr.tgl_perawatan, rawat_inap_pr.jam_rawat, rawat_inap_pr.kd_jenis_prw, jns_perawatan_inap.nm_perawatan,
       petugas.nama, rawat_inap_pr.biaya_rawat 
FROM rawat_inap_pr 
INNER JOIN jns_perawatan_inap ON rawat_inap_pr.kd_jenis_prw = jns_perawatan_inap.kd_jenis_prw 
INNER JOIN petugas ON rawat_inap_pr.nip = petugas.nip 
WHERE rawat_inap_pr.no_rawat = ? 
ORDER BY rawat_inap_pr.tgl_perawatan, rawat_inap_pr.jam_rawat
";

// Tindakan Rawat Inap Dokter & Paramedis
$query_tindakan_ranap_dokter_paramedis = "
SELECT rawat_inap_drpr.tgl_perawatan, rawat_inap_drpr.jam_rawat, rawat_inap_drpr.kd_jenis_prw, jns_perawatan_inap.nm_perawatan,
       dokter.nm_dokter, petugas.nama, rawat_inap_drpr.biaya_rawat 
FROM rawat_inap_drpr 
INNER JOIN jns_perawatan_inap ON rawat_inap_drpr.kd_jenis_prw = jns_perawatan_inap.kd_jenis_prw 
INNER JOIN dokter ON rawat_inap_drpr.kd_dokter = dokter.kd_dokter 
INNER JOIN petugas ON rawat_inap_drpr.nip = petugas.nip 
WHERE rawat_inap_drpr.no_rawat = ? 
ORDER BY rawat_inap_drpr.tgl_perawatan, rawat_inap_drpr.jam_rawat
";


// =============================================
// QUERY PENGGUNAAN KAMAR
//  ============================================


$query_penggunaan_kamar = "
SELECT kamar_inap.kd_kamar, bangsal.nm_bangsal, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar,
       kamar_inap.stts_pulang, kamar_inap.lama, kamar_inap.jam_masuk, kamar_inap.jam_keluar, kamar_inap.ttl_biaya 
FROM kamar_inap 
INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar 
INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal 
WHERE kamar_inap.no_rawat = ? 
ORDER BY kamar_inap.tgl_masuk, kamar_inap.jam_masuk
";


// =============================================
// QUERY OPERASI/VK
// =============================================

$query_operasi = "
SELECT operasi.tgl_operasi, operasi.jenis_anasthesi, operasi.operator1, operasi.operator2, operasi.operator3, operasi.asisten_operator1,
       operasi.asisten_operator2, operasi.asisten_operator3, operasi.biayaasisten_operator3, operasi.instrumen, operasi.dokter_anak, operasi.perawaat_resusitas,
       operasi.dokter_anestesi, operasi.asisten_anestesi, operasi.asisten_anestesi2, operasi.asisten_anestesi2, operasi.bidan, operasi.bidan2, operasi.bidan3, operasi.perawat_luar, operasi.omloop,
       operasi.omloop2, operasi.omloop3, operasi.omloop4, operasi.omloop5, operasi.dokter_pjanak, operasi.dokter_umum,
       operasi.kode_paket, paket_operasi.nm_perawatan, operasi.biayaoperator1, operasi.biayaoperator2, operasi.biayaoperator3,
       operasi.biayaasisten_operator1, operasi.biayaasisten_operator2, operasi.biayaasisten_operator3, operasi.biayainstrumen,
       operasi.biayadokter_anak, operasi.biayaperawaat_resusitas, operasi.biayadokter_anestesi,
       operasi.biayaasisten_anestesi, operasi.biayaasisten_anestesi2, operasi.biayabidan, operasi.biayabidan2, operasi.biayabidan3, operasi.biayaperawat_luar, operasi.biayaalat,
       operasi.biayasewaok, operasi.akomodasi, operasi.bagian_rs, operasi.biaya_omloop, operasi.biaya_omloop2, operasi.biaya_omloop3, operasi.biaya_omloop4, operasi.biaya_omloop5,
       operasi.biayasarpras, operasi.biaya_dokter_pjanak, operasi.biaya_dokter_umum,
       (operasi.biayaoperator1 + operasi.biayaoperator2 + operasi.biayaoperator3 +
        operasi.biayaasisten_operator1 + operasi.biayaasisten_operator2 + operasi.biayaasisten_operator3 + operasi.biayainstrumen +
        operasi.biayadokter_anak + operasi.biayaperawaat_resusitas + operasi.biayadokter_anestesi +
        operasi.biayaasisten_anestesi + operasi.biayaasisten_anestesi2 + operasi.biayabidan + operasi.biayabidan2 + operasi.biayabidan3 + operasi.biayaperawat_luar + operasi.biayaalat +
        operasi.biayasewaok + operasi.akomodasi + operasi.bagian_rs + operasi.biaya_omloop + operasi.biaya_omloop2 + operasi.biaya_omloop3 + operasi.biaya_omloop4 + operasi.biaya_omloop5 +
        operasi.biayasarpras + operasi.biaya_dokter_pjanak + operasi.biaya_dokter_umum) as total 
FROM operasi 
INNER JOIN paket_operasi ON operasi.kode_paket = paket_operasi.kode_paket 
WHERE operasi.no_rawat = ? 
ORDER BY operasi.tgl_operasi
";

$query_laporan_operasi = "
SELECT laporan_operasi.tanggal, laporan_operasi.diagnosa_preop, laporan_operasi.diagnosa_postop, laporan_operasi.jaringan_dieksekusi,
       laporan_operasi.selesaioperasi, laporan_operasi.permintaan_pa, laporan_operasi.laporan_operasi, laporan_operasi.nomor_implan 
FROM laporan_operasi 
WHERE no_rawat = ? 
GROUP BY no_rawat, tanggal 
ORDER BY tanggal
";


// =============================================
// QUERY PEMERIKSAAN RADIOLOGI
// =============================================

$query_pemeriksaan_radiologi = "
SELECT periksa_radiologi.tgl_periksa, periksa_radiologi.jam, periksa_radiologi.kd_jenis_prw,
       jns_perawatan_radiologi.nm_perawatan, petugas.nama, periksa_radiologi.biaya, periksa_radiologi.dokter_perujuk,
       dokter.nm_dokter, CONCAT(
           IF(periksa_radiologi.proyeksi <> '', CONCAT('Proyeksi : ', periksa_radiologi.proyeksi, ', '), ''),
           IF(periksa_radiologi.kV <> '', CONCAT('kV : ', periksa_radiologi.kV, ', '), ''),
           IF(periksa_radiologi.mAS <> '', CONCAT('mAS : ', periksa_radiologi.mAS, ', '), ''),
           IF(periksa_radiologi.FFD <> '', CONCAT('FFD : ', periksa_radiologi.FFD, ', '), ''),
           IF(periksa_radiologi.BSF <> '', CONCAT('BSF : ', periksa_radiologi.BSF, ', '), ''),
           IF(periksa_radiologi.inak <> '', CONCAT('Inak : ', periksa_radiologi.inak, ', '), ''),
           IF(periksa_radiologi.jml_penyinaran <> '', CONCAT('Jml Penyinaran : ', periksa_radiologi.jml_penyinaran, ', '), ''),
           IF(periksa_radiologi.dosis <> '', CONCAT('Dosis Radiasi : ', periksa_radiologi.dosis), '')
       ) as proyeksi 
FROM periksa_radiologi 
INNER JOIN jns_perawatan_radiologi ON periksa_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw 
INNER JOIN petugas ON periksa_radiologi.nip = petugas.nip 
INNER JOIN dokter ON periksa_radiologi.kd_dokter = dokter.kd_dokter 
WHERE periksa_radiologi.no_rawat = ? 
ORDER BY periksa_radiologi.tgl_periksa, periksa_radiologi.jam
";

$query_hasil_radiologi = "
SELECT hasil_radiologi.tgl_periksa, hasil_radiologi.jam, hasil_radiologi.hasil 
FROM hasil_radiologi 
WHERE hasil_radiologi.no_rawat = ? 
ORDER BY hasil_radiologi.tgl_periksa, hasil_radiologi.jam
";

$query_gambar_radiologi = "
SELECT gambar_radiologi.tgl_periksa, gambar_radiologi.jam, gambar_radiologi.lokasi_gambar 
FROM gambar_radiologi 
WHERE gambar_radiologi.no_rawat = ? 
ORDER BY gambar_radiologi.tgl_periksa, gambar_radiologi.jam
";


// =============================================
// QUERY PEMERIKSAAN LABORATORIUM
// =============================================

// Pemeriksaan Lab PK & MB
$query_periksa_lab_group = "
SELECT periksa_lab.tgl_periksa, periksa_lab.jam 
FROM periksa_lab 
WHERE periksa_lab.kategori <> 'PA' AND periksa_lab.no_rawat = ? 
GROUP BY CONCAT(periksa_lab.no_rawat, periksa_lab.tgl_periksa, periksa_lab.jam) 
ORDER BY periksa_lab.tgl_periksa, periksa_lab.jam
";

$query_periksa_lab_detail = "
SELECT periksa_lab.kd_jenis_prw, jns_perawatan_lab.nm_perawatan, petugas.nama, periksa_lab.biaya, periksa_lab.dokter_perujuk, dokter.nm_dokter 
FROM periksa_lab 
INNER JOIN jns_perawatan_lab ON periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw 
INNER JOIN petugas ON periksa_lab.nip = petugas.nip 
INNER JOIN dokter ON periksa_lab.kd_dokter = dokter.kd_dokter 
WHERE periksa_lab.kategori <> 'PA' AND periksa_lab.no_rawat = ? 
AND periksa_lab.tgl_periksa = ? AND periksa_lab.jam = ?
";

$query_detail_periksa_lab = "
SELECT template_laboratorium.Pemeriksaan, detail_periksa_lab.nilai, template_laboratorium.satuan, 
       detail_periksa_lab.nilai_rujukan, detail_periksa_lab.biaya_item, detail_periksa_lab.keterangan 
FROM detail_periksa_lab 
INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template 
WHERE detail_periksa_lab.no_rawat = ? AND detail_periksa_lab.kd_jenis_prw = ? 
AND detail_periksa_lab.tgl_periksa = ? AND detail_periksa_lab.jam = ? 
ORDER BY detail_periksa_lab.kd_jenis_prw, template_laboratorium.urut
";

$query_saran_kesan_lab = "
SELECT saran_kesan_lab.saran, saran_kesan_lab.kesan 
FROM saran_kesan_lab 
WHERE saran_kesan_lab.no_rawat = ? AND saran_kesan_lab.tgl_periksa = ? AND saran_kesan_lab.jam = ?
";

// Pemeriksaan Lab PA
$query_periksa_lab_pa = "
SELECT periksa_lab.tgl_periksa, periksa_lab.jam, periksa_lab.kd_jenis_prw, jns_perawatan_lab.nm_perawatan, 
       petugas.nama, periksa_lab.biaya, periksa_lab.dokter_perujuk, dokter.nm_dokter 
FROM periksa_lab 
INNER JOIN jns_perawatan_lab ON periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw 
INNER JOIN petugas ON periksa_lab.nip = petugas.nip 
INNER JOIN dokter ON periksa_lab.kd_dokter = dokter.kd_dokter 
WHERE periksa_lab.kategori = 'PA' AND periksa_lab.no_rawat = ? 
ORDER BY periksa_lab.tgl_periksa, periksa_lab.jam
";

$query_detail_periksa_labpa = "
SELECT detail_periksa_labpa.stts_validasi_labpa, detail_periksa_labpa.diagnosa_klinik, detail_periksa_labpa.makroskopik, 
       detail_periksa_labpa.mikroskopik, detail_periksa_labpa.kesimpulan, detail_periksa_labpa.kesan 
FROM detail_periksa_labpa 
WHERE detail_periksa_labpa.no_rawat = ? AND detail_periksa_labpa.kd_jenis_prw = ? 
AND detail_periksa_labpa.tgl_periksa = ? AND detail_periksa_labpa.jam = ?
";

$query_detail_periksa_labpa_gambar = "
SELECT detail_periksa_labpa_gambar.photo 
FROM detail_periksa_labpa_gambar 
WHERE detail_periksa_labpa_gambar.no_rawat = ? AND detail_periksa_labpa_gambar.kd_jenis_prw = ? 
AND detail_periksa_labpa_gambar.tgl_periksa = ? AND detail_periksa_labpa_gambar.jam = ?
";


// =============================================
// QUERY PEMBERIAN OBAT
// =============================================

$query_pemberian_obat = "
SELECT detail_pemberian_obat.tgl_perawatan, detail_pemberian_obat.jam, databarang.kode_sat,
       detail_pemberian_obat.kode_brng, detail_pemberian_obat.jml, detail_pemberian_obat.total, databarang.nama_brng 
FROM detail_pemberian_obat 
INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng  
WHERE detail_pemberian_obat.no_rawat = ? 
ORDER BY detail_pemberian_obat.tgl_perawatan, detail_pemberian_obat.jam
";

$query_aturan_pakai = "
SELECT aturan 
FROM aturan_pakai 
WHERE tgl_perawatan = ? AND jam = ? AND no_rawat = ? AND kode_brng = ?
";

$query_retur_obat = "
SELECT databarang.kode_brng, databarang.nama_brng, detreturjual.kode_sat, detreturjual.h_retur,
       (detreturjual.jml_retur * -1) as jumlah, (detreturjual.subtotal * -1) as total 
FROM detreturjual 
INNER JOIN databarang ON detreturjual.kode_brng = databarang.kode_brng  
INNER JOIN returjual ON returjual.no_retur_jual = detreturjual.no_retur_jual 
WHERE returjual.no_retur_jual LIKE CONCAT('%', ?, '%') 
ORDER BY databarang.nama_brng
";

// =============================================
// QUERY PENGGUNAAN OBAT OPERASI
// =============================================

$query_penggunaan_obat_operasi = "
SELECT beri_obat_operasi.tanggal, beri_obat_operasi.kd_obat, beri_obat_operasi.hargasatuan, obatbhp_ok.kode_sat,
       beri_obat_operasi.jumlah, obatbhp_ok.nm_obat, (beri_obat_operasi.hargasatuan * beri_obat_operasi.jumlah) as total 
FROM beri_obat_operasi 
INNER JOIN obatbhp_ok ON beri_obat_operasi.kd_obat = obatbhp_ok.kd_obat  
WHERE beri_obat_operasi.no_rawat = ? 
ORDER BY beri_obat_operasi.tanggal
";

// =============================================
// QUERY RESEP PULANG
// =============================================

$query_resep_pulang = "
SELECT resep_pulang.kode_brng, databarang.nama_brng, resep_pulang.dosis, resep_pulang.jml_barang,
       databarang.kode_sat, resep_pulang.dosis, resep_pulang.total 
FROM resep_pulang 
INNER JOIN databarang ON resep_pulang.kode_brng = databarang.kode_brng 
WHERE resep_pulang.no_rawat = ? 
ORDER BY databarang.nama_brng
";

// =============================================
// QUERY PPN OBAT
// =============================================

$query_ppn_obat = "
SELECT billing.totalbiaya 
FROM billing 
WHERE billing.nm_perawatan = 'PPN Obat' AND billing.status = 'Obat' AND billing.no_rawat = ?
";

// =============================================
// QUERY TAMBAHAN BIAYA
// =============================================

$query_tambahan_biaya = "
SELECT nama_biaya, besar_biaya 
FROM tambahan_biaya 
WHERE no_rawat = ? 
ORDER BY nama_biaya
";

// =============================================
// QUERY POTONGAN BIAYA
// =============================================

$query_potongan_biaya = "
SELECT nama_pengurangan, (-1 * besar_pengurangan) as besar_pengurangan 
FROM pengurangan_biaya 
WHERE no_rawat = ? 
ORDER BY nama_pengurangan
";

// =============================================
// QUERY RESUME PENAGIHAN
// =============================================


// Resume Pasien Ralan
$query_resume_pasien = "
SELECT resume_pasien.kd_dokter, dokter.nm_dokter, resume_pasien.kondisi_pulang, resume_pasien.keluhan_utama,
       resume_pasien.jalannya_penyakit, resume_pasien.pemeriksaan_penunjang, resume_pasien.hasil_laborat, resume_pasien.diagnosa_utama, resume_pasien.kd_diagnosa_utama,
       resume_pasien.diagnosa_sekunder, resume_pasien.kd_diagnosa_sekunder, resume_pasien.diagnosa_sekunder2, resume_pasien.kd_diagnosa_sekunder2,
       resume_pasien.diagnosa_sekunder3, resume_pasien.kd_diagnosa_sekunder3, resume_pasien.diagnosa_sekunder4, resume_pasien.kd_diagnosa_sekunder4,
       resume_pasien.prosedur_utama, resume_pasien.kd_prosedur_utama, resume_pasien.prosedur_sekunder, resume_pasien.kd_prosedur_sekunder,
       resume_pasien.prosedur_sekunder2, resume_pasien.kd_prosedur_sekunder2, resume_pasien.prosedur_sekunder3, resume_pasien.kd_prosedur_sekunder3,
       resume_pasien.obat_pulang 
FROM resume_pasien 
INNER JOIN dokter ON resume_pasien.kd_dokter = dokter.kd_dokter 
WHERE resume_pasien.no_rawat = ?
";

// Resume Pasien Ranap
$query_resume_pasien_ranap = "
SELECT resume_pasien_ranap.kd_dokter, dokter.nm_dokter, resume_pasien_ranap.diagnosa_awal, resume_pasien_ranap.alasan, resume_pasien_ranap.keluhan_utama, resume_pasien_ranap.pemeriksaan_fisik,
       resume_pasien_ranap.jalannya_penyakit, resume_pasien_ranap.pemeriksaan_penunjang, resume_pasien_ranap.hasil_laborat, resume_pasien_ranap.tindakan_dan_operasi, resume_pasien_ranap.obat_di_rs,
       resume_pasien_ranap.diagnosa_utama, resume_pasien_ranap.kd_diagnosa_utama, resume_pasien_ranap.diagnosa_sekunder, resume_pasien_ranap.kd_diagnosa_sekunder, resume_pasien_ranap.diagnosa_sekunder2,
       resume_pasien_ranap.kd_diagnosa_sekunder2, resume_pasien_ranap.diagnosa_sekunder3, resume_pasien_ranap.kd_diagnosa_sekunder3, resume_pasien_ranap.diagnosa_sekunder4,
       resume_pasien_ranap.kd_diagnosa_sekunder4, resume_pasien_ranap.prosedur_utama, resume_pasien_ranap.kd_prosedur_utama, resume_pasien_ranap.prosedur_sekunder, resume_pasien_ranap.kd_prosedur_sekunder,
       resume_pasien_ranap.prosedur_sekunder2, resume_pasien_ranap.kd_prosedur_sekunder2, resume_pasien_ranap.prosedur_sekunder3, resume_pasien_ranap.kd_prosedur_sekunder3, resume_pasien_ranap.alergi,
       resume_pasien_ranap.diet, resume_pasien_ranap.lab_belum, resume_pasien_ranap.edukasi, resume_pasien_ranap.cara_keluar, resume_pasien_ranap.ket_keluar, resume_pasien_ranap.keadaan,
       resume_pasien_ranap.ket_keadaan, resume_pasien_ranap.dilanjutkan, resume_pasien_ranap.ket_dilanjutkan, resume_pasien_ranap.kontrol, resume_pasien_ranap.obat_pulang 
FROM resume_pasien_ranap 
INNER JOIN dokter ON resume_pasien_ranap.kd_dokter = dokter.kd_dokter 
WHERE resume_pasien_ranap.no_rawat = ?
";

// =============================================
// QUERY HELPER UNTUK NAMA DOKTER/PETUGAS
// =============================================

$query_nama_dokter = "SELECT dokter.nm_dokter FROM dokter WHERE dokter.kd_dokter = ?";
$query_nama_petugas = "SELECT petugas.nama FROM petugas WHERE petugas.nip = ?";

// =============================================
//QUERY UNTUK SOAPI (PEMERIKSAAN)
//QUERY REGISTRASI PASIEN
// ============================================

// Query untuk R1 (5 data terbaru)
$query_r1 = "
    SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut 
    FROM reg_periksa 
    WHERE reg_periksa.stts <> 'Batal' 
    AND reg_periksa.no_rkm_medis = ? 
    ORDER BY reg_periksa.tgl_registrasi DESC 
    LIMIT 5
";

// Query untuk R2 (semua data)
$query_r2 = "
    SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut 
    FROM reg_periksa 
    WHERE reg_periksa.stts <> 'Batal' 
    AND reg_periksa.no_rkm_medis = ? 
    ORDER BY reg_periksa.tgl_registrasi
";

// Query untuk R3 (berdasarkan tanggal)
$query_r3 = "
    SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut 
    FROM reg_periksa 
    WHERE reg_periksa.stts <> 'Batal' 
    AND reg_periksa.no_rkm_medis = ? 
    AND reg_periksa.tgl_registrasi BETWEEN ? AND ? 
    ORDER BY reg_periksa.tgl_registrasi
";

// Query untuk R4 (berdasarkan no_rawat spesifik)
$query_r4 = "
    SELECT reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut 
    FROM reg_periksa 
    WHERE reg_periksa.stts <> 'Batal' 
    AND reg_periksa.no_rkm_medis = ? 
    AND reg_periksa.no_rawat = ?
";

// =============================================
// QUERY PEMERIKSAAN RALAN
// =============================================

$query_pemeriksaan_ralan = "
    SELECT 
        pemeriksaan_ralan.tgl_perawatan,
        pemeriksaan_ralan.jam_rawat,
        pemeriksaan_ralan.suhu_tubuh,
        pemeriksaan_ralan.tensi,
        pemeriksaan_ralan.nadi,
        pemeriksaan_ralan.respirasi,
        pemeriksaan_ralan.tinggi,
        pemeriksaan_ralan.berat,
        pemeriksaan_ralan.gcs,
        pemeriksaan_ralan.spo2,
        pemeriksaan_ralan.kesadaran,
        pemeriksaan_ralan.keluhan,
        pemeriksaan_ralan.pemeriksaan,
        pemeriksaan_ralan.alergi,
        pemeriksaan_ralan.lingkar_perut,
        pemeriksaan_ralan.rtl,
        pemeriksaan_ralan.penilaian,
        pemeriksaan_ralan.instruksi,
        pemeriksaan_ralan.evaluasi,
        pemeriksaan_ralan.nip,
        pegawai.nama,
        pegawai.jbtn 
    FROM pemeriksaan_ralan 
    INNER JOIN pegawai ON pemeriksaan_ralan.nip = pegawai.nik 
    WHERE pemeriksaan_ralan.no_rawat = ? 
    ORDER BY pemeriksaan_ralan.tgl_perawatan, pemeriksaan_ralan.jam_rawat
";

//  ============================================
// QUERY PEMERIKSAAN RANAP
//  ============================================

$query_pemeriksaan_ranap = "
    SELECT 
        pemeriksaan_ranap.no_rawat,
        reg_periksa.no_rkm_medis,
        pasien.nm_pasien,
        pemeriksaan_ranap.tgl_perawatan,
        pemeriksaan_ranap.jam_rawat,
        pemeriksaan_ranap.suhu_tubuh,
        pemeriksaan_ranap.tensi,
        pemeriksaan_ranap.nadi,
        pemeriksaan_ranap.respirasi,
        pemeriksaan_ranap.tinggi,
        pemeriksaan_ranap.berat,
        pemeriksaan_ranap.spo2,
        pemeriksaan_ranap.gcs,
        pemeriksaan_ranap.kesadaran,
        pemeriksaan_ranap.keluhan,
        pemeriksaan_ranap.pemeriksaan,
        pemeriksaan_ranap.alergi,
        pemeriksaan_ranap.penilaian,
        pemeriksaan_ranap.rtl,
        pemeriksaan_ranap.instruksi,
        pemeriksaan_ranap.evaluasi,
        pemeriksaan_ranap.nip,
        pegawai.nama,
        pegawai.jbtn 
    FROM pasien 
    INNER JOIN reg_periksa ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
    INNER JOIN pemeriksaan_ranap ON pemeriksaan_ranap.no_rawat = reg_periksa.no_rawat 
    INNER JOIN pegawai ON pemeriksaan_ranap.nip = pegawai.nik 
    WHERE pemeriksaan_ranap.no_rawat = ? 
    ORDER BY pemeriksaan_ranap.tgl_perawatan, pemeriksaan_ranap.jam_rawat
";


// =============================================
// QUERY UNTUK PEMBELIAN / PENJUALAN
// QUERY PENJUALAN 
// =============================================

// Query untuk R1 (5 data terbaru)
$query_penjualan_r1 = "
    SELECT 
        penjualan.nota_jual,
        penjualan.tgl_jual,
        penjualan.nip,
        petugas.nama,
        penjualan.no_rkm_medis,
        penjualan.nm_pasien,
        penjualan.nama_bayar,
        penjualan.keterangan,
        penjualan.jns_jual,
        penjualan.ongkir,
        bangsal.nm_bangsal,
        penjualan.status 
    FROM penjualan 
    INNER JOIN petugas ON penjualan.nip = petugas.nip 
    INNER JOIN bangsal ON penjualan.kd_bangsal = bangsal.kd_bangsal 
    WHERE penjualan.status = 'Sudah Dibayar' 
    AND penjualan.no_rkm_medis = ? 
    ORDER BY penjualan.tgl_jual DESC 
    LIMIT 5
";

// Query untuk R2 (semua data)
$query_penjualan_r2 = "
    SELECT 
        penjualan.nota_jual,
        penjualan.tgl_jual,
        penjualan.nip,
        petugas.nama,
        penjualan.no_rkm_medis,
        penjualan.nm_pasien,
        penjualan.nama_bayar,
        penjualan.keterangan,
        penjualan.jns_jual,
        penjualan.ongkir,
        bangsal.nm_bangsal,
        penjualan.status 
    FROM penjualan 
    INNER JOIN petugas ON penjualan.nip = petugas.nip 
    INNER JOIN bangsal ON penjualan.kd_bangsal = bangsal.kd_bangsal 
    WHERE penjualan.status = 'Sudah Dibayar' 
    AND penjualan.no_rkm_medis = ? 
    ORDER BY penjualan.tgl_jual
";

// Query untuk R3 (berdasarkan tanggal)
$query_penjualan_r3 = "
    SELECT 
        penjualan.nota_jual,
        penjualan.tgl_jual,
        penjualan.nip,
        petugas.nama,
        penjualan.no_rkm_medis,
        penjualan.nm_pasien,
        penjualan.nama_bayar,
        penjualan.keterangan,
        penjualan.jns_jual,
        penjualan.ongkir,
        bangsal.nm_bangsal,
        penjualan.status 
    FROM penjualan 
    INNER JOIN petugas ON penjualan.nip = petugas.nip 
    INNER JOIN bangsal ON penjualan.kd_bangsal = bangsal.kd_bangsal 
    WHERE penjualan.status = 'Sudah Dibayar' 
    AND penjualan.no_rkm_medis = ? 
    AND penjualan.tgl_jual BETWEEN ? AND ? 
    ORDER BY penjualan.tgl_jual
";

// Query untuk R4 (berdasarkan nota spesifik)
$query_penjualan_r4 = "
    SELECT 
        penjualan.nota_jual,
        penjualan.tgl_jual,
        penjualan.nip,
        petugas.nama,
        penjualan.no_rkm_medis,
        penjualan.nm_pasien,
        penjualan.nama_bayar,
        penjualan.keterangan,
        penjualan.jns_jual,
        penjualan.ongkir,
        bangsal.nm_bangsal,
        penjualan.status 
    FROM penjualan 
    INNER JOIN petugas ON penjualan.nip = petugas.nip 
    INNER JOIN bangsal ON penjualan.kd_bangsal = bangsal.kd_bangsal 
    WHERE penjualan.status = 'Sudah Dibayar' 
    AND penjualan.no_rkm_medis = ? 
    AND penjualan.nota_jual = ?
";


// =============================================
// QUERY DETAIL PENJUALAN
// =============================================

$query_detail_penjualan = "
    SELECT 
        detailjual.kode_brng,
        databarang.nama_brng,
        detailjual.kode_sat,
        kodesatuan.satuan,
        detailjual.h_jual,
        detailjual.jumlah,
        detailjual.subtotal,
        detailjual.dis,
        detailjual.bsr_dis,
        detailjual.tambahan,
        detailjual.embalase,
        detailjual.tuslah,
        detailjual.aturan_pakai,
        detailjual.total,
        detailjual.no_batch 
    FROM detailjual 
    INNER JOIN databarang ON detailjual.kode_brng = databarang.kode_brng 
    INNER JOIN kodesatuan ON detailjual.kode_sat = kodesatuan.kode_sat 
    INNER JOIN jenis ON databarang.kdjns = jenis.kdjns 
    WHERE detailjual.kode_brng NOT IN (
        SELECT kode_brng 
        FROM detail_obat_racikan_jual 
        WHERE nota_jual = ? 
        GROUP BY kode_brng
    ) 
    AND detailjual.nota_jual = ? 
    ORDER BY detailjual.kode_brng
";


// =============================================
// QUERY OBAT RACIKAN 
// =============================================


$query_obat_racikan = "
    SELECT 
        obat_racikan_jual.no_racik,
        obat_racikan_jual.nama_racik,
        obat_racikan_jual.kd_racik,
        metode_racik.nm_racik as metode,
        obat_racikan_jual.jml_dr,
        obat_racikan_jual.aturan_pakai,
        obat_racikan_jual.keterangan 
    FROM obat_racikan_jual 
    INNER JOIN metode_racik ON obat_racikan_jual.kd_racik = metode_racik.kd_racik 
    WHERE obat_racikan_jual.nota_jual = ? 
    ORDER BY obat_racikan_jual.no_racik
";


//  ============================================
// QUERY DETAIL OBAT RACIKAN
//  ============================================


$query_detail_obat_racikan = "
    SELECT 
        detailjual.kode_brng,
        databarang.nama_brng,
        detailjual.kode_sat,
        kodesatuan.satuan,
        detailjual.h_jual,
        detailjual.jumlah,
        detailjual.subtotal,
        detailjual.dis,
        detailjual.bsr_dis,
        detailjual.tambahan,
        detailjual.embalase,
        detailjual.tuslah,
        detailjual.aturan_pakai,
        detailjual.total,
        detailjual.no_batch 
    FROM detailjual 
    INNER JOIN databarang ON detailjual.kode_brng = databarang.kode_brng 
    INNER JOIN kodesatuan ON detailjual.kode_sat = kodesatuan.kode_sat 
    INNER JOIN jenis ON databarang.kdjns = jenis.kdjns 
    INNER JOIN detail_obat_racikan_jual ON detailjual.kode_brng = detail_obat_racikan_jual.kode_brng 
        AND detailjual.nota_jual = detail_obat_racikan_jual.nota_jual 
    WHERE detail_obat_racikan_jual.no_racik = ? 
    AND detailjual.nota_jual = ? 
    ORDER BY detailjual.kode_brng
";

// =============================================
// QUERY UNTUK PITUTANG
// QUERY PIUTANG
// =============================================


// Query untuk R1 (5 data terbaru)
$query_piutang_r1 = "
    SELECT 
        piutang.nota_piutang,
        piutang.tgl_piutang,
        piutang.nip,
        petugas.nama,
        piutang.no_rkm_medis,
        piutang.nm_pasien,
        piutang.jns_jual,
        bangsal.nm_bangsal,
        piutang.catatan 
    FROM piutang 
    INNER JOIN petugas ON piutang.nip = petugas.nip 
    INNER JOIN bangsal ON piutang.kd_bangsal = bangsal.kd_bangsal 
    WHERE piutang.no_rkm_medis = ? 
    ORDER BY piutang.tgl_piutang DESC 
    LIMIT 5
";

// Query untuk R2 (semua data)
$query_piutang_r2 = "
    SELECT 
        piutang.nota_piutang,
        piutang.tgl_piutang,
        piutang.nip,
        petugas.nama,
        piutang.no_rkm_medis,
        piutang.nm_pasien,
        piutang.jns_jual,
        bangsal.nm_bangsal,
        piutang.catatan 
    FROM piutang 
    INNER JOIN petugas ON piutang.nip = petugas.nip 
    INNER JOIN bangsal ON piutang.kd_bangsal = bangsal.kd_bangsal 
    WHERE piutang.no_rkm_medis = ? 
    ORDER BY piutang.tgl_piutang
";

// Query untuk R3 (berdasarkan tanggal)
$query_piutang_r3 = "
    SELECT 
        piutang.nota_piutang,
        piutang.tgl_piutang,
        piutang.nip,
        petugas.nama,
        piutang.no_rkm_medis,
        piutang.nm_pasien,
        piutang.jns_jual,
        bangsal.nm_bangsal,
        piutang.catatan 
    FROM piutang 
    INNER JOIN petugas ON piutang.nip = petugas.nip 
    INNER JOIN bangsal ON piutang.kd_bangsal = bangsal.kd_bangsal 
    WHERE piutang.no_rkm_medis = ? 
    AND piutang.tgl_piutang BETWEEN ? AND ? 
    ORDER BY piutang.tgl_piutang
";

// Query untuk R4 (berdasarkan nota spesifik)
$query_piutang_r4 = "
    SELECT 
        piutang.nota_piutang,
        piutang.tgl_piutang,
        piutang.nip,
        petugas.nama,
        piutang.no_rkm_medis,
        piutang.nm_pasien,
        piutang.jns_jual,
        bangsal.nm_bangsal,
        piutang.catatan 
    FROM piutang 
    INNER JOIN petugas ON piutang.nip = petugas.nip 
    INNER JOIN bangsal ON piutang.kd_bangsal = bangsal.kd_bangsal 
    WHERE piutang.no_rkm_medis = ? 
    AND piutang.nota_piutang = ?
";

// =============================================
// QUERY DETAIL PIUTANG
// =============================================


$query_detail_piutang = "
    SELECT 
        detailpiutang.kode_brng,
        databarang.nama_brng,
        detailpiutang.kode_sat,
        kodesatuan.satuan,
        detailpiutang.h_jual,
        detailpiutang.jumlah,
        detailpiutang.subtotal,
        detailpiutang.dis,
        detailpiutang.bsr_dis,
        detailpiutang.total,
        detailpiutang.no_batch 
    FROM detailpiutang 
    INNER JOIN databarang ON detailpiutang.kode_brng = databarang.kode_brng 
    INNER JOIN kodesatuan ON detailpiutang.kode_sat = kodesatuan.kode_sat 
    INNER JOIN jenis ON databarang.kdjns = jenis.kdjns 
    WHERE detailpiutang.nota_piutang = ? 
    ORDER BY detailpiutang.kode_brng
";

// =============================================
// QUERY ASUHAN KEPERAWATAN IGD
// =============================================


// Query utama untuk data asuhan keperawatan IGD
$sql_igd = "SELECT 
    penilaian_awal_keperawatan_igd.tanggal,
    penilaian_awal_keperawatan_igd.informasi,
    penilaian_awal_keperawatan_igd.keluhan_utama,
    penilaian_awal_keperawatan_igd.rpd,
    penilaian_awal_keperawatan_igd.rpo,
    penilaian_awal_keperawatan_igd.status_kehamilan,
    penilaian_awal_keperawatan_igd.gravida,
    penilaian_awal_keperawatan_igd.para,
    penilaian_awal_keperawatan_igd.abortus,
    penilaian_awal_keperawatan_igd.hpht,
    penilaian_awal_keperawatan_igd.tekanan,
    penilaian_awal_keperawatan_igd.pupil,
    penilaian_awal_keperawatan_igd.neurosensorik,
    penilaian_awal_keperawatan_igd.integumen,
    penilaian_awal_keperawatan_igd.turgor,
    penilaian_awal_keperawatan_igd.edema,
    penilaian_awal_keperawatan_igd.mukosa,
    penilaian_awal_keperawatan_igd.perdarahan,
    penilaian_awal_keperawatan_igd.jumlah_perdarahan,
    penilaian_awal_keperawatan_igd.warna_perdarahan,
    penilaian_awal_keperawatan_igd.intoksikasi,
    penilaian_awal_keperawatan_igd.bab,
    penilaian_awal_keperawatan_igd.xbab,
    penilaian_awal_keperawatan_igd.kbab,
    penilaian_awal_keperawatan_igd.wbab,
    penilaian_awal_keperawatan_igd.bak,
    penilaian_awal_keperawatan_igd.xbak,
    penilaian_awal_keperawatan_igd.wbak,
    penilaian_awal_keperawatan_igd.lbak,
    penilaian_awal_keperawatan_igd.psikologis,
    penilaian_awal_keperawatan_igd.jiwa,
    penilaian_awal_keperawatan_igd.perilaku,
    penilaian_awal_keperawatan_igd.dilaporkan,
    penilaian_awal_keperawatan_igd.sebutkan,
    penilaian_awal_keperawatan_igd.hubungan,
    penilaian_awal_keperawatan_igd.tinggal_dengan,
    penilaian_awal_keperawatan_igd.ket_tinggal,
    penilaian_awal_keperawatan_igd.budaya,
    penilaian_awal_keperawatan_igd.ket_budaya,
    penilaian_awal_keperawatan_igd.pendidikan_pj,
    penilaian_awal_keperawatan_igd.ket_pendidikan_pj,
    penilaian_awal_keperawatan_igd.edukasi,
    penilaian_awal_keperawatan_igd.ket_edukasi,
    penilaian_awal_keperawatan_igd.kemampuan,
    penilaian_awal_keperawatan_igd.aktifitas,
    penilaian_awal_keperawatan_igd.alat_bantu,
    penilaian_awal_keperawatan_igd.ket_bantu,
    penilaian_awal_keperawatan_igd.nyeri,
    penilaian_awal_keperawatan_igd.provokes,
    penilaian_awal_keperawatan_igd.ket_provokes,
    penilaian_awal_keperawatan_igd.quality,
    penilaian_awal_keperawatan_igd.ket_quality,
    penilaian_awal_keperawatan_igd.lokasi,
    penilaian_awal_keperawatan_igd.menyebar,
    penilaian_awal_keperawatan_igd.skala_nyeri,
    penilaian_awal_keperawatan_igd.durasi,
    penilaian_awal_keperawatan_igd.nyeri_hilang,
    penilaian_awal_keperawatan_igd.ket_nyeri,
    penilaian_awal_keperawatan_igd.pada_dokter,
    penilaian_awal_keperawatan_igd.ket_dokter,
    penilaian_awal_keperawatan_igd.berjalan_a,
    penilaian_awal_keperawatan_igd.berjalan_b,
    penilaian_awal_keperawatan_igd.berjalan_c,
    penilaian_awal_keperawatan_igd.hasil,
    penilaian_awal_keperawatan_igd.lapor,
    penilaian_awal_keperawatan_igd.ket_lapor,
    penilaian_awal_keperawatan_igd.rencana,
    penilaian_awal_keperawatan_igd.nip,
    petugas.nama 
FROM penilaian_awal_keperawatan_igd 
INNER JOIN petugas ON penilaian_awal_keperawatan_igd.nip = petugas.nip 
WHERE penilaian_awal_keperawatan_igd.no_rawat = ?";

// Query untuk masalah keperawatan IGD
$sql_masalah_igd = "SELECT 
    master_masalah_keperawatan_igd.nama_masalah 
FROM master_masalah_keperawatan_igd 
INNER JOIN penilaian_awal_keperawatan_igd_masalah ON penilaian_awal_keperawatan_igd_masalah.kode_masalah = master_masalah_keperawatan_igd.kode_masalah 
WHERE penilaian_awal_keperawatan_igd_masalah.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_igd_masalah.kode_masalah";

// Query untuk rencana keperawatan IGD
$sql_rencana_igd = "SELECT 
    master_rencana_keperawatan_igd.rencana_keperawatan 
FROM master_rencana_keperawatan_igd 
INNER JOIN penilaian_awal_keperawatan_ralan_rencana_igd ON penilaian_awal_keperawatan_ralan_rencana_igd.kode_rencana = master_rencana_keperawatan_igd.kode_rencana 
WHERE penilaian_awal_keperawatan_ralan_rencana_igd.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_ralan_rencana_igd.kode_rencana";

// =============================================
// QUERY ASUHAN KEPERAWATAN RALAN UMUM
// =============================================


// Query utama untuk data asuhan keperawatan rawat jalan umum
$sql_ralan = "SELECT 
    penilaian_awal_keperawatan_ralan.tanggal,
    penilaian_awal_keperawatan_ralan.informasi,
    penilaian_awal_keperawatan_ralan.td,
    penilaian_awal_keperawatan_ralan.nadi,
    penilaian_awal_keperawatan_ralan.rr,
    penilaian_awal_keperawatan_ralan.suhu,
    penilaian_awal_keperawatan_ralan.bb,
    penilaian_awal_keperawatan_ralan.tb,
    penilaian_awal_keperawatan_ralan.gcs,
    penilaian_awal_keperawatan_ralan.bmi,
    penilaian_awal_keperawatan_ralan.keluhan_utama,
    penilaian_awal_keperawatan_ralan.rpd,
    penilaian_awal_keperawatan_ralan.rpk,
    penilaian_awal_keperawatan_ralan.rpo,
    penilaian_awal_keperawatan_ralan.alergi,
    penilaian_awal_keperawatan_ralan.alat_bantu,
    penilaian_awal_keperawatan_ralan.ket_bantu,
    penilaian_awal_keperawatan_ralan.prothesa,
    penilaian_awal_keperawatan_ralan.ket_pro,
    penilaian_awal_keperawatan_ralan.adl,
    penilaian_awal_keperawatan_ralan.status_psiko,
    penilaian_awal_keperawatan_ralan.ket_psiko,
    penilaian_awal_keperawatan_ralan.hub_keluarga,
    penilaian_awal_keperawatan_ralan.tinggal_dengan,
    penilaian_awal_keperawatan_ralan.ket_tinggal,
    penilaian_awal_keperawatan_ralan.ekonomi,
    penilaian_awal_keperawatan_ralan.edukasi,
    penilaian_awal_keperawatan_ralan.ket_edukasi,
    penilaian_awal_keperawatan_ralan.berjalan_a,
    penilaian_awal_keperawatan_ralan.berjalan_b,
    penilaian_awal_keperawatan_ralan.berjalan_c,
    penilaian_awal_keperawatan_ralan.hasil,
    penilaian_awal_keperawatan_ralan.lapor,
    penilaian_awal_keperawatan_ralan.ket_lapor,
    penilaian_awal_keperawatan_ralan.sg1,
    penilaian_awal_keperawatan_ralan.nilai1,
    penilaian_awal_keperawatan_ralan.sg2,
    penilaian_awal_keperawatan_ralan.nilai2,
    penilaian_awal_keperawatan_ralan.total_hasil,
    penilaian_awal_keperawatan_ralan.nyeri,
    penilaian_awal_keperawatan_ralan.provokes,
    penilaian_awal_keperawatan_ralan.ket_provokes,
    penilaian_awal_keperawatan_ralan.quality,
    penilaian_awal_keperawatan_ralan.ket_quality,
    penilaian_awal_keperawatan_ralan.lokasi,
    penilaian_awal_keperawatan_ralan.menyebar,
    penilaian_awal_keperawatan_ralan.skala_nyeri,
    penilaian_awal_keperawatan_ralan.durasi,
    penilaian_awal_keperawatan_ralan.nyeri_hilang,
    penilaian_awal_keperawatan_ralan.ket_nyeri,
    penilaian_awal_keperawatan_ralan.pada_dokter,
    penilaian_awal_keperawatan_ralan.ket_dokter,
    penilaian_awal_keperawatan_ralan.rencana,
    penilaian_awal_keperawatan_ralan.nip,
    petugas.nama,
    penilaian_awal_keperawatan_ralan.budaya,
    penilaian_awal_keperawatan_ralan.ket_budaya 
FROM penilaian_awal_keperawatan_ralan 
INNER JOIN petugas ON penilaian_awal_keperawatan_ralan.nip = petugas.nip 
WHERE penilaian_awal_keperawatan_ralan.no_rawat = ?";

// Query untuk masalah keperawatan rawat jalan umum
$sql_masalah_ralan = "SELECT 
    master_masalah_keperawatan.nama_masalah 
FROM master_masalah_keperawatan 
INNER JOIN penilaian_awal_keperawatan_ralan_masalah ON penilaian_awal_keperawatan_ralan_masalah.kode_masalah = master_masalah_keperawatan.kode_masalah 
WHERE penilaian_awal_keperawatan_ralan_masalah.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_ralan_masalah.kode_masalah";

// Query untuk rencana keperawatan rawat jalan umum
$sql_rencana_ralan = "SELECT 
    master_rencana_keperawatan.rencana_keperawatan 
FROM master_rencana_keperawatan 
INNER JOIN penilaian_awal_keperawatan_ralan_rencana ON penilaian_awal_keperawatan_ralan_rencana.kode_rencana = master_rencana_keperawatan.kode_rencana 
WHERE penilaian_awal_keperawatan_ralan_rencana.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_ralan_rencana.kode_rencana";

// =============================================
// QUERY ASUHAN KEPERAWATAN RALAN GERIATRI
// =============================================


// Query utama untuk data asuhan keperawatan rawat jalan geriatri
$sql_geriatri = "SELECT 
    penilaian_awal_keperawatan_ralan_geriatri.tanggal,
    penilaian_awal_keperawatan_ralan_geriatri.informasi,
    penilaian_awal_keperawatan_ralan_geriatri.td,
    penilaian_awal_keperawatan_ralan_geriatri.nadi,
    penilaian_awal_keperawatan_ralan_geriatri.rr,
    penilaian_awal_keperawatan_ralan_geriatri.suhu,
    penilaian_awal_keperawatan_ralan_geriatri.bb,
    penilaian_awal_keperawatan_ralan_geriatri.tb,
    penilaian_awal_keperawatan_ralan_geriatri.gcs,
    penilaian_awal_keperawatan_ralan_geriatri.bmi,
    penilaian_awal_keperawatan_ralan_geriatri.keluhan_utama,
    penilaian_awal_keperawatan_ralan_geriatri.rpd,
    penilaian_awal_keperawatan_ralan_geriatri.rpk,
    penilaian_awal_keperawatan_ralan_geriatri.rpo,
    penilaian_awal_keperawatan_ralan_geriatri.alergi,
    penilaian_awal_keperawatan_ralan_geriatri.alat_bantu,
    penilaian_awal_keperawatan_ralan_geriatri.ket_bantu,
    penilaian_awal_keperawatan_ralan_geriatri.prothesa,
    penilaian_awal_keperawatan_ralan_geriatri.ket_pro,
    penilaian_awal_keperawatan_ralan_geriatri.adl,
    penilaian_awal_keperawatan_ralan_geriatri.status_psiko,
    penilaian_awal_keperawatan_ralan_geriatri.ket_psiko,
    penilaian_awal_keperawatan_ralan_geriatri.hub_keluarga,
    penilaian_awal_keperawatan_ralan_geriatri.tinggal_dengan,
    penilaian_awal_keperawatan_ralan_geriatri.ket_tinggal,
    penilaian_awal_keperawatan_ralan_geriatri.ekonomi,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi,
    penilaian_awal_keperawatan_ralan_geriatri.ket_edukasi,
    penilaian_awal_keperawatan_ralan_geriatri.berjalan_a,
    penilaian_awal_keperawatan_ralan_geriatri.berjalan_b,
    penilaian_awal_keperawatan_ralan_geriatri.berjalan_c,
    penilaian_awal_keperawatan_ralan_geriatri.hasil,
    penilaian_awal_keperawatan_ralan_geriatri.lapor,
    penilaian_awal_keperawatan_ralan_geriatri.ket_lapor,
    penilaian_awal_keperawatan_ralan_geriatri.sg1,
    penilaian_awal_keperawatan_ralan_geriatri.nilai1,
    penilaian_awal_keperawatan_ralan_geriatri.sg2,
    penilaian_awal_keperawatan_ralan_geriatri.nilai2,
    penilaian_awal_keperawatan_ralan_geriatri.total_hasil,
    penilaian_awal_keperawatan_ralan_geriatri.nyeri,
    penilaian_awal_keperawatan_ralan_geriatri.provokes,
    penilaian_awal_keperawatan_ralan_geriatri.ket_provokes,
    penilaian_awal_keperawatan_ralan_geriatri.quality,
    penilaian_awal_keperawatan_ralan_geriatri.ket_quality,
    penilaian_awal_keperawatan_ralan_geriatri.lokasi,
    penilaian_awal_keperawatan_ralan_geriatri.menyebar,
    penilaian_awal_keperawatan_ralan_geriatri.skala_nyeri,
    penilaian_awal_keperawatan_ralan_geriatri.durasi,
    penilaian_awal_keperawatan_ralan_geriatri.nyeri_hilang,
    penilaian_awal_keperawatan_ralan_geriatri.ket_nyeri,
    penilaian_awal_keperawatan_ralan_geriatri.pada_dokter,
    penilaian_awal_keperawatan_ralan_geriatri.ket_dokter,
    penilaian_awal_keperawatan_ralan_geriatri.rencana,
    penilaian_awal_keperawatan_ralan_geriatri.nip,
    petugas.nama,
    penilaian_awal_keperawatan_ralan_geriatri.budaya,
    penilaian_awal_keperawatan_ralan_geriatri.ket_budaya,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_kemampuan_bacatulis,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_kebutuhan_penerjemah,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_keterangan_kebutuhan_penerjemah,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_hambatan,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_hambatan_kategori,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_keterangan_hambatan,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_cara_bicara,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_bahasa_isyarat,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_menerima_informasi,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_keterangan_menerima_informasi,
    penilaian_awal_keperawatan_ralan_geriatri.edukasi_metode_belajar,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_berat_badan,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_berat_badan_nilai,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_aktifitas_fisik,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_aktifitas_fisik_nilai,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_kelelahan,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_kelelahan_nilai,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_kekuatan,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_kekuatan_nilai,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_waktu_berjalan,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_waktu_berjalan_nilai,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_nilai_total,
    penilaian_awal_keperawatan_ralan_geriatri.fraily_phenotype_status 
FROM penilaian_awal_keperawatan_ralan_geriatri 
INNER JOIN petugas ON penilaian_awal_keperawatan_ralan_geriatri.nip = petugas.nip 
WHERE penilaian_awal_keperawatan_ralan_geriatri.no_rawat = ?";

// Query untuk masalah keperawatan geriatri
$sql_masalah_geriatri = "SELECT 
    master_masalah_keperawatan_geriatri.nama_masalah 
FROM master_masalah_keperawatan_geriatri 
INNER JOIN penilaian_awal_keperawatan_ralan_masalah_geriatri ON penilaian_awal_keperawatan_ralan_masalah_geriatri.kode_masalah = master_masalah_keperawatan_geriatri.kode_masalah 
WHERE penilaian_awal_keperawatan_ralan_masalah_geriatri.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_ralan_masalah_geriatri.kode_masalah";

// Query untuk rencana keperawatan geriatri
$sql_rencana_geriatri = "SELECT 
    master_rencana_keperawatan_geriatri.rencana_keperawatan 
FROM master_rencana_keperawatan_geriatri 
INNER JOIN penilaian_awal_keperawatan_ralan_rencana_geriatri ON penilaian_awal_keperawatan_ralan_rencana_geriatri.kode_rencana = master_rencana_keperawatan_geriatri.kode_rencana 
WHERE penilaian_awal_keperawatan_ralan_rencana_geriatri.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_ralan_rencana_geriatri.kode_rencana";

// =============================================
// QUERY ASUHAN KEPERAWATAN RALAN GIGI & MULUT
// =============================================

// Query utama untuk data asuhan keperawatan gigi
$sql_gigi = "SELECT 
    penilaian_awal_keperawatan_gigi.tanggal,
    penilaian_awal_keperawatan_gigi.informasi,
    penilaian_awal_keperawatan_gigi.td,
    penilaian_awal_keperawatan_gigi.nadi,
    penilaian_awal_keperawatan_gigi.rr,
    penilaian_awal_keperawatan_gigi.suhu,
    penilaian_awal_keperawatan_gigi.bb,
    penilaian_awal_keperawatan_gigi.tb,
    penilaian_awal_keperawatan_gigi.bmi,
    penilaian_awal_keperawatan_gigi.keluhan_utama,
    penilaian_awal_keperawatan_gigi.riwayat_penyakit,
    penilaian_awal_keperawatan_gigi.ket_riwayat_penyakit,
    penilaian_awal_keperawatan_gigi.alergi,
    penilaian_awal_keperawatan_gigi.riwayat_perawatan_gigi,
    penilaian_awal_keperawatan_gigi.ket_riwayat_perawatan_gigi,
    penilaian_awal_keperawatan_gigi.kebiasaan_sikat_gigi,
    penilaian_awal_keperawatan_gigi.kebiasaan_lain,
    penilaian_awal_keperawatan_gigi.ket_kebiasaan_lain,
    penilaian_awal_keperawatan_gigi.obat_yang_diminum_saatini,
    penilaian_awal_keperawatan_gigi.alat_bantu,
    penilaian_awal_keperawatan_gigi.ket_alat_bantu,
    penilaian_awal_keperawatan_gigi.prothesa,
    penilaian_awal_keperawatan_gigi.ket_pro,
    penilaian_awal_keperawatan_gigi.status_psiko,
    penilaian_awal_keperawatan_gigi.ket_psiko,
    penilaian_awal_keperawatan_gigi.hub_keluarga,
    penilaian_awal_keperawatan_gigi.tinggal_dengan,
    penilaian_awal_keperawatan_gigi.ket_tinggal,
    penilaian_awal_keperawatan_gigi.ekonomi,
    penilaian_awal_keperawatan_gigi.budaya,
    penilaian_awal_keperawatan_gigi.ket_budaya,
    penilaian_awal_keperawatan_gigi.edukasi,
    penilaian_awal_keperawatan_gigi.ket_edukasi,
    penilaian_awal_keperawatan_gigi.berjalan_a,
    penilaian_awal_keperawatan_gigi.berjalan_b,
    penilaian_awal_keperawatan_gigi.berjalan_c,
    penilaian_awal_keperawatan_gigi.hasil,
    penilaian_awal_keperawatan_gigi.lapor,
    penilaian_awal_keperawatan_gigi.ket_lapor,
    penilaian_awal_keperawatan_gigi.nyeri,
    penilaian_awal_keperawatan_gigi.lokasi,
    penilaian_awal_keperawatan_gigi.skala_nyeri,
    penilaian_awal_keperawatan_gigi.durasi,
    penilaian_awal_keperawatan_gigi.frekuensi,
    penilaian_awal_keperawatan_gigi.nyeri_hilang,
    penilaian_awal_keperawatan_gigi.ket_nyeri,
    penilaian_awal_keperawatan_gigi.pada_dokter,
    penilaian_awal_keperawatan_gigi.ket_dokter,
    penilaian_awal_keperawatan_gigi.kebersihan_mulut,
    penilaian_awal_keperawatan_gigi.mukosa_mulut,
    penilaian_awal_keperawatan_gigi.karies,
    penilaian_awal_keperawatan_gigi.karang_gigi,
    penilaian_awal_keperawatan_gigi.gingiva,
    penilaian_awal_keperawatan_gigi.palatum,
    penilaian_awal_keperawatan_gigi.rencana,
    penilaian_awal_keperawatan_gigi.nip,
    petugas.nama 
FROM penilaian_awal_keperawatan_gigi 
INNER JOIN petugas ON penilaian_awal_keperawatan_gigi.nip = petugas.nip 
WHERE penilaian_awal_keperawatan_gigi.no_rawat = ?";

// Query untuk masalah keperawatan gigi
$sql_masalah_gigi = "SELECT 
    master_masalah_keperawatan_gigi.nama_masalah 
FROM master_masalah_keperawatan_gigi 
INNER JOIN penilaian_awal_keperawatan_gigi_masalah ON penilaian_awal_keperawatan_gigi_masalah.kode_masalah = master_masalah_keperawatan_gigi.kode_masalah 
WHERE penilaian_awal_keperawatan_gigi_masalah.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_gigi_masalah.kode_masalah";

// Query untuk rencana keperawatan gigi
$sql_rencana_gigi = "SELECT 
    master_rencana_keperawatan_gigi.rencana_keperawatan 
FROM master_rencana_keperawatan_gigi 
INNER JOIN penilaian_awal_keperawatan_ralan_rencana_gigi ON penilaian_awal_keperawatan_ralan_rencana_gigi.kode_rencana = master_rencana_keperawatan_gigi.kode_rencana 
WHERE penilaian_awal_keperawatan_ralan_rencana_gigi.no_rawat = ? 
ORDER BY penilaian_awal_keperawatan_ralan_rencana_gigi.kode_rencana";


// =============================================
// QUERY ASUHAN KEPERAWATAN RALAN GIGI
// =============================================

// Query utama penilaian awal keperawatan gigi
$query1 = "SELECT penilaian_awal_keperawatan_gigi.tanggal, penilaian_awal_keperawatan_gigi.informasi, 
                  penilaian_awal_keperawatan_gigi.td, penilaian_awal_keperawatan_gigi.nadi, 
                  penilaian_awal_keperawatan_gigi.rr, penilaian_awal_keperawatan_gigi.suhu, 
                  penilaian_awal_keperawatan_gigi.bb, penilaian_awal_keperawatan_gigi.tb, 
                  penilaian_awal_keperawatan_gigi.bmi, penilaian_awal_keperawatan_gigi.keluhan_utama, 
                  penilaian_awal_keperawatan_gigi.riwayat_penyakit, penilaian_awal_keperawatan_gigi.ket_riwayat_penyakit, 
                  penilaian_awal_keperawatan_gigi.alergi, penilaian_awal_keperawatan_gigi.riwayat_perawatan_gigi, 
                  penilaian_awal_keperawatan_gigi.ket_riwayat_perawatan_gigi, penilaian_awal_keperawatan_gigi.kebiasaan_sikat_gigi, 
                  penilaian_awal_keperawatan_gigi.kebiasaan_lain, penilaian_awal_keperawatan_gigi.ket_kebiasaan_lain, 
                  penilaian_awal_keperawatan_gigi.obat_yang_diminum_saatini, penilaian_awal_keperawatan_gigi.alat_bantu, 
                  penilaian_awal_keperawatan_gigi.ket_alat_bantu, penilaian_awal_keperawatan_gigi.prothesa, 
                  penilaian_awal_keperawatan_gigi.ket_pro, penilaian_awal_keperawatan_gigi.status_psiko, 
                  penilaian_awal_keperawatan_gigi.ket_psiko, penilaian_awal_keperawatan_gigi.hub_keluarga, 
                  penilaian_awal_keperawatan_gigi.tinggal_dengan, penilaian_awal_keperawatan_gigi.ket_tinggal, 
                  penilaian_awal_keperawatan_gigi.ekonomi, penilaian_awal_keperawatan_gigi.budaya, 
                  penilaian_awal_keperawatan_gigi.ket_budaya, penilaian_awal_keperawatan_gigi.edukasi, 
                  penilaian_awal_keperawatan_gigi.ket_edukasi, penilaian_awal_keperawatan_gigi.berjalan_a, 
                  penilaian_awal_keperawatan_gigi.berjalan_b, penilaian_awal_keperawatan_gigi.berjalan_c, 
                  penilaian_awal_keperawatan_gigi.hasil, penilaian_awal_keperawatan_gigi.lapor, 
                  penilaian_awal_keperawatan_gigi.ket_lapor, penilaian_awal_keperawatan_gigi.nyeri, 
                  penilaian_awal_keperawatan_gigi.lokasi, penilaian_awal_keperawatan_gigi.skala_nyeri, 
                  penilaian_awal_keperawatan_gigi.durasi, penilaian_awal_keperawatan_gigi.frekuensi, 
                  penilaian_awal_keperawatan_gigi.nyeri_hilang, penilaian_awal_keperawatan_gigi.ket_nyeri, 
                  penilaian_awal_keperawatan_gigi.pada_dokter, penilaian_awal_keperawatan_gigi.ket_dokter, 
                  penilaian_awal_keperawatan_gigi.kebersihan_mulut, penilaian_awal_keperawatan_gigi.mukosa_mulut, 
                  penilaian_awal_keperawatan_gigi.karies, penilaian_awal_keperawatan_gigi.karang_gigi, 
                  penilaian_awal_keperawatan_gigi.gingiva, penilaian_awal_keperawatan_gigi.palatum, 
                  penilaian_awal_keperawatan_gigi.rencana, penilaian_awal_keperawatan_gigi.nip, 
                  petugas.nama 
           FROM penilaian_awal_keperawatan_gigi 
           INNER JOIN petugas ON penilaian_awal_keperawatan_gigi.nip = petugas.nip 
           WHERE penilaian_awal_keperawatan_gigi.no_rawat = ?";

// Query masalah keperawatan gigi
$query2 = "SELECT master_masalah_keperawatan_gigi.nama_masalah 
           FROM master_masalah_keperawatan_gigi 
           INNER JOIN penilaian_awal_keperawatan_gigi_masalah ON penilaian_awal_keperawatan_gigi_masalah.kode_masalah = master_masalah_keperawatan_gigi.kode_masalah 
           WHERE penilaian_awal_keperawatan_gigi_masalah.no_rawat = ? 
           ORDER BY penilaian_awal_keperawatan_gigi_masalah.kode_masalah";

// Query rencana keperawatan gigi
$query3 = "SELECT master_rencana_keperawatan_gigi.rencana_keperawatan 
           FROM master_rencana_keperawatan_gigi 
           INNER JOIN penilaian_awal_keperawatan_ralan_rencana_gigi ON penilaian_awal_keperawatan_ralan_rencana_gigi.kode_rencana = master_rencana_keperawatan_gigi.kode_rencana 
           WHERE penilaian_awal_keperawatan_ralan_rencana_gigi.no_rawat = ? 
           ORDER BY penilaian_awal_keperawatan_ralan_rencana_gigi.kode_rencana";


// =============================================
// 

// =============================================
// 3. QUERY DATA PASIEN
// =============================================
$q2 = bukaquery("SELECT 
        pasien.no_rkm_medis,
        pasien.nm_pasien,
        pasien.alamat,
        pasien.jk,
        pasien.tmp_lahir,
        pasien.tgl_lahir,
        pasien.nm_ibu,
        pasien.gol_darah,
        pasien.stts_nikah,
        pasien.agama,
        pasien.pnd,
        bp.nama_bahasa,
        cf.nama_cacat
    FROM reg_periksa
    INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
    LEFT JOIN bahasa_pasien bp ON bp.id = pasien.bahasa_pasien
    LEFT JOIN cacat_fisik cf ON cf.id = pasien.cacat_fisik
    WHERE reg_periksa.no_rawat = '$no_rawat'
");

$data = mysqli_fetch_assoc($q2);

// Jika tidak ada data
if(!$data){
    die("DATA TIDAK DITEMUKAN");
}

// Format data
$jk       = ($data['jk'] == "L" ? "Laki-Laki" : "Perempuan");
$ttl      = $data['tmp_lahir'] . ", " . $data['tgl_lahir'];
$mr_raw   = $data['no_rkm_medis'];
$mr_clear = ltrim($mr_raw, '0');

// =============================================
// 4. MUAT CSS EXTERNAL
// =============================================
$css_file = __DIR__ . "css/file.css";
$css = "";
$no = 1;
if(file_exists($css_file)){
    $css = file_get_contents($css_file);
}
// =============================================
// 4. TEMPLATE HTML
// =============================================
$html = "
<style>
    table.dataresume {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    table.dataresume td {
        padding: 4px 6px;
        border: 1px solid #cfcfcf;
    }
    table.dataresume .label {
        width: 25%;
        background: #fafafa;
    }
    table.dataresume .colon {
        width: 2%;
        text-align:center;
    }
    table.dataresume .value {
        width: 73%;
    }
    h2, {
        margin-bottom: 5px;
        margin-top: 10px;
        text-align:center;
    }
      /* Margin halaman PDF */
    @page {
        margin: 1cm;
    }

    body {
        margin: 0;
        padding: 0;
    }

    table.dataresume {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }   
</style>

<h2>RIWAYAT PERAWATAN PASIEN</h2>
<br>
   <br><h3>".$no++.". Data Pasien</h3>

<table class='dataresume'>
    <tr><td class='label'>No. RM</td> <td class='colon'>:</td> <td class='value'>{$data['no_rkm_medis']}</td></tr>
    <tr><td class='label'>Nama Pasien</td> <td class='colon'>:</td> <td class='value'>{$data['nm_pasien']}</td></tr>
    <tr><td class='label'>Alamat</td> <td class='colon'>:</td> <td class='value'>{$data['alamat']}</td></tr>
    <tr><td class='label'>Jenis Kelamin</td> <td class='colon'>:</td> <td class='value'>$jk</td></tr>
    <tr><td class='label'>Tempat & Tanggal Lahir</td> <td class='colon'>:</td> <td class='value'>$ttl</td></tr>
    <tr><td class='label'>Ibu Kandung</td> <td class='colon'>:</td> <td class='value'>{$data['nm_ibu']}</td></tr>
    <tr><td class='label'>Golongan Darah</td> <td class='colon'>:</td> <td class='value'>{$data['gol_darah']}</td></tr>
    <tr><td class='label'>Status Nikah</td> <td class='colon'>:</td> <td class='value'>{$data['stts_nikah']}</td></tr>
    <tr><td class='label'>Agama</td> <td class='colon'>:</td> <td class='value'>{$data['agama']}</td></tr>
    <tr><td class='label'>Pendidikan Terakhir</td> <td class='colon'>:</td> <td class='value'>{$data['pnd']}</td></tr>
    <tr><td class='label'>Bahasa Dipakai</td> <td class='colon'>:</td> <td class='value'>{$data['nama_bahasa']}</td></tr>
    <tr><td class='label'>Cacat Fisik</td> <td class='colon'>:</td> <td class='value'>{$data['nama_cacat']}</td></tr>
</table>
";
// =============================================
// 5. QUERY REGISTRASI
// =============================================
$q_reg = bukaquery("SELECT 
        reg_periksa.no_rawat,
        reg_periksa.tgl_registrasi,
        reg_periksa.jam_reg,
        reg_periksa.status_lanjut,
        reg_periksa.no_reg,
        reg_periksa.kd_dokter,
        reg_periksa.p_jawab,
        reg_periksa.hubunganpj,
        reg_periksa.almt_pj,
        dokter.nm_dokter,
        CONCAT(reg_periksa.umurdaftar, ' ', reg_periksa.sttsumur) AS umur,
        poliklinik.nm_poli,
        penjab.png_jawab
    FROM reg_periksa
    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
    INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
    WHERE reg_periksa.stts <> 'Batal'
      AND reg_periksa.no_rkm_medis = '$mr_raw'
      AND reg_periksa.no_rawat = '$no_rawat'
");



while ($reg = mysqli_fetch_array($q_reg)) {

    // === HEADER DATA REGISTRASI ===
    $html .= "
    <br><h3>".$no++.". Data Registrasi</h3>
    <table class='dataresume'>
        <tr><td class='label'>No. Rawat</td><td class='colon'>:</td><td class='value'>{$reg['no_rawat']}</td></tr>
        <tr><td class='label'>No. Registrasi</td><td class='colon'>:</td><td class='value'>{$reg['no_reg']}</td></tr>
        <tr><td class='label'>Tanggal Registrasi</td><td class='colon'>:</td><td class='value'>{$reg['tgl_registrasi']} {$reg['jam_reg']}</td></tr>
        <tr><td class='label'>Umur Saat Daftar</td><td class='colon'>:</td><td class='value'>{$reg['umur']}</td></tr>
        <tr><td class='label'>Unit/Poliklinik</td><td class='colon'>:</td><td class='value'>{$reg['nm_poli']}</td></tr>
        <tr><td class='label'>Dokter Poli</td><td class='colon'>:</td><td class='value'>{$reg['nm_dokter']}</td></tr>
        <tr><td class='label'>Cara Bayar</td><td class='colon'>:</td><td class='value'>{$reg['png_jawab']}</td></tr>
        <tr><td class='label'>Penanggung Jawab</td><td class='colon'>:</td><td class='value'>{$reg['p_jawab']}</td></tr>
        <tr><td class='label'>Alamat P.J.</td><td class='colon'>:</td><td class='value'>{$reg['almt_pj']}</td></tr>
        <tr><td class='label'>Hubungan P.J.</td><td class='colon'>:</td><td class='value'>{$reg['hubunganpj']}</td></tr>
        <tr><td class='label'>Status</td><td class='colon'>:</td><td class='value'>{$reg['status_lanjut']}</td></tr>
    </table>
    ";

    // === RUJUKAN INTERNAL ===
    $q_rujuk = bukaquery("SELECT dokter.nm_dokter, poliklinik.nm_poli
            FROM rujukan_internal_poli
            INNER JOIN dokter ON dokter.kd_dokter = rujukan_internal_poli.kd_dokter
            INNER JOIN poliklinik ON poliklinik.kd_poli = rujukan_internal_poli.kd_poli
            WHERE rujukan_internal_poli.no_rawat = '{$reg['no_rawat']}'");

    while ($rujuk = mysqli_fetch_array($q_rujuk)) {
        $html .= "
        <table class='dataresume'>
            <tr><td class='label'>Rujukan Internal</td><td class='colon'>:</td>
            <td class='value'>{$rujuk['nm_dokter']} → {$rujuk['nm_poli']}</td></tr>
        </table>";
    }

    // === RAWAT INAP ===
    $q_ranap = bukaquery("SELECT kamar_inap.tgl_masuk, kamar_inap.jam_masuk, kamar_inap.kd_kamar, bangsal.nm_bangsal
                          FROM kamar_inap
                          INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
                          INNER JOIN bangsal ON bangsal.kd_bangsal = kamar.kd_bangsal
                          WHERE kamar_inap.no_rawat = '{$reg['no_rawat']}'");

    while ($ranap = mysqli_fetch_array($q_ranap)) {
        $html .= "
        <table class='dataresume'>
            <tr><td class='label'>Rawat Inap</td><td class='colon'>:</td>
            <td class='value'>{$ranap['kd_kamar']} - {$ranap['nm_bangsal']}<br>
                Masuk: {$ranap['tgl_masuk']} {$ranap['jam_masuk']}
            </td></tr>
        </table>";
    }
}


$q = bukaquery("
    SELECT
        master_berkas_digital.nama,
        berkas_digital_perawatan.lokasi_file
    FROM berkas_digital_perawatan
    INNER JOIN master_berkas_digital
        ON berkas_digital_perawatan.kode = master_berkas_digital.kode
    WHERE berkas_digital_perawatan.no_rawat = '$no_rawat'
");

if(mysqli_num_rows($q) > 0){

    $html .= "
    <h3>".$no++.". Berkas Digital Perawatan</h3>
    ";

    while($row = mysqli_fetch_assoc($q)){

        $nama = $row['nama'];
        $file = $row['lokasi_file'];

        // Lokasi file asli di server
        $local_path = $_SERVER['DOCUMENT_ROOT'] . "/webapps/berkasrawat/" . $file;

        // URL untuk link download
        $url = "http://localhost/webapps/berkasrawat/" . $file;

        // cek file type
        $is_image = preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
        $is_pdf   = preg_match('/\.pdf$/i', $file);

        // HEADER tiap halaman
        $html .= "
        <div style='page-break-before: always;'>
            <h4 style='margin-bottom:10px; font-weight:bold;'>$nama</h4>
        ";

        // === jika gambar ===
        if($is_image && file_exists($local_path)){

            // convert gambar ke base64
            $img_data = base64_encode(file_get_contents($local_path));
            $mime = mime_content_type($local_path);
            $base64 = "data:$mime;base64,$img_data";

            $html .= "
                <img src='$base64' style='width:100%; max-width:750px;'>
            ";
        }

                // === jika PDF ===
        elseif($is_pdf){
        
            $clean = pathinfo($file, PATHINFO_FILENAME);
        
            // file asli
            $pdf_local = $_SERVER['DOCUMENT_ROOT'] . "/webapps/berkasrawat/" . $file;
        
            // folder hasil convert
            $output_base = $clean;
        
            // cek sudah pernah convert?
            $converted_files = glob($_SERVER['DOCUMENT_ROOT'] . "/webapps/berkasrawat_converted/" . $output_base . "-*.jpg");
        
            if(empty($converted_files)){
                convert_pdf_to_jpg($pdf_local, $output_base);
                $converted_files = glob($_SERVER['DOCUMENT_ROOT'] . "/webapps/berkasrawat_converted/" . $output_base . "-*.jpg");
            }
        
            // jika gagal convert
            if(empty($converted_files)){
                $html .= "
                <div style='border:1px solid #ccc;padding:10px;margin-top:5px;'>
                    <b>PDF tidak dapat dikonversi.</b><br>
                    <a href='$url'>$nama</a>
                </div>";
            } else {
        
                // tampilkan semua halaman hasil convert
                $total = count($converted_files);
                $i = 1;
                
                foreach($converted_files as $jpg){
                
                    $img_data = base64_encode(file_get_contents($jpg));
                    $mime = mime_content_type($jpg);
                    $base64 = "data:$mime;base64,$img_data";
                
                    $html .= "
                        <img src='$base64' style='width:100%; max-width:800px; margin-bottom:10px; border:1px solid #ccc;'>
                    ";
                
                    // page-break hanya jika BUKAN halaman terakhir
                    if ($i < $total) {
                        $html .= "<div style='page-break-after: always;'></div>";
                    }
                
                    $i++;
                }

            }
        }



        // === file lain ===
        else {
            $clean = basename($file);
            $html .= "
                <p>
                    <a href='$url'>$nama ($clean)</a>
                </p>
            ";
        }

        $html .= "</div>";
    }
}


// =============================================
// 6. CETAK PDF
// =============================================
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// =============================================
// 7. DOWNLOAD FILE
// =============================================
$filename = "riwayat_perawatan " . $no_rawat_clean . " (" . $mr_clear . ").pdf";
$dompdf->stream($filename, ["Attachment" => true]);
?>