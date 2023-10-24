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
    $filterNomorPenyidikan = isset($requestData['filter']['nomor_penyidikan']) ? $requestData['filter']['nomor_penyidikan'] : "";
    $filterNamaJaksa = isset($requestData['filter']['nama_jaksa']) ? $requestData['filter']['nama_jaksa'] : "";
    $filterNamaWBP = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterNRP = isset($requestData['filter']['nrp']) ? $requestData['filter']['nrp'] : "";
    $filterNamaKasus = isset($requestData['filter']['nama_kasus']) ? $requestData['filter']['nama_kasus'] : "";
    $filterNomorKasus = isset($requestData['filter']['nomor_kasus']) ? $requestData['filter']['nomor_kasus'] : "";
    $filterNamaSaksi = isset($requestData['filter']['nama_saksi']) ? $requestData['filter']['nama_saksi'] : "";



    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'agenda_penyidikan';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_jaksa', 'nomor_penyidikan'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nomor_penyidikan'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT pivot_penyidikan_jaksa.pivot_penyidikan_jaksa_id,
    penyidikan.*,
    wbp_profile.nrp,
    kasus.nomor_kasus,
    kasus.nama_kasus,
    wbp_profile.nama,
    lokasi_otmil.nama_lokasi_otmil,
    lokasi_lemasmil.nama_lokasi_lemasmil,
    saksi.nama_saksi,
    pivot_penyidikan_jaksa.role_ketua,
    jaksa_penyidik.jaksa_penyidik_id,
    jaksa_penyidik.nip,
    jaksa_penyidik.nama_jaksa,
    jaksa_penyidik.alamat
    FROM pivot_penyidikan_jaksa 
    LEFT JOIN penyidikan ON pivot_penyidikan_jaksa.penyidikan_id = penyidikan.penyidikan_id
    LEFT JOIN jaksa_penyidik ON pivot_penyidikan_jaksa.jaksa_penyidik_id = jaksa_penyidik.jaksa_penyidik_id
    LEFT JOIN wbp_profile ON penyidikan.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN hunian_wbp_otmil ON wbp_profile.hunian_wbp_otmil_id = hunian_wbp_otmil.hunian_wbp_otmil_id
    LEFT JOIN lokasi_otmil ON hunian_wbp_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN hunian_wbp_lemasmil ON wbp_profile.hunian_wbp_lemasmil_id = hunian_wbp_lemasmil.hunian_wbp_lemasmil_id
    LEFT JOIN lokasi_lemasmil ON hunian_wbp_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    LEFT JOIN saksi ON penyidikan.saksi_id = saksi.saksi_id
    LEFT JOIN kasus ON penyidikan.kasus_id = kasus.kasus_id
    WHERE pivot_penyidikan_jaksa.is_deleted = 0"; // Ensure that is_deleted is 0

    // checking if the given parameters below are empty or not. If not empty, they will be inserted in the query as filters
    if (!empty($filterNomorPenyidikan)) {
        $query .= " AND penyidikan.nomor_penyidikan LIKE '%$filterNomorPenyidikan%'";
    }

    if (!empty($filterNamaJaksa)) {
        $query .= " AND penyidikan.nama_jaksa LIKE '%$filterNamaJaksa%'";
    }

    if (!empty($filterNamaWBP)) {
        $query .= " AND wbp_profile.nama LIKE '%$filterNamaWBP%'";
    }

    if (!empty($filterNRP)) {
        $query .= " AND wbp_profile.nrp LIKE '%$filterNRP%'";
    }

    if (!empty($filterNamaKasus)) {
        $query .= " AND kasus.nama_kasus LIKE '%$filterNamaKasus%'";
    }

    if (!empty($filterNomorKasus)) {
        $query .= " AND kasus.nomor_kasus LIKE '%$filterNomorKasus%'";
    }

    if (!empty($filterNamaSaksi)) {
        $query .= " AND saksi.nama_saksi LIKE '%$filterNamaSaksi%'";
    }

    $query .= " GROUP BY pivot_penyidikan_jaksa.pivot_penyidikan_jaksa_id";
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
            "pivot_penyidikan_jaksa_id" => $row['pivot_penyidikan_jaksa_id'],
            "penyidikan_id" => $row['penyidikan_id'],
            "wbp_profile_id" => $row['wbp_profile_id'],
            "nrp_wbp" => $row['nrp'],
            "nama_wbp" => $row['nama'],
            "saksi_id" => $row['saksi_id'],
            "nama_saksi" => $row['nama_saksi'],
            "kasus_id" => $row['kasus_id'],
            "nomor_kasus" => $row['nomor_kasus'],
            "nama_kasus" => $row['nama_kasus'],
            "alasan_penyidikan" => $row['alasan_penyidikan'],
            "lokasi_penyidikan" => $row['lokasi_penyidikan'],
            "waktu_penyidikan" => $row['waktu_penyidikan'],
            "agenda_penyidikan" => $row['agenda_penyidikan'],
            "hasil_penyidikan" => $row['hasil_penyidikan'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "role_ketua" => $row['role_ketua'],
            "jaksa_penyidik_id" => $row['jaksa_penyidik_id'],
            "nama_jaksa" => $row['nama_jaksa'],
            "alamat" => $row['alamat']
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
