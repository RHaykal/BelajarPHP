<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
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

    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;

    $wbp_profile_id = isset($requestData['filter']['wbp_profile_id']) ? $requestData['filter']['wbp_profile_id'] : "";
    $nama_terpidana = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $nama_sidang = isset($requestData['filter']['nama_sidang']) ? $requestData['filter']['nama_sidang'] : "";
    $nama_pengadilan_militer = isset($requestData['filter']['nama_pengadilan_militer']) ? $requestData['filter']['nama_pengadilan_militer'] : "";
    $query = "SELECT 
    histori_vonis.histori_vonis_id,
    histori_vonis.sidang_id,
    sidang.nama_sidang,
    sidang.pengadilan_militer_id,
    pengadilan_militer.nama_pengadilan_militer,
    sidang.jenis_persidangan_id,
    jenis_persidangan.nama_jenis_persidangan,
    perkara_persidangan_tersangka.wbp_profile_id AS tersangka_wbp_profile_id,
    perkara_persidangan_terdakwa.wbp_profile_id AS terdakwa_wbp_profile_id,
    perkara_persidangan_terpidana.wbp_profile_id AS terpidana_wbp_profile_id,
    wbp_profile.nama AS nama_wbp,
    histori_vonis.hasil_vonis,
    histori_vonis.masa_tahanan_tahun,
    histori_vonis.masa_tahanan_bulan,
    histori_vonis.masa_tahanan_hari,
    histori_vonis.created_at,
    histori_vonis.updated_at   
    FROM histori_vonis
    LEFT JOIN sidang ON sidang.sidang_id = histori_vonis.sidang_id
    LEFT JOIN pengadilan_militer ON pengadilan_militer.pengadilan_militer_id = sidang.pengadilan_militer_id
    LEFT JOIN jenis_persidangan ON jenis_persidangan.jenis_persidangan_id = sidang.jenis_persidangan_id
    LEFT JOIN perkara_persidangan_tersangka ON perkara_persidangan_tersangka.perkara_persidangan_tersangka_id = sidang.perkara_persidangan_tersangka_id
    LEFT JOIN perkara_persidangan_terdakwa ON perkara_persidangan_terdakwa.perkara_persidangan_terdakwa_id = sidang.perkara_persidangan_terdakwa_id
    LEFT JOIN perkara_persidangan_terpidana ON perkara_persidangan_terpidana.perkara_persidangan_terpidana_id = sidang.perkara_persidangan_terpidana_id
    LEFT JOIN wbp_profile ON 
    	CASE WHEN perkara_persidangan_tersangka.wbp_profile_id IS NOT NULL THEN perkara_persidangan_tersangka.wbp_profile_id = wbp_profile.wbp_profile_id ELSE NULL END OR
		CASE WHEN perkara_persidangan_terdakwa.wbp_profile_id IS NOT NULL THEN perkara_persidangan_terdakwa.wbp_profile_id = wbp_profile.wbp_profile_id ELSE NULL END OR
		CASE WHEN perkara_persidangan_terpidana.wbp_profile_id IS NOT NULL THEN perkara_persidangan_terpidana.wbp_profile_id = wbp_profile.wbp_profile_id ELSE NULL END
    WHERE histori_vonis.is_deleted = 0";

    
    if (!empty($nama_terpidana)) {
        $query .= " AND wbp_profile.nama LIKE '%$nama_terpidana%'";
    }

    if (!empty($wbp_profile_id)) {
        $query .= " AND wbp_profile.wbp_profile_id LIKE '%$wbp_profile_id%'";
    }

    if (!empty($nama_sidang)) {
        $query .= " AND sidang.nama_sidang LIKE '%$nama_sidang%'";
    }

    if (!empty($nama_pengadilan_militer)) {
        $query .= " AND pengadilan_militer.nama_pengadilan_militer LIKE '%$nama_pengadilan_militer%'";
    }


    $stmt = $conn->prepare($query);

    $countQuery = "SELECT COUNT(*) AS total FROM ($query) subquery";
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

    if (count($res) > 0) {
        foreach ($res as $row) {
            $recordData[] = [
                'histori_vonis_id' => $row['histori_vonis_id'],
                'sidang_id' => $row['sidang_id'],
                'nama_sidang' => $row['nama_sidang'],
                'pengadilan_militer_id' => $row['pengadilan_militer_id'],
                'nama_pengadilan_militer' => $row['nama_pengadilan_militer'],
                'jenis_persidangan_id' => $row['jenis_persidangan_id'],
                'tersangka_wbp_profile_id' => $row['tersangka_wbp_profile_id'],
                'terdakwa_wbp_profile_id' => $row['terdakwa_wbp_profile_id'],
                'terpidana_wbp_profile_id' => $row['terpidana_wbp_profile_id'],
                'nama_wbp' => $row['nama_wbp'],
                'hasil_vonis' => $row['hasil_vonis'],
                'masa_tahanan_tahun' => $row['masa_tahanan_tahun'],
                'masa_tahanan_bulan' => $row['masa_tahanan_bulan'],
                'masa_tahanan_hari' => $row['masa_tahanan_hari'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];

            $record = $recordData;
        }

        $response = [
            'message' => 'Data successfully retrieved',
            'status' => 'OK',
            'records' => $record,
            "pagination" => [
                "currentPage" => $page,
                "pageSize" => $pageSize,
                "totalRecords" => $totalRecords,
                "totalPages" => $totalPages
            ]
        ];
    } else {
        $response = [
            'message' => 'No matching data found in the table',
            'status' => 'OK',
            'records' => [],
            'totalRecords' => 0,
            'totalPages' => 0,
            'currentPage' => $page
        ];
    }

    echo json_encode($response);
} catch (Exception $e) {
    $response = [
        'message' => $e->getMessage(),
        'status' => 'NO',
        'records' => []
    ];

    echo json_encode($response);
}
?>
