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
    $filterNama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama'; // Default to 'nama' if the provided field is not allowed
    }

    // Initialize id and data variables with default values
    $id = "";
    $data = "";

    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    }

    if (isset($_GET['data'])) {
        $data = $_GET['data'];
    }

    // Construct the SQL query with filtering conditions
    $n = strlen($id);
    $m = ($n == 2 ? 5 : ($n == 5 ? 8 : 13));

    if ($data == "kabupaten" || $data == "kecamatan" || $data == "desa") {
        $query = "SELECT kode, nama FROM wilayah WHERE LEFT(kode, $n) = :id AND CHAR_LENGTH(kode) = $m";
    } else {
        $query = "SELECT kode, nama FROM wilayah WHERE CHAR_LENGTH(kode) = 2";
    }

    if (!empty($filterNama)) {
        $query .= " AND wilayah.nama LIKE :filterNama";
    }

    $query .= " GROUP BY wilayah.kode";
    $query .= " ORDER BY $sortField $sortOrder";

    // preparing and executing the query that has been updated with pagination feature
    $countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
    $countStmt = $conn->prepare($countQuery);

    if (!empty($filterNama)) {
        $filterNama = "%$filterNama%";
        $countStmt->bindParam(':filterNama', $filterNama, PDO::PARAM_STR);
    }

    $countStmt->bindParam(':id', $id, PDO::PARAM_STR);
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Apply pagination
    $totalPages = ceil($totalRecords / $pageSize);
    $start = ($page - 1) * $pageSize;
    $query .= " LIMIT $start, $pageSize";

    // executing query to fetch all the data with filter and pagination feature
    $stmt = $conn->prepare($query);

    if (!empty($filterNama)) {
        $stmt->bindParam(':filterNama', $filterNama, PDO::PARAM_STR);
    }

    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    // loop through the result of the query and assign it to the $recordData variable
    foreach ($res as $row) {
        $recordData = [
            "kode" => $row['kode'],
            "nama" => $row['nama']
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
