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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$wbp_perkara_id = generateUUID();
$kategori_perkara_id = (empty($param_POST->kategori_perkara_id)) ? '' : trim($param_POST->kategori_perkara_id);
$jenis_perkara_id = (empty($param_POST->jenis_perkara_id)) ? '' : trim($param_POST->jenis_perkara_id);
$vonis_tahun = (empty($param_POST->vonis_tahun)) ? '' : trim($param_POST->vonis_tahun);
$vonis_bulan = (empty($param_POST->vonis_bulan)) ? '' : trim($param_POST->vonis_bulan);
$vonis_hari = (empty($param_POST->vonis_hari)) ? '' : trim($param_POST->vonis_hari);
$tanggal_ditahan_otmil = (empty($param_POST->tanggal_ditahan_otmil)) ? '' : trim($param_POST->tanggal_ditahan_otmil);
$tanggal_ditahan_lemasmil = (empty($param_POST->tanggal_ditahan_lemasmil)) ? '' : trim($param_POST->tanggal_ditahan_lemasmil);
$lokasi_otmil_id = (empty($param_POST->lokasi_otmil_id)) ? '' : trim($param_POST->lokasi_otmil_id);
$lokasi_lemasmil_id = (empty($param_POST->lokasi_lemasmil_id)) ? '' : trim($param_POST->lokasi_lemasmil_id);
$residivis = (empty($param_POST->residivis)) ? '' : trim($param_POST->residivis);



$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $querySelect = "SELECT * FROM wbp_perkara 
        WHERE 
        kategori_perkara_id = ? AND
        jenis_perkara_id = ? AND
        vonis_tahun = ? AND
        vonis_bulan = ? AND
        vonis_hari = ? AND
        tanggal_ditahan_otmil = ? AND
        tanggal_ditahan_lemasmil = ? AND
        lokasi_otmil_id = ? AND
        lokasi_lemasmil_id = ? AND
        residivis = ?";

    $stmt = $conn->prepare($querySelect);
    $stmt->execute([ $kategori_perkara_id, $jenis_perkara_id, $vonis_tahun, $vonis_bulan, $vonis_hari, $tanggal_ditahan_otmil, $tanggal_ditahan_lemasmil, $lokasi_otmil_id, $lokasi_lemasmil_id, $residivis]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $queryInsert = "INSERT INTO wbp_perkara (wbp_perkara_id, kategori_perkara_id, jenis_perkara_id, vonis_tahun, vonis_bulan, vonis_hari, tanggal_ditahan_otmil, tanggal_ditahan_lemasmil, lokasi_otmil_id, lokasi_lemasmil_id, residivis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($queryInsert);
        $stmt1->bindParam(1, $wbp_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $kategori_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $jenis_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(4, $vonis_tahun, PDO::PARAM_STR);
        $stmt1->bindParam(5, $vonis_bulan, PDO::PARAM_STR);
        $stmt1->bindParam(6, $vonis_hari, PDO::PARAM_STR);
        $stmt1->bindParam(7, $tanggal_ditahan_otmil, PDO::PARAM_STR);
        $stmt1->bindParam(8, $tanggal_ditahan_lemasmil, PDO::PARAM_STR);
        $stmt1->bindParam(9, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(10, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(11, $residivis, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'wbp_perkara_id' => $wbp_perkara_id,
            'kategori_perkara_id' => $kategori_perkara_id,
            'jenis_perkara_id' => $jenis_perkara_id,
            'vonis_tahun' => $vonis_tahun,
            'vonis_bulan' => $vonis_bulan,
            'vonis_hari' => $vonis_hari,
            'tanggal_ditahan_otmil' => $tanggal_ditahan_otmil,
            'tanggal_ditahan_lemasmil' => $tanggal_ditahan_lemasmil,
            'lokasi_otmil_id' => $lokasi_otmil_id,
            'lokasi_lemasmil_id' => $lokasi_lemasmil_id,
            'residivis' => $residivis]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>