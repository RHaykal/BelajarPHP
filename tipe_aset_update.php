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

$tipe_aset_id = (empty($param_POST->tipe_aset_id)) ? '' : trim($param_POST->tipe_aset_id);
$nama_tipe = (empty($param_POST->nama_tipe)) ? '' : trim($param_POST->nama_tipe);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM tipe_aset WHERE tipe_aset_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$tipe_aset_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $query1 = "UPDATE tipe_aset SET tipe_aset_id = ?, nama_tipe = ? WHERE tipe_aset_id = ?";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $tipe_aset_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_tipe, PDO::PARAM_STR);
        $stmt1->bindParam(3, $tipe_aset_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data updated successfully";
        $result['status'] = 'OK';
        $result['records'] = [['tipe_aset_id' => $tipe_aset_id, 'nama_tipe' => $nama_tipe]];
    } else {
        $result['message'] = "Data not found";
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>