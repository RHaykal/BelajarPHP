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
    
    $filterNamaWBP = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterNamaPerkara = isset($requestData['filter']['nama_jenis_perkara']) ? $requestData['filter']['nama_jenis_perkara'] : "";
    $filterHasilPenyidikan = isset($requestData['filter']['hasil_penyidikan']) ? $requestData['filter']['hasil_penyidikan'] : "";
    $filterLamaMasaTahanan = isset($requestData['filter']['lama_masa_tahanan']) ? $requestData['filter']['lama_masa_tahanan'] : "";


    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : '';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama', 'nama_jenis_perkara', 'hasil_penyidikan', 'lama_masa_tahanan'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT histori_penyidikan.*,
    wbp_profile.nama,
    jenis_perkara.nama_jenis_perkara
    FROM histori_penyidikan
    LEFT JOIN penyidikan ON penyidikan.penyidikan_id = histori_penyidikan.penyidikan_id
    LEFT JOIN wbp_profile ON penyidikan.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN wbp_perkara ON penyidikan.wbp_perkara_id = wbp_perkara.wbp_perkara_id
    LEFT JOIN jenis_perkara ON wbp_perkara.jenis_perkara_id = jenis_perkara.jenis_perkara_id
    WHERE histori_penyidikan.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    
    if (!empty($filterNamaWBP)) {
        $query .= " AND wbp_profile.nama LIKE '%$filterNamaWBP%'";
    }

    if (!empty($filterNamaPerkara)) {
        $query .= " AND jenis_perkara.nama_jenis_perkara LIKE '%$filterNamaPerkara%'";
    }

    if (!empty($filterHasilPenyidikan)) {
        $query .= " AND histori_penyidikan.hasil_penyidikan LIKE '%$filterHasilPenyidikan%'";
    }

    if (!empty($filterLamaMasaTahanan)) {
        $query .= " AND histori_penyidikan.lama_masa_tahanan LIKE '%$filterLamaMasaTahanan%'";
    }

    $query .= " GROUP BY histori_penyidikan.histori_penyidikan_id";
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
            "histori_penyidikan_id" => $row['histori_penyidikan_id'],
            "penyidikan_id" => $row['penyidikan_id'],
            "nama_wbp" => $row['nama'],
            "nama_jenis_perkara" => $row['nama_jenis_perkara'],
            "hasil_penyidikan" => $row['hasil_penyidikan'],
            "lama_masa_tahanan" => $row['lama_masa_tahanan']
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
