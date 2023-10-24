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

$grup_petugas_id = generateUUID();
$nama_grup_petugas = (empty($param_POST->nama_grup_petugas)) ? '' : trim($param_POST->nama_grup_petugas);
$ketua_grup = (empty($param_POST->ketua_grup)) ? '' : trim($param_POST->ketua_grup);


$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');
 
    $query = "SELECT * FROM grup_petugas WHERE nama_grup_petugas = ? AND ketua_grup = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nama_grup_petugas, $ketua_grup]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO grup_petugas(grup_petugas_id, nama_grup_petugas, ketua_grup) VALUES (?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $grup_petugas_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_grup_petugas, PDO::PARAM_STR);
        $stmt1->bindValue(3, $ketua_grup, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['grup_petugas_id' => $grup_petugas_id, 'nama_grup_petugas' => $nama_grup_petugas, 'ketua_grup' => $ketua_grup]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>