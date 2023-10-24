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
    $allowedSortFields = ['nama_gateway'];

    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_gateway';
    }

    $lokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $lokasiLemasmilId = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterRuanganOtmilId = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterGMAC = isset($requestData['filter']['gmac']) ? $requestData['filter']['gmac'] : "";
    $filterNamaGateway = isset($requestData['filter']['nama_gateway']) ? $requestData['filter']['nama_gateway'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganOtmil = isset($requestData['filter']['jenis_ruangan_otmil']) ? $requestData['filter']['jenis_ruangan_otmil'] : "";
    $filterJenisRuanganLemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterStatusGateway = isset($requestData['filter']['status_gateway']) ? $requestData['filter']['status_gateway'] : "";

    // Construct the SQL query with filtering conditions
    $query = "SELECT DISTINCT
    G.gateway_id,
    G.gmac,
    G.nama_gateway,
    G.status_gateway,
    COUNT(G.gateway_id) AS jumlah_gateway,
    CASE
        WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.ruangan_lemasmil_id
        ELSE NULL
    END AS ruangan_lemasmil_id,
    CASE
        WHEN G.ruangan_otmil_id IS NOT NULL THEN G.ruangan_otmil_id
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
    FROM gateway G
    LEFT JOIN ruangan_lemasmil RL ON G.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
    LEFT JOIN ruangan_otmil RO ON G.ruangan_otmil_id = RO.ruangan_otmil_id
    LEFT JOIN lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
    LEFT JOIN lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
    LEFT JOIN zona ZL ON RL.zona_id = ZL.zona_id
    LEFT JOIN zona ZO ON RO.zona_id = ZO.zona_id
    WHERE G.is_deleted = '0'";

    if (!empty($lokasiOtmilId)) {
        $query .= " AND RO.lokasi_otmil_id LIKE '%$lokasiOtmilId%'";
    }
    if (!empty($lokasiLemasmilId)) {
        $query .= " AND RL.lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%'";
    }
    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND LL.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND LO.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $query .= " AND G.ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $query .= " AND G.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }

    if (!empty($filterGMAC)) {
        $query .= " AND G.gmac LIKE '%$filterGMAC%'";
    }

    if (!empty($filterNamaGateway)) {
        $query .= " AND G.nama_gateway LIKE '%$filterNamaGateway%'";
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

    if (!empty($filterStatusGateway)) {
        $query .= " AND G.status_gateway LIKE '%$filterStatusGateway%'";
    }

    // Total Gateway
$totalGatewayQuery = "SELECT COUNT(*) AS total_gateway FROM gateway WHERE is_deleted = '0'";

if (!empty($lokasiOtmilId)) {
    $totalGatewayQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
}

if (!empty($lokasiLemasmilId)) {
    $totalGatewayQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
}

if (!empty($filterNamaLokasiOtmil)) {
    $totalGatewayQuery .= " AND ruangan_otmil_id IN (
        SELECT ruangan_otmil_id 
        FROM ruangan_otmil 
        WHERE lokasi_otmil_id IN (
            SELECT lokasi_otmil_id 
            FROM lokasi_otmil 
            WHERE nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'
        )
    )";
}

if (!empty($filterNamaLokasiLemasmil)) {
    $totalGatewayQuery .= " AND ruangan_lemasmil_id IN (
        SELECT ruangan_lemasmil_id 
        FROM ruangan_lemasmil 
        WHERE lokasi_lemasmil_id IN (
            SELECT lokasi_lemasmil_id 
            FROM lokasi_lemasmil 
            WHERE nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'
        )
    )";
}

if (!empty($filterRuanganOtmilId)) {
    $totalGatewayQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
}

if (!empty($filterNamaGateway)) {
    $totalGatewayQuery .= " AND nama_gateway LIKE '%$filterNamaGateway%'";
}

if (!empty($filterRuanganLemasmilId)) {
    $totalGatewayQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
}

if (!empty($filterGMAC)) {
    $totalGatewayQuery .= " AND gmac LIKE '%$filterGMAC%'";
}

if (!empty($filterNamaRuanganOtmil)) {
    $totalGatewayQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%')";
}

if (!empty($filterNamaRuanganLemasmil)) {
    $totalGatewayQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%')";
}

if (!empty($filterJenisRuanganOtmil)) {
    $totalGatewayQuery .= " AND jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
}

if (!empty($filterJenisRuanganLemasmil)) {
    $totalGatewayQuery .= " AND jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
}

if (!empty($filterStatusGateway)) {
    $totalGatewayQuery .= " AND status_gateway LIKE '%$filterStatusGateway%'";
}

// Gateway aktif
$gatewayAktifQuery = "SELECT COUNT(*) AS total_gateway_aktif FROM gateway WHERE status_gateway = 'aktif' AND is_deleted = '0'";

if (!empty($lokasiOtmilId)) {
    $gatewayAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
}

if (!empty($lokasiLemasmilId)) {
    $gatewayAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
}

if (!empty($filterNamaLokasiOtmil)) {
    $gatewayAktifQuery .= " AND ruangan_otmil_id IN (
        SELECT ruangan_otmil_id 
        FROM ruangan_otmil 
        WHERE lokasi_otmil_id IN (
            SELECT lokasi_otmil_id 
            FROM lokasi_otmil 
            WHERE nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'
        )
    )";
}

if (!empty($filterNamaLokasiLemasmil)) {
    $gatewayAktifQuery .= " AND ruangan_lemasmil_id IN (
        SELECT ruangan_lemasmil_id 
        FROM ruangan_lemasmil 
        WHERE lokasi_lemasmil_id IN (
            SELECT lokasi_lemasmil_id 
            FROM lokasi_lemasmil 
            WHERE nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'
        )
    )";
}

if (!empty($filterNamaGateway)) {
    $gatewayAktifQuery .= " AND nama_gateway LIKE '%$filterNamaGateway%'";
}

if (!empty($filterRuanganOtmilId)) {
    $gatewayAktifQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
}

if (!empty($filterRuanganLemasmilId)) {
    $gatewayAktifQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
}

if (!empty($filterNamaRuanganOtmil)) {
    $gatewayAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%')";
}

if (!empty($filterNamaRuanganLemasmil)) {
    $gatewayAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%')";
}


// Gateway nonaktif
$gatewayNonAktifQuery = "SELECT COUNT(*) AS total_gateway_non_aktif FROM gateway WHERE status_gateway = 'nonaktif' AND is_deleted = '0'";

if (!empty($lokasiOtmilId)) {
    $gatewayNonAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE lokasi_otmil_id LIKE '%$lokasiOtmilId%')";
}

if (!empty($lokasiLemasmilId)) {
    $gatewayNonAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE lokasi_lemasmil_id LIKE '%$lokasiLemasmilId%')";
}

if (!empty($filterNamaLokasiOtmil)) {
    $gatewayNonAktifQuery .= " AND ruangan_otmil_id IN (
        SELECT ruangan_otmil_id 
        FROM ruangan_otmil 
        WHERE lokasi_otmil_id IN (
            SELECT lokasi_otmil_id 
            FROM lokasi_otmil 
            WHERE nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'
        )
    )";
}

if (!empty($filterNamaLokasiLemasmil)) {
    $gatewayNonAktifQuery .= " AND ruangan_lemasmil_id IN (
        SELECT ruangan_lemasmil_id 
        FROM ruangan_lemasmil 
        WHERE lokasi_lemasmil_id IN (
            SELECT lokasi_lemasmil_id 
            FROM lokasi_lemasmil 
            WHERE nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'
        )
    )";
}

if (!empty($filterNamaGateway)) {
    $gatewayNonAktifQuery .= " AND nama_gateway LIKE '%$filterNamaGateway%'";
}

if (!empty($filterRuanganOtmilId)) {
    $gatewayNonAktifQuery .= " AND ruangan_otmil_id LIKE '%$filterRuanganOtmilId%'";
}

if (!empty($filterRuanganLemasmilId)) {
    $gatewayNonAktifQuery .= " AND ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
}

if (!empty($filterNamaRuanganOtmil)) {
    $gatewayNonAktifQuery .= " AND ruangan_otmil_id IN (SELECT ruangan_otmil_id FROM ruangan_otmil WHERE nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%')";
}

if (!empty($filterNamaRuanganLemasmil)) {
    $gatewayNonAktifQuery .= " AND ruangan_lemasmil_id IN (SELECT ruangan_lemasmil_id FROM ruangan_lemasmil WHERE nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%')";
}


    // Execute the queries to get the total counts
    $countStmt = $conn->prepare($totalGatewayQuery);
    $countStmt->execute();
    $totalGatewayCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_gateway'];

    $countStmt = $conn->prepare($gatewayAktifQuery);
    $countStmt->execute();
    $gatewayAktifCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_gateway_aktif'];


    $countStmt = $conn->prepare($gatewayNonAktifQuery);
    $countStmt->execute();
    $gatewatNonAktifCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total_gateway_non_aktif'];

    $query .= " GROUP BY G.gateway_id";
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
            "gateway_id" => $row['gateway_id'],
            "gmac" => $row['gmac'],
            "nama_gateway" => $row['nama_gateway'],
            "status_gateway" => $row['status_gateway'],
            "jumlah_gateway" => $row['jumlah_gateway'],
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
        "total_gateway" => $totalGatewayCount,
        "gateway_aktif" => $gatewayAktifCount,
        "gateway_non_aktif" => $gatewatNonAktifCount,
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
