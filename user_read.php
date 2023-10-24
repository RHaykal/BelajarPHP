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

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);

    tokenAuth($conn, 'superadmin');

    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;

    $filterNama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterLokasiLemasmilId = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterLokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'created_at';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['username', 'nama_lokasi_lemasmil', 'nama_lokasi_otmil', 'created_at', 'last_login', 'role_name', 'is_suspended', 'email', 'phone', 'petugas_id', 'user_role_id','updated_at'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'username'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT user.*, user_role.role_name, user_role.deskripsi_role, lokasi_otmil.nama_lokasi_otmil, lokasi_lemasmil.nama_lokasi_lemasmil, petugas.nama, petugas.jabatan, petugas.divisi, petugas.nrp, matra.nama_matra
    FROM user
    LEFT JOIN user_role ON user.user_role_id = user_role.user_role_id
    LEFT JOIN lokasi_otmil ON user.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON user.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    LEFT JOIN petugas ON petugas.petugas_id = user.petugas_id
    LEFT JOIN matra ON matra.matra_id = petugas.matra_id
    WHERE user.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    if (!empty($filterNama)) {
        $query .= " AND petugas.nama LIKE '%$filterNama%'";
    }

    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }

    if (!empty($filterLokasiLemasmilId)) {
        $query .= " AND lokasi_lemasmil.lokasi_lemasmil_id = '$filterLokasiLemasmilId'";
    }

    if (!empty($filterLokasiOtmilId)) {
        $query .= " AND lokasi_otmil.lokasi_otmil_id = '$filterLokasiOtmilId'";
    }



    $query .= " GROUP BY user.user_id";
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
            "user_id" => $row['user_id'],
            "nama" => $row['nama'],
            "username" => $row['username'],
            "image" => $row['image'],
            "phone" => $row['phone'],
            "email" => $row['email'],
            "is_suspended" => $row['is_suspended'],
            "petugas_id" => $row['petugas_id'],
            "user_role_id" => $row['user_role_id'],
            "role_name" => $row['role_name'],
            "deskripsi_role" => $row['deskripsi_role'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "last_login" => $row['last_login'],
            "jabatan" => $row['jabatan'],
            "divisi" => $row['divisi'],
            "nama_matra" => $row['nama_matra'],
            "nrp" => $row['nrp']
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
