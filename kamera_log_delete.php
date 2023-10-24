<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$kamera_log_id = (empty($_POST['kamera_log_id'])) ? trim($param_POST->kamera_log_id) : trim($_POST['kamera_log_id']);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "UPDATE kamera_log SET is_deleted = 1 WHERE kamera_log_id = :kamera_log_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':kamera_log_id', $kamera_log_id);
    $stmt->execute();

    $result = '{ "status":"OK", "message":"Status berhasil diubah menjadi is_deleted=1", "records": [] }';

} catch (PDOException $e) {
    $result = '{ "status":"NO", "message":"Gagal mengubah status", "records":[] }';
}

echo $result;
