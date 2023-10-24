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
    
    $filterNamaAset = isset($requestData['filter']['nama_aset']) ? $requestData['filter']['nama_aset'] : "";
    $filterNamaTipeAset = isset($requestData['filter']['nama_tipe']) ? $requestData['filter']['nama_tipe'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterKondisi = isset($requestData['filter']['kondisi']) ? $requestData['filter']['kondisi'] : "";
    $filterTanggalMasuk = isset($requestData['filter']['tanggal_masuk']) ? $requestData['filter']['tanggal_masuk'] : "";
    $filterSerialNumber = isset($requestData['filter']['serial_number']) ? $requestData['filter']['serial_number'] : "";
    $filterModel = isset($requestData['filter']['model']) ? $requestData['filter']['model'] : "";
    $filterMerek = isset($requestData['filter']['merek']) ? $requestData['filter']['merek'] : "";


    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_aset';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_aset', 'nama_tipe', 'nama_ruangan_otmil', 'nama_ruangan_lemasmil', 'kondisi', 'tanggal_masuk'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_aset'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT aset.*, 
    tipe_aset.nama_tipe,
    ruangan_otmil.nama_ruangan_otmil,
    ruangan_lemasmil.nama_ruangan_lemasmil,
    ruangan_otmil.jenis_ruangan_otmil,
    ruangan_lemasmil.jenis_ruangan_lemasmil,
    ruangan_otmil.zona_id AS zona_otmil_id,
    ruangan_lemasmil.zona_id AS zona_lemasmil_id, 
    zona_otmil.nama_zona AS status_zona_otmil, 
    zona_lemasmil.nama_zona AS status_zona_lemasmil 
FROM aset
LEFT JOIN tipe_aset ON tipe_aset.tipe_aset_id = aset.tipe_aset_id
LEFT JOIN ruangan_otmil ON ruangan_otmil.ruangan_otmil_id = aset.ruangan_otmil_id
LEFT JOIN ruangan_lemasmil ON ruangan_lemasmil.ruangan_lemasmil_id = aset.ruangan_lemasmil_id
LEFT JOIN zona AS zona_otmil ON zona_otmil.zona_id = ruangan_otmil.zona_id
LEFT JOIN zona AS zona_lemasmil ON zona_lemasmil.zona_id = ruangan_lemasmil.zona_id
WHERE aset.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    
    if (!empty($filterNamaAset)) {
        $query .= " AND aset.nama_aset LIKE '%$filterNamaAset%'";
    }

    if (!empty($filterNamaTipeAset)) {
        $query .= " AND tipe_aset.nama_tipe LIKE '%$filterNamaTipeAset%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $query .= " AND ruangan_otmil.nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $query .= " AND ruangan_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }

    if (!empty($filterKondisi)) {
        $query .= " AND aset.kondisi LIKE '%$filterKondisi%'";
    }

    if (!empty($filterTanggalMasuk)) {
        $query .= " AND aset.tanggal_masuk LIKE '%$filterTanggalMasuk%'";
    }

    if (!empty($filterSerialNumber)) {
        $query .= " AND aset.serial_number LIKE '%$filterSerialNumber%'";
    }

    if (!empty($filterModel)) {
        $query .= " AND aset.model LIKE '%$filterModel%'";
    }

    if (!empty($filterMerek)) {
        $query .= " AND aset.merek LIKE '%$filterMerek%'";
    }

    $query .= " GROUP BY aset.aset_id";
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
            "aset_id" => $row['aset_id'],
            "nama_aset" => $row['nama_aset'],
            "tipe_aset_id" => $row['tipe_aset_id'],
            "nama_tipe" => $row['nama_tipe'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
            "kondisi" => $row['kondisi'],
            "tanggal_masuk" => $row['tanggal_masuk'],
            "foto_barang" => $row['image'],
            "keterangan" => $row['keterangan'],
            "updated_at" => $row['updated_at'],
            "status_zona_otmil" => $row['status_zona_otmil'],
            "status_zona_lemasmil" => $row['status_zona_lemasmil'],
            "serial_number" => $row['serial_number'],
            "model" => $row['model'],
            "merek" => $row['merek'],
            "garansi" => $row['garansi']
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