<?php 
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = (object) [];
}

$lokasi_lemasmil_id = generateUUID();
$nama_lokasi_lemasmil = (empty($param_POST->nama_lokasi_lemasmil)) ? '' : trim($param_POST->nama_lokasi_lemasmil);
$latitude = (empty($param_POST->latitude)) ? '' : trim($param_POST->latitude);
$longitude = (empty($param_POST->longitude)) ? '' : trim($param_POST->longitude);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');
 
    $query = "SELECT * FROM lokasi_lemasmil WHERE  nama_lokasi_lemasmil = ? AND latitude = ? AND longitude = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([ $nama_lokasi_lemasmil, $latitude, $longitude]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO lokasi_lemasmil(lokasi_lemasmil_id, nama_lokasi_lemasmil, latitude,longitude) VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_lokasi_lemasmil, PDO::PARAM_STR);
        $stmt1->bindValue(3, $latitude, PDO::PARAM_STR);
        $stmt1->bindValue(4, $longitude, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['lokasi_lemasmil_id' => $lokasi_lemasmil_id, 'nama_lokasi_lemasmil' => $nama_lokasi_lemasmil]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>