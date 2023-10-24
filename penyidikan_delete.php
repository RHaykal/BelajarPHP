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

$id = (empty($_POST['penyidikan_id'])) ? trim($param_POST->penyidikan_id) : trim($_POST['penyidikan_id']);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "UPDATE pivot_penyidikan_jaksa SET is_deleted = 1 WHERE penyidikan_id = :penyidikan_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':penyidikan_id', $id);
    $stmt->execute();

    $query2 = "UPDATE pivot_penyidikan_saksi SET is_deleted = 1 WHERE penyidikan_id = :penyidikan_id";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bindParam(':penyidikan_id', $id);
    $stmt2->execute();

    $query3 = "UPDATE histori_penyidikan SET is_deleted = 1 WHERE penyidikan_id = :penyidikan_id";
    $stmt3 = $conn->prepare($query3);
    $stmt3->bindParam(':penyidikan_id', $id);
    $stmt3->execute();


    $query4 = "UPDATE penyidikan SET is_deleted = 1 WHERE penyidikan_id = :penyidikan_id";
    $stmt4 = $conn->prepare($query4);
    $stmt4->bindParam(':penyidikan_id', $id);
    $stmt4->execute();

    $result = '{ "status":"OK", "message":"Status berhasil diubah menjadi is_deleted=1", "records": [] }';

} catch (PDOException $e) {
    $result = '{ "status":"NO", "message":"Gagal mengubah status", "records":[] }';
}

echo $result;
