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
    $filterNama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterNamaPangkat = isset($requestData['filter']['nama_pangkat']) ? $requestData['filter']['nama_pangkat'] : "";
    $filterNamaKesatuan = isset($requestData['filter']['nama_kesatuan']) ? $requestData['filter']['nama_kesatuan'] : "";
    $filterNamaLokasiKesatuan = isset($requestData['filter']['nama_lokasi_kesatuan']) ? $requestData['filter']['nama_lokasi_kesatuan'] : "";
    $filterJabatan = isset($requestData['filter']['jabatan']) ? $requestData['filter']['jabatan'] : "";
    $filterDivisi = isset($requestData['filter']['divisi']) ? $requestData['filter']['divisi'] : "";
    $filterNomorPetugas = isset($requestData['filter']['nomor_petugas']) ? $requestData['filter']['nomor_petugas'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterPetugasGrupId = isset($requestData['filter']['grup_petugas_id']) ? $requestData['filter']['grup_petugas_id'] : "";
    $filterNamaGrupPetugas = isset($requestData['filter']['nama_grup_petugas']) ? $requestData['filter']['nama_grup_petugas'] : "";
    $filterNamaMatra = isset($requestData['filter']['nama_matra']) ? $requestData['filter']['nama_matra'] : "";
    $filterNRP = isset($requestData['filter']['nrp']) ? $requestData['filter']['nrp'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $validSortFields = ['nama', 'nama_pangkat', 'nama_kesatuan', 'nama_lokasi_kesatuan', 'nama_lokasi_otmil', 'nama_lokasi_lemasmil'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'nama';
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT petugas.*, pangkat.nama_pangkat,
    kesatuan.nama_kesatuan,lokasi_kesatuan.nama_lokasi_kesatuan,
    provinsi.nama_provinsi, kota.nama_kota, agama.nama_agama,
    status_kawin.nama_status_kawin, pendidikan.nama_pendidikan,
    bidang_keahlian.nama_bidang_keahlian,grup_petugas.nama_grup_petugas,
    matra.nama_matra,
 CASE 
    WHEN lokasi_otmil.nama_lokasi_otmil IS NOT NULL THEN
    CONCAT('Otmil ', lokasi_otmil.nama_lokasi_otmil)
    ELSE 
    CONCAT('Lemasmil ', lokasi_lemasmil.nama_lokasi_lemasmil)
END AS lokasi_tugas

FROM petugas
LEFT JOIN lokasi_otmil ON petugas.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
LEFT JOIN lokasi_lemasmil ON petugas.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
LEFT JOIN pangkat ON petugas.pangkat_id = pangkat.pangkat_id
LEFT JOIN kesatuan ON petugas.kesatuan_id = kesatuan.kesatuan_id
LEFT JOIN lokasi_kesatuan ON kesatuan.lokasi_kesatuan_id = lokasi_kesatuan.lokasi_kesatuan_id
LEFT JOIN provinsi ON petugas.provinsi_id = provinsi.provinsi_id
LEFT JOIN kota ON petugas.kota_id = kota.kota_id
LEFT JOIN agama ON petugas.agama_id = agama.agama_id
LEFT JOIN status_kawin ON petugas.status_kawin_id = status_kawin.status_kawin_id
LEFT JOIN pendidikan ON petugas.pendidikan_id = pendidikan.pendidikan_id
LEFT JOIN bidang_keahlian ON petugas.bidang_keahlian_id = bidang_keahlian.bidang_keahlian_id
LEFT JOIN grup_petugas ON petugas.grup_petugas_id = grup_petugas.grup_petugas_id
LEFT JOIN matra ON matra.matra_id = petugas.matra_id
WHERE petugas.is_deleted = 0";


    if (!empty($filterNama)) {
        $query .= " AND petugas.nama LIKE '%$filterNama%'";
    }
    if (!empty($filterNamaPangkat)) {
        $query .= " AND pangkat.nama_pangkat LIKE '%$filterNamaPangkat%'";
    }
    if (!empty($filterNamaKesatuan)) {
        $query .= " AND kesatuan.nama_kesatuan LIKE '%$filterNamaKesatuan%'";
    }
    if (!empty($filterNamaLokasiKesatuan)) {
        $query .= " AND lokasi_kesatuan.nama_lokasi_kesatuan LIKE '%$filterNamaLokasiKesatuan%'";
    }
    if (!empty($filterJabatan)) {
        $query .= " AND petugas.jabatan LIKE '%$filterJabatan%'";
    }
    if (!empty($filterDivisi)) {
        $query .= " AND petugas.divisi LIKE '%$filterDivisi%'";
    }
    if (!empty($filterNomorPetugas)) {
        $query .= " AND petugas.nomor_petugas LIKE '%$filterNomorPetugas%'";
    }
    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }
    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }
    if (!empty($filterPetugasGrupId)) {
        $query .= " AND petugas.grup_petugas_id LIKE '%$filterPetugasGrupId%'";
    }
    if (!empty($filterNamaGrupPetugas)) {
        $query .= " AND grup_petugas.nama_grup_petugas LIKE '%$filterNamaGrupPetugas%'";
    }
    if (!empty($filterNamaMatra)) {
        $query .= " AND matra.nama_matra = $filterNamaMatra";
    }
    if (!empty($filterNRP)) {
        $query .= " AND petugas.nrp LIKE '%$filterNRP%'";
    }


    $query .= " GROUP BY petugas.petugas_id";
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
            "petugas_id" => $row['petugas_id'],
            "nrp" => $row['nrp'],
            "nama" => $row['nama'],
            "pangkat_id" => $row['pangkat_id'],
            "nama_pangkat" => $row['nama_pangkat'],
            "kesatuan_id" => $row['kesatuan_id'],
            "nama_kesatuan" => $row['nama_kesatuan'],
            "nama_lokasi_kesatuan" => $row['nama_lokasi_kesatuan'],
            "tempat_lahir" => $row['tempat_lahir'],
            "tanggal_lahir" => $row['tanggal_lahir'],
            "jenis_kelamin" => $row['jenis_kelamin'],
            "provinsi_id" => $row['provinsi_id'],
            "nama_provinsi" => $row['nama_provinsi'],
            "kota_id" => $row['kota_id'],
            "nama_kota" => $row['nama_kota'],
            "alamat" => $row['alamat'],
            "agama_id" => $row['agama_id'],
            "nama_agama" => $row['nama_agama'],
            "status_kawin_id" => $row['status_kawin_id'],
            "nama_status_kawin" => $row['nama_status_kawin'],
            "pendidikan_id" => $row['pendidikan_id'],
            "nama_pendidikan" => $row['nama_pendidikan'],
            "bidang_keahlian_id" => $row['bidang_keahlian_id'],
            "nama_bidang_keahlian" => $row['nama_bidang_keahlian'],
            "jabatan" => $row['jabatan'],
            "divisi" => $row['divisi'],
            "nomor_petugas" => $row['nomor_petugas'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "lokasi_tugas" => $row['lokasi_tugas'],
            "foto_wajah" => $row['foto_wajah'],
            "grup_petugas_id" => $row['grup_petugas_id'],
            "nama_grup_petugas" => $row['nama_grup_petugas'],
            "matra_id" => $row['matra_id'],
            "nama_matra" => $row['nama_matra']
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
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];

    echo json_encode($result);
}

$stmt = null;
$conn = null;
?>
