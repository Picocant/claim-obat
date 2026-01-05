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