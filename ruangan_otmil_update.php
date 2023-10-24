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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$ruangan_otmil_id = isset($param_POST->ruangan_otmil_id) ? trim($param_POST->ruangan_otmil_id) : "";
$nama_ruangan_otmil = isset($param_POST->nama_ruangan_otmil) ? trim($param_POST->nama_ruangan_otmil) : "";
$jenis_ruangan_otmil = isset($param_POST->jenis_ruangan_otmil) ? trim($param_POST->jenis_ruangan_otmil) : "";
$lokasi_otmil_id = isset($param_POST->lokasi_otmil_id) ? trim($param_POST->lokasi_otmil_id) : "";
$zona_id = isset($param_POST->zona_id) ? trim($param_POST->zona_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM ruangan_otmil WHERE ruangan_otmil_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $ruangan_otmil_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data penugasan not found");
    } else {
        $query3 = "UPDATE ruangan_otmil SET
            nama_ruangan_otmil = ?,
            jenis_ruangan_otmil = ?,
            lokasi_otmil_id = ?,
            zona_id = ?
            WHERE ruangan_otmil_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $nama_ruangan_otmil, PDO::PARAM_STR);
        $stmt3->bindValue(2, $jenis_ruangan_otmil, PDO::PARAM_STR);
        $stmt3->bindValue(3, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(4, $zona_id, PDO::PARAM_STR);
        $stmt3->bindValue(5, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "ruangan_otmil_id" => $ruangan_otmil_id,
                    "nama_ruangan_otmil" => $nama_ruangan_otmil,
                    "jenis_ruangan_otmil" => $jenis_ruangan_otmil,
                    "lokasi_otmil_id" => $lokasi_otmil_id,
                    "zona_id" => $zona_id
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
