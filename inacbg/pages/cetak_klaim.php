<?php
include 'wsinacbg.php';

$sep = $_GET['sep'] ?? '';
if ($sep == '') {
    die('Nomor SEP tidak ditemukan.');
}

$data = request('claim_data', ['nomor_sep' => $sep]); // sesuaikan fungsi di wsinacbg.php

if (empty($data['response'])) {
    die('Data klaim tidak ditemukan.');
}

$resp = $data['response'];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Cetak Berkas Klaim</title>
    <style>
    body {
        font-family: Arial;
        font-size: 12px;
        margin: 40px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .table td {
        padding: 4px;
        vertical-align: top;
    }

    .right {
        text-align: right;
    }

    .header {
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <div class="header">
        KEMENTERIAN KESEHATAN REPUBLIK INDONESIA<br>
        <b>Berkas Klaim Individual Pasien</b><br>
        <small>JKN</small><br><br>
    </div>

    <table class="table">
        <tr>
            <td>Kode Rumah Sakit</td>
            <td>3512012</td>
            <td>Kelas Rumah Sakit</td>
            <td>C</td>
        </tr>
        <tr>
            <td>Nama RS</td>
            <td>RSUD DR. ABDOER RAHEM SITUBONDO</td>
            <td>Tanggal</td>
            <td><?=date('d/m/Y')?></td>
        </tr>
    </table>

    <hr>

    <table class="table">
        <tr>
            <td>Nomor SEP</td>
            <td><?=$resp['nomor_sep']?></td>
            <td>Diagnosa</td>
            <td><?=$resp['diagnosa']?></td>
        </tr>
        <tr>
            <td>Kode INA-CBG</td>
            <td><?=$resp['inacbg_code']?></td>
            <td>Nama INA-CBG</td>
            <td><?=$resp['inacbg_name']?></td>
        </tr>
        <tr>
            <td colspan="3" class="right"><b>Total Tarif</b></td>
            <td class="right">Rp <?=number_format($resp['tarif'],0,',','.')?></td>
        </tr>
    </table>

</body>

</html>