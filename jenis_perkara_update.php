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

$jenis_perkara_id = trim(isset($param_POST->jenis_perkara_id) ? $param_POST->jenis_perkara_id : "");
$kategori_perkara_id = trim(isset($param_POST->kategori_perkara_id) ? $param_POST->kategori_perkara_id : "");
$nama_jenis_perkara = trim(isset($param_POST->nama_jenis_perkara) ? $param_POST->nama_jenis_perkara : "");
$pasal = trim(isset($param_POST->pasal) ? $param_POST->pasal : "");
$vonis_tahun_perkara = trim(isset($param_POST->vonis_tahun_perkara) ? $param_POST->vonis_tahun_perkara : "");
$vonis_bulan_perkara = trim(isset($param_POST->vonis_bulan_perkara) ? $param_POST->vonis_bulan_perkara : "");
$vonis_hari_perkara = trim(isset($param_POST->vonis_hari_perkara) ? $param_POST->vonis_hari_perkara : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM jenis_perkara WHERE jenis_perkara_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $jenis_perkara_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data not found");
    } else {
        $query3 = "UPDATE jenis_perkara set
            kategori_perkara_id = ?,
            nama_jenis_perkara = ?,
            pasal = ?,
            vonis_tahun_perkara = ?,
            vonis_bulan_perkara = ?,
            vonis_hari_perkara = ?
            WHERE jenis_perkara_id = ?
        ";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindParam(1, $kategori_perkara_id, PDO::PARAM_STR);
        $stmt3->bindParam(2, $nama_jenis_perkara, PDO::PARAM_STR);
        $stmt3->bindParam(3, $pasal, PDO::PARAM_STR);
        $stmt3->bindParam(4, $vonis_tahun_perkara, PDO::PARAM_STR);
        $stmt3->bindParam(5, $vonis_bulan_perkara, PDO::PARAM_STR);
        $stmt3->bindParam(6, $vonis_hari_perkara, PDO::PARAM_STR);
        $stmt3->bindParam(7, $jenis_perkara_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "jenis_perkara_id" => $jenis_perkara_id,
                    "kategori_perkara_id" => $kategori_perkara_id,
                    "nama_jenis_perkara" => $nama_jenis_perkara,
                    "pasal" => $pasal,
                    "vonis_tahun_perkara" => $vonis_tahun_perkara,
                    "vonis_bulan_perkara" => $vonis_bulan_perkara,
                    "vonis_hari_perkara" => $vonis_hari_perkara
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
