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
}

$bidang_keahlian_id = generateUUID();
$nama_bidang_keahlian = (empty($param_POST->nama_bidang_keahlian)) ? '' : trim($param_POST->nama_bidang_keahlian);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    tokenAuth($conn, 'admin');
 
    $query = "SELECT * FROM bidang_keahlian WHERE  nama_bidang_keahlian = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([ $nama_bidang_keahlian]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO bidang_keahlian (bidang_keahlian_id, nama_bidang_keahlian) VALUES (?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $bidang_keahlian_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_bidang_keahlian, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['bidang_keahlian_id' => $bidang_keahlian_id, 'nama_bidang_keahlian' => $nama_bidang_keahlian]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>