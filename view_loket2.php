<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "br_rsar";

$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) die("Koneksi gagal: ".$koneksi->connect_error);

// --- Ambil semua antrian terkini ---
$sql_now = "SELECT loket, antrian FROM antriloket";
$result = $koneksi->query($sql_now);
$dataNow = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataNow[] = $row;
    }
}

// --- Ambil data setting ---
$sql_setting = "SELECT * FROM setting LIMIT 1";
$result_setting = $koneksi->query($sql_setting);
$setting = [];
if ($result_setting && $row = $result_setting->fetch_assoc()) {
    $setting = $row;
} 
?>




<head>
    <title>Display Antrian RS</title>
    <meta http-equiv="refresh" content="5"> <!-- auto refresh tiap 5 detik -->
    <style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        text-align: center;
        margin: 0;
        padding: 0;
        background: linear-gradient(-115deg, #ff9800, #ffc107, #fff3e0);
        background-size: cover;
        color: #333;
    }

    .overlay {
        background: rgba(0, 0, 0, 0.4);
        min-height: 100vh;
        padding: 10px 20px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }

    header {
        padding: 5px 15px;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    .content {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        /* agar kartu mengikuti tinggi maksimum */
        gap: 20px;
        width: 90%;
        margin: 0 auto;
        margin-top: 5px;
        height: 50vh;
        /* tinggi content setengah dari viewport */
    }

    .card {
        background: rgba(255, 193, 7, 0.65);
        border-radius: 15px;
        padding: 15px;
        margin: 10px auto;
        width: 100%;
        box-shadow: 0px 6px 15px rgba(0, 0, 0, .3);
        color: #333;
    }


    .card.left,
    .card.right {
        flex: 1;
        max-width: 53%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        font-size: 2em;
        /* sebelumnya 1.2em, sekarang lebih besar */
        padding: 20px;
    }

    /* Jika ingin angka besar di card left (misal nomor sekarang) */
    .card.left .now,
    .card.right .now {
        font-size: 160px;
        /* sebelumnya 120px */
    }

    /* Jika ingin tulisan loket lebih besar */
    .card.left .loket,
    .card.right .loket {
        font-size: 36px;
        /* sebelumnya 28px */
    }

    /* Jika ada tabel di kartu dan ingin teks lebih besar */
    .card.left table td,
    .card.left table th,
    .card.right table td,
    .card.right table th {
        font-size: 36px;
        /* sebelumnya 28px */
        padding: 15px;
        /* sedikit lebih besar */
    }


    header img {
        max-height: 80px;
    }

    .instansi {
        text-align: left;
    }

    .instansi h1 {
        margin: 0;
        font-size: 40px;
        color: #ffff;
        text-shadow: 2px 2px 6px rgba(0, 0, 0, .4);
    }

    .instansi p {
        margin: 3px 0;
        font-size: 16px;
        color: #fff;
    }

    .now {
        font-size: 120px;
        font-weight: bold;
        color: #f44336;
        margin: 0;
        text-shadow: 3px 3px 10px rgba(0, 0, 0, .6);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }

    .loket {
        font-size: 28px;
        margin-bottom: 10px;
        color: #ff9800;
        font-weight: bold;
    }

    table {
        margin: 10px auto;
        border-collapse: collapse;
        width: 90%;
        height: 100%;
        /* isi tabel memenuhi tinggi kartu */
    }


    th,
    td {
        border: 1px solid #ddd;
        padding: 12px;
        font-size: 28px;
        /* lebih besar untuk visibilitas */
        background: #fff8e1;
        text-align: center;
        vertical-align: middle;
    }

    th {
        background: #ffb300;
        color: #fff;
    }

    td {
        color: #333;
    }

    .next-title {
        font-size: 32px;
        margin-top: 30px;
        color: #fff;
        font-weight: bold;
    }

    .info-box {
        background: rgba(255, 152, 0, 0.1);
        border: 2px solid #ff9800;
        border-radius: 10px;
        padding: 15px;
        margin: 20px auto;
        width: 80%;
        color: #333;
        font-size: 20px;
    }

    .info-box h3 {
        margin-top: 0;
        color: #ff5722;
    }

    .running-text {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #ff9800;
        color: #fff;
        font-size: 24px;
        font-weight: bold;
        overflow: hidden;
        white-space: nowrap;
    }

    .marquee {
        display: inline-block;
        padding-left: 50%;
        animation: marquee 12s linear infinite;
    }

    @keyframes marquee {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-100%);
        }
    }

    .info-box {
        background: rgba(255, 255, 255, 0.6);
        /* kuning mewah semi-transparan, bisa diganti */
        border: 2px solid #ff9800;
        padding: 10px;
        /* sebelumnya 15px, dikurangi */
        margin-top: 5px;
        /* sebelumnya 15px, dikurangi */
        color: #333;
    }

    .info-box h3 {
        margin: 0;
        /* hapus jarak atas/bawah */
        padding: 0;
        /* hapus padding tambahan */
        color: #ff5722;
        font-size: 70px;
    }

    .info-box h4 {
        margin: 0;
        /* hapus jarak atas/bawah */
        padding: 0;
        color: #333;
        font-size: 30px;
    }
    </style>

</head>

<body>
    <div class="overlay">

        <!-- Header Identitas Instansi -->
        <header>
            <!-- <img src="<?= $setting['logo']; ?>" alt="Logo"> -->
            <div class="instansi">
                <h1><?= $setting['nama_instansi']; ?></h1>
                <p><?= $setting['alamat_instansi']; ?>, <?= $setting['kabupaten']; ?> - <?= $setting['propinsi']; ?></p>
                <p>Telp: <?= $setting['kontak']; ?> | Email: <?= $setting['email']; ?></p>
            </div>
        </header>

        <div class="content">
            <div class="card left">
                <h2>Sedang Dipanggil</h2>

                <table>
                    <tr>
                        <th>Loket</th>
                        <th>Nomor</th>
                    </tr>
                    <?php if (!empty($dataNow)): ?>
                    <?php foreach ($dataNow as $row): ?>
                    <tr>
                        <td class="loket" style="font-size:44px; font-weight:bold; color:#ff9800;"><?= $row['loket'] ?>
                        </td>
                        <td class="now"><?= $row['antrian'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align:center; font-style:italic; color:#888;">
                            Belum ada pemanggilan antrian
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

            </div>

            <div class="card right">
                <h2 class="next-title">Antrian Berikutnya</h2>
                <table>
                    <tr>
                        <th>Nomor</th>
                    </tr>
                    <?php
                        $sql_last = "SELECT MAX(CAST(h_nomor AS UNSIGNED)) AS last_nomor FROM antrian_history_loket";
                        $result_last = $koneksi->query($sql_last);
                        if (!$result_last) {
                            die("Query terakhir gagal: " . $koneksi->error);
                        }
                
                        $last_nomor = 0;
                        if ($row = $result_last->fetch_assoc()) {
                            $last_nomor = (int)$row['last_nomor'];
                        }
                
                        // ambil 1 antrian berikutnya
                        $sql_next = "SELECT nomor FROM antriloketcetak 
                                     WHERE CAST(nomor AS UNSIGNED) > $last_nomor 
                                     ORDER BY CAST(nomor AS UNSIGNED) ASC 
                                     LIMIT 1";
                
                        $next = $koneksi->query($sql_next);
                        if (!$next) {
                            die("Query antrian berikutnya gagal: " . $koneksi->error);
                        }
                
                        if ($row = $next->fetch_assoc()) {
                          echo "<tr><td style='font-size:66px; font-weight:bold;'>" . $row['nomor'] . "</td></tr>";
                        } else {
                            echo "<tr><td>Tidak ada antrian berikutnya</td></tr>";
                        }
                        ?>
                </table>
            </div>
        </div>
        <!-- Kotak informasi di bawah tabel -->
        <div class="info-box">
            <h3>Informasi</h3>
            <h4>Harap menunggu dengan tertib hingga nomor Anda dipanggil. Silakan persiapkan dokumen yang diperlukan.
            </h4>
        </div>


        <!-- Running text -->
        <div class="running-text">
            <div class="marquee">
                Selamat Datang di RSUD dr. Abdoer Rahem Situbondo | Harap Menunggu Antrian dengan Tertib | Terima Kasih
            </div>
        </div>


    </div>
</body>

<script>
// ===== Mapping angka dan loket ke file suara =====
const angkaToFile = {
    1: 'satu.mp3',
    2: 'dua.mp3',
    3: 'tiga.mp3',
    4: 'empat.mp3',
    5: 'lima.mp3',
    6: 'enam.mp3',
    7: 'tujuh.mp3',
    8: 'delapan.mp3',
    9: 'sembilan.mp3',
    10: 'sepuluh.mp3',
    11: 'sebelas.mp3',
    100: 'seratus.mp3'
};

// ===== Setup Web Audio API =====
const audioContext = new(window.AudioContext || window.webkitAudioContext)();
let audioQueue = [];
let isPlaying = false;

// Fungsi load audio dari file
async function loadAudioBuffer(url) {
    const response = await fetch(url);
    const arrayBuffer = await response.arrayBuffer();
    return await audioContext.decodeAudioData(arrayBuffer);
}

// Fungsi mainkan queue audio
async function playQueue() {
    if (isPlaying || audioQueue.length === 0) return;
    isPlaying = true;

    while (audioQueue.length > 0) {
        const buffer = await loadAudioBuffer(audioQueue.shift());
        const source = audioContext.createBufferSource();
        source.buffer = buffer;
        source.connect(audioContext.destination);
        await new Promise(resolve => {
            source.onended = resolve;
            source.start(0);
        });
    }

    isPlaying = false;
}

// ===== Fungsi konversi nomor ke file audio =====
function convertNumberToAudioFiles(nomor) {
    let files = [];
    if (nomor <= 11) {
        files.push(`suara/${angkaToFile[nomor]}`);
    } else if (nomor < 20) {
        files.push(`suara/${angkaToFile[nomor - 10]}`);
        files.push('suara/belas.mp3');
    } else if (nomor < 100) {
        const puluhan = Math.floor(nomor / 10);
        const satuan = nomor % 10;
        files.push(`suara/${angkaToFile[puluhan]}`);
        files.push('suara/puluh.mp3');
        if (satuan > 0) files.push(`suara/${angkaToFile[satuan]}`);
    } else if (nomor < 1000) {
        const ratusan = Math.floor(nomor / 100);
        const sisa = nomor % 100;
        if (ratusan === 1) files.push('suara/seratus.mp3');
        else {
            files.push(`suara/${angkaToFile[ratusan]}`);
            files.push('suara/ratus.mp3');
        }
        if (sisa > 0) files = files.concat(convertNumberToAudioFiles(sisa));
    } else if (nomor < 10000) {
        const ribuan = Math.floor(nomor / 1000);
        const sisa = nomor % 1000;
        files.push(`suara/${angkaToFile[ribuan]}`);
        files.push('suara/ribu.mp3');
        if (sisa > 0) files = files.concat(convertNumberToAudioFiles(sisa));
    }
    return files;
}

// ===== Fungsi panggil suara antrian =====
function panggilSuaraNomor(nomor, loket) {
    if (!nomor || !loket) return;

    // Mulai dengan suara "Nomor urut"
    let audioFiles = ['suara/nomor-urut.mp3'];

    // Tambahkan suara angka antrian
    audioFiles = audioFiles.concat(convertNumberToAudioFiles(parseInt(nomor)));

    // Tambahkan "Loket" lalu nomor loket
    audioFiles.push('suara/loket.mp3');
    audioFiles = audioFiles.concat(convertNumberToAudioFiles(parseInt(loket)));

    // Masukkan ke queue dan mainkan
    audioQueue = audioQueue.concat(audioFiles);
    playQueue();
}


// ===== Memantau perubahan data di tabel =====
let lastAntrian = {};

function cekAntrianTabel() {
    const rows = document.querySelectorAll('table tr');

    if (rows.length <= 1) return;

    rows.forEach((row, index) => {
        if (index === 0) return;
        const loket = row.querySelector('.loket')?.textContent;
        const nomor = row.querySelector('.now')?.textContent;
        if (!loket || !nomor) return;

        if (lastAntrian[loket] !== nomor) {
            panggilSuaraNomor(nomor, loket);
            lastAntrian[loket] = nomor;
        }
    });
}

// cek awal
cekAntrianTabel();

// cek berkala tiap 2 detik
setInterval(cekAntrianTabel, 2000);
</script>