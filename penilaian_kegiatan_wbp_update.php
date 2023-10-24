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

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$penilaian_kegiatan_wbp_id = (empty($param_POST->penilaian_kegiatan_wbp_id)) ? '' : trim($param_POST->penilaian_kegiatan_wbp_id);
$wbp_profile_id = (empty($param_POST->wbp_profile_id)) ? '' : trim($param_POST->wbp_profile_id);
$kegiatan_id = (empty($param_POST->kegiatan_id)) ? '' : trim($param_POST->kegiatan_id);
$absensi = (empty($param_POST->absensi)) ? '' : trim($param_POST->absensi);
$durasi = (empty($param_POST->durasi)) ? '' : trim($param_POST->durasi);
$nilai = (empty($param_POST->nilai)) ? '' : trim($param_POST->nilai);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM penilaian_kegiatan_wbp WHERE penilaian_kegiatan_wbp_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$penilaian_kegiatan_wbp_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $result['message'] = "Data not found";
    } else {
        $query1 = "UPDATE penilaian_kegiatan_wbp SET 
        wbp_profile_id = ?,
        kegiatan_id = ?,
        absensi = ?,
        durasi = ?,
        nilai = ?
        WHERE penilaian_kegiatan_wbp_id = ?";

        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $wbp_profile_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $kegiatan_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $absensi, PDO::PARAM_STR);
        $stmt1->bindParam(4, $durasi, PDO::PARAM_STR);
        $stmt1->bindParam(5, $nilai, PDO::PARAM_STR);
        $stmt1->bindParam(6, $penilaian_kegiatan_wbp_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Update data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'wbp_profile_id' => $wbp_profile_id,
                'kegiatan_id' => $kegiatan_id,
                'absensi' => $absensi,
                'durasi' => $durasi,
                'nilai' => $nilai
            ]
        ];
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
