<?php
 session_start();
 require_once('conf/command.php');
 require_once('../conf/conf.php');
 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
 header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); 
 header("Cache-Control: no-store, no-cache, must-revalidate"); 
 header("Cache-Control: post-check=0, pre-check=0", false);
 header("Pragma: no-cache"); // HTTP/1.0
 
 
// --- Cek AJAX --- 
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $action = $_POST['action'] ?? '';

    if($action == 'selesai'){
        $nomor = $_POST['nomor'] ?? '';
        $waktu = date('Y-m-d H:i:s');

        if(!empty($nomor)){
            bukaquery2("INSERT INTO antrian_history_loket (h_nomor, h_date) VALUES ('$nomor', '$waktu')");
            echo "Nomor antrian $nomor selesai dan disimpan ke history ($waktu)";
        } else {
            echo "Nomor antrian tidak valid!";
        }
        exit; // <<< Penting, hentikan agar HTML utama tidak dikirim
    }

    // tambahkan action lain jika perlu
}
?>




<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/dataTables/dataTables.bootstrap.css" rel="stylesheet">
    <link href="css/dataTables/dataTables.responsive.css" rel="stylesheet">
    <link href="css/startmin.css" rel="stylesheet">
    <title><?php title();?></title>
    <script>
    function PopupCenter(pageURL, title, w, h) {
        var left = (screen.width / 2) - (w / 2);
        var top = (screen.height / 2) - (h / 2);
        var targetWin = window.open(pageURL, title,
            'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' +
            w + ', height=' + h + ', top=' + top + ', left=' + left);

    }
    </script>
</head>

<body>
    <div id="mainContent">
        <?php actionPages();?>
    </div>
</body>

</html>