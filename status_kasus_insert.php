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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$status_kasus_id = generateUUID();
$nama_status_kasus = (empty($param_POST->nama_status_kasus)) ? '' : trim($param_POST->nama_status_kasus);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');
 
    $query = "SELECT * FROM status_kasus WHERE nama_status_kasus = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([ $nama_status_kasus]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO status_kasus(status_kasus_id, nama_status_kasus) VALUES (?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $status_kasus_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_status_kasus, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['status_kasus_id' => $status_kasus_id, 'nama_status_kasus' => $nama_status_kasus]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>