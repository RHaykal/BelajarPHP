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
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $requestData = json_decode(file_get_contents("php://input"), true);

    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1; 
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10; 

    
    $start = ($page - 1) * $pageSize;

    // filters
    $penilaian_kegiatan_wbp_id = isset($requestData['filter']['penilaian_kegiatan_wbp_id']) ? $requestData['filter']['penilaian_kegiatan_wbp_id'] : "";
    $wbp_profile_id = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $nama_wbp = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";
    $nama_kegiatan = isset($requestData['filter']['nama_kegiatan']) ? $requestData['filter']['nama_kegiatan'] : "";
    
    $ruangan_otmil_id = isset($requestData['filter']['ruangan_otmil_id']) ? $requestData['filter']['ruangan_otmil_id'] : "";
    $nama_ruangan_otmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $lokasi_otmil_id = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";
    $nama_lokasi_otmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    
    $ruangan_lemasmil_id = isset($requestData['filter']['ruangan_lemasmil_id']) ? $requestData['filter']['ruangan_lemasmil_id'] : "";
    $nama_ruangan_lemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $lokasi_lemasmil_id = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $nama_lokasi_lemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    


    $query = "SELECT 
        PKW.penilaian_kegiatan_wbp_id,
        PKW.wbp_profile_id,
        PKW.kegiatan_id,
        PKW.absensi,
        PKW.durasi,
        PKW.nilai,
        WP.nama AS nama_wbp,
        K.nama_kegiatan AS nama_kegiatan,
        K.ruangan_otmil_id AS ruangan_otmil_id,
        K.ruangan_lemasmil_id AS ruangan_lemasmil_id,
        RO.nama_ruangan_otmil AS nama_ruangan_otmil,
        RO.jenis_ruangan_otmil AS jenis_ruangan_otmil,
        RO.lokasi_otmil_id AS lokasi_otmil_id,
        LO.nama_lokasi_otmil AS nama_lokasi_otmil,
        RL.nama_ruangan_lemasmil AS nama_ruangan_lemasmil,
        RL.jenis_ruangan_lemasmil AS jenis_ruangan_lemasmil,
        RL.lokasi_lemasmil_id AS lokasi_lemasmil_id,
        LL.nama_lokasi_lemasmil AS nama_lokasi_lemasmil
    FROM penilaian_kegiatan_wbp PKW
    LEFT JOIN wbp_profile WP ON PKW.wbp_profile_id = WP.wbp_profile_id
    LEFT JOIN kegiatan K ON PKW.kegiatan_id = K.kegiatan_id
    LEFT JOIN ruangan_otmil RO ON RO.ruangan_otmil_id = K.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil RL ON RL.ruangan_lemasmil_id = K.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil LO ON RO.lokasi_otmil_id = LO.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil LL ON RL.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
    WHERE PKW.is_deleted = 0";

    // filters
    if (!empty($penilaian_kegiatan_wbp_id)) {
        $query .= " AND PKW.penilaian_kegiatan_wbp_id LIKE '%$penilaian_kegiatan_wbp_id%'";
    }
    if (!empty($wbp_profile_id)) {
        $query .= " AND WP.wbp_profile_id LIKE '%$wbp_profile_id%'";
    }
    if (!empty($nama_wbp)) {
        $query .= " AND WP.nama LIKE '%$nama_wbp%'";
    }
    if (!empty($nama_kegiatan)) {
        $query .= " AND K.nama_kegiatan LIKE '%$nama_kegiatan%'";
    }
    
    if (!empty($ruangan_otmil_id)) {
        $query .= " AND RO.ruangan_otmil_id LIKE '%$ruangan_otmil_id%'";
    }
    if (!empty($nama_ruangan_otmil)) {
        $query .= " AND RO.nama_ruangan_otmil LIKE '%$nama_ruangan_otmil%'";
    }
    if (!empty($lokasi_otmil_id)) {
        $query .= " AND LO.lokasi_otmil_id LIKE '%$lokasi_otmil_id%'";
    }
    if (!empty($nama_lokasi_otmil)) {
        $query .= " AND LO.nama_lokasi_otmil LIKE '%$nama_lokasi_otmil%'";
    }

    if (!empty($ruangan_lemasmil_id)) {
        $query .= " AND RL.ruangan_lemasmil_id LIKE '%$ruangan_lemasmil_id%'";
    }
    if (!empty($nama_ruangan_lemasmil)) {
        $query .= " AND RL.nama_ruangan_lemasmil LIKE '%$nama_ruangan_lemasmil%'";
    }
    if (!empty($lokasi_lemasmil_id)) {
        $query .= " AND LL.lokasi_lemasmil_id LIKE '%$lokasi_lemasmil_id%'";
    }
    if (!empty($nama_lokasi_lemasmil)) {
        $query .= " AND LL.nama_lokasi_lemasmil LIKE '%$nama_lokasi_lemasmil%'";
    }



    $countQuery = "SELECT COUNT(*) as total FROM ($query) as countQuery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC);
    $totalRecords = $totalRecords['total'];

    
    $totalPages = ceil($totalRecords / $pageSize);

    $query .= " LIMIT $start, $pageSize";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $record = [];
    if (count($res) > 0) {
        foreach ($res as $row) {
            $data = [
                "penilaian_kegiatan_wbp_id" => $row['penilaian_kegiatan_wbp_id'],
                "wbp_profile_id" => $row['wbp_profile_id'],
                "nama_wbp" => $row['nama_wbp'],
                "kegiatan_id" => $row['kegiatan_id'],
                "nama_kegiatan" => $row['nama_kegiatan'],
                "ruangan_otmil_id" => $row['ruangan_otmil_id'],
                "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
                "lokasi_otmil_id" => $row['lokasi_otmil_id'],
                "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],


                "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
                "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
                "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
                "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
                "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
                "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],


                "absensi" => $row['absensi'],
                "durasi" => $row['durasi'],
                "nilai" => $row['nilai']
            ];
            $record[] = $data;
        }
        $response = [
            "status" => "OK",
            "message" => "Data Successfully Fetched",
            "data" => $record,
            "pagination" => [
                "currentPage" => $page,
                "pageSize" => $pageSize,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
            ],
        ];
    } else {
        $response = [
            "status" => "NO",
            "message" => "No Data Found",
            "data" => []
        ];
    }

    echo json_encode($response); // Mengonversi respons ke format JSON
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "data" => []
    ];

    echo json_encode($result); // Mengonversi respons ke format JSON dalam kasus kesalahan
} finally {
    $stmt = null;
    $conn = null;
}
?>
