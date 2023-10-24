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
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // tokenAuth($conn, 'operator');

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;
    
    $filterNamaPengadilanMiliter = isset($requestData['filter']['nama_pengadilan_militer']) ? $requestData['filter']['nama_pengadilan_militer'] : "";
    $filterProvinsi = isset($requestData['filter']['nama_provinsi']) ? $requestData['filter']['nama_provinsi'] : "";
    $filterKota = isset($requestData['filter']['nama_kota']) ? $requestData['filter']['nama_kota'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_pengadilan_militer';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_pengadilan_militer', 'nama_provinsi', 'nama_kota'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_pengadilan_militer'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT pengadilan_militer.*, 
    provinsi.nama_provinsi,
    kota.nama_kota
    FROM pengadilan_militer
    LEFT JOIN provinsi ON provinsi.provinsi_id = pengadilan_militer.provinsi_id
    LEFT JOIN kota ON kota.kota_id = pengadilan_militer.kota_id
    WHERE pengadilan_militer.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    
    if (!empty($filterNamaPengadilanMiliter)) {
        $query .= " AND pengadilan_militer.nama_pengadilan_militer LIKE '%$filterNamaPengadilanMiliter%'";
    }

    if (!empty($filterProvinsi)) {
        $query .= " AND provinsi.nama_provinsi LIKE '%$filterProvinsi%'";
    }

    if (!empty($filterKota)) {
        $query .= " AND kota.nama_kota LIKE '%$filterKota%'";
    }

    $query .= " GROUP BY pengadilan_militer.pengadilan_militer_id";
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
            "pengadilan_militer_id" => $row['pengadilan_militer_id'],
            "nama_pengadilan_militer" => $row['nama_pengadilan_militer'],
            "provinsi_id" => $row['provinsi_id'],
            "nama_provinsi" => $row['nama_provinsi'],
            "kota_id" => $row['kota_id'],
            "nama_kota" => $row['nama_kota'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude']
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
