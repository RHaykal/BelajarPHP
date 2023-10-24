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

    $namaAhli = isset($requestData['filter']['nama_ahli']) ? $requestData['filter']['nama_ahli'] : "";
    $bidangAhli = isset($requestData['filter']['bidang_ahli']) ? $requestData['filter']['bidang_ahli'] : "";
    $buktiKeahlian = isset($requestData['filter']['bukti_keahlian']) ? $requestData['filter']['bukti_keahlian'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_ahli';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_ahli', 'bidang_ahli', 'bukti_keahlian'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_ahli'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT *
    FROM ahli
    WHERE is_deleted = 0"; // Ensure that is_deleted is 0

    // checking if the given parameters below are empty or not. If not empty, they will be inserted in the query as filters
    if (!empty($namaAhli)) {
        $query .= " AND nama_ahli LIKE '%$namaAhli%'";
    }
    if (!empty($bidangAhli)) {
        $query .= " AND bidang_ahli LIKE '%$bidangAhli%'";
    }
    if (!empty($buktiKeahlian)) {
        $query .= " AND bukti_keahlian LIKE '%$buktiKeahlian%'";
    }

    $query .= " GROUP BY ahli_id";
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

    // executing the query to fetch all the data with filters and pagination feature
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    // loop through the result of the query and assign it to the $recordData variable
    foreach ($res as $row) {
        $recordData = [
            "ahli_id" => $row['ahli_id'],
            "nama_ahli" => $row['nama_ahli'],
            "bidang_ahli" => $row['bidang_ahli'],
            "bukti_keahlian" => $row['bukti_keahlian']
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
