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

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$jenis_perkara_id = generateUUID();
$kategori_perkara_id = (empty($param_POST->kategori_perkara_id)) ? '' : trim($param_POST->kategori_perkara_id);
$nama_jenis_perkara = (empty($param_POST->nama_jenis_perkara)) ? '' : trim($param_POST->nama_jenis_perkara);
$pasal = (empty($param_POST->pasal)) ? '' : trim($param_POST->pasal);
$vonis_tahun_perkara = (empty($param_POST->vonis_tahun_perkara)) ? '' : trim($param_POST->vonis_tahun_perkara);
$vonis_bulan_perkara = (empty($param_POST->vonis_bulan_perkara)) ? '' : trim($param_POST->vonis_bulan_perkara);
$vonis_hari_perkara = (empty($param_POST->vonis_hari_perkara)) ? '' : trim($param_POST->vonis_hari_perkara);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM jenis_perkara WHERE jenis_perkara_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$jenis_perkara_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT into jenis_perkara 
                    (
                        jenis_perkara_id,
                        kategori_perkara_id,
                        nama_jenis_perkara,
                        pasal,
                        vonis_tahun_perkara,
                        vonis_bulan_perkara,
                        vonis_hari_perkara
                    )
                    VALUES(?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $jenis_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $kategori_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $nama_jenis_perkara, PDO::PARAM_STR);
        $stmt1->bindParam(4, $pasal, PDO::PARAM_STR);
        $stmt1->bindParam(5, $vonis_tahun_perkara, PDO::PARAM_STR);
        $stmt1->bindParam(6, $vonis_bulan_perkara, PDO::PARAM_STR);
        $stmt1->bindParam(7, $vonis_hari_perkara, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            "jenis_perkara_id" => $jenis_perkara_id,
            "kategori_perkara_id" => $kategori_perkara_id,
            "nama_jenis_perkara" => $nama_jenis_perkara,
            "pasal" => $pasal,
            "vonis_tahun_perkara" => $vonis_tahun_perkara,
            "vonis_bulan_perkara" => $vonis_bulan_perkara,
            "vonis_hari_perkara" => $vonis_hari_perkara
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
