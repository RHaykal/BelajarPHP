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
} 
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$bap_id = trim(isset($param_POST->bap_id) ? $param_POST->bap_id : "");
$penyidikan_id = trim(isset($param_POST->penyidikan_id) ? $param_POST->penyidikan_id : "");
$dokumen_bap_id = trim(isset($param_POST->dokumen_bap_id) ? $param_POST->dokumen_bap_id : "");
$updated_at = date("Y:m:d H:i:s");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM bap WHERE bap_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $bap_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data not found");
    } else {
        $query3 = "UPDATE bap set
            penyidikan_id = ?,
            dokumen_bap_id = ?,
            updated_at = ?
            WHERE bap_id = ?
        ";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $penyidikan_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $dokumen_bap_id, PDO::PARAM_STR);
        $stmt3->bindValue(3, $updated_at, PDO::PARAM_STR);
        $stmt3->bindValue(4, $bap_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Data berhasil diupdate",
            "records" => [
                [
                "bap_id" => $bap_id,
                "penyidikan_id" => $penyidikan_id,
                "dokumen_bap_id" => $dokumen_bap_id,
                "updated_at" => $updated_at
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