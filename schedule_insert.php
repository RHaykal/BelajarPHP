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

$schedule_id = generateUUID();
$tanggal = (empty($param_POST->tanggal)) ? 0 : trim($param_POST->tanggal);
$bulan = (empty($param_POST->bulan)) ? 0 : trim($param_POST->bulan);
$tahun = (empty($param_POST->tahun)) ? 0 : trim($param_POST->tahun);
$shift_id = (empty($param_POST->shift_id)) ? '' : trim($param_POST->shift_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM schedule S
        WHERE 
        S.tanggal = ? AND 
        S.bulan = ? AND
        S.tahun = ? AND 
        S.shift_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $tanggal, 
        $bulan, 
        $tahun, 
        $shift_id
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO schedule (
            schedule_id, 
            tanggal, 
            bulan, 
            tahun, 
            shift_id
        ) 
        VALUES (?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $schedule_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $tanggal, PDO::PARAM_STR);
        $stmt1->bindParam(3, $bulan, PDO::PARAM_STR);
        $stmt1->bindParam(4, $tahun, PDO::PARAM_STR);
        $stmt1->bindParam(5, $shift_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'schedule_id' => $schedule_id,
            'tanggal' => $tanggal,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'shift_id' => $shift_id
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
