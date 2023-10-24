<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, PUT, GET, OPTIONS"); // Add PUT method
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_PUT = json_decode(file_get_contents("php://input"));
} else {
    $param_PUT = $_POST;
}

$pengunjung_id = trim(isset($param_PUT->pengunjung_id) ? $param_PUT->pengunjung_id : "");
$nama = trim(isset($param_PUT->nama) ? $param_PUT->nama : "");
$tempat_lahir = trim(isset($param_PUT->tempat_lahir) ? $param_PUT->tempat_lahir : "");
$tanggal_lahir = trim(isset($param_PUT->tanggal_lahir) ? $param_PUT->tanggal_lahir : "");
$jenis_kelamin = trim(isset($param_PUT->jenis_kelamin) ? $param_PUT->jenis_kelamin : "");
$provinsi_id = trim(isset($param_PUT->provinsi_id) ? $param_PUT->provinsi_id : "");
$kota_id = trim(isset($param_PUT->kota_id) ? $param_PUT->kota_id : "");
$alamat = trim(isset($param_PUT->alamat) ? $param_PUT->alamat : "");
$foto_wajah = trim(isset($param_PUT->foto_wajah) ? $param_PUT->foto_wajah : "");
$wbp_profile_id = trim(isset($param_PUT->wbp_profile_id) ? $param_PUT->wbp_profile_id : "");
$hubungan_wbp = trim(isset($param_PUT->hubungan_wbp) ? $param_PUT->hubungan_wbp : "");
$nik = trim(isset($param_PUT->nik) ? $param_PUT->nik : "");
$updated_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record with the given pengunjung_id exists
    $check_query = "SELECT * FROM pengunjung WHERE pengunjung_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$pengunjung_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Update the record
        $update_query = "UPDATE pengunjung SET
            is_deleted = 1,
            updated_at = ?
            WHERE pengunjung_id = ?";

        $update_stmt = $conn->prepare($update_query);
     
        $update_stmt->bindParam(1, $updated_at, PDO::PARAM_STR);
        $update_stmt->bindParam(2, $pengunjung_id, PDO::PARAM_STR);
        $update_stmt->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully deleted the record with pengunjung_id = $pengunjung_id",
            "records" => [
                [
              
                ]
            ]
        ];
    }
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>
