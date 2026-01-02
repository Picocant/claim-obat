<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body{
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
}
.header{
    text-align:center;
}
.header img{
    width:70px;
}
.header table{
    width:100%;
}
hr{
    border:1px solid #000;
}
.table-info td{
    padding:2px;
}
.table-obat{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}
.table-obat th,
.table-obat td{
    border:1px solid #000;
    padding:4px;
}
.table-obat th{
    background:#f0f0f0;
}
.right{text-align:right;}
.center{text-align:center;}
.ttd{
    margin-top:40px;
    width:100%;
}
</style>
</head>

<body>

<!-- HEADER -->
<table width="100%">
<tr>
<td width="15%" class="center">
<img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Lambang_Kabupaten_Situbondo.png">
</td>

<td width="70%" class="center">
<b>RSUD dr. ABDOER RAHEM</b><br>
<b>RINCIAN INSTALASI FARMASI</b><br>
Jl. Anggrek No. 68 Situbondo
</td>

<td width="15%" class="center">
<img src="logo_rsud.png">
</td>
</tr>
</table>

<hr>

<!-- DATA PASIEN -->
<table class="table-info" width="100%">
<tr>
<td width="15%">No Nota</td>
<td width="35%">: <?= $nota['no_nota'] ?></td>
<td width="15%">No Rekmed</td>
<td width="35%">: <?= $pasien['no_rkm_medis'] ?></td>
</tr>

<tr>
<td>Nama</td>
<td>: <?= $pasien['nm_pasien'] ?></td>
<td>Dokter</td>
<td>: <?= $dokter['nm_dokter'] ?></td>
</tr>

<tr>
<td>Alamat</td>
<td>: <?= $pasien['alamat'] ?></td>
<td>Tgl Lahir</td>
<td>: <?= $pasien['tgl_lahir'] ?></td>
</tr>

<tr>
<td>Ruang</td>
<td>: <?= $reg['nm_poli'] ?></td>
<td>Gol Pasien</td>
<td>: <?= $reg['png_jawab'] ?></td>
</tr>

<tr>
<td>Tgl Resep</td>
<td colspan="3">: <?= $nota['tanggal']." ".$nota['jam'] ?></td>
</tr>
</table>

<!-- TABEL OBAT -->
<table class="table-obat">
<thead>
<tr>
<th width="5%">No</th>
<th width="35%">Nama Barang</th>
<th width="15%">Harga</th>
<th width="10%">Qty</th>
<th width="10%">Disc</th>
<th width="15%">Sub Total</th>
</tr>
</thead>

<tbody>
<?php
$no=1;
$total=0;
$total_qty=0;

foreach($detail_obat as $row){
$subtotal = $row['harga'] * $row['jumlah'];
$total += $subtotal;
$total_qty += $row['jumlah'];
?>
<tr>
<td class="center"><?= $no++ ?></td>
<td><?= $row['nama_brng'] ?></td>
<td class="right"><?= number_format($row['harga']) ?></td>
<td class="center"><?= $row['jumlah'] ?></td>
<td class="center">0</td>
<td class="right"><?= number_format($subtotal) ?></td>
</tr>
<?php } ?>
</tbody>
</table>

<!-- TOTAL -->
<table width="100%" style="margin-top:10px;">
<tr>
<td width="70%"></td>
<td width="15%">Jumlah</td>
<td width="15%" class="right"><?= number_format($total) ?></td>
</tr>
<tr>
<td></td>
<td>Disc (0%)</td>
<td class="right">0</td>
</tr>
<tr>
<td></td>
<td><b>Total</b></td>
<td class="right"><b><?= number_format($total) ?></b></td>
</tr>
<tr>
<td></td>
<td>Jumlah Qty</td>
<td class="right"><?= $total_qty ?></td>
</tr>
</table>

<!-- TERBILANG -->
<p><b>TERBILANG :</b> <?= strtoupper(terbilang($total)) ?> RUPIAH</p>

<!-- TTD -->
<table class="ttd">
<tr>
<td width="50%">
Yang Menerima
<br><br><br>
____________________
</td>

<td width="50%" class="center">
Petugas
<br><br><br>
<?= $petugas ?>
</td>
</tr>
</table>

<p class="center">
Waktu Cetak : <?= date('l, d F Y H:i:s') ?>
</p>

</body>
</html>
