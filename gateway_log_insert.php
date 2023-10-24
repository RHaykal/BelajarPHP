<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');
// require_once('function.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$gateway_log_id = generateUUID();
$wbp_profile_id = isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : "";
$image = trim(isset($param_POST->image) ? $param_POST->image : "");
$gateway_id = trim(isset($param_POST->gateway_id) ? $param_POST->gateway_id : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM gateway_log WHERE 
        wbp_profile_id = ? AND 
        image = ? AND 
        gateway_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $wbp_profile_id, PDO::PARAM_STR);
    $stmt->bindValue(2, $image, PDO::PARAM_STR);
    $stmt->bindValue(3, $gateway_id, PDO::PARAM_STR);

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $filename = md5($hashkey.time());
        $photoLocation = '/siram_api/images_user_data'.'/'.$filename.'.jpg';
        $photoResult = base64_to_jpeg($image, $photoLocation);   

        $query3 = "INSERT INTO gateway_log (
            gateway_log_id,
            wbp_profile_id,
            image,
            gateway_id
        ) VALUES (
            ?, ?, ?, ?)";
  
        $stmt3 = $conn->prepare($query3);   
        $stmt3->bindValue(1, $gateway_log_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $wbp_profile_id, PDO::PARAM_STR);
        $stmt3->bindValue(3, $photoResult, PDO::PARAM_STR);
        $stmt3->bindValue(4, $gateway_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Data berhasil disimpan",
            "records" => [
                "gateway_log_id" => $gateway_log_id,
                "wbp_profile_id" => $wbp_profile_id,
                "image" => $photoResult,
                "gateway_id" => $gateway_id
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
