<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');
// require_once('function.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$kamera_id = generateUUID();
// $nama = trim(isset($param_POST->nama) ? $param_POST->nama : "");
$nama_kamera = isset($param_POST->nama_kamera) ? trim($param_POST->nama_kamera) : "";
$url_rtsp = trim(isset($param_POST->url_rtsp) ? $param_POST->url_rtsp : "");
$ip_address = trim(isset($param_POST->ip_address) ? $param_POST->ip_address : "");
$ruangan_otmil_id = trim(isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : "");
$ruangan_lemasmil_id =  trim( isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : "");
$merk = trim(isset($param_POST->merk) ? $param_POST->merk : "");
$model = trim(isset($param_POST->model) ? $param_POST->model : "");
$status_kamera = trim(isset($param_POST->status_kamera) ? $param_POST->status_kamera : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM kamera WHERE 
        nama_kamera = ? AND 
        url_rtsp = ? AND 
        ip_address = ? AND 
        ruangan_otmil_id = ? AND 
        ruangan_lemasmil_id = ? AND 
        merk = ? AND 
        model = ? AND 
        status_kamera = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nama_kamera, PDO::PARAM_STR);
    $stmt->bindValue(2, $url_rtsp, PDO::PARAM_STR);
    $stmt->bindValue(3, $ip_address, PDO::PARAM_STR);
    $stmt->bindValue(4, $ruangan_otmil_id, PDO::PARAM_STR);
    $stmt->bindValue(5, $ruangan_lemasmil_id, PDO::PARAM_STR);
    $stmt->bindValue(6, $merk, PDO::PARAM_STR);
    $stmt->bindValue(7, $model, PDO::PARAM_STR);
    $stmt->bindValue(8, $status_kamera, PDO::PARAM_STR);

    
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $query3 = "INSERT INTO kamera (
            kamera_id,
            nama_kamera,
            url_rtsp,
            ip_address,
            ruangan_otmil_id,
            ruangan_lemasmil_id,
            merk,
            model,
            status_kamera
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  
        $stmt3 = $conn->prepare($query3);   
        $stmt3->bindValue(1, $kamera_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $nama_kamera, PDO::PARAM_STR);
        $stmt3->bindValue(3, $url_rtsp, PDO::PARAM_STR);
        $stmt3->bindValue(4, $ip_address, PDO::PARAM_STR);
        $stmt3->bindValue(5, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(6, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(7, $merk, PDO::PARAM_STR);
        $stmt3->bindValue(8, $model, PDO::PARAM_STR);
        $stmt3->bindValue(9, $status_kamera, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Data berhasil disimpan",
            "records" => [
                "kamera_id" => $kamera_id,
                "nama_kamera" => $nama_kamera,
                "url_rtsp" => $url_rtsp,
                "ip_address" => $ip_address,
                "ruangan_otmil_id" => $ruangan_otmil_id,
                "ruangan_lemasmil_id" => $ruangan_lemasmil_id,
                "merk" => $merk,
                "model" => $model,
                "status_kamera" => $status_kamera
            ]
        ];
    }
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>
