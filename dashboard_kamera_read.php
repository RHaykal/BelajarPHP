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
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_kamera';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';
    $allowedSortFields = ['nama_kamera'];

    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_kamera';
    }

    $lokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $lokasiLemasmilId = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterRuanganOtmilId = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterNamaKamera = isset($requestData['filter']['nama_kamera']) ? $requestData['filter']['nama_kamera'] : "";
    $filterUrlRtsp = isset($requestData['filter']['url_rtsp']) ? $requestData['filter']['url_rtsp'] : "";
    $filterIpAddress = isset($requestData['filter']['ip_address']) ? $requestData['filter']['ip_address'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganOtmil = isset($requestData['filter']['jenis_ruangan_otmil']) ? $requestData['filter']['jenis_ruangan_otmil'] : "";
    $filterJenisRuanganLemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterStatusKamera = isset($requestData['filter']['status_kamera']) ? $requestData['filter']['status_kamera'] : "";
    $filterMerk = isset($requestData['filter']['merk']) ? $requestData['filter']['merk'] : "";
    $filterModel = isset($requestData['filter']['model']) ? $requestData['filter']['model'] : "";

    // Construct the SQL query with filtering conditions
    $query = "SELECT DISTINCT
    K.kamera_id,
    K.nama_kamera,
    K.url_rtsp,
    K.ip_address,
    K.status_kamera,
    K.merk,
    K.model,
    COUNT(K.kamera_id) AS jumlah_kamera,
    CASE
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.ruangan_lemasmil_id
        ELSE NULL
    END AS ruangan_lemasmil_id,
    CASE
        WHEN K.ruangan_otmil_id IS NOT NULL THEN K.ruangan_otmil_id
        ELSE NULL
    END AS ruangan_otmil_id,
    CASE 
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.nama_ruangan_lemasmil
        ELSE NULL
    END AS nama_ruangan_lemasmil,
    CASE 
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.jenis_ruangan_lemasmil
        ELSE NULL
    END AS jenis_ruangan_lemasmil,
    CASE 
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.zona_id
        ELSE NULL
    END AS zona_id_lemasmil,
    CASE 
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN ZL.nama_zona
        ELSE NULL
    END AS status_zona_ruangan_lemasmil,
    CASE 
        WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.nama_ruangan_otmil
        ELSE NULL
    END AS nama_ruangan_otmil,
    CASE 
        WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.jenis_ruangan_otmil
        ELSE NULL
    END AS jenis_ruangan_otmil,
    CASE 
        WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.zona_id
        ELSE NULL
    END AS zona_id_otmil,
    CASE 
        WHEN RO.ruangan_otmil_id IS NOT NULL THEN ZO.nama_zona
        ELSE NULL
    END AS status_zona_ruangan_otmil,
    CASE 
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.lokasi_lemasmil_id
        ELSE NULL
    END AS lokasi_lemasmil_id,
    CASE 
        WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.lokasi_otmil_id
        ELSE NULL
    END AS lokasi_otmil_id,
    CASE 
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN LL.nama_lokasi_lemasmil
        ELSE NULL
    END AS nama_lokasi_lemasmil,
    CASE 
        WHEN RO.ruangan_otmil_id IS NOT NULL THEN LO.nama_lokasi_otmil
        ELSE NULL
    END AS nama_lokasi_otmil
FROM
    kamera K
LEFT JOIN 
    ruangan_lemasmil RL ON K.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
LEFT JOIN
    ruangan_otmil RO ON K.ruangan_otmil_id = RO.ruangan_otmil_id
LEFT JOIN
    lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
LEFT JOIN
    lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
LEFT JOIN
    zona ZO ON RO.zona_id = ZO.zona_id
LEFT JOIN
    zona ZL ON RL.zona_id = ZL.zona_id
WHERE
    K.is_deleted = '0'";

    if (!empty($lokasiOtmilId)) {
        $query .= " AND RO.lokasi_otmil_id LIKE '%$lokasiOtmilId%'";
    }
    if (!empty($lokasiLemasmilId)) {
        $query .= " AND RL.lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $query .= " AND K.ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $query .= " AND K.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaKamera)) {
        $query .= " AND K.nama_kamera LIKE '%$filterNamaKamera%'";
    }

    if (!empty($filterUrlRtsp)) {
        $query .= " AND K.url_rtsp LIKE '%$filterUrlRtsp%'";
    }

    if (!empty($filterIpAddress)) {
        $query .= " AND K.ip_address LIKE '%$filterIpAddress%'";
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

    if (!empty($filterStatusKamera)) {
        $query .= " AND K.status_kamera LIKE '%$filterStatusKamera%'";
    }

    if (!empty($filterMerk)) {
        $query .= " AND K.merk LIKE '%$filterMerk%'";
    }

    if (!empty($filterModel)) {
        $query .= " AND K.model LIKE '%$filterModel%'";
    }

    // Separate queries for total counts
    $totalKameraQuery = "SELECT COUNT(*) AS total_kamera FROM kamera WHERE is_deleted = '0'";
    if (!empty($lokasiOtmilId)) {
        $totalKameraQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
    }

    if (!empty($lokasiLemasmilId)) {
        $totalKameraQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
    }

    if (!empty($filterRuanganOtmilId)) {
        $totalKameraQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $totalKameraQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaKamera)) {
        $totalKameraQuery .= " AND nama_kamera LIKE '%$filterNamaKamera%'";
    }

    if (!empty($filterUrlRtsp)) {
        $totalKameraQuery .= " AND url_rtsp LIKE '%$filterUrlRtsp%'";
    }

    if (!empty($filterIpAddress)) {
        $totalKameraQuery .= " AND ip_address LIKE '%$filterIpAddress%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $totalKameraQuery .= " AND nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $totalKameraQuery .= " AND nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $totalKameraQuery .= " AND jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $totalKameraQuery .= " AND jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
    }

    if (!empty($filterStatusKamera)) {
        $totalKameraQuery .= " AND status_kamera LIKE '%$filterStatusKamera%'";
    }

    if (!empty($filterMerk)) {
        $totalKameraQuery .= " AND merk LIKE '%$filterMerk%'";
    }

    if (!empty($filterModel)) {
        $totalKameraQuery .= " AND model LIKE '%$filterModel%'";
    }



    $kameraAktifQuery = "SELECT COUNT(*) AS total_kamera_aktif FROM kamera WHERE status_kamera = 'aktif' AND is_deleted = '0'";
    if (!empty($lokasiOtmilId)) {
        $kameraAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
    }

    if (!empty($lokasiLemasmilId)) {
        $kameraAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
    }

    if (!empty($filterRuanganOtmilId)) {
        $kameraAktifQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $kameraAktifQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaKamera)) {
        $kameraAktifQuery .= " AND nama_kamera LIKE '%$filterNamaKamera%'";
    }

    if (!empty($filterUrlRtsp)) {
        $kameraAktifQuery .= " AND url_rtsp LIKE '%$filterUrlRtsp%'";
    }

    if (!empty($filterIpAddress)) {
        $kameraAktifQuery .= " AND ip_address LIKE '%$filterIpAddress%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $kameraAktifQuery .= " AND nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $kameraAktifQuery .= " AND nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $kameraAktifQuery .= " AND jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $kameraAktifQuery .= " AND jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
    }

    if (!empty($filterStatusKamera)) {
        $kameraAktifQuery .= " AND status_kamera LIKE '%$filterStatusKamera%'";
    }

    if (!empty($filterMerk)) {
        $kameraAktifQuery .= " AND merk LIKE '%$filterMerk%'";
    }

    if (!empty($filterModel)) {
        $kameraAktifQuery .= " AND model LIKE '%$filterModel%'";
    }


    $kameraNonAktifQuery = "SELECT COUNT(*) AS total_kamera_non_aktif FROM kamera WHERE status_kamera = 'non_aktif' AND is_deleted = '0'";
    if (!empty($lokasiOtmilId)) {
        $kameraNonAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
    }

    if (!empty($lokasiLemasmilId)) {
        $kameraNonAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
    }

    if (!empty($filterRuanganOtmilId)) {
        $totalKameraQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $totalKameraQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaKamera)) {
        $kameraNonAktifQuery .= " AND nama_kamera LIKE '%$filterNamaKamera%'";
    }

    if (!empty($filterUrlRtsp)) {
        $kameraNonAktifQuery .= " AND url_rtsp LIKE '%$filterUrlRtsp%'";
    }

    if (!empty($filterIpAddress)) {
        $kameraNonAktifQuery .= " AND ip_address LIKE '%$filterIpAddress%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $kameraNonAktifQuery .= " AND nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $kameraNonAktifQuery .= " AND nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $kameraNonAktifQuery .= " AND jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $kameraNonAktifQuery .= " AND jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
    }

    if (!empty($filterStatusKamera)) {
        $kameraNonAktifQuery .= " AND status_kamera LIKE '%$filterStatusKamera%'";
    }

    if (!empty($filterMerk)) {
        $kameraNonAktifQuery .= " AND merk LIKE '%$filterMerk%'";
    }

    if (!empty($filterModel)) {
        $kameraNonAktifQuery .= " AND model LIKE '%$filterModel%'";
    }

    // Execute the queries to get the total counts
    $countStmt = $conn->prepare($totalKameraQuery);
    $countStmt->execute();
    $totalGelangCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_kamera'];

    $countStmt = $conn->prepare($kameraAktifQuery);
    $countStmt->execute();
    $gelangAktifCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_kamera_aktif'];


    $countStmt = $conn->prepare($kameraNonAktifQuery);
    $countStmt->execute();
    $gelangLowCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_kamera_non_aktif'];

    $query .= " GROUP BY K.kamera_id";
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

    // executing query to fetch all the data with filter and pagination feature
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    // loop through the result of the query and assign it to the $recordData variable
    foreach ($res as $row) {
        $recordData = [
            "kamera_id" => $row['kamera_id'],
            "nama_kamera" => $row['nama_kamera'],
            "url_rtsp" => $row['url_rtsp'],
            "ip_address" => $row['ip_address'],
            "status_kamera" => $row['status_kamera'],
            "merk" => $row['merk'],
            "model" => $row['model'],
            "jumlah_kamera" => $row['jumlah_kamera'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
            "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
            "zona_id_otmil" => $row['zona_id_otmil'],
            "status_zona_ruangan_otmil" => $row['status_zona_ruangan_otmil'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "ruangan_lemasmi_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
            "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
            "zona_id_lemasmil" => $row['zona_id_lemasmil'],
            "status_zona_ruangan_lemasmil" => $row['status_zona_ruangan_lemasmil']
        ];
        $record[] = $recordData;
    }

    // Prepare the JSON response with pagination information and counts
    $response = [
        "status" => "OK",
        "message" => "",
        "records" => $record,
        "total_kamera" => $totalGelangCount,
        "kamera_aktif" => $gelangAktifCount,
        "kamera_non_aktif" => $gelangLowCount,
        "pagination" => [
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "totalRecords" => $totalRecords,
            "totalPages" => $totalPages
        ],
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => []
    ];

    echo json_encode($result);
} finally {
    // Close the database connection
    $stmt = null;
    $conn = null;
}
