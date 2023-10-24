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

$status_kawin_id = generateUUID();
$nama_status_kawin = (empty($param_POST->nama_status_kawin)) ? '' : trim($param_POST->nama_status_kawin);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');
 
    $query = "SELECT * FROM status_kawin WHERE  nama_status_kawin = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([ $nama_status_kawin]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO status_kawin (status_kawin_id, nama_status_kawin) VALUES (?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $status_kawin_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_status_kawin, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['status_kawin_id' => $status_kawin_id, 'nama_status_kawin' => $nama_status_kawin]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>