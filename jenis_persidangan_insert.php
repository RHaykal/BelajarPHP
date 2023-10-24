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

$jenis_persidangan_id = generateUUID();
$nama_jenis_persidangan = (empty($param_POST->nama_jenis_persidangan)) ? '' : trim($param_POST->nama_jenis_persidangan);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM jenis_persidangan WHERE jenis_persidangan_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$jenis_persidangan_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT into jenis_persidangan 
                    (
                        jenis_persidangan_id,
                        nama_jenis_persidangan
                    )
                    VALUES(?, ?)
        ";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $jenis_persidangan_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_jenis_persidangan, PDO::PARAM_STR);

        $stmt1->execute();

        $result['message'] = "Data successfully added";
        $result['status'] = "Ok";
        $result['records'] = [
            'jenis_persidangan_id' => $jenis_persidangan_id,
            'nama_jenis_persidangan' => $nama_jenis_persidangan
        ];
    }
} catch(PDOException $e) {
    $result['message'] = $e->getMessage();
}
echo json_encode($result);
?>