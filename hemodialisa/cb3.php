<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Rawat Jalan - Khanza Style</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="css/metisMenu.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/startmin.css" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <style>
    textarea {
        resize: vertical;
    }

    .form-section {
        margin-bottom: 15px;
    }
    </style>
</head>

<body>
    <div id="wrapper">

        <!-- Navigation -->
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="navbar-header">
                <a class="navbar-brand" href="#">Form Rawat Jalan</a>
            </div>
        </nav>

        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-lg-12">
                        <h2 class="page-header">Input Rawat Jalan</h2>
                    </div>
                </div>

                <!-- Dua Kolom -->
                <form role="form">
                    <div class="row">

                        <!-- KIRI -->
                        <div class="col-md-6">
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
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="reset" class="btn btn-default">Reset</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="js/metisMenu.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="js/startmin.js"></script>
</body>

</html>