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

$penilaian_kegiatan_wbp_id = generateUUID();
$wbp_profile_id = (empty($param_POST->wbp_profile_id)) ? '' : trim($param_POST->wbp_profile_id);
$kegiatan_id = (empty($param_POST->kegiatan_id)) ? '' : trim($param_POST->kegiatan_id);
$absensi = (empty($param_POST->absensi)) ? '' : trim($param_POST->absensi);
$durasi = (empty($param_POST->durasi)) ? '' : trim($param_POST->durasi);
$nilai = (empty($param_POST->nilai)) ? '' : trim($param_POST->nilai);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM penilaian_kegiatan_wbp 
            WHERE   wbp_profile_id = ? AND
                    kegiatan_id = ? AND
                    absensi = ? AND
                    durasi = ? AND
                    nilai = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $wbp_profile_id, PDO::PARAM_STR);
    $stmt->bindParam(2, $kegiatan_id, PDO::PARAM_STR);
    $stmt->bindParam(3, $absensi, PDO::PARAM_STR);
    $stmt->bindParam(4, $durasi, PDO::PARAM_STR);
    $stmt->bindParam(5, $nilai, PDO::PARAM_STR);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
    $query1 = "INSERT INTO penilaian_kegiatan_wbp (
                penilaian_kegiatan_wbp_id,
                wbp_profile_id,
                kegiatan_id,
                absensi,
                durasi,
                nilai
                ) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt1 = $conn->prepare($query1);
    $stmt1->bindParam(1, $penilaian_kegiatan_wbp_id, PDO::PARAM_STR);    
    $stmt1->bindParam(2, $wbp_profile_id, PDO::PARAM_STR);    
    $stmt1->bindParam(3, $kegiatan_id, PDO::PARAM_STR);    
    $stmt1->bindParam(4, $absensi, PDO::PARAM_STR);    
    $stmt1->bindParam(5, $durasi, PDO::PARAM_STR);    
    $stmt1->bindParam(6, $nilai, PDO::PARAM_STR);
    $stmt1->execute();    
    
    $result['message'] = "Insert data successfully";
    $result['status'] = "OK";
    $result['records'] = [[
            'penilaian_kegiatan_wbp_id' => $penilaian_kegiatan_wbp_id, 
            'wbp_profile_id' => $wbp_profile_id, 
            'kegiatan_id' => $kegiatan_id,
            'absensi' => $absensi,
            'durasi' => $durasi,
            'nilai' => $nilai
            ]];
        }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>