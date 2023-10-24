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
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_gelang';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';
    $allowedSortFields = ['nama_gelang'];

    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_gelang'; // Default to nama_gelang if the provided field is not allowed
    }

    $lokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $lokasiLemasmilId = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterNamaGelang = isset($requestData['filter']['nama_gelang']) ? $requestData['filter']['nama_gelang'] : "";
    $filterDMACGelang = isset($requestData['filter']['dmac']) ? $requestData['filter']['dmac'] : "";
    $filterRuanganOtmilId = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganOtmil = isset($requestData['filter']['jenis_ruangan_otmil']) ? $requestData['filter']['jenis_ruangan_otmil'] : "";
    $filterJenisRuanganLemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterWBPProfileId = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $filterNamaWBP = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";

    // Construct the SQL query with filtering conditions
    $query = "SELECT DISTINCT
    A.gelang_id,
    A.dmac,
    A.nama_gelang,
    A.tanggal_pasang,
    A.tanggal_aktivasi,
    A.baterai,
    CASE
        WHEN A.baterai < 20 THEN 'Gelang Low'
        ELSE 'Gelang Aktif'
    END AS gelang_status,
    COUNT(A.gelang_id) AS jumlah_gelang,

    CASE
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.ruangan_lemasmil_id
        ELSE NULL
    END AS ruangan_lemasmil_id,
    CASE
        WHEN A.ruangan_otmil_id IS NOT NULL THEN A.ruangan_otmil_id
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
    END AS nama_lokasi_otmil,
    
    -- Subquery to retrieve wbp_profile_id and nama_wbp values as a list or concatenated string
    (
        SELECT GROUP_CONCAT(w.wbp_profile_id) 
        FROM wbp_profile w 
        WHERE w.gelang_id = A.gelang_id
    ) AS wbp_profile_id_list,
    (
        SELECT GROUP_CONCAT(w.nama) 
        FROM wbp_profile w 
        WHERE w.gelang_id = A.gelang_id
    ) AS nama_wbp_list
    
FROM
    gelang A
LEFT JOIN
    ruangan_lemasmil RL ON A.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
LEFT JOIN
    ruangan_otmil RO ON A.ruangan_otmil_id = RO.ruangan_otmil_id
LEFT JOIN
    lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
LEFT JOIN
    lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
LEFT JOIN
    zona ZL ON RL.zona_id = ZL.zona_id
LEFT JOIN
    zona ZO ON RO.zona_id = ZO.zona_id
WHERE
    A.is_deleted = '0'";

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

    if (!empty($filterNamaGelang)) {
        $query .= " AND A.nama_gelang LIKE '%$filterNamaGelang%'";
    }

    if (!empty($filterDMACGelang)) {
        $query .= " AND A.dmac LIKE '%$filterDMACGelang%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $query .= " AND A.ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $query .= " AND A.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
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
        $query .= " AND w.wbp_profile_id LIKE '%$filterWBPProfileId%'";
    }

    if (!empty($filterNamaWBP)) {
        $query .= " AND w.nama LIKE '%$filterNamaWBP%'";
    }


    // Separate queries for total counts
    $totalGelangQuery = "SELECT COUNT(*) AS total_gelang FROM gelang WHERE is_deleted = '0'";
    if (!empty($lokasiOtmilId)) {
        $totalGelangQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
    }

    if (!empty($lokasiLemasmilId)) {
        $totalGelangQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $totalGelangQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%')";
    }

    if (!empty($filterNamaLokasiLemasmil)) {
        $totalGelangQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%')";
    }

    if (!empty($filterNamaGelang)) {
        $totalGelangQuery .= " AND nama_gelang LIKE '%$filterNamaGelang%'";
    }

    if (!empty($filterDMACGelang)) {
        $totalGelangQuery .= " AND dmac LIKE '%$filterDMACGelang%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $totalGelangQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $totalGelangQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $totalGelangQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%')";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $totalGelangQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%')";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $totalGelangQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%')";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $totalGelangQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%')";
    }

    if (!empty($filterWBPProfileId)) {
        $totalGelangQuery .= " AND wbp_profile_id IN (SELECT wbp_profile_id FROM wbp_profile WHERE wbp_profile_id LIKE '%$filterWBPProfileId%')";
    }

    if (!empty($filterNamaWBP)) {
        $totalGelangQuery .= " AND wbp_profile_id IN (SELECT wbp_profile_id FROM wbp_profile WHERE nama LIKE '%$filterNamaWBP%')";
    }

    $gelangAktifQuery = "SELECT COUNT(*) AS total_gelang_aktif FROM gelang WHERE baterai >= 20 AND is_deleted = '0'";
    if (!empty($lokasiOtmilId)) {
        $gelangAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
    }

    if (!empty($lokasiLemasmilId)) {
        $gelangAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $gelangAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%')";
    }

    if (!empty($filterNamaLokasiLemasmil)) {
        $gelangAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%')";
    }

    if (!empty($filterNamaGelang)) {
        $gelangAktifQuery .= " AND nama_gelang LIKE '%$filterNamaGelang%'";
    }

    if (!empty($filterDMACGelang)) {
        $gelangAktifQuery .= " AND dmac LIKE '%$filterDMACGelang%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $gelangAktifQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $gelangAktifQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $gelangAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%')";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $gelangAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%')";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $gelangAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%')";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $gelangAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%')";
    }

    if (!empty($filterWBPProfileId)) {
        $gelangAktifQuery .= " AND wbp_profile_id IN (SELECT wbp_profile_id FROM wbp_profile WHERE wbp_profile_id LIKE '%$filterWBPProfileId%')";
    }

    if (!empty($filterNamaWBP)) {
        $gelangAktifQuery .= " AND wbp_profile_id IN (SELECT wbp_profile_id FROM wbp_profile WHERE nama LIKE '%$filterNamaWBP%')";
    }

    $gelangLowQuery = "SELECT COUNT(*) AS total_gelang_low FROM gelang WHERE baterai < 20 AND is_deleted = '0'";

    if (!empty($lokasiOtmilId)) {
        $gelangLowQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
    }

    if (!empty($lokasiLemasmilId)) {
        $gelangLowQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $gelangLowQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%')";
    }

    if (!empty($filterNamaLokasiLemasmil)) {
        $gelangLowQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%')";
    }

    if (!empty($filterNamaGelang)) {
        $gelangLowQuery .= " AND nama_gelang LIKE '%$filterNamaGelang%'";
    }

    if (!empty($filterDMACGelang)) {
        $gelangLowQuery .= " AND dmac LIKE '%$filterDMACGelang%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $gelangLowQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $gelangLowQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $gelangLowQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%')";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $gelangLowQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%')";
    }

    if (!empty($filterJenisRuanganOtmil)) {
        $gelangLowQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%')";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $gelangLowQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%')";
    }

    if (!empty($filterWBPProfileId)) {
        $gelangLowQuery .= " AND wbp_profile_id IN (SELECT wbp_profile_id FROM wbp_profile WHERE wbp_profile_id LIKE '%$filterWBPProfileId%')";
    }

    if (!empty($filterNamaWBP)) {
        $gelangLowQuery .= " AND wbp_profile_id IN (SELECT wbp_profile_id FROM wbp_profile WHERE nama LIKE '%$filterNamaWBP%')";
    }


    $countStmt = $conn->prepare($totalGelangQuery);
    $countStmt->execute();
    $totalGelangCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_gelang'];

    $countStmt = $conn->prepare($gelangAktifQuery);
    $countStmt->execute();
    $gelangAktifCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_gelang_aktif'];

    $countStmt = $conn->prepare($gelangLowQuery);
    $countStmt->execute();
    $gelangLowCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_gelang_low'];

    $query .= " GROUP BY A.gelang_id";
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

    $wbpProfileIdQuery = "SELECT w.wbp_profile_id, w.nama FROM wbp_profile w WHERE w.gelang_id = :gelang_id";
    

// Prepare the queries only once outside the loop
$wbpProfileIdStmt = $conn->prepare($wbpProfileIdQuery);

$gelangStmt = $conn->prepare($query);
$gelangStmt->execute();

$record = array();

// loop through the result of the query and assign it to the $recordData variable
while ($row = $gelangStmt->fetch(PDO::FETCH_ASSOC)) {
    $wbpProfileIdStmt->execute(['gelang_id' => $row['gelang_id']]);

    // Fetch all WBP profiles related to the current gelang
    $wbpProfiles = array();
    while ($wbpRow = $wbpProfileIdStmt->fetch(PDO::FETCH_ASSOC)) {
        $wbpProfiles[] = [
            "wbp_profile_id" => $wbpRow['wbp_profile_id'],
            "nama_wbp" => $wbpRow['nama']
        ];
    }

    $recordData = [
        "gelang_id" => $row['gelang_id'],
        "dmac" => $row['dmac'],
        "nama_gelang" => $row['nama_gelang'],
        "tanggal_pasang" => $row['tanggal_pasang'],
        "tanggal_aktivasi" => $row['tanggal_aktivasi'],
        "baterai" => $row['baterai'],
        "lokasi_otmil_id" => $row['lokasi_otmil_id'],
        "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
        "ruangan_otmil_id" => $row['ruangan_otmil_id'],
        "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
        "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
        "zona_id_otmil" => $row['zona_id_otmil'],
        "status_zona_ruangan_otmil" => $row['status_zona_ruangan_otmil'],
        "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
        "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
        "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
        "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
        "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
        "zona_id_lemasmil" => $row['zona_id_lemasmil'],
        "status_zona_ruangan_lemasmil" => $row['status_zona_ruangan_lemasmil'],
        "wbp" => $wbpProfiles
    ];
    $record[] = $recordData;
}


    // Prepare the JSON response with pagination information and counts
    $response = [
        "status" => "OK",
        "message" => "Success",
        "records" => $record,
        "total_gelang" => $totalGelangCount,
        "gelang_aktif" => $gelangAktifCount,
        "gelang_low" => $gelangLowCount,
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
