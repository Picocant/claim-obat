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
        align-items: flex-start;
        gap: 20px;
        width: 90%;
        margin: 0 auto;
        margin-top: 5px;
    }

    .card {
        background: rgba(255, 255, 255, 0.85);
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
        max-width: 48%;
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
        font-size: 80px;
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
        margin: 20px auto;
        border-collapse: collapse;
        width: 70%;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 12px;
        font-size: 22px;
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
        color: #ff9800;
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
                        <td class="loket"><?= $row['loket'] ?></td>
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
                            echo "<tr><td>" . $row['nomor'] . "</td></tr>";
                        } else {
                            echo "<tr><td>Tidak ada antrian berikutnya</td></tr>";
                        }
                        ?>
                </table>
            </div>
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
let voices = [];

function speakText(text) {
    const utter = new SpeechSynthesisUtterance(text);

    // pilih suara perempuan bahasa Indonesia
    const femaleVoice = voices.find(v => v.lang.startsWith('id') && v.name.toLowerCase().includes('female'));
    if (femaleVoice) utter.voice = femaleVoice;

    utter.rate = 1; // kecepatan bicara
    utter.pitch = 1; // nada
    speechSynthesis.speak(utter);
}

// ambil suara setelah voices berubah
speechSynthesis.onvoiceschanged = () => {
    voices = speechSynthesis.getVoices();
};

// ===== Fungsi panggil suara dengan Browser SpeechSynthesis (Bahasa Indonesia, suara perempuan) =====
function speakNomor(nomor, loket) {
    if (!('speechSynthesis' in window)) return console.warn('Browser tidak mendukung SpeechSynthesis');

    // Cari suara perempuan Bahasa Indonesia
    let suara = speechSynthesis.getVoices().find(v => v.lang.includes('id') && v.name.toLowerCase().includes('female'));
    if (!suara) suara = speechSynthesis.getVoices()[0]; // fallback jika tidak ada

    const utterNomor = new SpeechSynthesisUtterance(`Nomor ${nomor}`);
    utterNomor.voice = suara;
    utterNomor.lang = 'id-ID';

    const utterLoket = new SpeechSynthesisUtterance(`Silakan ke loket ${loket}`);
    utterLoket.voice = suara;
    utterLoket.lang = 'id-ID';

    // Urutkan: nomor dulu, baru loket
    window.speechSynthesis.speak(utterNomor);
    utterNomor.onend = () => {
        window.speechSynthesis.speak(utterLoket);
    };
}

// ===== Panggil otomatis setiap ada antrian baru =====
let lastAntrian = {};

function cekAntrianTabel() {
    const rows = document.querySelectorAll('table tr');

    if (rows.length <= 1) return;

    rows.forEach((row, index) => {
        if (index === 0) return; // skip header
        const loket = row.querySelector('.loket')?.textContent;
        const nomor = row.querySelector('.now')?.textContent;
        if (!loket || !nomor) return;

        if (lastAntrian[loket] !== nomor) {
            speakNomor(nomor, loket);
            lastAntrian[loket] = nomor;
        }
    });
}

// cek awal
cekAntrianTabel();

// polling setiap 2 detik
setInterval(cekAntrianTabel, 2000);
</script>