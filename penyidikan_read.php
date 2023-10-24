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
    $filterNamaWbp = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";
    $filterNamaKasus = isset($requestData['filter']['nama_kasus']) ? $requestData['filter']['nama_kasus'] : "";
    $filterNamaJenisPerkara = isset($requestData['filter']['nama_jenis_perkara']) ? $requestData['filter']['nama_jenis_perkara'] : "";
    $filterNamaKategoriPerkara = isset($requestData['filter']['nama_kategori_perkara']) ? $requestData['filter']['nama_kategori_perkara'] : "";
    $filterAlasanPenyidikan = isset($requestData['filter']['alasan_penyidikan']) ? $requestData['filter']['alasan_penyidikan'] : "";
    $filterLokasiPenyidikan = isset($requestData['filter']['lokasi_penyidikan']) ? $requestData['filter']['lokasi_penyidikan'] : "";
    $filterAgendaPenyidikan = isset($requestData['filter']['agenda_penyidikan']) ? $requestData['filter']['agenda_penyidikan'] : "";
    $filterHasilPenyidikan = isset($requestData['filter']['hasil_penyidikan']) ? $requestData['filter']['hasil_penyidikan'] : "";


    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'agenda_penyidikan';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_wbp', 'nama_kasus', 'nama_jenis_perkara', 'nama_kategori_perkara', 'alasan_penyidikan', 'lokasi_penyidikan', 'agenda_penyidikan', 'hasil_penyidikan'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'agenda_penyidikan'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT    penyidikan.penyidikan_id,
                        penyidikan.wbp_profile_id,
                        penyidikan.kasus_id,
                        penyidikan.alasan_penyidikan,
                        penyidikan.lokasi_penyidikan,
                        penyidikan.waktu_penyidikan,
                        penyidikan.agenda_penyidikan,
                        penyidikan.hasil_penyidikan,
                        penyidikan.created_at,
                        penyidikan.updated_at,
                        penyidikan.is_deleted,
                        wbp_profile.nama as nama_wbp,
                        kasus.jenis_perkara_id,
                        kasus.kategori_perkara_id,
                        jenis_perkara.nama_jenis_perkara,
                        kategori_perkara.nama_kategori_perkara
            FROM penyidikan
            LEFT JOIN wbp_profile ON penyidikan.wbp_profile_id = wbp_profile.wbp_profile_id
            LEFT JOIN kasus ON penyidikan.kasus_id = kasus.kasus_id
            LEFT JOIN jenis_perkara ON kasus.jenis_perkara_id = jenis_perkara.jenis_perkara_id
            LEFT JOIN kategori_perkara ON kasus.kategori_perkara_id = kategori_perkara.kategori_perkara_id
            WHERE penyidikan.is_deleted = 0"; // Ensure that is_deleted is 0

    // checking if the given parameters below are empty or not. If not empty, they will be inserted in the query as filters
    if (!empty($filterNamaWbp)) {
        $query .= " AND wbp_profile.nama LIKE '%$filterNamaWbp%'";
    }

    if (!empty($filterNamaKasus)) {
        $query .= " AND kasus.nama_kasus LIKE '%$filterNamaKasus%'";
    }

    if (!empty($filterNamaJenisPerkara)) {
        $query .= " AND jenis_perkara.nama_jenis_perkara LIKE '%$filterNamaJenisPerkara%'";
    }

    if (!empty($filterNamaKategoriPerkara)) {
        $query .= " AND kategori_perkara.nama_kategori_perkara LIKE '%$filterNamaKategoriPerkara%'";
    }

    if (!empty($filterAlasanPenyidikan)) {
        $query .= " AND penyidikan.alasan_penyidikan LIKE '%$filterAlasanPenyidikan%'";
    }

    if (!empty($filterLokasiPenyidikan)) {
        $query .= " AND penyidikan.lokasi_penyidikan LIKE '%$filterLokasiPenyidikan%'";
    }

    if (!empty($filterAgendaPenyidikan)) {
        $query .= " AND penyidikan.agenda_penyidikan LIKE '%$filterAgendaPenyidikan%'";
    }

    if (!empty($filterHasilPenyidikan)) {
        $query .= " AND penyidikan.hasil_penyidikan LIKE '%$filterHasilPenyidikan%'";
    }

    $query .= " GROUP BY penyidikan.penyidikan_id";
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

        $penyidikan_id = $row['penyidikan_id'];

        //pivot penyidikan jaksa
        $pivot_penyidikan_jaksa = "SELECT
        pivot_penyidikan_jaksa.jaksa_penyidik_id,
        jaksa_penyidik.nip,
        jaksa_penyidik.nama_jaksa,
        pivot_penyidikan_jaksa.role_ketua,
        jaksa_penyidik.alamat
        FROM pivot_penyidikan_jaksa
        LEFT JOIN jaksa_penyidik ON pivot_penyidikan_jaksa.jaksa_penyidik_id = jaksa_penyidik.jaksa_penyidik_id
        WHERE pivot_penyidikan_jaksa.penyidikan_id = :penyidikan_id
        AND pivot_penyidikan_jaksa.penyidikan_id IS NOT NULL
        AND (pivot_penyidikan_jaksa.jaksa_penyidik_id IS NOT NULL OR pivot_penyidikan_jaksa.jaksa_penyidik_id != '')";

        $stmt_pivot_penyidikan_jaksa = $conn->prepare($pivot_penyidikan_jaksa);
        $stmt_pivot_penyidikan_jaksa->bindParam(':penyidikan_id', $penyidikan_id, PDO::PARAM_STR);
        $stmt_pivot_penyidikan_jaksa->execute();
        $pivot_penyidikan_jaksa_res = $stmt_pivot_penyidikan_jaksa->fetchAll(PDO::FETCH_ASSOC);


        //pivot penyidikan saksi
        $pivot_penyidikan_saksi = "SELECT
        pivot_penyidikan_saksi.saksi_id,
        saksi.nama_saksi,
        saksi.no_kontak,
        saksi.alamat,
        saksi.keterangan
        FROM pivot_penyidikan_saksi
        LEFT JOIN saksi ON pivot_penyidikan_saksi.saksi_id = saksi.saksi_id
        WHERE pivot_penyidikan_saksi.penyidikan_id = :penyidikan_id
        AND pivot_penyidikan_saksi.penyidikan_id IS NOT NULL
        AND (pivot_penyidikan_saksi.saksi_id IS NOT NULL OR pivot_penyidikan_saksi.saksi_id != '')";

        $stmt_pivot_penyidikan_saksi = $conn->prepare($pivot_penyidikan_saksi);
        $stmt_pivot_penyidikan_saksi->bindParam(':penyidikan_id', $penyidikan_id, PDO::PARAM_STR);
        $stmt_pivot_penyidikan_saksi->execute();
        $pivot_penyidikan_saksi_res = $stmt_pivot_penyidikan_saksi->fetchAll(PDO::FETCH_ASSOC);

        // histori penyidikan
        $histori_penyidikan = "SELECT histori_penyidikan.histori_penyidikan_id,
        histori_penyidikan.hasil_penyidikan,
        histori_penyidikan.lama_masa_tahanan
        FROM histori_penyidikan
        WHERE histori_penyidikan.penyidikan_id = :penyidikan_id
        AND histori_penyidikan.penyidikan_id IS NOT NULL OR histori_penyidikan.penyidikan_id != ''";

        $stmt_histori_penyidikan = $conn->prepare($histori_penyidikan);
        $stmt_histori_penyidikan->bindParam(':penyidikan_id', $penyidikan_id, PDO::PARAM_STR);
        $stmt_histori_penyidikan->execute();
        $histori_penyidikan_res = $stmt_histori_penyidikan->fetchAll(PDO::FETCH_ASSOC);


        $recordData = [
            "penyidikan_id" => $row['penyidikan_id'],
            "wbp_profile_id" => $row['wbp_profile_id'],
            "kasus_id" => $row['kasus_id'],
            "alasan_penyidikan" => $row['alasan_penyidikan'],
            "lokasi_penyidikan" => $row['lokasi_penyidikan'],
            "waktu_penyidikan" => $row['waktu_penyidikan'],
            "agenda_penyidikan" => $row['agenda_penyidikan'],
            "hasil_penyidikan" => $row['hasil_penyidikan'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "nama_wbp" => $row['nama_wbp'],
            "jenis_perkara_id" => $row['jenis_perkara_id'],
            "nama_jenis_perkara" => $row['nama_jenis_perkara'],
            "kategori_perkara_id" => $row['kategori_perkara_id'],
            "nama_kategori_perkara" => $row['nama_kategori_perkara'],
            "jaksa_penyidik" => isset($pivot_penyidikan_jaksa_res) ? $pivot_penyidikan_jaksa_res : null,
            "saksi" => isset($pivot_penyidikan_saksi_res) ? $pivot_penyidikan_saksi_res : null,
            "histori_penyidikan" => isset($histori_penyidikan_res) ? $histori_penyidikan_res : null
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
