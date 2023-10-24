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
    $lokasiOtmilId = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : ""; 
    $lokasiLemasmilId = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterRuanganOtmilId = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $filterRuanganLemasmilId = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $filterNamaAktivitasPengunjung = isset($requestData['filter']['nama_aktivitas_pengunjung']) ? $requestData['filter']['nama_aktivitas_pengunjung'] : "";
    $filterWaktuMulaiKunjungan = isset($requestData['filter']['waktu_mulai_kunjungan']) ? $requestData['filter']['waktu_mulai_kunjungan'] : "";
    $filterWaktuSelesaiKunjungan = isset($requestData['filter']['ip_address']) ? $requestData['filter']['ip_address'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganOtmil = isset($requestData['filter']['jenis_ruangan_otmil']) ? $requestData['filter']['jenis_ruangan_otmil'] : "";
    $filterJenisRuanganLemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterTujuanKunjungan = isset($requestData['filter']['tujuan_kunjungan']) ? $requestData['filter']['tujuan_kunjungan'] : "";
    $filterPetugasId = isset($requestData['filter']['petugas_id']) ? $requestData['filter']['petugas_id'] : "";
    $filterNamaPetugas = isset($requestData['filter']['nama_petugas']) ? $requestData['filter']['nama_petugas'] : "";
    $filterPengunjung = isset($requestData['filter']['pengunjung_id']) ? $requestData['filter']['pengunjung_id'] : "";
    $filterNamaPengunjung = isset($requestData['filter']['nama_pengunjung']) ? $requestData['filter']['nama_pengunjung'] : "";
    $filterWbpProfileId = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $filterNamaWbp = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";


    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_aktivitas_pengunjung';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_aktivitas_pengunjung'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_aktivitas_pengunjung'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
        $query = "SELECT DISTINCT
        AP.aktivitas_pengunjung_id,
        AP.nama_aktivitas_pengunjung,
        AP.waktu_mulai_kunjungan,
        AP.waktu_selesai_kunjungan,
        AP.tujuan_kunjungan,
        
         CASE
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.ruangan_lemasmil_id
            ELSE NULL
        END AS ruangan_lemasmil_id,
        CASE
            WHEN AP.ruangan_otmil_id IS NOT NULL THEN AP.ruangan_otmil_id
            ELSE NULL
        END AS ruangan_otmil_id,
        CASE 
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.nama_ruangan_lemasmil
            ELSE NULL
        END AS nama_ruangan_lemasmil,
        CASE 
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.jenis_ruangan_lemasmil
            ELSE NULL
        END AS jenis_ruangan_lemasmil,
        CASE 
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.zona_id
            ELSE NULL
        END AS zona_id_lemasmil,
        CASE 
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN ZL.nama_zona
            ELSE NULL
        END AS status_zona_ruangan_lemasmil,
        CASE 
            WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.nama_ruangan_otmil
            ELSE NULL
        END AS nama_ruangan_otmil,
        CASE 
            WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.jenis_ruangan_otmil
            ELSE NULL
        END AS jenis_ruangan_otmil,
        CASE 
            WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.zona_id
            ELSE NULL
        END AS zona_id_otmil,
        CASE 
            WHEN RO.ruangan_otmil_id IS NOT NULL THEN ZO.nama_zona
            ELSE NULL
        END AS status_zona_ruangan_otmil,
        CASE 
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN RL.lokasi_lemasmil_id
            ELSE NULL
        END AS lokasi_lemasmil_id,
        CASE 
            WHEN RO.ruangan_otmil_id IS NOT NULL THEN RO.lokasi_otmil_id
            ELSE NULL
        END AS lokasi_otmil_id,
        CASE 
            WHEN RL.ruangan_lemasmil_id IS NOT NULL THEN LL.nama_lokasi_lemasmil
            ELSE NULL
        END AS nama_lokasi_lemasmil,
        CASE 
            WHEN RO.ruangan_otmil_id IS NOT NULL THEN LO.nama_lokasi_otmil
            ELSE NULL
        END AS nama_lokasi_otmil,
        -- petugas
        CASE 
            WHEN AP.petugas_id IS NOT NULL THEN AP.petugas_id
            ELSE NULL
        END AS petugas_id,
        CASE 
            WHEN AP.petugas_id IS NOT NULL THEN P.nama
            ELSE NULL
        END AS nama_petugas,
        CASE 
            WHEN AP.pengunjung_id IS NOT NULL THEN PN.pengunjung_id
            ELSE NULL
        END AS pengunjung_id,
        CASE
            WHEN AP.pengunjung_id IS NOT NULL THEN PN.nama
            ELSE NULL
        END AS nama_pengunjung,
        CASE 
            WHEN PN.wbp_profile_id IS NOT NULL THEN WP.wbp_profile_id
            ELSE NULL
        END AS wbp_profile_id,
        CASE 
            WHEN PN.wbp_profile_id IS NOT NULL THEN WP.nama
            ELSE NULL
        END AS nama_wbp
    FROM
        aktivitas_pengunjung AP
    LEFT JOIN ruangan_lemasmil RL ON AP.ruangan_lemasmil_id = RL.ruangan_lemasmil_id
    LEFT JOIN ruangan_otmil RO ON AP.ruangan_otmil_id = RO.ruangan_otmil_id
    LEFT JOIN lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
    LEFT JOIN lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
    LEFT JOIN zona ZO ON RO.zona_id = ZO.zona_id
    LEFT JOIN zona ZL ON RL.zona_id = ZL.zona_id
    LEFT JOIN petugas P ON AP.petugas_id = P.petugas_id
    LEFT JOIN pengunjung PN ON AP.pengunjung_id = PN.pengunjung_id
    LEFT JOIN wbp_profile WP ON PN.wbp_profile_id = WP.wbp_profile_id
    WHERE
        AP.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    
    if (!empty($lokasiOtmilId)) {
        $query .= " AND RO.lokasi_otmil_id = '$lokasiOtmilId'";
    }

    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND LO.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }

    if (!empty($lokasiLemasmilId)) {
        $query .= " AND RL.lokasi_lemasmil_id = '$lokasiLemasmilId'";
    }

    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND LL.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }

    if (!empty($filterRuanganOtmilId)) {
        $query .= " AND RO.ruangan_otmil_id = '$filterRuanganOtmilId'";
    }

    if (!empty($filterRuanganLemasmilId)) {
        $query .= " AND RL.ruangan_lemasmil_id = '$filterRuanganLemasmilId'";
    }

    if (!empty($filterNamaAktivitasPengunjung)) {
        $query .= " AND AP.nama_aktivitas_pengunjung LIKE '%$filterNamaAktivitasPengunjung%'";
    }

    if (!empty($filterWaktuMulaiKunjungan)) {
        $query .= " AND AP.waktu_mulai_kunjungan LIKE '%$filterWaktuMulaiKunjungan%'";
    }

    if (!empty($filterWaktuSelesaiKunjungan)) {
        $query .= " AND AP.waktu_selesai_kunjungan LIKE '%$filterWaktuSelesaiKunjungan%'";
    }

    if (!empty($filterNamaRuanganOtmil)) {
        $query .= " AND RO.nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }

    if (!empty($filterNamaRuanganLemasmil)) {
        $query .= " AND RL.nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }


    if (!empty($filterJenisRuanganOtmil)) {
        $query .= " AND RO.jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
    }

    if (!empty($filterJenisRuanganLemasmil)) {
        $query .= " AND RL.jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
    }

    if (!empty($filterTujuanKunjungan)) {
        $query .= " AND AP.tujuan_kunjungan LIKE '%$filterTujuanKunjungan%'";
    }

    if (!empty($filterPetugasId)) {
        $query .= " AND AP.petugas_id LIKE '%$filterPetugasId%'";
    }

    if (!empty($filterNamaPetugas)) {
        $query .= " AND P.nama LIKE '%$filterNamaPetugas%'";
    }

    if (!empty($filterPengunjung)) {
        $query .= " AND AP.pengunjung_id LIKE '%$filterPengunjung%'";
    }

    if (!empty($filterNamaPengunjung)) {
        $query .= " AND PN.nama LIKE '%$filterNamaPengunjung%'";
    }

    if (!empty($filterWbpProfileId)) {
        $query .= " AND PN.wbp_profile_id LIKE '%$filterWbpProfileId%'";
    }

    if (!empty($filterNamaWbp)) {
        $query .= " AND WP.nama LIKE '%$filterNamaWbp%'";
    }


    $query .= " GROUP BY AP.aktivitas_pengunjung_id";
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
            "aktivitas_pengunjung_id" => $row['aktivitas_pengunjung_id'],
            "nama_aktivitas_pengunjung" => $row['nama_aktivitas_pengunjung'],
            "waktu_mulai_kunjungan" => $row['waktu_mulai_kunjungan'],
            "waktu_selesai_kunjungan" => $row['waktu_selesai_kunjungan'],
            "tujuan_kunjungan" => $row['tujuan_kunjungan'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
            "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
            "zona_id_otmil" => $row['zona_id_otmil'],
            "status_zona_ruangan_otmil" => $row['status_zona_ruangan_otmil'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],  
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],      
            "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
            "zona_id_lemasmil" => $row['zona_id_lemasmil'],
            "status_zona_ruangan_lemasmil" => $row['status_zona_ruangan_lemasmil'],
            "petugas_id" => $row['petugas_id'],
            "nama_petugas" => $row['nama_petugas'],
            "pengunjung_id" => $row['pengunjung_id'],
            "nama_pengunjung" => $row['nama_pengunjung'],
            "wbp_profile_id" => $row['wbp_profile_id'],
            "nama_wbp" => $row['nama_wbp']
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
