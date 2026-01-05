<?php
require_once('../conf/conf.php');
date_default_timezone_set('Asia/Jakarta');

// --- Skip redirect untuk AJAX ---
if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'){
    if(strpos($_SERVER['REQUEST_URI'], "pages") !== false){
        exit(header("Location:../index.php"));
    }
}


if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $action = $_POST['action'] ?? '';

    if($action == 'panggil'){
        $loket = $_POST['loket'];
        $antrian = $_POST['antrian'];
        bukaquery2("DELETE FROM antriloket");
        bukaquery2("INSERT INTO antriloket (loket, antrian) VALUES ('$loket', '$antrian')");

        echo "Loket $loket memanggil $antrian";
        exit;
    }

    if($action == 'stop'){
        bukaquery2("DELETE FROM antriloket");
        echo "Pemanggilan dihentikan, semua data dihapus";
        exit;
    }

    if($action == 'selesai'){
        $nomor = $_POST['nomor'] ?? '';
        $waktu = date('Y-m-d H:i:s');

        if(!empty($nomor)){
            bukaquery2("INSERT INTO antrian_history_loket (h_nomor, h_date) VALUES ('$nomor', '$waktu')");

            echo "Nomor antrian $nomor selesai dan disimpan ke history ($waktu)";
        } else {
            echo "Nomor antrian tidak valid!";
        }
        exit;
    }
}

// --- Nama hari dan bulan ---
$hari = array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');
$bulan = array(
    1 => 'Januari','Februari','Maret','April','Mei','Juni',
         'Juli','Agustus','September','Oktober','November','Desember'
);

$nama_hari = $hari[date('w')];
$tanggal    = date('d');
$nama_bulan = $bulan[date('n')];
$tahun      = date('Y');

// --- ambil data antrian ---
$tanggalHariIni = date("Y-m-d");
$sql = "SELECT a.tanggal, a.jam, a.nomor
        FROM antriloketcetak a
        LEFT JOIN antrian_history_loket h ON a.nomor = h.h_nomor
        WHERE a.tanggal = '$tanggalHariIni' AND h.h_nomor IS NULL
        ORDER BY a.jam ASC";
$result = bukaquery2($sql);
$dataAntrian = [];
while ($row = mysqli_fetch_assoc($result)) {
    $dataAntrian[] = $row;
}
$dataAntrianJson = json_encode($dataAntrian);


?>



<head>
    <meta charset="utf-8">
    <title>Data Antrian Loket</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/dataTables/dataTables.bootstrap.css" rel="stylesheet">
    <link href="css/dataTables/dataTables.responsive.css" rel="stylesheet">
    <link href="css/startmin.css" rel="stylesheet">
</head>

<body>
    <div id="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3">
                    <!-- Panel Panggilan -->
                    <h1 class="page-header">Panggil Antrian</h1>
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="form-inline mb-3" style="display:flex; align-items:center; gap:30px;">
                                <label style="font-size:18px; font-weight:bold;">Loket :</label>
                                <select class="form-control" id="loket"
                                    style="font-size:18px; height:50px; width:70px;">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                                <label style="font-size:18px; font-weight:bold;">Antrian :</label>
                                <input type="text" class="form-control" id="nomorAntrian"
                                    style="width:70px; height:50px; font-size:18px; text-align:center;">
                            </div>
                        </div>
                        <div class=" panel-footer">
                            <div class="mb-3">
                                <button class="btn btn-primary" id="btnAntri"><i class="fa fa-book"></i> Antri</button>
                                <button class="btn btn-warning" id="btnStop"><i class="fa fa-stop"></i> Stop</button>
                                <button class="btn btn-danger" id="btnReset"><i class="fa fa-times"></i> Reset</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <!-- Tabel Antrian -->
                    <h1 class="page-header">Data Antrian Loket</h1>
                    <div class="panel panel-default">
                        <div class="panel-heading position-relative">
                            <span>
                                Data Antrian -
                                <?php echo $nama_hari . ", " . $tanggal . " " . $nama_bulan . " " . $tahun; ?>
                            </span>

                            <!-- Tombol Refresh -->
                            <button class="btn btn-sm btn-primary position-absolute" style="top:5px; right:90px;"
                                onclick="location.reload();">
                                Refresh
                            </button>

                            <!-- Tombol Logout -->
                            <a href='?aksi=Keluar' class="btn btn-sm btn-danger position-absolute"
                                style="top:5px; right:5px;">
                                Back
                            </a>
                        </div>




                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="antrianTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Jam</th>
                                            <th>No Antrian</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody> <!-- Kosong, nanti diisi JS -->
                                </table>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>
    </div>




    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/dataTables/jquery.dataTables.min.js"></script>
    <script src="js/dataTables/dataTables.bootstrap.min.js"></script>


    <script>
    $(document).ready(function() {
        // Ambil data antrian dari PHP

        const dataAntrian = <?php echo $dataAntrianJson; ?>;

        const table = $('#antrianTable').DataTable({
            data: dataAntrian,
            columns: [{
                    data: null
                }, // No otomatis
                {
                    data: 'tanggal'
                },
                {
                    data: 'jam'
                },
                {
                    data: 'nomor'
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `<button class='btn btn-success btn-selesai' data-nomor='${row.nomor}'>Selesai</button>`;
                    }
                }
            ],
            columnDefs: [{
                targets: 0,
                render: function(data, type, row, meta) {
                    return meta.row + 1;
                }
            }]
        });

        // Event klik tombol Selesai di tabel
        $('#antrianTable').on('click', '.btn-selesai', function(e) {
            e.preventDefault();
            const nomor = $(this).data('nomor');

            if (!nomor) {
                alert('Nomor antrian tidak valid!');
                return;
            }

            $.post('', {
                    action: 'selesai',
                    nomor: nomor
                },
                function(res) {
                    // langsung tampilkan alert dengan teks dari PHP
                    alert(res);
                    // refresh otomatis setelah OK
                    location.reload();
                });
        });

        $('#btnAntri').click(function() {
            const loket = $('#loket').val();
            const antrian = $('#nomorAntrian').val();
            if (!antrian) return alert('Pilih nomor antrian dulu!');

            // POST ke server simpan ke DB
            $.post('', {
                action: 'panggil',
                loket: loket,
                antrian: antrian
            });

            // POST ke monitor view_loket.php
            $.post('../view_loket.php', { // sesuaikan path relatif
                action: 'triggerAudio',
                loket: loket,
                antrian: antrian
            }).done(function(res) {
                console.log('Trigger berhasil:', res);
            }).fail(function(err) {
                console.error('Trigger gagal:', err);
            });
        });



        $('#btnStop').click(function() {
            const loket = $('#loket').val();
            $.post('', {
                action: 'stop',
                loket: loket
            });
        });
        $(document).ready(function() {
            // Tombol Reset
            $('#btnReset').click(function() {
                // Set dropdown Loket ke 1
                $('#loket').val('1');
                // Kosongkan input Nomor Antrian
                $('#nomorAntrian').val('');
            });
        });
    });
    </script>


</body>