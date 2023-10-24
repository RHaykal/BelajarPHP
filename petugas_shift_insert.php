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

$petugas_shift_id = generateUUID();
$shift_id = (empty($param_POST->shift_id)) ? '' : trim($param_POST->shift_id);
$petugas_id = (empty($param_POST->petugas_id)) ? '' : trim($param_POST->petugas_id);
$schedule_id = (empty($param_POST->schedule_id)) ? '' : trim($param_POST->schedule_id);
$status_kehadiran = (empty($param_POST->status_kehadiran)) ? 0 : trim($param_POST->status_kehadiran);
$jam_kehadiran = (empty($param_POST->jam_kehadiran)) ? null : trim($param_POST->jam_kehadiran);
$status_izin = (empty($param_POST->status_izin)) ? '' : trim($param_POST->status_izin);
$penugasan_id = (empty($param_POST->penugasan_id)) ? '' : trim($param_POST->penugasan_id);
$ruangan_otmil_id = (empty($param_POST->ruangan_otmil_id)) ? '' : trim($param_POST->ruangan_otmil_id);
$ruangan_lemasmil_id = (empty($param_POST->ruangan_lemasmil_id)) ? '' : trim($param_POST->ruangan_lemasmil_id);
$status_pengganti = (empty($param_POST->status_pengganti)) ? 0 : trim($param_POST->status_pengganti);
$lokasi_otmil_id  = (empty($param_POST->lokasi_otmil_id)) ? '' : trim($param_POST->lokasi_otmil_id);
$lokasi_lemasmil_id = (empty($param_POST->lokasi_lemasmil_id)) ? '' : trim($param_POST->lokasi_lemasmil_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM petugas_shift PS
        WHERE 
        PS.shift_id = ? AND 
        PS.petugas_id = ? AND
        PS.schedule_id = ? AND 
        PS.status_kehadiran = ? AND 
        PS.jam_kehadiran = ? AND 
        PS.status_izin = ? AND 
        PS.penugasan_id = ? AND 
        PS.ruangan_otmil_id = ? AND 
        PS.ruangan_lemasmil_id = ? AND 
        PS.status_pengganti = ? AND
        PS.lokasi_otmil_id = ? AND
        PS.lokasi_lemasmil_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $shift_id, 
        $petugas_id, 
        $schedule_id, 
        $status_kehadiran, 
        $jam_kehadiran, 
        $status_izin, 
        $penugasan_id,
        $ruangan_otmil_id,
        $ruangan_lemasmil_id,
        $status_pengganti,
        $lokasi_otmil_id,
        $lokasi_lemasmil_id
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO petugas_shift (
            petugas_shift_id, 
            shift_id, 
            petugas_id, 
            schedule_id, 
            status_kehadiran, 
            jam_kehadiran, 
            status_izin, 
            penugasan_id,
            ruangan_otmil_id,
            ruangan_lemasmil_id,
            status_pengganti,
            lokasi_otmil_id,
            lokasi_lemasmil_id
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $petugas_shift_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $shift_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $petugas_id, PDO::PARAM_STR);
        $stmt1->bindParam(4, $schedule_id, PDO::PARAM_STR);
        $stmt1->bindParam(5, $status_kehadiran, PDO::PARAM_STR);
        $stmt1->bindParam(6, $jam_kehadiran, PDO::PARAM_STR);
        $stmt1->bindParam(7, $status_izin, PDO::PARAM_STR);
        $stmt1->bindParam(8, $penugasan_id, PDO::PARAM_STR);
        $stmt1->bindParam(9, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(10, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(11, $status_pengganti, PDO::PARAM_STR);
        $stmt1->bindParam(12, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(13, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
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
            'lokasi_lemasmil_id' => $lokasi_lemasmil_id
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
