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

$kategori_perkara_id = (empty($param_POST->kategori_perkara_id)) ? '' : trim($param_POST->kategori_perkara_id); // Add kategori_perkara_id



$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record with the given kategori_perkara_id exists
    $check_query = "SELECT * FROM kategori_perkara WHERE kategori_perkara_id = ? AND is_deleted = 0";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$kategori_perkara_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        $result['message'] = "Data not found";
    } else {
        // Update the record
        $update_query = "UPDATE kategori_perkara SET
            is_deleted = 1
            WHERE kategori_perkara_id = ?";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(1, $kategori_perkara_id, PDO::PARAM_STR);
        $update_stmt->execute();

        $result['message'] = "Deleted data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'kategori_perkara_id' => $kategori_perkara_id
            ]
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>