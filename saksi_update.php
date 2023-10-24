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


if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$saksi_id = isset($param_POST->saksi_id) ? trim($param_POST->saksi_id) : "";
$nama_saksi = isset($param_POST->nama_saksi) ? trim($param_POST->nama_saksi) : "";
$no_kontak = isset($param_POST->no_kontak) ? trim($param_POST->no_kontak) : "";
$alamat = isset($param_POST->alamat) ? trim($param_POST->alamat) : "";
$keterangan = isset($param_POST->keterangan) ? trim($param_POST->keterangan) : "";
$kasus_id = isset($param_POST->kasus_id) ? trim($param_POST->kasus_id) : "";
$updated_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM saksi WHERE saksi_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $saksi_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data Hakim not found");
    } else {
        $query3 = "UPDATE saksi SET
            nama_saksi = ?,
            no_kontak = ?,
            alamat = ?,
            keterangan = ?,
            kasus_id = ?
            WHERE saksi_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $nama_saksi, PDO::PARAM_STR);
        $stmt3->bindValue(2, $no_kontak, PDO::PARAM_STR);
        $stmt3->bindValue(3, $alamat, PDO::PARAM_STR);
        $stmt3->bindValue(4, $keterangan, PDO::PARAM_STR);
        $stmt3->bindValue(5, $kasus_id, PDO::PARAM_STR);
        $stmt3->bindValue(6, $saksi_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "saksi_id" => $saksi_id,
                    "nama_saksi" => $nama_saksi,
                    "no_kontak" => $no_kontak,
                    "alamat" => $alamat,
                    "keterangan" => $keterangan,
                    "kasus_id" => $kasus_id
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
