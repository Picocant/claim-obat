<?php
if(strpos($_SERVER['REQUEST_URI'],"pages")){
    if(!strpos($_SERVER['REQUEST_URI'],"pages/upload/")){
        exit(header("Location:../index.php"));
    }
}
?>

<style>
.form-container {
    width: 100%;
    max-width: 650px;
    background: #f7f7f7;
    padding: 15px 20px;
    margin: 10px auto;
    border-radius: 8px;
    border: 1px solid #d1d1d1;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    font-family: Arial, Helvetica, sans-serif;
}

.result-container {
    margin-top: 20px;
}

.tbl_form label {
    font-size: 14px;
    font-weight: bold;
    color: #333;
}

.tbl_form input[type="date"],
.tbl_form select {
    width: 90%;
    padding: 6px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #aaa;
    background: white;
}
</style>

<div class="form-container">
    <h3>Filter Data</h3>

    <form method="GET" action="index.php">
        <input type="hidden" name="act" value="List">

        <table width="100%" border="0" cellpadding="6px" cellspacing="0" class="tbl_form">

            <tr>
                <td width="35%"><label>Tanggal Mulai</label></td>
                <td><input type="date" name="tgl_mulai" value="<?=date('Y-m-d')?>" required></td>
            </tr>

            <tr>
                <td><label>Tanggal Selesai</label></td>
                <td><input type="date" name="tgl_selesai" value="<?=date('Y-m-d')?>" required></td>
            </tr>

            <tr>
                <td><label>Dokter</label></td>
                <td>
                    <select name="dokter">
                        <option value="SEMUA">-- SEMUA --</option>
                        <?php
                            $query_dokter = bukaquery("SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
                            while($d = mysqli_fetch_array($query_dokter)){
                                echo "<option value='$d[kd_dokter]'>$d[nm_dokter]</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td><label>Cara Bayar</label></td>
                <td>
                    <select name="carabayar">
                        <option value="SEMUA">-- SEMUA --</option>
                        <?php
                            $query_bayar = bukaquery("SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
                            while($b = mysqli_fetch_array($query_bayar)){
                                echo "<option value='$b[kd_pj]'>$b[png_jawab]</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td><label>Ruangan / Unit</label></td>
                <td>
                    <select name="ruangan">
                        <option value="SEMUA">-- SEMUA --</option>
                        <?php
                            $query_poli = bukaquery("SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli");
                            while($p = mysqli_fetch_array($query_poli)){
                                echo "<option value='POLI:$p[kd_poli]'>Poliklinik - $p[nm_poli]</option>";
                            }

                            $query_bangsal = bukaquery("SELECT kd_bangsal, nm_bangsal FROM bangsal ORDER BY nm_bangsal");
                            while($r = mysqli_fetch_array($query_bangsal)){
                                echo "<option value='RANAP:$r[kd_bangsal]'>Rawat Inap - $r[nm_bangsal]</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td></td>
                <td>
                    <button type="submit" class="btn-submit">Tampilkan</button>
                    <button type="reset" class="btn-reset">Reset</button>
                </td>
            </tr>

        </table>
    </form>
</div><?php
if(isset($_GET['act']) && $_GET['act']=="List"){

    echo "<div class='result-container'>";
    echo "<h3><center>HASIL LAPORAN TINDAKAN</center></h3>";

    $tgl_mulai      = validTeks($_GET['tgl_mulai']);
    $tgl_selesai    = validTeks($_GET['tgl_selesai']);
    $dokter         = validTeks($_GET['dokter']);
    $carabayar      = validTeks($_GET['carabayar']);
    $ruangan        = validTeks($_GET['ruangan']);

    echo "
    <table width='100%' border='1' cellpadding='3' cellspacing='0' class='tbl_form'>
        <tr class='head'>
            <th>No.</th>
            <th>No.Rawat</th>
            <th>No.R.M.</th>
            <th>Nama Pasien</th>
            <th>Kd.Tnd</th>
            <th>Perawatan/Tindakan</th>
            <th>Kode Dokter</th>
            <th>Dokter Yang Menangani</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Cara Bayar</th>
            <th>Ruangan</th>
            <th>Jasa Sarana</th>
            <th>Jasa Medis</th>
            <th>Jasa Paramedis</th>
            <th>Manajemen</th>
            <th>Total</th>
        </tr>
    ";

    // ==========================
    //   BANGUN QUERY KHANZA
    // ==========================

    $sql = "SELECT 
            reg.no_rawat,
            reg.no_rkm_medis,
            ps.nm_pasien,
            t.kd_jenis_prw,
            jp.nm_perawatan,
            d.kd_dokter,
            d.nm_dokter,
            t.tgl_perawatan,
            t.jam_rawat,
            pj.png_jawab,
            pol.nm_poli,
            bg.nm_bangsal,
            jp.bhp,
            jp.tarif_tindakan_dokter,
            jp.tarif_tindakan_perawat,
            jp.kso,
            jp.total_byrdrpr
        FROM reg_periksa reg
        INNER JOIN pasien ps ON ps.no_rkm_medis = reg.no_rkm_medis
        INNER JOIN dokter d ON d.kd_dokter = reg.kd_dokter
        INNER JOIN penjab pj ON pj.kd_pj = reg.kd_pj

        -- Ambil semua jenis tindakan
        INNER JOIN (
            SELECT no_rawat, kd_jenis_prw, tgl_perawatan, jam_rawat FROM rawat_jl_dr
            UNION ALL
            SELECT no_rawat, kd_jenis_prw, tgl_perawatan, jam_rawat FROM rawat_jl_drpr
            UNION ALL
            SELECT no_rawat, kd_jenis_prw, tgl_perawatan, jam_rawat FROM rawat_inap_dr
            UNION ALL
            SELECT no_rawat, kd_jenis_prw, tgl_perawatan, jam_rawat FROM rawat_inap_drpr
        ) t ON t.no_rawat = reg.no_rawat

        INNER JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw
        LEFT JOIN poliklinik pol ON pol.kd_poli = reg.kd_poli
        LEFT JOIN kamar_inap ki ON ki.no_rawat = reg.no_rawat
        LEFT JOIN kamar km ON km.kd_kamar = ki.kd_kamar
        LEFT JOIN bangsal bg ON bg.kd_bangsal = km.kd_bangsal

        WHERE t.tgl_perawatan BETWEEN '$tgl_mulai' AND '$tgl_selesai'
    ";

    // === FILTER DOKTER ===
    if($dokter != "SEMUA"){
        $sql .= " AND reg.kd_dokter = '$dokter' ";
    }

    // === FILTER CARA BAYAR ===
    if($carabayar != "SEMUA"){
        $sql .= " AND reg.kd_pj = '$carabayar' ";
    }

    // === FILTER RUANGAN ===
    if($ruangan != "SEMUA"){
        $r = explode(":", $ruangan);
        $jenis_ruang = $r[0];
        $kode_ruang  = $r[1];

        if($jenis_ruang == "POLI"){
            $sql .= " AND reg.kd_poli = '$kode_ruang' ";
        } else {
            $sql .= " AND bg.kd_bangsal = '$kode_ruang' ";
        }
    }

    $sql .= " ORDER BY t.tgl_perawatan DESC, t.jam_rawat DESC";

    // Eksekusi
    $hasil = bukaquery($sql);
    $no = 1;

    while($row = mysqli_fetch_array($hasil)){

        $ruang_tampil = $row['nm_poli'] ?: $row['nm_bangsal'];

        echo "
            <tr>
                <td>$no</td>
                <td>$row[no_rawat]</td>
                <td>$row[no_rkm_medis]</td>
                <td>$row[nm_pasien]</td>
                <td>$row[kd_jenis_prw]</td>
                <td>$row[nm_perawatan]</td>
                <td>$row[kd_dokter]</td>
                <td>$row[nm_dokter]</td>
                <td>$row[tgl_perawatan]</td>
                <td>$row[jam_rawat]</td>
                <td>$row[png_jawab]</td>
                <td>$ruang_tampil</td>
                <td>$row[bhp]</td>
                <td>$row[tarif_tindakan_dokter]</td>
                <td>$row[tarif_tindakan_perawat]</td>
                <td>$row[kso]</td>
                <td>$row[total_byrdrpr]</td>
            </tr>
        ";

        $no++;
    }

    echo "</table></div>";
}
?>