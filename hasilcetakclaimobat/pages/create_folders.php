<?php
/************************************************
 * SCRIPT UNTUK MEMBUAT FOLDER BERKAS PEMERIKSAAN
 ************************************************/

$base_path = $_SERVER['DOCUMENT_ROOT'] . '/webapps/';

$folders = [
    'hasilpemeriksaaneeg',
    'hasilpemeriksaanhba1c',
    'hasilpemeriksaanmmse',
    'hasilpemeriksaanecho',
    'hasilpemeriksaanechopediatrik',
    'hasilpemeriksaanekg',
    'hasilpemeriksaanoct',
    'hasilpemeriksaanslitlamp',
    'hasilpemeriksaantreadmill',
    'hasilpemeriksaanusg',
    'hasilpemeriksaanusggynecologi',
    'hasilpemeriksaanusgneonatus',
    'hasilpemeriksaanusgurologi',
    'hasilpemeriksaanendoskopifaringlaring',
    'hasilpemeriksaanendoskopihidung',
    'hasilpemeriksaanendoskopitelinga'
];

echo "<h3>Membuat Folder Berkas Pemeriksaan</h3>";
echo "<ul>";

foreach ($folders as $folder) {
    $full_path = $base_path . $folder;
    
    if (file_exists($full_path)) {
        echo "<li><span style='color: blue;'>✓</span> Folder <strong>$folder</strong> sudah ada</li>";
    } else {
        if (mkdir($full_path, 0777, true)) {
            echo "<li><span style='color: green;'>✓</span> Folder <strong>$folder</strong> berhasil dibuat</li>";
        } else {
            echo "<li><span style='color: red;'>✗</span> Folder <strong>$folder</strong> GAGAL dibuat</li>";
        }
    }
}

echo "</ul>";
echo "<hr>";
echo "<p><strong>Selesai!</strong> Semua folder sudah siap digunakan.</p>";
echo "<p><a href='previewriwayat.php?no_rkm_medis='>← Kembali ke Preview</a></p>";
?>
