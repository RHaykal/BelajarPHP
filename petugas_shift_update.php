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

$petugas_shift_id = trim(isset($param_POST->petugas_shift_id) ? $param_POST->petugas_shift_id : "");
$shift_id = trim(isset($param_POST->shift_id) ? $param_POST->shift_id : "");
$petugas_id = trim(isset($param_POST->petugas_id) ? $param_POST->petugas_id : "");
$schedule_id = trim(isset($param_POST->schedule_id) ? $param_POST->schedule_id : "");
$status_kehadiran = trim(isset($param_POST->status_kehadiran) ? $param_POST->status_kehadiran : 0);
$jam_kehadiran = (empty($param_POST->jam_kehadiran)) ? null : trim($param_POST->jam_kehadiran);
$status_izin = trim(isset($param_POST->status_izin) ? $param_POST->status_izin : "");
$penugasan_id = trim(isset($param_POST->penugasan_id) ? $param_POST->penugasan_id : "");
$ruangan_otmil_id = trim(isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : "");
$ruangan_lemasmil_id = trim(isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : "");
$status_pengganti = trim(isset($param_POST->status_pengganti) ? $param_POST->status_pengganti : 0);
$lokasi_otmil_id = trim(isset($param_POST->lokasi_otmil_id) ? $param_POST->lokasi_otmil_id : "");
$lokasi_lemasmil_id = trim(isset($param_POST->lokasi_lemasmil_id) ? $param_POST->lokasi_lemasmil_id : "");
$updated_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM petugas_shift WHERE petugas_shift_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $petugas_shift_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Petugas Shift not found");
    } else {
        $query3 = "UPDATE petugas_shift SET
            shift_id = ?,
            petugas_id = ?,
            schedule_id = ?,
            status_kehadiran = ?,
            jam_kehadiran = ?,
            status_izin = ?,
            penugasan_id = ?,
            ruangan_otmil_id = ?,
            ruangan_lemasmil_id = ?,
            status_pengganti = ?,
            lokasi_otmil_id = ?,
            lokasi_lemasmil_id = ?,
            updated_at = ?
            WHERE petugas_shift_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $shift_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $petugas_id, PDO::PARAM_STR);
        $stmt3->bindValue(3, $schedule_id, PDO::PARAM_STR);
        $stmt3->bindValue(4, $status_kehadiran, PDO::PARAM_STR);
        $stmt3->bindValue(5, $jam_kehadiran, PDO::PARAM_STR);
        $stmt3->bindValue(6, $status_izin, PDO::PARAM_STR);
        $stmt3->bindValue(7, $penugasan_id, PDO::PARAM_STR);
        $stmt3->bindValue(8, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(9, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(10, $status_pengganti, PDO::PARAM_STR);
        $stmt3->bindValue(11, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(12, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(13, $updated_at, PDO::PARAM_STR);
        $stmt3->bindValue(14, $petugas_shift_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    'petugas_shift_id' => $petugas_shift_id,
                    'shift_id' => $shift_id,
                    'petugas_id' => $petugas_id,
                    'schedule_id' => $schedule_id,
                    'status_kehadiran' => $status_kehadiran,
                    'jam_kehadiran' => $jam_kehadiran,
                    'status_izin' => $status_izin,
                    'penugasan_id' => $penugasan_id,
                    'ruangan_otmil_id' => $ruangan_otmil_id,
                    'ruangan_lemasmil_id' => $ruangan_lemasmil_id,
                    'status_pengganti' => $status_pengganti,
                    'lokasi_otmil_id' => $lokasi_otmil_id,
                    'lokasi_lemasmil_id' => $lokasi_lemasmil_id,
                    "updated_at" => $updated_at
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
