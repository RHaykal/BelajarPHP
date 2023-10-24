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
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $json_data = file_get_contents("php://input");

    // Initialize an empty array to store filter conditions
    $conditions = [];

    // Decode the JSON data
    $requestData = json_decode($json_data, true);

    // Filter
    if (isset($requestData['filters'])) {
        $filters = $requestData['filters'];

        if (isset($filters['nama_kegiatan']) && !empty($filters['nama_kegiatan'])) {
            $nama_kegiatan = "%" . $filters['nama_kegiatan'] . "%"; // Wildcard search
            $conditions[] = "A.nama_kegiatan LIKE :nama_kegiatan";
        }
        if (isset($filters['nama_ruangan_lemasmil']) && !empty($filters['nama_ruangan_lemasmil'])) {
            $nama_ruangan_lemasmil = "%" . $filters['nama_ruangan_lemasmil'] . "%"; // Wildcard search
            $conditions[] = "RL.nama_ruangan_lemasmil LIKE :nama_ruangan_lemasmil";
        }
    }

    // Construct the WHERE clause based on the conditions
    $where_clause = "";
    if (!empty($conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $conditions);
    }

    // Pagination parameters
    $pageSize = isset($requestData['pageSize']) ? intval($requestData['pageSize']) : 10;
    $pageIndex = isset($requestData['pageIndex']) ? intval($requestData['pageIndex']) : 1;

    // Calculate the offset for pagination
    $offset = ($pageIndex - 1) * $pageSize;

    $query = "SELECT DISTINCT
    A.kegiatan_id,
    A.nama_kegiatan,
    A.status_kegiatan,
    A.waktu_mulai_kegiatan,
    A.waktu_selesai_kegiatan,
    CASE
            WHEN A.ruangan_lemasmil_id IS NOT NULL THEN RL.nama_ruangan_lemasmil 
            ELSE NULL
        END AS nama_ruangan_lemasmil,
        CASE
            WHEN A.ruangan_otmil_id IS NOT NULL THEN RO.nama_ruangan_otmil
            ELSE NULL
        END AS nama_ruangan_otmil,
        CASE
            WHEN A.ruangan_lemasmil_id IS NOT NULL THEN RL.lokasi_lemasmil_id
            ELSE NULL
        END AS lokasi_lemasmil_id,
        CASE
            WHEN A.ruangan_otmil_id IS NOT NULL THEN RO.lokasi_otmil_id
            ELSE NULL
        END AS lokasi_otmil_id,
        CASE
            WHEN A.ruangan_lemasmil_id IS NOT NULL THEN LL.nama_lokasi_lemasmil
            ELSE NULL
        END AS nama_lokasi_lemasmil,
        CASE
            WHEN A.ruangan_otmil_id IS NOT NULL THEN LO.nama_lokasi_otmil
            ELSE NULL
        END AS nama_lokasi_otmil,
        CASE
            WHEN KW.kegiatan_wbp_id IS NOT NULL THEN KW.wbp_profile_id
            ELSE NULL
        END AS wbp_profile_id
        -- CASE
        --     WHEN KW.kegiatan_wbp_id IS NOT NULL THEN RL.ruangan_lemasmil_id
        --     ELSE NULL
        -- END AS ruangan_lemasmil_id
FROM kegiatan A
LEFT JOIN ruangan_lemasmil RL ON 
    CASE
        WHEN A.ruangan_lemasmil_id IS NOT NULL THEN A.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
        ELSE TRUE
    END
LEFT JOIN ruangan_otmil RO ON 
    CASE
        WHEN A.ruangan_otmil_id IS NOT NULL THEN A.ruangan_otmil_id = RO.ruangan_otmil_id
        ELSE TRUE
    END
LEFT JOIN lokasi_lemasmil LL ON 
    CASE
        WHEN RL.lokasi_lemasmil_id IS NOT NULL THEN RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
        ELSE TRUE
    END
LEFT JOIN lokasi_otmil LO ON 
    CASE
        WHEN RO.lokasi_otmil_id IS NOT NULL THEN RO.lokasi_otmil_id = LO.lokasi_otmil_id
        ELSE TRUE
    END
LEFT JOIN kegiatan_wbp KW ON 
    CASE
        WHEN KW.kegiatan_wbp_id IS NOT NULL THEN KW.kegiatan_wbp_id = KW.kegiatan_wbp_id
        ELSE TRUE
    END
-- LEFT JOIN ruangan_lemasmil RL ON 
--     CASE
--         WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.ruangan_lemasmil_id = KW.kegiatan_
--         ELSE TRUE
--     END
$where_clause AND A.is_deleted = '0'
LIMIT :offset, :pageSize";



    $stmt = $conn->prepare($query);

    // Bind parameters for filtering
    if (isset($nama_kegiatan)) {
        $stmt->bindValue(':nama_kegiatan', $nama_kegiatan, PDO::PARAM_STR);
    }
    if (isset($nama_ruangan_lemasmil)) {
        $stmt->bindValue(':nama_ruangan_lemasmil', $nama_ruangan_lemasmil, PDO::PARAM_STR);
    }

    // Bind parameters for pagination
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate the total number of pages
    $totalPages = ceil(count($result) / $pageSize);

    $response = [
        "status" => 200,
        "message" => "Data Successfully Fetched",
        "data" => $result,
        "totalPage" => $totalPages,
        "pageIndex" => $pageIndex,
        "pageSize" => $pageSize,
    ];

    // Check if data is empty and add a status
    if (empty($result)) {
        $response['status'] = 404;
        $response['message'] = "No data found";
    }

    echo json_encode($response);
} catch (Exception $e) {
    $result = '{"status":"error", "message":"' . $e->getMessage() . '",
    "records":[]}';
    echo $result;
}
$stmt = null;
$conn = null;
