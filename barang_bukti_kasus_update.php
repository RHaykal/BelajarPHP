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
} else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$barang_bukti_kasus_id = trim(isset($param_POST['barang_bukti_kasus_id']) ? $param_POST['barang_bukti_kasus_id'] : "");
$kasus_id = trim(isset($param_POST['kasus_id']) ? $param_POST['kasus_id'] : "");
$nama_bukti_kasus = trim(isset($param_POST['nama_bukti_kasus']) ? $param_POST['nama_bukti_kasus'] : "");
$nomor_barang_bukti = trim(isset($param_POST['nomor_barang_bukti']) ? $param_POST['nomor_barang_bukti'] : "");
// $dokumen_barang_bukti = trim(isset($param_POST['dokumen_barang_bukti']) ? $param_POST['dokumen_barang_bukti'] : "");
$gambar_barang_bukti = trim(isset($param_POST['gambar_barang_bukti']) ? $param_POST['gambar_barang_bukti'] : "");
$keterangan = trim(isset($param_POST['keterangan']) ? $param_POST['keterangan'] : "");
$tanggal_diambil = trim(isset($param_POST['tanggal_diambil']) ? $param_POST['tanggal_diambil'] : "");

$result = ['status' => 'OK', 'message' => 'Update data successfully', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record exists
    $query = "SELECT * FROM barang_bukti_kasus WHERE barang_bukti_kasus_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $barang_bukti_kasus_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) === 0) {
        throw new Exception("Aset not found");
    } else {
        // The record exists, so proceed with the update
        $query3 = "UPDATE barang_bukti_kasus SET
            kasus_id = ?,
            nama_bukti_kasus = ?,
            nomor_barang_bukti = ?,
            -- dokumen_barang_bukti = ?,
            keterangan = ?,
            tanggal_diambil = ?";

        $bindParams = [$kasus_id, $nama_bukti_kasus, $nomor_barang_bukti, $keterangan, $tanggal_diambil];

        if (strpos($gambar_barang_bukti, 'data:image/') === 0) {
            $query3 .= ", image = ?";
            $filename = md5($nama_bukti_kasus . time());
            $photoLocation = '/siram_api/images_barang_bukti_kasus' . '/' . $filename . '.jpg';
            $photoResult = base64_to_jpeg($gambar_barang_bukti, $photoLocation);
            $bindParams[] = $photoResult;
        }

        $query3 .= " WHERE barang_bukti_kasus_id = ?";

        $stmt3 = $conn->prepare($query3);

        for ($i = 1; $i <= count($bindParams); $i++) {
            $stmt3->bindValue($i, $bindParams[$i - 1], PDO::PARAM_STR);
        }
        $stmt3->bindValue(count($bindParams) + 1, $barang_bukti_kasus_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result['records'][] = [
            "barang_bukti_kasus_id" => $barang_bukti_kasus_id,
            "kasus_id" => $kasus_id,
            "nama_bukti_kasus" => $nama_bukti_kasus,
            "nomor_barang_bukti" => $nomor_barang_bukti,
            // "dokumen_barang_bukti" => $dokumen_barang_bukti,
            "keterangan" => $keterangan,
            "tanggal_diambil" => $tanggal_diambil,
            // "image" => $image
        ];
    }
} catch (Exception $e) {
    $result = [
        "status" => "Error",
        "message" => $e->getMessage()
    ];
}

echo json_encode($result);
?>
