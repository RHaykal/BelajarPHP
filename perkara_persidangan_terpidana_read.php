<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Headers: Token");
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

try {
    // Connect to your database
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    // Read JSON data from the request body
    $json_data = file_get_contents("php://input");
    $request_data = json_decode($json_data);

    // Check if the JSON data contains pagination parameters
    $page = isset($request_data->page) ? (int) $request_data->page : 1; // Current page
    $pageSize = isset($request_data->pageSize) ? (int) $request_data->pageSize : 10; // Number of records per page
    $offset = ($page - 1) * $pageSize;

    $filter_nama_perkara_persidangan_terpidana = isset($requestData['filter']['nama_perkara_persidangan_terpidana']) ? $requestData['filter']['nama_perkara_persidangan_terpidana'] : "";
    $filter_nomor_perkara_persidangan_terpidana = isset($requestData['filter']['nomor_perkara_persidangan_terpidana']) ? $requestData['filter']['nomor_perkara_persidangan_terpidana'] : "";
    $filter_nrp = isset($requestData['filter']['nrp']) ? $requestData['filter']['nrp'] : "";
    $filter_nama_wbp = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filter_nama_perkara = isset($requestData['filter']['nama_perkara']) ? $requestData['filter']['nama_perkara'] : "";
    $filter_status_perkara_persidangan_terpidana = isset($requestData['filter']['status_perkara_persidangan_terpidana']) ? $requestData['filter']['status_perkara_persidangan_terpidana'] : "";
    $filter_tanggal_penetapan_terpidana = isset($requestData['filter']['tanggal_penetapan_terpidana']) ? $requestData['filter']['tanggal_penetapan_terpidana'] : "";
    $filter_tanggal_registrasi_terpidana = isset($requestData['filter']['tanggal_registrasi_terpidana']) ? $requestData['filter']['tanggal_registrasi_terpidana'] : "";
    $filter_nama_oditur = isset($requestData['filter']['nama_oditur']) ? $requestData['filter']['nama_oditur'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'perkara_persidangan_terpidana.nama_perkara_persidangan_terpidana';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_perkara_persidangan_terpidana', 'nomor_perkara_persidangan_terpidana', 'nrp', 'nama', 'nama_perkara', 'status_perkara_persidangan_terpidana', 'tanggal_penetapan_terpidana', 'tanggal_registrasi_terpidana', 'nama_oditur'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'perkara_persidangan_terpidana.nama_perkara_persidangan_terpidana'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Modify your SQL query to include pagination and filtering
    $query = "SELECT perkara_persidangan_terpidana.*, wbp_profile.*, wbp_perkara.*,oditur.nama_oditur,
                kategori_perkara.nama_kategori_perkara,
                jenis_perkara.*
              FROM perkara_persidangan_terpidana
              LEFT JOIN wbp_profile ON perkara_persidangan_terpidana.wbp_profile_id = wbp_profile.wbp_profile_id
              LEFT JOIN wbp_perkara ON perkara_persidangan_terpidana.wbp_perkara_id = wbp_perkara.wbp_perkara_id
              LEFT JOIN kategori_perkara ON wbp_perkara.kategori_perkara_id = kategori_perkara.kategori_perkara_id
              LEFT JOIN jenis_perkara ON wbp_perkara.jenis_perkara_id = jenis_perkara.jenis_perkara_id
              LEFT JOIN oditur ON perkara_persidangan_terpidana.oditur_id = oditur.oditur_id
              WHERE perkara_persidangan_terpidana.is_deleted = 0";


    if (!empty($filter_nama_perkara_persidangan_terpidana)) {
        $query .= " AND perkara_persidangan_terpidana.nama_perkara_persidangan_terpidana LIKE '%$filter_nama_perkara_persidangan_terpidana%'";
    }

    if (!empty($filter_nomor_perkara_persidangan_terpidana)) {
        $query .= " AND perkara_persidangan_terpidana.nomor_perkara_persidangan_terpidana LIKE '%$filter_nomor_perkara_persidangan_terpidana%'";
    }

    if (!empty($filter_nrp)) {
        $query .= " AND wbp_profile.nrp LIKE '%$filter_nrp%'";
    }

    if (!empty($filter_nama_wbp)) {
        $query .= " AND wbp_profile.nama LIKE '%$filter_nama_wbp%'";
    }

    if (!empty($filter_nama_perkara)) {
        $query .= " AND jenis_perkara.nama_jenis_perkara LIKE '%$filter_nama_perkara%'";
    }

    if (!empty($filter_status_perkara_persidangan_terpidana)) {
        $query .= " AND perkara_persidangan_terpidana.status_perkara_persidangan_terpidana LIKE '%$filter_status_perkara_persidangan_terpidana%'";
    }

    if (!empty($filter_tanggal_penetapan_terpidana)) {
        $query .= " AND perkara_persidangan_terpidana.tanggal_penetapan_terpidana LIKE '%$filter_tanggal_penetapan_terpidana%'";
    }

    if (!empty($filter_tanggal_registrasi_terpidana)) {
        $query .= " AND perkara_persidangan_terpidana.tanggal_registrasi_terpidana LIKE '%$filter_tanggal_registrasi_terpidana%'";
    }

    if (!empty($filter_nama_oditur)) {
        $query .= " AND oditur.nama_oditur LIKE '%$filter_nama_oditur%'";
    }
    $query .= " ORDER BY perkara_persidangan_terpidana.nama_perkara_persidangan_terpidana ASC LIMIT :offset, :pageSize";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    $res = $st = $conn->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = $res;

    $totalCountQuery = "SELECT COUNT(*) FROM perkara_persidangan_terpidana WHERE is_deleted = 0";

    $stmt = $conn->prepare($totalCountQuery);
    $stmt->execute();
    $totalCount = $stmt->fetchColumn();

    // Create a response object with pagination info
    $result = array(
        "status" => "OK",
        "message" => "",
        "records" => $record,
        "pagination" => array(
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "totalRecords" => $totalCount,
            "totalPages" => ceil($totalCount / $pageSize)
        )
    );

    echo json_encode($result);
} catch (Exception $e) {
    $result = '{"status":"error", "message":"' . $e->getMessage() . '", "records":[]}';
    echo $result;
} finally {
    // Close the database connection
    if ($conn) {
        $conn = null;
    }
    if ($stmt) {
        $stmt = null;
    }
}
?>