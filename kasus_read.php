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

    tokenAuth($conn, 'admin');

    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;

    $kasus_id = isset($requestData['filter']['kasus_id']) ? $requestData['filter']['kasus_id'] : '';
    $nomor_kasus = isset($requestData['filter']['nomor_kasus']) ? $requestData['filter']['nomor_kasus'] : '';
    $nama_kasus = isset($requestData['filter']['nama_kasus']) ? $requestData['filter']['nama_kasus'] : '';
    $nrp = isset($requestData['filter']['nrp']) ? $requestData['filter']['nrp'] : '';
    $nama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : '';
    $nama_kategori_perkara = isset($requestData['filter']['nama_saksi']) ? $requestData['filter']['nama_saksi'] : '';
    $nama_jenis_perkara = isset($requestData['filter']['nama_saksi']) ? $requestData['filter']['nama_saksi'] : '';
    $tanggal_registrasi_kasus = isset($requestData['filter']['nama_saksi']) ? $requestData['filter']['nama_saksi'] : '';
    $nama_oditur = isset($requestData['filter']['nama_saksi']) ? $requestData['filter']['nama_saksi'] : '';

    $query = "SELECT kasus.*,
    wbp_profile.nrp,
    wbp_profile.nama,
    kategori_perkara.nama_kategori_perkara,
    jenis_perkara.nama_jenis_perkara,
    oditur.nama_oditur
    FROM kasus 
    LEFT JOIN wbp_profile ON kasus.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN kategori_perkara ON kasus.kategori_perkara_id = kategori_perkara.kategori_perkara_id
    LEFT JOIN jenis_perkara ON kasus.jenis_perkara_id = jenis_perkara.jenis_perkara_id
    LEFT JOIN oditur ON kasus.oditur_id - oditur.oditur_id
    WHERE kasus.is_deleted = 0";

    if (!empty($kasus_id)) {
        $query .= " AND kasus.kasus_id LIKE '%$kasus_id%'";
    }

    if (!empty($nomor_kasus)) {
        $query .= " AND kasus.nomor_kasus LIKE '%$nomor_kasus%'";
    }

    if (!empty($nama_kasus)) {
        $query .= " AND kasus.nama_kasus LIKE '%$nama_kasus%'";
    }

    if (!empty($nrp)) {
        $query .= " AND wbp_profile.nrp LIKE '%$nrp%'";
    }
    
    if (!empty($nama)) {
        $query .= " AND wbp_profile.nama LIKE '%$nama%'";
    }

    if (!empty($nama_kategori_perkara)) {
        $query .= " AND kategori_perkara.nama_kategori_perkara LIKE '%$nama_kategori_perkara%'";
    }
    
    if (!empty($nama_jenis_perkara)) {
        $query .= " AND jenis_perkara.nama_jenis_perkara LIKE '%$nama_jenis_perkara%'";
    }

    if (!empty($tanggal_registrasi_kasus)) {
        $query .= " AND kasus.tanggal_registrasi_kasus LIKE '%$tanggal_registrasi_kasus%'";
    }

    if (!empty($nama_oditur)) {
        $query .= " AND oditur.nama_oditur LIKE '%$nama_oditur%'";
    }


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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $records = [];

    if (count($data) > 0) {
        foreach ($data as $row) {
            $records[] = [
                'kasus_id' => $row['kasus_id'],
                'nama_kasus' => $row['nama_kasus'],
                'nomor_kasus' => $row['nomor_kasus'],
                'wbp_profile_id' => $row['wbp_profile_id'],
                'nrp' => $row['nrp'],
                'nama' => $row['nama'],
                'kategori_perkara_id' => $row['kategori_perkara_id'],
                'nama_kategori_perkara' => $row['nama_kategori_perkara'],
                'jenis_perkara_id' => $row['jenis_perkara_id'],
                'nama_jenis_perkara' => $row['nama_jenis_perkara'],
                'tanggal_registrasi_kasus' => $row['tanggal_registrasi_kasus'],
                'tanggal_penutupan_kasus' => $row['tanggal_penutupan_kasus'],
                'status_kasus_id' => $row['status_kasus_id'],
                'tanggal_mulai_penyidikan' => $row['tanggal_mulai_penyidikan'],
                'tanggal_mulai_sidang' => $row['tanggal_mulai_sidang'],
                'oditur_id' => $row['oditur_id'],
                'nama_oditur' => $row['nama_oditur'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        $response = [
            'status' => 'OK',
            'message' => 'Data berhasil diambil',
            'records' => $records,
            "pagination" => [
                "currentPage" => $page,
                "pageSize" => $pageSize,
                "totalRecords" => $totalRecords,
                "totalPages" => $totalPages
            ]
        ];
    } else {
        $response = [
            'status' => 'No Data',
            'message' => 'Tidak ada data yang ditemukan',
            'records' => []
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => []
    ];
    echo "Connection failed: " . $e->getMessage();
}

$stmt = null;
$conn = null;
?>
