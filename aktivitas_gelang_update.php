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

$aktivitas_gelang_id = (empty($param_POST->aktivitas_gelang_id)) ? '' : trim($param_POST->aktivitas_gelang_id);
$gmac = (empty($param_POST->gmac)) ? '' : trim($param_POST->gmac);
$dmac = (empty($param_POST->dmac)) ? '' : trim($param_POST->dmac);
$baterai = (empty($param_POST->baterai)) ? '' : trim($param_POST->baterai);
$step = (empty($param_POST->step)) ? '' : trim($param_POST->step);
$heartrate = (empty($param_POST->heartrate)) ? '' : trim($param_POST->heartrate);
$temp = (empty($param_POST->temp)) ? '' : trim($param_POST->temp);
$spo = (empty($param_POST->spo)) ? '' : trim($param_POST->spo);
$systolic = (empty($param_POST->systolic)) ? '' : trim($param_POST->systolic);
$diastolic = (empty($param_POST->diastolic)) ? '' : trim($param_POST->diastolic);
$cutoff_flag = (empty($param_POST->cutoff_flag)) ? '' : trim($param_POST->cutoff_flag);
$type = (empty($param_POST->type)) ? '' : trim($param_POST->type);
$x0 = (empty($param_POST->x0)) ? '' : trim($param_POST->x0);
$y0 = (empty($param_POST->y0)) ? '' : trim($param_POST->y0);
$z0 = (empty($param_POST->z0)) ? '' : trim($param_POST->z0);
$timestamp = (empty($param_POST->timestamp)) ? '' : trim($param_POST->timestamp);
$wbp_profile_id = (empty($param_POST->wbp_profile_id)) ? '' : trim($param_POST->wbp_profile_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM aktivitas_gelang WHERE aktivitas_gelang_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$aktivitas_gelang_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $result['message'] = "Data not found";
    } else {
        $query1 = "UPDATE aktivitas_gelang SET 
        gmac = ?,
        dmac = ?,
        baterai = ?,
        step = ?,
        heartrate = ?,
        temp = ?,
        spo = ?,
        systolic = ?,
        diastolic = ?,
        cutoff_flag = ?,
        type = ?,
        x0 = ?,
        y0 = ?,
        z0 = ?,
        timestamp = ?,
        wbp_profile_id = ? WHERE aktivitas_gelang_id = ?";

        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $gmac, PDO::PARAM_STR);
        $stmt1->bindParam(2, $dmac, PDO::PARAM_STR);
        $stmt1->bindParam(3, $baterai, PDO::PARAM_STR);
        $stmt1->bindParam(4, $step, PDO::PARAM_STR);
        $stmt1->bindParam(5, $heartrate, PDO::PARAM_STR);
        $stmt1->bindParam(6, $temp, PDO::PARAM_STR);
        $stmt1->bindParam(7, $spo, PDO::PARAM_STR);
        $stmt1->bindParam(8, $systolic, PDO::PARAM_STR);
        $stmt1->bindParam(9, $diastolic, PDO::PARAM_STR);
        $stmt1->bindParam(10, $cutoff_flag, PDO::PARAM_STR);
        $stmt1->bindParam(11, $type, PDO::PARAM_STR);
        $stmt1->bindParam(12, $x0, PDO::PARAM_STR);
        $stmt1->bindParam(13, $y0, PDO::PARAM_STR);
        $stmt1->bindParam(14, $z0, PDO::PARAM_STR);
        $stmt1->bindParam(15, $timestamp, PDO::PARAM_STR);
        $stmt1->bindParam(16, $wbp_profile_id, PDO::PARAM_STR);
        $stmt1->bindParam(17, $aktivitas_gelang_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Update data successfully";
        $result['status'] = "OK";
        $result['recods'] = [
            [
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
            ]
        ];
    }


} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>