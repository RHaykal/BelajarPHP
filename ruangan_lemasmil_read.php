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
    $filterNamaRuanganlemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganlemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterNamaLokasilemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterNamaZona = isset($requestData['filter']['nama_zona']) ? $requestData['filter']['nama_zona'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_ruangan_lemasmil';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_ruangan_lemasmil', 'jenis_ruangan_lemasmil', 'nama_lokasi_lemasmil', 'nama_zona'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_ruangan_lemasmil'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT ruangan_lemasmil.*, 
    lokasi_lemasmil.is_deleted AS lokasi_lemasmil_is_deleted, 
    ruangan_lemasmil.is_deleted AS ruangan_lemasmil_is_deleted, 
    lokasi_lemasmil.nama_lokasi_lemasmil,
    zona.nama_zona
    FROM ruangan_lemasmil
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    LEFT JOIN zona ON zona.zona_id = ruangan_lemasmil.zona_id
    WHERE ruangan_lemasmil.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    if (!empty($filterNamaRuanganlemasmil)) {
        $query .= " AND ruangan_lemasmil.nama_ruangan_lemasmil LIKE '%$filterNamaRuanganlemasmil%'";
    }
    if (!empty($filterJenisRuanganlemasmil)) {
        $query .= " AND ruangan_lemasmil.jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganlemasmil%'";
    }
    if (!empty($filterNamaLokasilemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaLokasilemasmil%'";
    }
    if (!empty($filterRuanganLemasmilId)) {
        $query .= " AND ruangan_lemasmil.ruangan_lemasmil_id LIKE '%$filterRuanganLemasmilId%'";
    }
    if (!empty($filterNamaZona)) {
        $query .= " AND zona.nama_zona LIKE '%$filterNamaZona%'";
    }

    $query .= " GROUP BY ruangan_lemasmil.ruangan_lemasmil_id";
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
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
            "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "zona_id" => $row['zona_id'],
            "nama_zona" => $row['nama_zona']
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
