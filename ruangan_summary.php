<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

try {
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD 
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $filterData = json_decode(file_get_contents('php://input'), true); // Assuming JSON is sent via POST

    if (isset($filterData['filter'])) {
        $filterRuanganOtmil = isset($filterData['filter']['ruangan_otmil_id']) ? $filterData['filter']['ruangan_otmil_id'] : "";
        $filterRuanganLemasmil = isset($filterData['filter']['ruangan_lemasmil_id']) ? $filterData['filter']['ruangan_lemasmil_id'] : "";
    }

    $records = [];
    if (!empty($filterRuanganOtmil)) {
        $queryLocationNameOtmil = "SELECT nama_ruangan_otmil FROM ruangan_otmil WHERE ruangan_otmil_id = :ruangan_otmil_id";
        $stmtOtmil = $conn->prepare($queryLocationNameOtmil);
        $stmtOtmil->bindParam(":ruangan_otmil_id", $filterRuanganOtmil);
        $stmtOtmil->execute();
        $lokasiOtmil = $stmtOtmil->fetch(PDO::FETCH_ASSOC);
        $records['ruangan_otmil'] = $lokasiOtmil['nama_ruangan_otmil'];
    }
    
    if (!empty($filterRuanganLemasmil)) {
        $queryLocationNameLemasmil = "SELECT nama_ruangan_lemasmil FROM ruangan_lemasmil WHERE ruangan_lemasmil_id = :ruangan_lemasmil_id";
        $stmtLemasmil = $conn->prepare($queryLocationNameLemasmil);
        $stmtLemasmil->bindParam(":ruangan_lemasmil_id", $filterRuanganLemasmil);
        $stmtLemasmil->execute();
        $lokasiLemasmil = $stmtLemasmil->fetch(PDO::FETCH_ASSOC);
        $records['ruangan_lemasmil'] = $lokasiLemasmil['nama_ruangan_lemasmil'];
    }

    $queryWBPtotal = "SELECT COUNT(*) as total_wbp FROM wbp_profile 
    LEFT JOIN gelang ON gelang.gelang_id = wbp_profile.gelang_id
    LEFT JOIN ruangan_otmil ON gelang.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gelang.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    WHERE wbp_profile.is_deleted = 0";

    // Add filter conditions
    if (!empty($filterRuanganOtmil)) {
        $queryWBPtotal .= " AND ruangan_otmil.ruangan_otmil_id LIKE '%$filterRuanganOtmil%'";
    }
    if (!empty($filterRuanganLemasmil)) {
        $queryWBPtotal .= " AND ruangan_lemasmil.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmil%'";
    }

    $stmt = $conn->prepare($queryWBPtotal);
    $stmt->execute();
    $totalWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_wbp'] = $totalWBP['total_wbp'];

    $querytotalkamera = "SELECT COUNT(*) as total_kamera FROM kamera 
    LEFT JOIN ruangan_otmil ON kamera.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON kamera.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    WHERE kamera.is_deleted = 0 ";

    // Add filter conditions
    if (!empty($filterRuanganOtmil)) {
        $querytotalkamera .= " AND ruangan_otmil.ruangan_otmil_id LIKE '%$filterRuanganOtmil%'";
    }
    if (!empty($filterRuanganLemasmil)) {
        $querytotalkamera .= " AND ruangan_lemasmil.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmil%'";
    }

    $stmt = $conn->prepare($querytotalkamera);
    $stmt->execute();
    $isolatedWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_kamera'] = $isolatedWBP['total_kamera'];

    $querytotalgateway = "SELECT COUNT(*) as total_gateway FROM gateway 
    LEFT JOIN ruangan_otmil ON gateway.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gateway.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    WHERE gateway.is_deleted = 0 ";

    // Add filter conditions
    if (!empty($filterRuanganOtmil)) {
        $querytotalgateway .= " AND ruangan_otmil.ruangan_otmil_id LIKE '%$filterRuanganOtmil%'";
    }
    if (!empty($filterRuanganLemasmil)) {
        $querytotalgateway .= " AND ruangan_lemasmil.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmil%'";
    }

    $stmt = $conn->prepare($querytotalgateway);
    $stmt->execute();
    $isolatedWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_gateway'] = $isolatedWBP['total_gateway'];

    $response = [
        "status" => "OK",
        "message" => "Data berhasil diambil",
        "records" => $records
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => 0
    ];

    echo json_encode($result);
}

$stmt = null;
$conn = null;
?>