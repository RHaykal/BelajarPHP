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

    $bap_id = isset($requestData['filter']['bap_id']) ? $requestData['filter']['bap_id'] : '';
    $nomor_kasus = isset($requestData['filter']['nomor_kasus']) ? $requestData['filter']['nomor_kasus'] : '';
    $nama_kasus = isset($requestData['filter']['nama_kasus']) ? $requestData['filter']['nama_kasus'] : '';
    $nrp = isset($requestData['filter']['nrp']) ? $requestData['filter']['nrp'] : '';
    $nama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : '';
    $nama_saksi = isset($requestData['filter']['nama_saksi']) ? $requestData['filter']['nama_saksi'] : '';

    $query = "SELECT bap.*,
    penyidikan.wbp_profile_id,
    penyidikan.saksi_id,
    penyidikan.kasus_id,
    penyidikan.alasan_penyidikan,
    penyidikan.lokasi_penyidikan,
    penyidikan.waktu_penyidikan,
    penyidikan.agenda_penyidikan,
    penyidikan.hasil_penyidikan,
    wbp_profile.nrp,
    wbp_profile.nama,
    saksi.nama_saksi,
    saksi.keterangan,
    saksi.no_kontak,
    kasus.nomor_kasus,
    kasus.nama_kasus,
    dokumen_bap.nama_dokumen_bap,
    dokumen_bap.link_dokumen_bap
    FROM bap
    LEFT JOIN penyidikan ON bap.penyidikan_id = penyidikan.penyidikan_id
    LEFT JOIN wbp_profile ON penyidikan.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN saksi ON penyidikan.saksi_id = saksi.saksi_id
    LEFT JOIN kasus ON penyidikan.kasus_id = kasus.kasus_id
    LEFT JOIN dokumen_bap ON bap.dokumen_bap_id = dokumen_bap.dokumen_bap_id
    WHERE bap.is_deleted = 0";

    if (!empty($bap_id)) {
        $query .= " AND bap.bap_id LIKE '%$bap_id%'";
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

    if (!empty($nama_saksi)) {
        $query .= " AND saksi.nama_saksi LIKE '%$nama_saksi%'";
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
                'bap_id' => $row['bap_id'],
                'penyidikan_id' => $row['penyidikan_id'],
                'alasan_penyidikan' => $row['alasan_penyidikan'],
                'lokasi_penyidikan' => $row['lokasi_penyidikan'],
                'waktu_penyidikan' => $row['waktu_penyidikan'],
                'agenda_penyidikan' => $row['agenda_penyidikan'],
                'hasil_penyidikan' => $row['hasil_penyidikan'],
                'nrp' => $row['nrp'],
                'nama' => $row['nama'],
                'nama_saksi' => $row['nama_saksi'],
                'keterangan' => $row['keterangan'],
                'kasus_id' => $row['kasus_id'],
                'nomor_kasus' => $row['nomor_kasus'],
                'nama_kasus' => $row['nama_kasus'],
                'dokumen_bap_id' => $row['dokumen_bap_id'],
                'nama_dokumen_bap' => $row['nama_dokumen_bap'],
                'link_dokumen_bap' => $row['link_dokumen_bap'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'is_deleted' => $row['is_deleted']
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
