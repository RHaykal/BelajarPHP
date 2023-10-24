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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} 
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$pengadilan_militer_id = trim(isset($param_POST->pengadilan_militer_id) ? $param_POST->pengadilan_militer_id : "");
$nama_pengadilan_militer = trim(isset($param_POST->nama_pengadilan_militer) ? $param_POST->nama_pengadilan_militer : "");
$provinsi_id = trim(isset($param_POST->provinsi_id) ? $param_POST->provinsi_id : "");
$kota_id = trim(isset($param_POST->kota_id) ? $param_POST->kota_id : "");
$latitude = trim(isset($param_POST->latitude) ? $param_POST->latitude : "");
$longitude = trim(isset($param_POST->longitude) ? $param_POST->longitude : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM pengadilan_militer WHERE pengadilan_militer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $pengadilan_militer_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data not found");
    } else {
        $query3 = "UPDATE pengadilan_militer SET
            nama_pengadilan_militer = ?,
            provinsi_id = ?,
            kota_id = ?,
            latitude = ?,
            longitude = ?";

        // Create an array to store bind parameters
        $bindParams = [
            $nama_pengadilan_militer,
            $provinsi_id,
            $kota_id,
            $latitude,
            $longitude
        ];

        $query3 .= " WHERE pengadilan_militer_id = ?";

        $stmt3 = $conn->prepare($query3);

        // Bind parameters using a loop
        for ($i = 1; $i <= count($bindParams); $i++) {
            $stmt3->bindValue($i, $bindParams[$i - 1], PDO::PARAM_STR);
        }

        $stmt3->bindValue(count($bindParams) + 1, $pengadilan_militer_id, PDO::PARAM_STR);

        $stmt3->execute();

        
        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    'pengadilan_militer_id' => $pengadilan_militer_id,
                    'nama_pengadilan_militer' => $nama_pengadilan_militer,
                    'provinsi_id' => $provinsi_id,
                    'kota_id' => $kota_id,
                    'latitude' => $latitude,
                    'longitude' => $longitude
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
