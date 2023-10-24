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
    $param_POST = json_decode(file_get_contents("php://input"));
} 
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$shift_id = trim(isset($param_POST->shift_id) ? $param_POST->shift_id : "");
$nama_shift = (empty($param_POST->nama_shift)) ? '' : trim($param_POST->nama_shift);
$waktu_mulai = (empty($param_POST->waktu_mulai)) ? '' : trim($param_POST->waktu_mulai);
$waktu_selesai = (empty($param_POST->waktu_selesai)) ? '' : trim($param_POST->waktu_selesai);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM shift WHERE shift_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $shift_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Shift not found");
    } else {
        $query3 = "UPDATE shift SET
            nama_shift = ?,
            waktu_mulai = ?,
            waktu_selesai = ?
            WHERE shift_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $nama_shift, PDO::PARAM_STR);
        $stmt3->bindValue(2, $waktu_mulai, PDO::PARAM_STR);
        $stmt3->bindValue(3, $waktu_selesai, PDO::PARAM_STR);
        $stmt3->bindValue(4, $shift_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    'shift_id' => $shift_id,
                    'nama_shift' => $nama_shift,
                    'waktu_mulai' => $waktu_mulai,
                    'waktu_selesai' => $waktu_selesai
                ]
            ]
        ];
        
    }
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>
