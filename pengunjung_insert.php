<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');
// require_once('function.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$pengunjung_id = generateUUID();
$nama = trim(isset($param_POST->nama) ? $param_POST->nama : "");
$tempat_lahir = trim(isset($param_POST->tempat_lahir) ? $param_POST->tempat_lahir : "");
$tanggal_lahir =  trim(isset($param_POST->tanggal_lahir) ? $param_POST->tanggal_lahir : "");
$jenis_kelamin = trim(isset($param_POST->jenis_kelamin) ? $param_POST->jenis_kelamin : "");
$provinsi_id = trim(isset($param_POST->provinsi_id) ? $param_POST->provinsi_id : "");
$kota_id = trim(isset($param_POST->kota_id) ? $param_POST->kota_id : "");
$alamat = trim(isset($param_POST->alamat) ? $param_POST->alamat : "");
$foto_wajah = trim(isset($param_POST->foto_wajah) ? $param_POST->foto_wajah : "");
$wbp_profile_id = trim(isset($param_POST->wbp_profile_id) ? $param_POST->wbp_profile_id : "");
$hubungan_wbp = trim(isset($param_POST->hubungan_wbp) ? $param_POST->hubungan_wbp : "");
$nik = trim(isset($param_POST->nik) ? $param_POST->nik : "");
$created_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM pengunjung WHERE 
        nama = ? AND
        tempat_lahir = ? AND
        tanggal_lahir = ? AND
        jenis_kelamin = ? AND
        provinsi_id = ? AND
        kota_id = ? AND
        alamat = ? AND
        wbp_profile_id = ? AND
        hubungan_wbp = ? AND
        nik = ? ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nama, PDO::PARAM_STR);
    $stmt->bindValue(2, $tempat_lahir, PDO::PARAM_STR);
    $stmt->bindValue(3, $tanggal_lahir, PDO::PARAM_STR);
    $stmt->bindValue(4, $jenis_kelamin, PDO::PARAM_STR);
    $stmt->bindValue(5, $provinsi_id, PDO::PARAM_STR);
    $stmt->bindValue(6, $kota_id, PDO::PARAM_STR);
    $stmt->bindValue(7, $alamat, PDO::PARAM_STR);
    $stmt->bindValue(8, $wbp_profile_id, PDO::PARAM_STR);
    $stmt->bindValue(9, $hubungan_wbp, PDO::PARAM_STR);
    $stmt->bindValue(10, $nik, PDO::PARAM_STR);
    
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $query3 = "
            INSERT INTO pengunjung (
                pengunjung_id,
                nama,
                tempat_lahir,
                tanggal_lahir,
                jenis_kelamin,
                provinsi_id,
                kota_id,
                alamat,
                foto_wajah,
                wbp_profile_id,
                hubungan_wbp,
                nik,
                created_at,
                updated_at
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        
        ";

        $filename = md5($nama);
        $photoLocation = '/siram_api/images_pengunjung_data'.'/'.$filename.'.jpg';
        $photoResult = base64_to_jpeg($foto_wajah, $photoLocation);   
        $stmt3 = $conn->prepare($query3);   
        $stmt3->bindValue(1, $pengunjung_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $nama, PDO::PARAM_STR);
        $stmt3->bindValue(3, $tempat_lahir, PDO::PARAM_STR);
        $stmt3->bindValue(4, $tanggal_lahir, PDO::PARAM_STR);
        $stmt3->bindValue(5, $jenis_kelamin, PDO::PARAM_STR);
        $stmt3->bindValue(6, $provinsi_id, PDO::PARAM_STR);
        $stmt3->bindValue(7, $kota_id, PDO::PARAM_STR);
        $stmt3->bindValue(8, $alamat, PDO::PARAM_STR);
        $stmt3->bindValue(9, $photoResult, PDO::PARAM_STR);
        $stmt3->bindValue(10, $wbp_profile_id, PDO::PARAM_STR);
        $stmt3->bindValue(11, $hubungan_wbp, PDO::PARAM_STR);
        $stmt3->bindValue(12, $nik, PDO::PARAM_STR);
        $stmt3->bindValue(13, $created_at, PDO::PARAM_STR);
        $stmt3->bindValue(14, $created_at, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully registered",
            "records" => [
                [
                    "pengunjung_id" => $pengunjung_id,
                    "nama" => $nama,
                    "tempat_lahir" => $tempat_lahir,
                    "tanggal_lahir" => $tanggal_lahir,
                    "jenis_kelamin" => $jenis_kelamin,
                    "provinsi_id" => $provinsi_id,
                    "kota_id" => $kota_id,
                    "alamat" => $alamat,
                    "foto_wajah" => $photoResult,
                    "wbp_profile_id" => $wbp_profile_id,
                    "hubungan_wbp" => $hubungan_wbp,
                    "nik" => $nik,
                    "created_at" => $created_at,
                    "updated_at" => $created_at
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
