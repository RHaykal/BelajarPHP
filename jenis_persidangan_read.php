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
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate the user's token using the tokenAuth function.
    // Make sure the tokenAuth function exists in 'require_files.php'.
    tokenAuth($conn, 'operator');

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;

    $filterNamaJenisPersidangan = isset($requestData['filter']['nama_jenis_persidangan']) ? $requestData['filter']['nama_jenis_persidangan'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_jenis_persidangan';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_jenis_persidangan'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_jenis_persidangan'; // Default to 'nama_jenis_persidangan' if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT * FROM jenis_persidangan WHERE jenis_persidangan.is_deleted = 0";

    if (!empty($filterNamaJenisPersidangan)) {
        $query .= " AND jenis_persidangan.nama_jenis_persidangan LIKE :nama_jenis_persidangan";
    }

    // Determine the offset (number of items to skip) for the query
    $countQuery = "SELECT COUNT(*) total FROM jenis_persidangan WHERE jenis_persidangan.is_deleted = 0";

    if (!empty($filterNamaJenisPersidangan)) {
        $countQuery .= " AND jenis_persidangan.nama_jenis_persidangan LIKE :nama_jenis_persidangan";
    }

    $countStmt = $conn->prepare($countQuery);
    if (!empty($filterNamaJenisPersidangan)) {
        $countStmt->bindValue(':nama_jenis_persidangan', '%' . $filterNamaJenisPersidangan . '%', PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $totalPages = ceil($total / $pageSize);
    $offset = ($page - 1) * $pageSize;

    $query .= " LIMIT $offset, $pageSize";

    // Prepare the SQL query and bind parameters
    $stmt = $conn->prepare($query);

    if (!empty($filterNamaJenisPersidangan)) {
        $stmt->bindValue(':nama_jenis_persidangan', '%' . $filterNamaJenisPersidangan . '%', PDO::PARAM_STR);
    }

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    foreach ($res as $row) {
        $record = [
            'jenis_persidangan_id' => $row['jenis_persidangan_id'],
            'nama_jenis_persidangan' => $row['nama_jenis_persidangan']
        ];
    }

    $result = [
        'message' => 'Success fetch data',
        'status' => 'OK',
        'data' => $record,
        'pagination' => [
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'totalRecords' => $total,
            'totalPages' => $totalPages
        ],
    ];

    echo json_encode($result);
} catch (Exception $e) {
    $result = [
        'message' => 'Error: ' . $e->getMessage(),
        'status' => 'ERROR'
    ];

    echo json_encode($result);
}
$stmt = null;
$conn = null;
?>
