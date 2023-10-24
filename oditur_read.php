<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    // header("Access-Control-Allow-Headers: Token");
    // header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $query = "SELECT * FROM oditur WHERE is_deleted = 0";
    $stmt = $conn->prepare($query); 
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = '';
    foreach ($res as $row) {
        if($record .= '') $record .= ',';
            $record .=  '{"oditur_id":"' . $row['oditur_id'] . '",';
            $record .=  '"nama_oditur":"' . $row['nama_oditur'] . '"}';
    }
    $result = '{"status":"OK", "message":"", "records":[' . $record . ']}';
    echo $result;
} catch (Exception $e) {
    $result = '{"status":"error", "message":"' . $e->getMessage() . '",
        "records":[]}';
        echo $result;
}
$stmt = null;
$conn = null;
?>