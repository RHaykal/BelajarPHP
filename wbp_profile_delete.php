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
    $param_POST = $_POST;
}

$wbp_profile_id = isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');


    if (empty($wbp_profile_id)) {
        throw new Exception("WBP profile ID is missing in the update request.");
    }

    // Check if the WBP profile exists
    $query = "SELECT * FROM wbp_profile WHERE wbp_profile_id = ? AND is_deleted = 0 ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$wbp_profile_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        throw new Exception("WBP profile with ID $wbp_profile_id not found.");
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

            // Update the WBP profile with new data
            $query3 = "UPDATE wbp_profile SET
        is_deleted = 1 ,
        updated_at = ?
        WHERE wbp_profile_id = ?";

            $stmt3 = $conn->prepare($query3);


            $stmt3->execute([$created_at, $wbp_profile_id]);

            $result = [
                "status" => "OK",
                "message" => "WBP profile with ID $wbp_profile_id has been deleted.",
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

