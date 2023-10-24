<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, PUT, GET, OPTIONS"); // Add PUT method
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$penilaian_kegiatan_wbp_id = (empty($param_POST->penilaian_kegiatan_wbp_id)) ? '' : trim($param_POST->penilaian_kegiatan_wbp_id); // Add penilaian_kegiatan_wbp_id



$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM penilaian_kegiatan_wbp WHERE penilaian_kegiatan_wbp_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$penilaian_kegiatan_wbp_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $result['message'] = "Data not found";
    } else {
        // Update the record
        $query1 = "UPDATE penilaian_kegiatan_wbp SET
            is_deleted = 1
            WHERE penilaian_kegiatan_wbp_id = ?";

        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $penilaian_kegiatan_wbp_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Deleted data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'penilaian_kegiatan_wbp_id' => $penilaian_kegiatan_wbp_id
            ]
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>