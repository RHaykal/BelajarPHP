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
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;

    // Validate and sanitize sortField to prevent SQL injection
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_gateway';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';
    $allowedSortFields = ['nama_gateway', 'status_gateway', 'timestamp'];

    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_gateway';
    }

    $lokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $lokasiLemasmilId = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterRuanganOtmilId = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterWBPProfileId = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $filterNamaWBP = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";
    $filterRuanganOtmilId = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganOtmil = isset($requestData['filter']['jenis_ruangan_otmil']) ? $requestData['filter']['jenis_ruangan_otmil'] : "";
    $filterJenisRuanganLemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterWBPProfileId = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $filterNamaWBP = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";
    $filterGMAC = isset($requestData['filter']['gmac']) ? $requestData['filter']['gmac'] : "";
    $gateway_log_id = isset($requestData['filter']['gateway_log_id']) ? $requestData['filter']['gateway_log_id'] : "";
    $filterNamaGateway = isset($requestData['filter']['nama_gateway']) ? $requestData['filter']['nama_gateway'] : "";
    $filterStatusGateway = isset($requestData['filter']['status_gateway']) ? $requestData['filter']['status_gateway'] : "";
    $filterTimeStamp = isset($requestData['filter']['timestamp']) ? $requestData['filter']['timestamp'] : "";

    $query = "SELECT DISTINCT
    GL.gateway_log_id,
    GL.image,
    G.nama_gateway,
    G.status_gateway,
    G.gmac,
    WBP.wbp_profile_id,
    WBP.nama AS nama_wbp,
    GL.timestamp,
    RL.ruangan_lemasmil_id,
    G.ruangan_otmil_id AS ruangan_otmil_id,
    RL.nama_ruangan_lemasmil,
    RL.jenis_ruangan_lemasmil,
    RL.zona_id AS zona_id_lemasmil,
    ZL.nama_zona AS status_zona_ruangan_lemasmil,
    RO.nama_ruangan_otmil,
    RO.jenis_ruangan_otmil,
    RO.zona_id AS zona_id_otmil,
    ZO.nama_zona AS status_zona_ruangan_otmil,
    RL.lokasi_lemasmil_id,
    RO.lokasi_otmil_id,
    LL.nama_lokasi_lemasmil,
    LO.nama_lokasi_otmil
FROM 
    gateway_log GL
LEFT JOIN gateway G ON GL.gateway_id = G.gateway_id
LEFT JOIN ruangan_lemasmil RL ON G.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
LEFT JOIN ruangan_otmil RO ON G.ruangan_otmil_id = RO.ruangan_otmil_id
LEFT JOIN lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
LEFT JOIN lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
LEFT JOIN zona ZL ON RL.zona_id = ZL.zona_id
LEFT JOIN zona ZO ON RO.zona_id = ZO.zona_id
LEFT JOIN wbp_profile WBP ON GL.wbp_profile_id = WBP.wbp_profile_id

WHERE GL.is_deleted = 0";

    if (!empty($gateway_log_id)) {
        $query .= " AND GL.gateway_log_id LIKE '%$gateway_log_id%'";
    }

    if (!empty($lokasiOtmilId)) {
        $query .= " AND RO.lokasi_otmil_id LIKE '%$lokasiOtmilId%'";
    }

    if (!empty($lokasiLemasmilId)) {
        $query .= " AND RL.lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%'";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND LO.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }

    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND LL.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $query .= " AND RO.ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $query .= " AND RL.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $query .= " AND RO.nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $query .= " AND RL.nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $query .= " AND RO.jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $query .= " AND RL.jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
    }

    if (!empty($filterWBPProfileId)) {
        $query .= " AND WBP.wbp_profile_id LIKE '%$filterWBPProfileId%'";
    }

    if (!empty($filterNamaWBP)) {
        $query .= " AND WBP.nama_wbp LIKE '%$filterNamaWBP%'";
    }

    if (!empty($filterGMAC)) {
        $query .= " AND G.gmac LIKE '%$filterGMAC%'";
    }

    if (!empty($filterNamaGateway)) {
        $query .= " AND G.nama_gateway LIKE '%$filterNamaGateway%'";
    }

    if (!empty($filterStatusGateway)) {
        $query .= " AND G.status_gateway LIKE '%$filterStatusGateway%'";
    }

    if (!empty($filterTimeStamp)) {
        $query .= " AND GL.timestamp LIKE '%$filterTimeStamp%'";
    }

    $query .= " GROUP BY GL.gateway_log_id";
    $query .= " ORDER BY $sortField $sortOrder";

    // preparing and executing the query that has been updated with pagination feature
    $countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Apply pagination
    $totalPages = ceil($totalRecords / $pageSize);
    $start = ($page - 1) * $pageSize;
    $query .= " LIMIT $start, $pageSize";

    // executing query to fecth all the data with filter and pagination feature
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    //loop through the result of the query and assigned it on $recordData variable
    foreach ($res as $row) {
        $recordData = [
            "gateway_log_id" => $row['gateway_log_id'],
            "image" => $row['image'],
            "nama_gateway" => $row['nama_gateway'],
            "status_gateway" => $row['status_gateway'],
            "gmac" => $row['gmac'],
            "timestamp" => $row['timestamp'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
            "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
            "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
            "zona_id_otmil" => $row['zona_id_otmil'],
            "zona_id_lemasmil" => $row['zona_id_lemasmil'],
            "status_zona_ruangan_otmil" => $row['status_zona_ruangan_otmil'],
            "status_zona_ruangan_lemasmil" => $row['status_zona_ruangan_lemasmil'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "wbp_profile_id" => $row['wbp_profile_id'],
            "nama_wbp" => $row['nama_wbp']
        ];
        $record[] = $recordData;
    }

    // Prepare the JSON response with pagination information
    $response = [
        "status" => "OK",
        "message" => "",
        "records" => $record,
        "pagination" => [
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "totalRecords" => $totalRecords,
            "totalPages" => $totalPages
        ]
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => []
    ];

    echo json_encode($result);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
