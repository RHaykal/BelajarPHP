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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$pengadilan_militer_id = generateUUID();
$nama_pengadilan_militer = (empty($param_POST->nama_pengadilan_militer)) ? '' : trim($param_POST->nama_pengadilan_militer);
$provinsi_id = (empty($param_POST->provinsi_id)) ? '' : trim($param_POST->provinsi_id);
$kota_id = (empty($param_POST->kota_id)) ? '' : trim($param_POST->kota_id);
$latitude = (empty($param_POST->latitude)) ? '' : trim($param_POST->latitude);
$longitude = (empty($param_POST->longitude)) ? '' : trim($param_POST->longitude);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM pengadilan_militer PM
        WHERE 
        PM.nama_pengadilan_militer = ? AND 
        PM.provinsi_id = ? AND
        PM.kota_id = ? AND 
        PM.latitude = ? AND 
        PM.longitude = ?
        ";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $nama_pengadilan_militer, 
        $provinsi_id, 
        $kota_id, 
        $latitude, 
        $longitude
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";

    } else {

        $query1 = "INSERT INTO pengadilan_militer (
            pengadilan_militer_id,
            nama_pengadilan_militer, 
            provinsi_id, 
            kota_id, 
            latitude, 
            longitude
        ) 
        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $pengadilan_militer_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_pengadilan_militer, PDO::PARAM_STR);
        $stmt1->bindParam(3, $provinsi_id, PDO::PARAM_STR);
        $stmt1->bindParam(4, $kota_id, PDO::PARAM_STR);
        $stmt1->bindParam(5, $latitude, PDO::PARAM_STR);
        $stmt1->bindParam(6, $longitude, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'pengadilan_militer_id' => $pengadilan_militer_id,
            'nama_pengadilan_militer' => $nama_pengadilan_militer,
            'provinsi_id' => $provinsi_id,
            'kota_id' => $kota_id,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
