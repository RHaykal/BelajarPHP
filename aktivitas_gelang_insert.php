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



$param_POST = (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) ? json_decode(file_get_contents("php://input")) : (object) [];

$aktivitas_gelang_id = generateUUID();
$gmac = isset($param_POST->gmac) ? trim($param_POST->gmac) : '';
$dmac = isset($param_POST->dmac) ? trim($param_POST->dmac) : '';
$baterai = isset($param_POST->baterai) ? trim($param_POST->baterai) : '';
$step = isset($param_POST->step) ? trim($param_POST->step) : '';
$heartrate = isset($param_POST->heartrate) ? trim($param_POST->heartrate) : '';
$temp = isset($param_POST->temp) ? trim($param_POST->temp) : '';
$spo = isset($param_POST->spo) ? trim($param_POST->spo) : '';
$systolic = isset($param_POST->systolic) ? trim($param_POST->systolic) : '';
$diastolic = isset($param_POST->diastolic) ? trim($param_POST->diastolic) : '';
$cutoff_flag = isset($param_POST->cutoff_flag) ? trim($param_POST->cutoff_flag) : '';
$type = isset($param_POST->type) ? trim($param_POST->type) : '';
$x0 = isset($param_POST->x0) ? trim($param_POST->x0) : '';
$y0 = isset($param_POST->y0) ? trim($param_POST->y0) : '';
$z0 = isset($param_POST->z0) ? trim($param_POST->z0) : '';
$timestamp = isset($param_POST->timestamp) ? trim($param_POST->timestamp) : '';
$wbp_profile_id = isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : '';

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM aktivitas_gelang AG
        WHERE 
        AG.gmac = ? AND
        AG.dmac = ? AND
        AG.baterai = ? AND
        AG.step = ? AND
        AG.heartrate = ? AND
        AG.temp = ? AND
        AG.spo = ? AND
        AG.systolic = ? AND
        AG.diastolic = ? AND
        AG.cutoff_flag = ? AND
        AG.type = ? AND
        AG.x0 = ? AND
        AG.y0 = ? AND
        AG.z0 = ? AND
        AG.timestamp = ? AND
        AG.wbp_profile_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([$gmac, $dmac, $baterai, $step, $heartrate, $temp, $spo, $systolic, $diastolic, $cutoff_flag, $type, $x0, $y0, $z0, $timestamp, $wbp_profile_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO aktivitas_gelang (
            aktivitas_gelang_id,
            gmac,
            dmac,
            baterai,
            step,
            heartrate,
            temp,
            spo,
            systolic,
            diastolic,
            cutoff_flag,
            type,
            x0,
            y0,
            z0,
            timestamp,
            wbp_profile_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $aktivitas_gelang_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $gmac, PDO::PARAM_STR);
        $stmt1->bindParam(3, $dmac, PDO::PARAM_STR);
        $stmt1->bindParam(4, $baterai, PDO::PARAM_STR);
        $stmt1->bindParam(5, $step, PDO::PARAM_STR);
        $stmt1->bindParam(6, $heartrate, PDO::PARAM_STR);
        $stmt1->bindParam(7, $temp, PDO::PARAM_STR);
        $stmt1->bindParam(8, $spo, PDO::PARAM_STR);
        $stmt1->bindParam(9, $systolic, PDO::PARAM_STR);
        $stmt1->bindParam(10, $diastolic, PDO::PARAM_STR);
        $stmt1->bindParam(11, $cutoff_flag, PDO::PARAM_STR);
        $stmt1->bindParam(12, $type, PDO::PARAM_STR);
        $stmt1->bindParam(13, $x0, PDO::PARAM_STR);
        $stmt1->bindParam(14, $y0, PDO::PARAM_STR);
        $stmt1->bindParam(15, $z0, PDO::PARAM_STR);
        $stmt1->bindParam(16, $timestamp, PDO::PARAM_STR);
        $stmt1->bindParam(17, $wbp_profile_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'aktivitas_gelang_id' => $aktivitas_gelang_id,
            'gmac' => $gmac,
            'dmac' => $dmac,
            'baterai' => $baterai,
            'step' => $step,
            'heartrate' => $heartrate,
            'temp' => $temp,
            'spo' => $spo,
            'systolic' => $systolic,
            'diastolic' => $diastolic,
            'cutoff_flag' => $cutoff_flag,
            'type' => $type,
            'x0' => $x0,
            'y0' => $y0,
            'z0' => $z0,
            'timestamp' => $timestamp,
            'wbp_profile_id' => $wbp_profile_id
        ]];
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
