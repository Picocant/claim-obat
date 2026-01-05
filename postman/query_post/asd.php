<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Biaya Rawat</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f7faff;
        color: #333;
        padding: 20px;
    }

    h2 {
        color: #007BFF;
        border-bottom: 2px solid #007BFF;
        padding-bottom: 5px;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 15px;
        background: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px 10px;
        text-align: left;
    }

    th {
        background-color: #007BFF;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .summary {
        margin-top: 20px;
        padding: 10px;
        background: #e9f5ff;
        border-left: 5px solid #007BFF;
    }
    </style>
</head>

<body>

    <h2>Detail Biaya Pasien</h2>

    <div>
        <label>No. Rawat: </label>
        <input type="text" id="no_rawat" placeholder="Masukkan no_rawat">
        <button onclick="ambilData()">Lihat Detail</button>
    </div>

    <div id="hasil"></div>

    <script>
    async function ambilData() {
        const no_rawat = document.getElementById('no_rawat').value.trim();
        if (!no_rawat) {
            alert("Masukkan nomor rawat terlebih dahulu.");
            return;
        }

        document.getElementById('hasil').innerHTML = "<p>Sedang memuat data...</p>";

        try {
            const res = await fetch("asd.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    no_rawat
                })
            });

            const data = await res.json();
            if (data.status !== "success") {
                document.getElementById('hasil').innerHTML = "<p style='color:red;'>Gagal: " + data.message +
                    "</p>";
                return;
            }

            // ===== Proses dan kelompokkan data per kategori =====
            const kategoriTotal = {}; // { 'KTA': 120000, 'KONSULTASI': 20000, ... }

            Object.values(data.kelompok_data).forEach(grup => {
                grup.forEach(item => {
                    const kategori = item.kategori || '-';
                    const biaya = parseFloat(item.biaya || 0);
                    kategoriTotal[kategori] = (kategoriTotal[kategori] || 0) + biaya;
                });
            });

            // ===== Buat tabel HTML =====
            let html = `
          <div class="summary">
            <p><b>No. Rawat:</b> ${data.no_rawat}</p>
            <p><b>No. RM:</b> ${data.no_rkm_medis}</p>
            <p><b>Tanggal Registrasi:</b> ${data.tgl_registrasi}</p>
            <p><b>Total Biaya Keseluruhan:</b> Rp ${data.total_biaya.toLocaleString('id-ID')}</p>
            <p><b>Total KTA:</b> Rp ${data.total_kta.toLocaleString('id-ID')}</p>
            <p><b>Total Konsultasi:</b> Rp ${data.total_konsultasi.toLocaleString('id-ID')}</p>
          </div>

          <table>
            <thead>
              <tr>
                <th>Kategori</th>
                <th>Total Biaya (Rp)</th>
              </tr>
            </thead>
            <tbody>
        `;

            for (const [kategori, total] of Object.entries(kategoriTotal)) {
                html += `
            <tr>
              <td>${kategori}</td>
              <td>${total.toLocaleString('id-ID')}</td>
            </tr>
          `;
            }

            html += `
            </tbody>
          </table>
        `;

            document.getElementById('hasil').innerHTML = html;

        } catch (error) {
            document.getElementById('hasil').innerHTML = "<p style='color:red;'>Terjadi kesalahan: " + error
                .message + "</p>";
        }
    }
    </script>

</body>

</html>