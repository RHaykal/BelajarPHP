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

    tokenAuth($conn, 'operator');

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;
    
    $filterKategoriPerkaraId = isset($requestData['filter']['kategori_perkara_id']) ? $requestData['filter']['kategori_perkara_id'] : "";
    $filterNamaKategoriPerkara = isset($requestData['filter']['nama_kategori_perkara']) ? $requestData['filter']['nama_kategori_perkara'] : "";
    $filterNamaJenisPerkara = isset($requestData['filter']['nama_jenis_perkara']) ? $requestData['filter']['nama_jenis_perkara'] : "";
    $filterPasal = isset($requestData['filter']['pasal']) ? $requestData['filter']['pasal'] : "";
    $filterVonisTahun = isset($requestData['filter']['vonis_tahun_perkara']) ? $requestData['filter']['vonis_tahun_perkara'] : "";
    $filterVonisBulan = isset($requestData['filter']['vonis_bulan_perkara']) ? $requestData['filter']['vonis_bulan_perkara'] : "";
    $filterVonisHari = isset($requestData['filter']['vonis_hari_perkara']) ? $requestData['filter']['vonis_hari_perkara'] : "";


    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_jenis_perkara';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['kategori_perkara_id', 'nama_kategori_perkara', 'nama_jenis_perkara', 'pasal', 'vonis_tahun_perkara', 'vonis_bulan_perkara', 'vonis_hari_perkara'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_jenis_perkara'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT
                jenis_perkara.jenis_perkara_id,
                jenis_perkara.kategori_perkara_id,
                jenis_perkara.nama_jenis_perkara,
                jenis_perkara.pasal,
                jenis_perkara.vonis_tahun_perkara,
                jenis_perkara.vonis_bulan_perkara,
                jenis_perkara.vonis_hari_perkara,
                kategori_perkara.nama_kategori_perkara
            FROM jenis_perkara
            LEFT JOIN kategori_perkara ON jenis_perkara.kategori_perkara_id = kategori_perkara.kategori_perkara_id
            WHERE jenis_perkara.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    
    if(!empty($filterKategoriPerkaraId)){
        $query .= " AND jenis_perkara.kategori_perkara_id LIKE '%$filterKategoriPerkaraId%'";
    }

    if (!empty($filterNamaKategoriPerkara)) {
        $query .= " AND kategori_perkara.nama_kategori_perkara LIKE '%$filterNamaKategoriPerkara%'";
    }

    if (!empty($filterNamaJenisPerkara)) {
        $query .= " AND jenis_perkara.nama_jenis_perkara LIKE '%$filterNamaJenisPerkara%'";
    }

    if (!empty($filterPasal)) {
        $query .= " AND jenis_perkara.pasal LIKE '%$filterPasal%'";
    }

    if (!empty($filterVonisTahun)) {
        $query .= " AND jenis_perkara.vonis_tahun_perkara LIKE '%$filterVonisTahun%'";
    }

    if (!empty($filterVonisBulan)) {
        $query .= " AND jenis_perkara.vonis_bulan_perkara LIKE '%$filterVonisBulan%'";
    }

    if (!empty($filterVonisHari)) {
        $query .= " AND jenis_perkara.vonis_hari_perkara LIKE '%$filterVonisHari%'";
    }

    $query .= " GROUP BY jenis_perkara.jenis_perkara_id";
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
            "jenis_perkara_id" => $row['jenis_perkara_id'],
            "kategori_perkara_id" => $row['kategori_perkara_id'],
            "nama_kategori_perkara" => $row['nama_kategori_perkara'],
            "nama_jenis_perkara" => $row['nama_jenis_perkara'],
            "pasal" => $row['pasal'],
            'vonis_tahun_perkara' => $row['vonis_tahun_perkara'],
            'vonis_bulan_perkara' => $row['vonis_bulan_perkara'],
            'vonis_hari_perkara' => $row['vonis_hari_perkara']
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
