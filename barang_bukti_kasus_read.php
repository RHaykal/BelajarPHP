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

    $barang_bukti_kasus_id = isset($requestData['filter']['barang_bukti_kasus_id']) ? $requestData['filter']['barang_bukti_kasus_id'] : "";
    $kasus_id = isset($requestData['filter']['kasus_id']) ? $requestData['filter']['kasus_id'] : "";
    $nama_kasus = isset($requestData['filter']['nama_kasus']) ? $requestData['filter']['nama_kasus'] : "";
    $nama_bukti_kasus = isset($requestData['filter']['nama_bukti_kasus']) ? $requestData['filter']['nama_bukti_kasus'] : "";
    $nomor_barang_bukti = isset($requestData['filter']['nomor_barang_bukti']) ? $requestData['filter']['nomor_barang_bukti'] : "";
    $tanggal_diambil = isset($requestData['filter']['tanggal_diambil']) ? $requestData['filter']['tanggal_diambil'] : "";

    $query = "SELECT
    BBK.barang_bukti_kasus_id,
    BBK.kasus_id,
    K.nama_kasus,
    BBK.nama_bukti_kasus,
    BBK.nomor_barang_bukti,
    BBK.dokumen_barang_bukti,
    BBK.gambar_barang_bukti,
    BBK.keterangan,
    BBK.tanggal_diambil
    FROM barang_bukti_kasus BBK
    LEFT JOIN kasus K ON K.kasus_id = BBK.kasus_id
    WHERE BBK.is_deleted = 0";

    if (!empty($barang_bukti_kasus_id)) {
        $query .= " AND BBK.barang_bukti_kasus_id = '$barang_bukti_kasus_id'";
    }

    if (!empty($kasus_id)) {
        $query .= " AND BBK.kasus_id = '$kasus_id'";
    }

    if (!empty($nama_kasus)) {
        $query .= " AND K.nama_kasus LIKE '%$nama_kasus%'";
    }

    if (!empty($nama_bukti_kasus)) {
        $query .= " AND BBK.nama_bukti_kasus LIKE '%$nama_bukti_kasus%'";
    }

    if (!empty($nomor_barang_bukti)) {
        $query .= " AND BBK.nomor_barang_bukti LIKE '%$nomor_barang_bukti%'";
    }

    if (!empty($tanggal_diambil)) {
        $query .= " AND BBK.tanggal_diambil = '$tanggal_diambil'";
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
                "barang_bukti_kasus_id" => $row['barang_bukti_kasus_id'],
                "kasus_id" => $row['kasus_id'],
                "nama_kasus" => $row['nama_kasus'],
                "nama_bukti_kasus" => $row['nama_bukti_kasus'],
                "nomor_barang_bukti" => $row['nomor_barang_bukti'],
                "dokumen_barang_bukti" => $row['dokumen_barang_bukti'],
                "gambar_barang_bukti" => $row['gambar_barang_bukti'],
                "keterangan" => $row['keterangan'],
                "tanggal_diambil" => $row['tanggal_diambil']
            ];
            $record[] = $data;
        }
        $response = [
            "status" => "OK",
            "message" => "Data berhasil diambil",
            "records" => $record,
            "pagination" => [
                "currentPage" => $page,
                "pageSize" => $pageSize,
                "totalPages" => $totalPages,
                "totalRecords" => $totalRecords,
            ],
        ];
    }
    echo json_encode($response);

} catch (PDOException $e) {
    $response = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
    echo json_encode($response);
} finally {
    $stmt = null;
    $conn = null;

}
