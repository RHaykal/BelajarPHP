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

// Check if the request content type is JSON and decode it
if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = (object) [];
}

$lokasi_otmil_id = generateUUID();
$nama_lokasi_otmil = (empty($param_POST->nama_lokasi_otmil)) ? '' : trim($param_POST->nama_lokasi_otmil);
$latitude = (empty($param_POST->latitude)) ? '' : trim($param_POST->latitude);
$longitude = (empty($param_POST->longitude)) ? '' : trim($param_POST->longitude);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the data already exists in the database
    $query = "SELECT * FROM lokasi_otmil WHERE nama_lokasi_otmil = ? AND latitude = ? AND longitude = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nama_lokasi_otmil, $latitude, $longitude]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        // Insert data into the database
        $query1 = "INSERT INTO lokasi_otmil(lokasi_otmil_id, nama_lokasi_otmil, latitude, longitude) VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_lokasi_otmil, PDO::PARAM_STR);
        $stmt1->bindValue(3, $latitude, PDO::PARAM_STR);
        $stmt1->bindValue(4, $longitude, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['lokasi_otmil_id' => $lokasi_otmil_id, 'nama_lokasi_otmil' => $nama_lokasi_otmil, 'latitude' => $latitude, 'longitude' => $longitude]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>
