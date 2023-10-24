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

$lokasi_otmil_id = (empty($param_POST->lokasi_otmil_id)) ? '' : trim($param_POST->lokasi_otmil_id);
$nama_lokasi_otmil = (empty($param_POST->nama_lokasi_otmil)) ? '' : trim($param_POST->nama_lokasi_otmil);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if lokasi_otmil_id is provided and exists in the database
    $query = "SELECT * FROM lokasi_otmil WHERE lokasi_otmil_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$lokasi_otmil_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        // Update the record
        $query1 = "UPDATE lokasi_otmil SET nama_lokasi_otmil = ? WHERE lokasi_otmil_id = ?";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $nama_lokasi_otmil, PDO::PARAM_STR);
        $stmt1->bindParam(2, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data updated successfully";
        $result['status'] = 'OK';
        $result['records'] = [['lokasi_otmil_id' => $lokasi_otmil_id, 'nama_lokasi_otmil' => $nama_lokasi_otmil]];
    } else {
        $result['message'] = "Data not found";
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
