<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, PUT, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}


$param_POST = isset($param_POST) ? $param_POST : (object) [];

$aktivitas_gelang_id = (empty($param_POST->aktivitas_gelang_id)) ? '' : trim($param_POST->aktivitas_gelang_id); 

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM aktivitas_gelang WHERE aktivitas_gelang_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$aktivitas_gelang_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $result['message'] = "Data not found";
    } else {
        // Update the record
        $query1 = "UPDATE aktivitas_gelang SET
            is_deleted = 1
            WHERE aktivitas_gelang_id = ?";

        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $aktivitas_gelang_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Deleted data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'aktivitas_gelang_id' => $aktivitas_gelang_id
            ]
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>