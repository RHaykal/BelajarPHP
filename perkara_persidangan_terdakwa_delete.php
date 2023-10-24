<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Headers: Token");
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$perkara_persidangan_terdakwa_id = (empty($_POST['perkara_persidangan_terdakwa_id'])) ? trim($param_POST->perkara_persidangan_terdakwa_id) : trim($_POST['perkara_persidangan_terdakwa_id']);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "UPDATE perkara_persidangan_terdakwa SET is_deleted = 1 WHERE perkara_persidangan_terdakwa_id = :perkara_persidangan_terdakwa_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':perkara_persidangan_terdakwa_id', $perkara_persidangan_terdakwa_id);
    $stmt->execute();

    $result = '{ "status":"OK", "message":"Status berhasil diubah menjadi is_deleted=1", "records": [] }';

} catch (PDOException $e) {
    $result = '{ "status":"NO", "message":"Gagal mengubah status", "records":[] }';
}

echo $result;
