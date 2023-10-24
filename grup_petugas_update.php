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
} else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$grup_petugas_id = isset($param_POST->grup_petugas_id) ? trim($param_POST->grup_petugas_id) : "";
$nama_grup_petugas = isset($param_POST->nama_grup_petugas) ? trim($param_POST->nama_grup_petugas) : "";
$ketua_grup = isset($param_POST->ketua_grup) ? trim($param_POST->ketua_grup) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM grup_petugas WHERE grup_petugas_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $grup_petugas_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data grup_petugas not found");
    } else {
        $query3 = "UPDATE grup_petugas SET
            nama_grup_petugas = ?,
            ketua_grup = ?
            WHERE grup_petugas_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $nama_grup_petugas, PDO::PARAM_STR);
        $stmt3->bindValue(2, $ketua_grup, PDO::PARAM_STR);
        $stmt3->bindValue(3, $grup_petugas_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "grup_petugas_id" => $grup_petugas_id,
                    "nama_grup_petugas" => $nama_grup_petugas,
                    "ketua_grup" => $ketua_grup
                ]
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
