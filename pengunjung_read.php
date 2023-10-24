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
    $filterNamaPengunjung = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterAlamat = isset($requestData['filter']['alamat']) ? $requestData['filter']['alamat'] : "";
    $filterNamaWBP = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $validSortFields = ['nama', 'alamat', 'nama_wbp'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'nama';
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT pengunjung.pengunjung_id, pengunjung.nama AS nama, pengunjung.tempat_lahir,pengunjung.tanggal_lahir,  pengunjung.jenis_kelamin, provinsi.nama_provinsi, kota.nama_kota,
                    pengunjung.alamat, 
                    pengunjung.foto_wajah,
                    wbp_profile.nama AS nama_wbp, 
                    wbp_profile.wbp_profile_id, 
                    pengunjung.provinsi_id, 
                    pengunjung.kota_id, 
                    pengunjung.hubungan_wbp,
                    pengunjung.nik
                FROM pengunjung
                LEFT JOIN provinsi ON pengunjung.provinsi_id = provinsi.provinsi_id
                LEFT JOIN kota ON pengunjung.kota_id = kota.kota_id
                LEFT JOIN wbp_profile ON pengunjung.wbp_profile_id = wbp_profile.wbp_profile_id
                WHERE pengunjung.is_deleted = 0";

    if (!empty($filterNamaPengunjung)) {
        $query .= " AND pengunjung.nama LIKE '%$filterNamaPengunjung%'";
    }
    if (!empty($filterAlamat)) {
        $query .= " AND pengunjung.alamat LIKE '%$filterAlamat%'";
    }
    if (!empty($filterNamaWBP)) {
        $query .= " AND wbp_profile.nama LIKE '%$filterNamaWBP%'";
    }
    $query .= " GROUP BY pengunjung.pengunjung_id";
    $query .= " ORDER BY $sortField $sortOrder";

    // Add pagination
    $countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $totalPages = ceil($totalRecords / $pageSize);
    $start = ($page - 1) * $pageSize;
    $query .= " LIMIT $start, $pageSize";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    foreach ($res as $row) {
        $recordData = [
            "pengunjung_id" => $row['pengunjung_id'],
            "nama" => $row['nama'],
            "tempat_lahir" => $row['tempat_lahir'],
            "tanggal_lahir" => $row['tanggal_lahir'],
            "jenis_kelamin" => $row['jenis_kelamin'],
            "nama_provinsi" => $row['nama_provinsi'],
            "nama_kota" => $row['nama_kota'],
            "provinsi_id" => $row['provinsi_id'],
            "kota_id" => $row['kota_id'],
            "alamat" => $row['alamat'],
            "foto_wajah" => $row['foto_wajah'],
            "nama_wbp" => $row['nama_wbp'],
            "wbp_profile_id" => $row['wbp_profile_id'],
            "hubungan_wbp" => $row['hubungan_wbp'],
            "nik" => $row['nik']
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
