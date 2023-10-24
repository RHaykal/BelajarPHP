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
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}


if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$jaksa_penuntut_id = isset($param_POST->jaksa_penuntut_id) ? trim($param_POST->jaksa_penuntut_id) : "";
$nip = isset($param_POST->nip) ? trim($param_POST->nip) : "";
$nama_jaksa = isset($param_POST->nama_jaksa) ? trim($param_POST->nama_jaksa) : "";
$alamat = isset($param_POST->alamat) ? trim($param_POST->alamat) : "";


$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM jaksa_penuntut WHERE jaksa_penuntut_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $jaksa_penuntut_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data Jaksa Penuntut not found");
    } else {
        $query3 = "UPDATE jaksa_penuntut SET
            nip = ?,
            nama_jaksa = ?,
            alamat = ?
            WHERE jaksa_penuntut_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $nip, PDO::PARAM_STR);
        $stmt3->bindValue(2, $nama_jaksa, PDO::PARAM_STR);
        $stmt3->bindValue(3, $alamat, PDO::PARAM_STR);
        $stmt3->bindValue(4, $jaksa_penuntut_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "jaksa_penuntut_id" => $jaksa_penuntut_id,
                    "nip" => $nip,
                    "nama_jaksa" => $nama_jaksa,
                    "alamat" => $alamat
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
