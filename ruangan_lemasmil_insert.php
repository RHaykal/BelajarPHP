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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$ruangan_lemasmil_id = generateUUID();
$nama_ruangan_lemasmil = (empty($param_POST->nama_ruangan_lemasmil)) ? '' : trim($param_POST->nama_ruangan_lemasmil);
$jenis_ruangan_lemasmil = (empty($param_POST->jenis_ruangan_lemasmil)) ? '' : trim($param_POST->jenis_ruangan_lemasmil);
$lokasi_lemasmil_id = (empty($param_POST->lokasi_lemasmil_id)) ? '' : trim($param_POST->lokasi_lemasmil_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM ruangan_lemasmil R
        WHERE 
        R.nama_ruangan_lemasmil = ? AND 
        R.jenis_ruangan_lemasmil = ? AND
        R.lokasi_lemasmil_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([ $nama_ruangan_lemasmil, $jenis_ruangan_lemasmil, $lokasi_lemasmil_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO ruangan_lemasmil (ruangan_lemasmil_id, nama_ruangan_lemasmil, jenis_ruangan_lemasmil, lokasi_lemasmil_id) VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_ruangan_lemasmil, PDO::PARAM_STR);
        $stmt1->bindParam(3, $jenis_ruangan_lemasmil, PDO::PARAM_STR);
        $stmt1->bindParam(4, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'ruangan_lemasmil_id' => $ruangan_lemasmil_id,
            'nama_ruangan_lemasmil' => $nama_ruangan_lemasmil,
            'jenis_ruangan_lemasmil' => $jenis_ruangan_lemasmil,
            'lokasi_lemasmil_id' => $lokasi_lemasmil_id
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
