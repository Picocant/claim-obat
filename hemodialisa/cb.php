<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resume Pasien - SIMKES</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="css/metisMenu.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/startmin.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div id="wrapper">
        <!-- Navigation -->
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <!-- Navbar Header -->
            <div class="navbar-header">
                <a class="navbar-brand" href="#">
                    NURIL AZIZAH (13148059)
                    <span class="label label-success">SELESAI</span>
                </a>
            </div>

            <!-- Navbar Right Info -->
            <ul class="nav navbar-right navbar-top-links">
                <li class="dropdown">
                    <a href="#">
                        27-03-1972 (53 th) | Rujukan: 01928D0606125P00340
                    </a>
                </li>
                <li>
                    <button class="btn btn-success navbar-btn">ASESMEN AWAL</button>
                </li>
            </ul>

            <!-- Sidebar -->
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="active">
                            <a href="#"><i class="fa fa-list-alt fa-fw"></i> Daftar Form</a>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-id-card fa-fw"></i> Informasi Pasien</a>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-cogs fa-fw"></i> Pengaturan Pasien</a>
                        </li>

                        <li>
                            <a href="#"><i class="fa fa-upload fa-fw"></i> Upload Berkas</a>
                        </li>

                        <li class="divider"></li>

                        <!-- Submenu Pelayanan -->
                        <li>
                            <a href="#"><i class="fa fa-hospital-o fa-fw"></i> Pelayanan <span
                                    class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li><a href="#"><i class="fa fa-credit-card fa-fw text-success"></i> Billing
                                        Perawatan</a></li>
                                <li><a href="#"><i class="fa fa-file-prescription fa-fw text-primary"></i> E-Resep</a>
                                </li>
                                <li><a href="#"><i class="fa fa-flask fa-fw text-info"></i> Penunjang</a></li>
                                <li><a href="#"><i class="fa fa-comments fa-fw text-warning"></i> Konsultasi</a></li>
                                <li><a href="#"><i class="fa fa-history fa-fw text-danger"></i> History</a></li>
                                <li><a href="#"><i class="fa fa-ellipsis-h fa-fw"></i> Menu Lainnya</a></li>
                            </ul>
                        </li>

                    </ul>
                </div>
            </div>

        </nav>

        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="container-fluid" style="margin-top:70px;">

                <!-- Informasi Alert -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <i class="fa fa-user"></i> Informasi Pasien
                    </div>
                    <div class="panel-body">
                        <h4><strong>NURIL AZIZAH (13148059)</strong></h4>
                        <p>
                            <strong>Tanggal Lahir:</strong> 27-03-1972 (53 tahun 5 bulan 23 hari)<br>
                            <strong>Rujukan:</strong> 01928D0606125P00340<br>
                            <strong>Status:</strong>
                            <span class="label label-success">SELESAI</span>
                        </p>
                    </div>
                </div>



                <!-- Pemeriksaan -->
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Pemeriksaan</b></div>
                    <div class="panel-body">
                        <!-- Dua Kolom -->
                        <form role="form">
                            <div class="row">

                                <!-- KIRI -->
                                <div class="col-md-6">
                                    <div class="row form-section">
                                        <div class="col-sm-5">
                                            <label>Tanggal</label>
                                            <input type="date" class="form-control" id="tgl" name="tgl">
                                        </div>
                                        <div class="col-sm-5">
                                            <label>Jam</label>
                                            <input type="time" step="1" class="form-control" id="jam" name="jam">
                                            <!-- step=1 biar ada detik -->
                                        </div>
                                        <div class="col-sm-2" style="margin-top:25px;">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="autoTime"> Otomatis
                                            </label>
                                        </div>
                                    </div>

                                    <script>
                                    // Format ke YYYY-MM-DD HH:mm:ss
                                    function formatDateTime(date) {
                                        const pad = (n) => (n < 10 ? '0' + n : n);
                                        return date.getFullYear() + "-" +
                                            pad(date.getMonth() + 1) + "-" +
                                            pad(date.getDate()) + " " +
                                            pad(date.getHours()) + ":" +
                                            pad(date.getMinutes()) + ":" +
                                            pad(date.getSeconds());
                                    }

                                    let autoInterval = null;

                                    document.getElementById("autoTime").addEventListener("change", function() {
                                        const tglInput = document.getElementById("tgl");
                                        const jamInput = document.getElementById("jam");

                                        if (this.checked) {
                                            // Isi langsung
                                            const now = new Date();
                                            tglInput.value = now.toISOString().slice(0, 10); // YYYY-MM-DD
                                            jamInput.value = now.toTimeString().slice(0, 8); // HH:mm:ss

                                            // Update tiap detik
                                            autoInterval = setInterval(() => {
                                                const now = new Date();
                                                tglInput.value = now.toISOString().slice(0, 10);
                                                jamInput.value = now.toTimeString().slice(0, 8);
                                            }, 1000);

                                            tglInput.setAttribute("readonly", true);
                                            jamInput.setAttribute("readonly", true);
                                        } else {
                                            clearInterval(autoInterval);
                                            tglInput.removeAttribute("readonly");
                                            jamInput.removeAttribute("readonly");
                                        }
                                    });
                                    </script>

                                    <div class="row form-section">
                                        <div class="col-sm-6">
                                            <label> Dilakukan oleh </label>
                                            <input class="form-control" name="dilakukan">
                                        </div>
                                        <div class="col-sm-6">
                                            <label> Profesi / Jabatan / Departemen </label>
                                            <input class="form-control" name="profesi">
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <label> Subjektif </label>
                                        <textarea class="form-control" rows="2" name="subjektif"></textarea>
                                    </div>

                                    <div class="form-section">
                                        <label> Objektif </label>
                                        <textarea class="form-control" rows="2" name="objektif"></textarea>
                                    </div>

                                    <h4>Tanda Vital</h4>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <label>Suhu (°C)</label>
                                            <input class="form-control" name="suhu">
                                        </div>
                                        <div class="col-sm-4">
                                            <label>Tensi (mmHg)</label>
                                            <input class="form-control" name="tensi">
                                        </div>
                                        <div class="col-sm-4">
                                            <label>Berat (Kg)</label>
                                            <input class="form-control" name="berat">
                                        </div>
                                    </div><br>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <label>Tinggi (Cm)</label>
                                            <input class="form-control" name="tb">
                                        </div>
                                        <div class="col-sm-4">
                                            <label>RR (/menit)</label>
                                            <input class="form-control" name="rr">
                                        </div>
                                        <div class="col-sm-4">
                                            <label>Nadi (/menit)</label>
                                            <input class="form-control" name="nadi">
                                        </div>
                                    </div><br>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <label>SpO2 (%)</label>
                                            <input class="form-control" name="spo2">
                                        </div>
                                        <div class="col-sm-4">
                                            <label>GCS (E,V,M)</label>
                                            <input class="form-control" name="gcs">
                                        </div>
                                        <div class="col-sm-4">
                                            <label>Kesadaran</label>
                                            <select class="form-control" name="kesadaran">
                                                <option>Compos Mentis</option>
                                                <option>Apatis</option>
                                                <option>Delirium</option>
                                                <option>Somnolen</option>
                                                <option>Sopor</option>
                                                <option>Koma</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- KANAN -->
                                <div class="col-md-6">
                                    <div class="row form-section">
                                        <div class="col-sm-4">
                                            <label> L.P. (Cm) </label>
                                            <input class="form-control" name="lp">
                                        </div>
                                        <div class="col-sm-8">
                                            <label> Alergi </label>
                                            <input class="form-control" name="alergi">
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <label> Asesmen </label>
                                        <textarea class="form-control" rows="2" name="asesmen"></textarea>
                                    </div>

                                    <div class="form-section">
                                        <label> Plan </label>
                                        <textarea class="form-control" rows="2" name="plan"></textarea>
                                    </div>

                                    <div class="form-section">
                                        <label> Instruksi / Implementasi </label>
                                        <textarea class="form-control" rows="2" name="instruksi"></textarea>
                                    </div>

                                    <div class="form-section">
                                        <label> Evaluasi </label>
                                        <textarea class="form-control" rows="2" name="evaluasi"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="margin-top:15px;">
                                <div class="col-md-12 text-center">
                                    <!-- Simpan Data Baru -->
                                    <button type="submit" name="aksi" value="simpan" class="btn btn-success">
                                        <i class="fa fa-save"></i> Simpan
                                    </button>

                                    <!-- Ganti / Update Data -->
                                    <button type="submit" name="aksi" value="ganti" class="btn btn-warning">
                                        <i class="fa fa-edit"></i> Ganti
                                    </button>

                                    <!-- Reset Form -->
                                    <button type="reset" class="btn btn-danger">
                                        <i class="fa fa-times"></i> Reset
                                    </button>
                                </div>
                            </div>


                        </form>
                    </div>
                </div>

                <!-- tabel isi insert -->
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Hasil Pemeriksaan</b></div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="tabelPemeriksaan">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Dilakukan Oleh</th>
                                        <th>Subjektif</th>
                                        <th>Objektif</th>
                                        <th>Tanda Vital</th>
                                        <th>Asesmen</th>
                                        <th>Plan</th>
                                        <th>Instruksi</th>
                                        <th>Evaluasi</th>
                                        <th>Tanggal & Jam</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>dr. Budi<br>Dokter Umum</td>
                                        <td>Pasien mengeluh lemas</td>
                                        <td>Tekanan darah tinggi</td>
                                        <td>
                                            Suhu: 37°C<br>
                                            Tensi: 160/100<br>
                                            Berat: 65kg<br>
                                            Tinggi: 170cm<br>
                                            Nadi: 88<br>
                                            RR: 20<br>
                                            SpO₂: 96%<br>
                                            GCS: 15<br>
                                            Kesadaran: Compos Mentis
                                        </td>
                                        <td>Hipertensi</td>
                                        <td>Kontrol 1 minggu lagi</td>
                                        <td>Berikan obat A</td>
                                        <td>Perbaikan kondisi</td>
                                        <td>2025-09-03 09:30:12</td>
                                        <td class="text-center">
                                            <button class="btn btn-warning btn-xs"><i class="fa fa-edit"></i>
                                                Edit</button>
                                            <button class="btn btn-danger btn-xs"><i class="fa fa-trash"></i>
                                                Hapus</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <script>
                        $(document).ready(function() {
                            $('#tabelPemeriksaan').DataTable({
                                responsive: true,
                                pageLength: 5,
                                lengthMenu: [5, 10, 20, 50],
                                language: {
                                    search: "Cari:",
                                    lengthMenu: "Tampilkan _MENU_ data",
                                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                                    paginate: {
                                        previous: "Sebelumnya",
                                        next: "Berikutnya"
                                    }
                                }
                            });
                        });
                        </script>
                    </div>
                </div>

            </div>
        </div>

        <!-- jQuery -->
        <script src="js/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="js/bootstrap.min.js"></script>
        <!-- Metis Menu -->
        <script src="js/metisMenu.min.js"></script>
        <!-- Custom Theme -->
        <script src="js/startmin.js"></script>
</body>

</html>