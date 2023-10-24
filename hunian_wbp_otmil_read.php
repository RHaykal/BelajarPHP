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
    $filterNamaHunianOtmil = isset($requestData['filter']['nama_hunian_wbp_otmil']) ? $requestData['filter']['nama_hunian_wbp_otmil'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterLokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_hunian_wbp_otmil';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_hunian_wbp_otmil', 'nama_lokasi_otmil'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_hunian_wbp_otmil'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT hunian_wbp_otmil.*, 
    lokasi_otmil.is_deleted AS lokasi_otmil_is_deleted, 
    hunian_wbp_otmil.is_deleted AS hunian_wbp_otmil_is_deleted, 
    lokasi_otmil.nama_lokasi_otmil
    FROM hunian_wbp_otmil
    JOIN lokasi_otmil ON hunian_wbp_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id 
    WHERE hunian_wbp_otmil.is_deleted = 0";

    //ORDER BY nama_hunian_wbp_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    if (!empty($filterNamaHunianOtmil)) {
        $query .= " AND hunian_wbp_otmil.nama_hunian_wbp_otmil LIKE '%$filterNamaHunianOtmil%'";
    }
 
    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }
    if (!empty($filterLokasiOtmilId)) {
        $query .= " AND hunian_wbp_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmilId%'";
    }

    // $query .= " GROUP BY ruangan_otmil.ruangan_otmil_id";
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
            "hunian_wbp_otmil_id" => $row['hunian_wbp_otmil_id'],
            "nama_hunian_wbp_otmil" => $row['nama_hunian_wbp_otmil'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil']
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
}

$stmt = null;
$conn = null;
?>
