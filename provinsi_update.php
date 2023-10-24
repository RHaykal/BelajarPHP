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
    // header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$provinsi_id = (empty($param_POST->provinsi_id)) ? '' : trim($param_POST->provinsi_id);
$nama_provinsi = (empty($param_POST->nama_provinsi)) ? '' : trim($param_POST->nama_provinsi);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM provinsi WHERE provinsi_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$provinsi_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $query1 = "UPDATE provinsi SET provinsi_id = ?, nama_provinsi = ? WHERE provinsi_id = ?";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $provinsi_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_provinsi, PDO::PARAM_STR);
        $stmt1->bindParam(3, $provinsi_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data updated successfully";
        $result['status'] = 'OK';
        $result['records'] = [['provinsi_id' => $provinsi_id, 'nama_provinsi' => $nama_provinsi]];
    } else {
        $result['message'] = "Data not found";
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>