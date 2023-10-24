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

$shift_id = generateUUID();
$nama_shift = (empty($param_POST->nama_shift)) ? '' : trim($param_POST->nama_shift);
$waktu_mulai = (empty($param_POST->waktu_mulai)) ? '' : trim($param_POST->waktu_mulai);
$waktu_selesai = (empty($param_POST->waktu_selesai)) ? '' : trim($param_POST->waktu_selesai);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM shift S
        WHERE 
        S.nama_shift = ? AND 
        S.waktu_mulai = ? AND
        S.waktu_selesai = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $nama_shift, 
        $waktu_mulai, 
        $waktu_selesai
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO shift (
            shift_id, 
            nama_shift, 
            waktu_mulai, 
            waktu_selesai
        ) 
        VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $shift_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_shift, PDO::PARAM_STR);
        $stmt1->bindParam(3, $waktu_mulai, PDO::PARAM_STR);
        $stmt1->bindParam(4, $waktu_selesai, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'shift_id' => $shift_id,
            'nama_shift' => $nama_shift,
            'waktu_mulai' => $waktu_mulai,
            'waktu_selesai' => $waktu_selesai
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
