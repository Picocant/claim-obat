<?php
    if(strpos($_SERVER['REQUEST_URI'],"pages")){
        if(!strpos($_SERVER['REQUEST_URI'],"pages/upload/")){
            exit(header("Location:../index.php"));
        }
    }
?>
<?php
                echo "";
                $action             = isset($_GET['action'])?$_GET['action']:NULL;
                $no_rawat           = validTeks4((isset($_GET['no_rawat'])?$_GET['no_rawat']:NULL),20);
                $no_rm              = getOne("select reg_periksa.no_rkm_medis from reg_periksa where reg_periksa.no_rawat='$no_rawat'");
                $nama_pasien        = getOne("select pasien.nm_pasien from pasien where pasien.no_rkm_medis='$no_rm'");
                echo "<input type=hidden name=no_rawat value=$no_rawat>
                      <input type=hidden name=action value=$action>";
                      
         ?>
<?php
$q2 = bukaquery("SELECT 
        pasien.no_rkm_medis,
        pasien.nm_pasien,
        pasien.jk,
        pasien.tmp_lahir,
        pasien.tgl_lahir,
        pasien.agama,
        bahasa_pasien.nama_bahasa,
        cacat_fisik.nama_cacat,
        pasien.gol_darah,
        pasien.nm_ibu,
        pasien.stts_nikah,
        pasien.pnd,
        CONCAT(pasien.alamat, ', ', kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) AS alamat,
        pasien.pekerjaan
    FROM pasien
    INNER JOIN bahasa_pasien ON bahasa_pasien.id = pasien.bahasa_pasien
    INNER JOIN cacat_fisik ON cacat_fisik.id = pasien.cacat_fisik
    INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
    INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
    INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
    WHERE pasien.no_rkm_medis = '$no_rm'
");







$rm           = "-";
$nama_pasien  = "-";
$alamat       = "-";
$jk           = "-";
$tmp_lahir    = "-";
$tgl_lahir    = "-";
$ibu          = "-";
$goldar       = "-";
$status_nikah = "-";
$agama        = "-";
$pendidikan   = "-";
$bahasa       = "-";
$cacat_fisik  = "-";

if($px = mysqli_fetch_array($q2)){
    $rm           = $px['no_rkm_medis'];
    $nama_pasien  = $px['nm_pasien'];
    $alamat       = $px['alamat'];
    $jk           = ($px['jk']=="L"?"Laki-Laki":"Perempuan");
    $tmp_lahir    = $px['tmp_lahir'];
    $tgl_lahir    = $px['tgl_lahir'];
    $ibu          = $px['nm_ibu'];
    $goldar       = $px['gol_darah'];
    $status_nikah = $px['stts_nikah'];
    $agama        = $px['agama'];
    $pendidikan   = $px['pnd'];
    $bahasa       = $px['nama_bahasa'];   // hasil join
    $cacat_fisik  = $px['nama_cacat'];    // hasil join
}
$no=1;

?>


<link href="css/file.css" rel="stylesheet" type="text/css" />
<center>
    <h2>RIWAYAT PERAWATAN PASIEN</h2>
</center>

<table class="tbl_form" width="100%" cellpadding="3px" cellspacing="0" border="0">

    <!-- Header Section -->
    <tr class="isi">
        <td colspan="4" style=" font-size:12px; padding:8px 10px;">
            <?= $no++ ?>. Data Pasien
        </td>
    </tr>

    <!-- No RM -->
    <tr class="isi">
        <td width="25%" style="padding-left:10px;">No. RM</td>
        <td width="2%" align="center">:</td>
        <td width="70%"><?= $no_rm ?></td>
    </tr>

    <!-- Nama Pasien -->
    <tr class="isi">
        <td style="padding-left:10px;">Nama Pasien</td>
        <td align="center">:</td>
        <td><?= $nama_pasien ?></td>
    </tr>

    <!-- Alamat -->
    <tr class="isi">
        <td style="padding-left:10px;">Alamat</td>
        <td align="center">:</td>
        <td><?= $alamat ?></td>
    </tr>

    <!-- Jenis Kelamin -->
    <tr class="isi">
        <td style="padding-left:10px;">Jenis Kelamin</td>
        <td align="center">:</td>
        <td><?= $jk ?></td>
    </tr>

    <!-- Tanggal Lahir -->
    <tr class="isi">
        <td style="padding-left:10px;">Tempat & Tanggal Lahir</td>
        <td align="center">:</td>
        <td><?= $tmp_lahir ?>, <?= $tgl_lahir ?></td>
    </tr>

    <!-- Ibu Kandung -->
    <tr class="isi">
        <td style="padding-left:10px;">Ibu Kandung</td>
        <td align="center">:</td>
        <td><?= $ibu ?></td>
    </tr>

    <!-- Golongan Darah -->
    <tr class="isi">
        <td style="padding-left:10px;">Golongan Darah</td>
        <td align="center">:</td>
        <td><?= $goldar ?></td>
    </tr>

    <!-- Status Nikah -->
    <tr class="isi">
        <td style="padding-left:10px;">Status Nikah</td>
        <td align="center">:</td>
        <td><?= $status_nikah ?></td>
    </tr>

    <!-- Agama -->
    <tr class="isi">
        <td style="padding-left:10px;">Agama</td>
        <td align="center">:</td>
        <td><?= $agama ?></td>
    </tr>

    <!-- Pendidikan -->
    <tr class="isi">
        <td style="padding-left:10px;">Pendidikan</td>
        <td align="center">:</td>
        <td><?= $pendidikan ?></td>
    </tr>

    <!-- Bahasa -->
    <tr class="isi">
        <td style="padding-left:10px;">Bahasa Dipakai</td>
        <td align="center">:</td>
        <td><?= $bahasa ?></td>
    </tr>

    <!-- Cacat Fisik -->
    <tr class="isi">
        <td style="padding-left:10px;">Cacat Fisik</td>
        <td align="center">:</td>
        <td><?= $cacat_fisik ?></td>
    </tr>


    <?php
// QUERY REGISTRASI UTAMA
$q_reg = bukaquery(" SELECT 
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
      AND reg_periksa.no_rkm_medis = '$no_rm'
      AND reg_periksa.no_rawat = '$no_rawat'
");

// LOOP UTAMA – REGISTRASI
while ($reg = mysqli_fetch_array($q_reg)) {

    // ==============================
    // 1. TAMPILKAN DATA REGISTRASI
    // ==============================
         echo "
        <tr class='isi' style='background:#eef6ff; font-weight:bold; border-top:1px solid #ccc;'>
            <td colspan='4' style='font-size:12px; padding:8px 10px;'>
                ".$no++.". Data Registrasi
            </td>
        </tr>
        
        <tr class='isi'>
            <td width='25%' style='padding-left:10px;'>No. Rawat</td>
            <td width='2%' align='center'>:</td>
            <td width='70%'>".$reg['no_rawat']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>No. Registrasi</td>
            <td align='center'>:</td>
            <td>".$reg['no_reg']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Tanggal Registrasi</td>
            <td align='center'>:</td>
            <td>".$reg['tgl_registrasi']." ".$reg['jam_reg']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Umur Saat Daftar</td>
            <td align='center'>:</td>
            <td>".$reg['umur']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Unit/Poliklinik</td>
            <td align='center'>:</td>
            <td>".$reg['nm_poli']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Dokter Poli</td>
            <td align='center'>:</td>
            <td>".$reg['nm_dokter']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Cara Bayar</td>
            <td align='center'>:</td>
            <td>".$reg['png_jawab']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Penanggung Jawab</td>
            <td align='center'>:</td>
            <td>".$reg['p_jawab']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Alamat P.J.</td>
            <td align='center'>:</td>
            <td>".$reg['almt_pj']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Hubungan P.J.</td>
            <td align='center'>:</td>
            <td>".$reg['hubunganpj']."</td>
        </tr>
        
        <tr class='isi'>
            <td style='padding-left:10px;'>Status</td>
            <td align='center'>:</td>
            <td>".$reg['status_lanjut']."</td>
        </tr>
        ";
        

    // ==============================
    // 2. DATA RUJUKAN INTERNAL POLI
    // ==============================
    $q_rujuk = bukaquery("SELECT
    rujukan_internal_poli.kd_dokter,
    dokter.nm_dokter,
    rujukan_internal_poli.kd_poli,
    poliklinik.nm_poli
    FROM rujukan_internal_poli
    INNER JOIN dokter ON rujukan_internal_poli.kd_dokter = dokter.kd_dokter
    INNER JOIN poliklinik ON rujukan_internal_poli.kd_poli = poliklinik.kd_poli
    WHERE rujukan_internal_poli.no_rawat = '{$reg['no_rawat']}'
    ");

    while ($rujuk = mysqli_fetch_array($q_rujuk)) {
    echo "
    <tr class='isi'>
        <td style='padding-left:10px;'>Rujukan Internal</td>
        <td align='center'>:</td>
        <td>{$rujuk['nm_dokter']} → {$rujuk['nm_poli']}</td>
    </tr>
    ";
    }

    // ==============================
    // 3. DATA RAWAT INAP
    // ==============================
    $q_ranap = bukaquery("SELECT
    kamar_inap.tgl_masuk,
    kamar_inap.jam_masuk,
    kamar_inap.kd_kamar,
    bangsal.nm_bangsal
    FROM kamar_inap
    INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
    INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
    WHERE kamar_inap.no_rawat = '{$reg['no_rawat']}'
    ");

    while ($ranap = mysqli_fetch_array($q_ranap)) {
    echo "
    <tr class='isi'>
        <tdstyle='padding-left:10px;'>Rawat Inap</tdstyle=>
        <td align='center'>:</td>
        <td>{$ranap['kd_kamar']} - {$ranap['nm_bangsal']}<br>
            Masuk: {$ranap['tgl_masuk']} {$ranap['jam_masuk']}
        </td>
    </tr>
    ";
    }
    }
    ?>
    <?php 
$q = bukaquery("SELECT
        master_berkas_digital.nama,
        berkas_digital_perawatan.lokasi_file
    FROM berkas_digital_perawatan
    INNER JOIN master_berkas_digital
        ON berkas_digital_perawatan.kode = master_berkas_digital.kode
    WHERE berkas_digital_perawatan.no_rawat = '$no_rawat'
");

if(mysqli_num_rows($q) > 0){

    echo "
    <tr class='isi'>
        <td colspan='4' style='font-size:12px; padding:8px 10px;'>
            ".$no++.". Berkas Digital Perawatan
        </td>
    </tr>
    ";

    while($row = mysqli_fetch_assoc($q)){

        $nama = $row['nama'];
        $file = $row['lokasi_file'];
        $url  = "http://localhost/webapps/berkasrawat/" . $file;

        $is_image = preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
        $is_pdf   = preg_match('/\.pdf$/i', $file);

        echo "<tr class='isi'>
                <td width='25%' style='padding-left:10px;'>$nama</td>
                <td width='2%' align='center'>:</td>
                <td width='70%'>";
        
        // === GAMBAR ===
        if($is_image){
            echo "
                <a href='$url' target='_blank'>
                    <img src='$url' style='max-width:450px; max-height:450px; border:1px solid #ccc;'>
                </a>
            ";
        } 
        // === PDF — IFRAME ===
        elseif($is_pdf){
            echo "
                <a href='$url' target='_blank' style='font-size:14px; font-weight:bold;'>$nama (PDF)</a>
                <br><br>

                <iframe src='$url'
                        width='100%'
                        height='850px'
                        style='border:1px solid #ccc; border-radius:4px;'>
                </iframe>
            ";
        }
        // === FILE BIASA ===
        else {
            echo "
                <a href='$url' target='_blank'>$nama</a>
            ";
        }

        echo "</td></tr>";
    }
}
?>




</table>


<br>
<center>
    <a href="cetak.php?no_rawat=<?= $no_rawat ?>" class="btn"
        style="padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;"
        target="_blank">
        🖨 Cetak PDF
    </a>
</center>
<br>