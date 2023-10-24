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
    $filterNama = isset($requestData['filter']['nama_peserta']) ? $requestData['filter']['nama_peserta'] : "";
    $filterNamaKegiatan = isset($requestData['filter']['nama_kegiatan']) ? $requestData['filter']['nama_kegiatan'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";

    
    $filterLokasiLemasmilID = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterLokasiOtmilID = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";


    // Determine the sorting parameters (assuming they are passed as query parameters)
// $sortField = isset($requestData['sortBy']) ? (int) $requestData['sortBy'] : 'waktu_mulai_kegiatan';
// $sortOrder = isset($requestData['sortOrder']) ? (int) $requestData['sortOrder'] : 'waktu_mulai_kegiatan';
$sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'waktu_mulai_kegiatan';
$sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

// Validate and sanitize sortField to prevent SQL injection
$allowedSortFields = ['waktu_mulai_kegiatan', 'nama_kegiatan', 'nama_ruangan_otmil', 'nama_ruangan_lemasmil', 'nama_lokasi_otmil', 'nama_lokasi_lemasmil'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'waktu_mulai_kegiatan'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
}

    // Construct the SQL query with filtering conditions
    $query = "SELECT kegiatan.*, 
    ruangan_otmil.nama_ruangan_otmil, 
    ruangan_otmil.jenis_ruangan_otmil, 
    ruangan_otmil.lokasi_otmil_id, 
    ruangan_lemasmil.nama_ruangan_lemasmil, 
    ruangan_lemasmil.jenis_ruangan_lemasmil, 
    ruangan_lemasmil.lokasi_lemasmil_id, 
    ruangan_otmil.zona_id AS zona_otmil_id,
    ruangan_lemasmil.zona_id AS zona_lemasmil_id,
    zona_otmil.nama_zona AS status_zona_otmil, 
    zona_lemasmil.nama_zona AS status_zona_lemasmil,
    lokasi_otmil.nama_lokasi_otmil,
    lokasi_lemasmil.nama_lokasi_lemasmil,
    kegiatan_wbp.kegiatan_wbp_id, 
    GROUP_CONCAT(kegiatan_wbp.wbp_profile_id) AS list_wbp_id, GROUP_CONCAT(wbp_profile.nama) AS list_nama
    FROM kegiatan 
    LEFT JOIN ruangan_otmil ON ruangan_otmil.ruangan_otmil_id = kegiatan.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON ruangan_lemasmil.ruangan_lemasmil_id = kegiatan.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON lokasi_otmil.lokasi_otmil_id = ruangan_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON lokasi_lemasmil.lokasi_lemasmil_id = ruangan_lemasmil.lokasi_lemasmil_id
    LEFT JOIN kegiatan_wbp ON kegiatan_wbp.kegiatan_id = kegiatan.kegiatan_id
    LEFT JOIN wbp_profile ON wbp_profile.wbp_profile_id = kegiatan_wbp.wbp_profile_id
    LEFT JOIN zona AS zona_otmil ON zona_otmil.zona_id = ruangan_otmil.zona_id
    LEFT JOIN zona AS zona_lemasmil ON zona_lemasmil.zona_id = ruangan_lemasmil.zona_id
    WHERE kegiatan.is_deleted = 0";

    if (!empty($filterNama)) {
        $query .= " AND wbp_profile.nama LIKE '%$filterNama%'";
    }
    if (!empty($filterNamaKegiatan)) {
        $query .= " AND kegiatan.nama_kegiatan LIKE '%$filterNamaKegiatan%'";
    }
    if (!empty($filterNamaRuanganOtmil)) {
        $query .= " AND ruangan_otmil.nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }
    if (!empty($filterNamaRuanganLemasmil)) {
        $query .= " AND ruangan_lemasmil.nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }
    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }
    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }
    if (!empty($filterLokasiLemasmilID)) {
        $query .= " AND lokasi_lemasmil.lokasi_lemasmil_id = '$filterLokasiLemasmilID'";
    }
    if (!empty($filterLokasiOtmilID)) {
        $query .= " AND lokasi_otmil.lokasi_otmil_id = '$filterLokasiOtmilID'";
    }

    $query .= " GROUP BY kegiatan.kegiatan_id";
    
    $query .= " ORDER BY $sortField $sortOrder";


    // Add sorting conditions based on the selected field and order
// $query .= " ORDER BY ";

// // Sort by waktu_mulai_kegiatan
// if ($sortField === 'waktu_mulai_kegiatan') {
//     $query .= "waktu_mulai_kegiatan $sortOrder, ";
// }

// // Sort by nama_kegiatan
// if ($sortField === 'nama_kegiatan') {
//     $query .= "kegiatan.nama_kegiatan $sortOrder, ";
// }

// // Sort by nama_ruangan_otmil
// if ($sortField === 'nama_ruangan_otmil') {
//     $query .= "ruangan_otmil.nama_ruangan_otmil $sortOrder, ";
// }

// // Sort by nama_ruangan_lemasmil
// if ($sortField === 'nama_ruangan_lemasmil') {
//     $query .= "ruangan_lemasmil.nama_ruangan_lemasmil $sortOrder, ";
// }

// // Remove the trailing comma and space
// $query = rtrim($query, ', ');

$countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute();
$totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Apply pagination
$totalPages = ceil($totalRecords / $pageSize);
$start = ($page - 1) * $pageSize;
$query .= " LIMIT $start, $pageSize";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    foreach ($res as $row) {
        $recordData = [
            "kegiatan_id" => $row['kegiatan_id'],
            "nama_kegiatan" => $row['nama_kegiatan'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "status_kegiatan" => $row['status_kegiatan'],
            "waktu_mulai_kegiatan" => $row['waktu_mulai_kegiatan'],
            "waktu_selesai_kegiatan" => $row['waktu_selesai_kegiatan'],
            // "is_deleted" => $row['is_deleted'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
            "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            // "is_deleted" => $row['is_deleted'],
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
            "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            // "is_deleted" => $row['is_deleted'],
            // "kegiatan_wbp_id" => $row['kegiatan_wbp_id'],
            "kegiatan_id" => $row['kegiatan_id'],
            // "list_wbp_id" => $row['list_wbp_id'],
            "status_zona_otmil" => $row['status_zona_otmil'],
            "status_zona_lemasmil" => $row['status_zona_lemasmil']
        ];

        // Inisialisasi list_peserta dalam setiap iterasi
        $list_peserta = [];

        // Menggunakan JSON untuk menyusun data peserta
        $list_wbp_id = explode(',', $row['list_wbp_id']);
        // $list_nama = explode(',', $row['list_nama']);

        if (count($list_wbp_id) == 1 && $list_wbp_id[0] == '')
            unset($list_wbp_id[0]);

        foreach ($list_wbp_id as $index => $wbp_id) {
            $namaquery = "SELECT nama FROM wbp_profile WHERE wbp_profile_id = '$wbp_id'";
            $stmt = $conn->prepare($namaquery);
            $stmt->execute();
            $nama = $stmt->fetch(PDO::FETCH_ASSOC)['nama'];
            
            $list_peserta[] = [
                "wbp_profile_id" => $wbp_id,
                "wbp_nama" => $nama
            ];
        }

        $recordData["peserta"] = $list_peserta;
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
