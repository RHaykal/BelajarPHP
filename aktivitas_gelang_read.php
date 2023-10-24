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

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $requestData = json_decode(file_get_contents("php://input"), true);

    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1; 
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $start = ($page - 1) * $pageSize;

    $aktivitas_gelang_id = isset($requestData['filter']['aktivitas_gelang_id']) ? $requestData['filter']['aktivitas_gelang_id'] : "";
    $wbp_profile_id = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $nama_wbp = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";
    $gelang_id = isset($requestData['filter']['gelang_id']) ? $requestData['filter']['gelang_id'] : "";
    $nama_gelang = isset($requestData['filter']['nama_gelang']) ? $requestData['filter']['nama_gelang'] : "";

    $ruangan_otmil_id = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $nama_ruangan_otmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $lokasi_otmil_id = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $nama_lokasi_otmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    
    $ruangan_lemasmil_id = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $nama_ruangan_lemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $lokasi_lemasmil_id = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $nama_lokasi_lemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    

    $query = "SELECT 
    AG.aktivitas_gelang_id,
    AG.gmac,
    AG.dmac,
    AG.baterai,
    AG.step,
    AG.heartrate,
    AG.temp,
    AG.spo,
    AG.systolic,
    AG.diastolic,
    AG.cutoff_flag,
    AG.type,
    AG.x0,
    AG.y0,
    AG.z0,
    AG.timestamp,
    AG.wbp_profile_id,
    WP.nama AS nama_wbp,
    WP.gelang_id AS gelang_id,
    G.nama_gelang AS nama_gelang,
    RO.ruangan_otmil_id AS ruangan_otmil_id, 
    RO.nama_ruangan_otmil AS nama_ruangan_otmil, 
    LO.lokasi_otmil_id AS lokasi_otmil_id, 
    LO.nama_lokasi_otmil AS nama_lokasi_otmil,
    RL.ruangan_lemasmil_id AS ruangan_lemasmil_id, 
    RL.nama_ruangan_lemasmil AS nama_ruangan_lemasmil,
    LL.lokasi_lemasmil_id AS lokasi_lemasmil_id,
    LL.nama_lokasi_lemasmil AS nama_lokasi_lemasmil
    FROM aktivitas_gelang AG
    LEFT JOIN wbp_profile WP ON AG.wbp_profile_id = WP.wbp_profile_id
    LEFT JOIN gelang G ON WP.gelang_id = G.gelang_id
    LEFT JOIN ruangan_otmil RO ON G.ruangan_otmil_id = RO.ruangan_otmil_id
    LEFT JOIN lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
    LEFT JOIN ruangan_lemasmil RL ON G.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
    LEFT JOIN lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id

    WHERE AG.is_deleted = 0";

    if (!empty($aktivitas_gelang_id)) {
        $query .= " AND AG.aktivitas_gelang_id LIKE '%$aktivitas_gelang_id%'";
    }
    if (!empty($wbp_profile_id)) {
        $query .= " AND AG.wbp_profile_id LIKE '%$wbp_profile_id%'";
    }
    if (!empty($nama_wbp)) {
        $query .= " AND WP.nama LIKE '%$nama_wbp%'";
    }
    if (!empty($gelang_id)) {
        $query .= " AND WP.gelang_id LIKE '%$gelang_id%'";
    }
    if (!empty($nama_gelang)) {
        $query .= " AND G.nama_gelang LIKE '%$nama_gelang%'";
    }

    if (!empty($ruangan_otmil_id)) {
        $query .= " AND RO.ruangan_otmil_id LIKE '%$ruangan_otmil_id%'";
    }
    if (!empty($nama_ruangan_otmil)) {
        $query .= " AND RO.nama_ruangan_otmil LIKE '%$nama_ruangan_otmil%'";
    }
    if (!empty($lokasi_otmil_id)) {
        $query .= " AND LO.lokasi_otmil_id LIKE '%$lokasi_otmil_id%'";
    }
    if (!empty($nama_lokasi_otmil)) {
        $query .= " AND LO.nama_lokasi_otmil LIKE '%$nama_lokasi_otmil%'";
    }

    if (!empty($ruangan_lemasmil_id)) {
        $query .= " AND RL.ruangan_lemasmil_id LIKE '%$ruangan_lemasmil_id%'";
    }
    if (!empty($nama_ruangan_lemasmil)) {
        $query .= " AND RL.nama_ruangan_lemasmil LIKE '%$nama_ruangan_lemasmil%'";
    }
    if (!empty($lokasi_lemasmil_id)) {
        $query .= " AND LL.lokasi_lemasmil_id LIKE '%$lokasi_lemasmil_id%'";
    }
    if (!empty($nama_lokasi_lemasmil)) {
        $query .= " AND LL.nama_lokasi_lemasmil LIKE '%$nama_lokasi_lemasmil%'";
    }


    $countQuery = "SELECT COUNT(*) as total FROM ($query) as countQuery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC);
    $totalRecords = $totalRecords['total'];

    
    $totalPages = ceil($totalRecords / $pageSize);

    $query .= " LIMIT $start, $pageSize";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $record = [];
    if (count($res) > 0) {
        foreach ($res as $row) {
            $data = [
                "aktivitas_gelang_id" => $row['aktivitas_gelang_id'],
                "gmac" => $row['gmac'],
                "dmac" => $row['dmac'],
                "baterai" => $row['baterai'],
                "step" => $row['step'],
                "heartrate" => $row['heartrate'],
                "temp" => $row['temp'],
                "spo" => $row['spo'],
                "systolic" => $row['systolic'],
                "diastolic" => $row['diastolic'],
                "cutoff_flag" => $row['cutoff_flag'],
                "type" => $row['type'],
                "x0" => $row['x0'],
                "y0" => $row['y0'],
                "z0" => $row['z0'],
                "timestamp" => $row['timestamp'],
                "wbp_profile_id" => $row['wbp_profile_id'],
                "nama_wbp" => $row['nama_wbp'],
                "gelang_id" => $row['gelang_id'],
                "nama_gelang" => $row['nama_gelang'],
                "ruangan_otmil_id" => $row['ruangan_otmil_id'],
                "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
                "lokasi_otmil_id" => $row['lokasi_otmil_id'],
                "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],

                "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
                "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
                "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
                "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil']
            ];
            $record[] = $data;
        }
        $response = [
            "status" => "OK",
            "message" => "Data Successfully Fetched",
            "data" => $record,
            "pagination" => [
                "currentPage" => $page,
                "pageSize" => $pageSize,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
            ],
        ];
    } else {
        $response = [
            "status" => "NO",
            "message" => "No Data Found",
            "data" => []
        ];
    }

    echo json_encode($response);
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "data" => []
    ];

    echo json_encode($result);
} finally {
    $stmt = null;
    $conn = null;
}
