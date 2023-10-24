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

$petugas_id = trim(isset($param_POST->petugas_id) ? $param_POST->petugas_id : "");


$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM petugas WHERE petugas_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $petugas_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Petugas not found");
    } else {

        $foto_wajah_fr = $res['foto_wajah_fr'];

        $imageIDArray = [$foto_wajah_fr];

        $data_array_delete = array(
            "groupID" => "1",
            // ID of face group 
            "dbID" => "testcideng",
            // ID of face database
            "imageIDs" => $imageIDArray
        );

        $json_data_delete = json_encode($data_array_delete);

        $make_call_delete = callAPI('DELETE', 'https://faceengine.deepcam.cn/pipeline/api/face/delete', $json_data_delete);
        $responseDeleteFR = $make_call_delete;

        // print_r($responseDeleteFR);
        $formattedResponseDelete = json_decode($responseDeleteFR, true);
        if ($formattedResponseDelete['code'] != '1000') {
            throw new Exception("Failed to delete FR");
        } else {
            $query3 = "UPDATE petugas SET
            is_deleted = 1,
            updated_at = ?
            WHERE petugas_id = ?";

            $stmt3 = $conn->prepare($query3);
            $stmt3->bindValue(1, $updated_at, PDO::PARAM_STR);
            $stmt3->bindValue(2, $petugas_id, PDO::PARAM_STR);
            $stmt3->execute();

            $result = [
                "status" => "OK",
                "message" => "Successfully deleted petugas data with petugas_id: $petugas_id",
                "records" => []
            ];

        }
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

