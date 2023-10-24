<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    // header("Access-Control-Allow-Headers: Token");
    // header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_PUT = json_decode(file_get_contents("php://input"));
} else {
    $param_PUT = $_POST;
}

$schedule_id = trim(isset($param_PUT->schedule_id) ? $param_PUT->schedule_id : "");
$tanggal = trim(isset($param_PUT->tanggal) ? $param_PUT->tanggal : "");
$bulan = trim(isset($param_PUT->bulan) ? $param_PUT->bulan : "");
$tahun = trim(isset($param_PUT->tahun) ? $param_PUT->tahun : "");
$shift_id = trim(isset($param_PUT->shift_id) ? $param_PUT->shift_id : "");
$updated_at = date('Y-m-d H:i:s');

$peserta = isset($param_PUT->peserta) ? $param_PUT->peserta : [];

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record with the given kegiatan_id exists
    $check_query = "SELECT * FROM schedule WHERE schedule_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$schedule_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Update the record
        $update_query = "UPDATE schedule SET
            tanggal = ?,
            bulan = ?,
            tahun = ?,
            shift_id = ?,
            updated_at = ?
            WHERE schedule_id = ?";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(1, $tanggal, PDO::PARAM_STR);
        $update_stmt->bindParam(2, $bulan, PDO::PARAM_STR);
        $update_stmt->bindParam(3, $tahun, PDO::PARAM_STR);
        $update_stmt->bindParam(4, $shift_id, PDO::PARAM_STR);
        $update_stmt->bindParam(5, $updated_at, PDO::PARAM_STR);
        $update_stmt->bindParam(6, $schedule_id, PDO::PARAM_STR);
        $update_stmt->execute();

        $result['message'] = "Update data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'schedule_id' => $schedule_id,
                'tanggal' => $tanggal,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'shift_id' => $shift_id,
                'updated_at' => $updated_at
            ]
        ];
    }
} catch (Exception $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>
