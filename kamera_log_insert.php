<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');
require_once('function.php');

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

$kamera_log_id = generateUUID();
$wbp_profile_id = isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : "";
$image = trim(isset($param_POST->image) ? $param_POST->image : "");
$kamera_id = trim(isset($param_POST->kamera_id) ? $param_POST->kamera_id : "");
$foto_wajah_fr = trim(isset($param_POST->foto_wajah_fr) ? $param_POST->foto_wajah_fr : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // tokenAuth($conn, 'operator');

    $filename = md5($kamera_log_id.time());
    $photoLocation = '/siram_api/images_kamera_log_data'.'/' . $filename . '.jpg';
    $photoResult = base64_to_jpeg($image, $photoLocation);

    $query3 = "INSERT INTO kamera_log (
            kamera_log_id,
            wbp_profile_id,
            image,
            kamera_id,
            foto_wajah_fr
        ) VALUES (
            ?, ?, ?, ?, ?)";

    $stmt3 = $conn->prepare($query3);
    $stmt3->bindValue(1, $kamera_log_id, PDO::PARAM_STR);
    $stmt3->bindValue(2, $wbp_profile_id, PDO::PARAM_STR);
    $stmt3->bindValue(3, $photoResult, PDO::PARAM_STR);
    $stmt3->bindValue(4, $kamera_id, PDO::PARAM_STR);
    $stmt3->bindValue(5, $foto_wajah_fr, PDO::PARAM_STR);
    $stmt3->execute();

    $result = [
        "status" => "OK",
        "message" => "Data berhasil disimpan",
        "records" => [
            "kamera_log_id" => $kamera_log_id,
            "image" => $photoResult,
            "kamera_id" => $kamera_id
        ]
    ];

} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>

