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

$perkara_persidangan_tersangka_id = generateUUID();
$nama_perkara_persidangan_tersangka = (empty($param_POST->nama_perkara_persidangan_tersangka)) ? '' : trim($param_POST->nama_perkara_persidangan_tersangka);
$nomor_perkara_persidangan_tersangka = (empty($param_POST->nomor_perkara_persidangan_tersangka)) ? '' : trim($param_POST->nomor_perkara_persidangan_tersangka);
$wbp_profile_id = (empty($param_POST->wbp_profile_id)) ? '' : trim($param_POST->wbp_profile_id);
$wbp_perkara_id = (empty($param_POST->wbp_perkara_id)) ? '' : trim($param_POST->wbp_perkara_id);
$status_perkara_persidangan_tersangka = (empty($param_POST->status_perkara_persidangan_tersangka)) ? '' : trim($param_POST->status_perkara_persidangan_tersangka);
$tanggal_penetapan_tersangka = (empty($param_POST->tanggal_penetapan_tersangka)) ? '' : trim($param_POST->tanggal_penetapan_tersangka);
$tanggal_registrasi_tersangka = (empty($param_POST->tanggal_registrasi_tersangka)) ? '' : trim($param_POST->tanggal_registrasi_tersangka);
$oditur_id = (empty($param_POST->oditur_id)) ? '' : trim($param_POST->oditur_id);
$lama_proses_persidangan_tersangka = (empty($param_POST->lama_proses_persidangan_tersangka)) ? '' : trim($param_POST->lama_proses_persidangan_tersangka);
$bap_id = (empty($param_POST->bap_id)) ? '' : trim($param_POST->bap_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM perkara_persidangan_tersangka PPTS
        WHERE 
        PPTS.nama_perkara_persidangan_tersangka = ? AND 
        PPTS.nomor_perkara_persidangan_tersangka = ? AND
        PPTS.wbp_profile_id = ? AND 
        PPTS.wbp_perkara_id = ? AND 
        PPTS.status_perkara_persidangan_tersangka = ? AND 
        PPTS.tanggal_penetapan_tersangka = ? AND 
        PPTS.tanggal_registrasi_tersangka = ? AND
        PPTS.oditur_id = ? AND
        PPTS.lama_proses_persidangan_tersangka = ? AND
        PPTS.bap_id = ?
        ";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $nama_perkara_persidangan_tersangka, 
        $nomor_perkara_persidangan_tersangka, 
        $wbp_profile_id, 
        $wbp_perkara_id, 
        $status_perkara_persidangan_tersangka, 
        $tanggal_penetapan_tersangka, 
        $tanggal_registrasi_tersangka,
        $oditur_id,
        $lama_proses_persidangan_tersangka,
        $bap_id
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";

    } else {

        $query1 = "INSERT INTO perkara_persidangan_tersangka (
            perkara_persidangan_tersangka_id, 
            nama_perkara_persidangan_tersangka, 
            nomor_perkara_persidangan_tersangka, 
            wbp_profile_id, 
            wbp_perkara_id, 
            status_perkara_persidangan_tersangka, 
            tanggal_penetapan_tersangka, 
            tanggal_registrasi_tersangka,
            oditur_id,
            lama_proses_persidangan_tersangka,
            bap_id
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $perkara_persidangan_tersangka_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_perkara_persidangan_tersangka, PDO::PARAM_STR);
        $stmt1->bindParam(3, $nomor_perkara_persidangan_tersangka, PDO::PARAM_STR);
        $stmt1->bindParam(4, $wbp_profile_id, PDO::PARAM_STR);
        $stmt1->bindParam(5, $wbp_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(6, $status_perkara_persidangan_tersangka, PDO::PARAM_STR);
        $stmt1->bindParam(7, $tanggal_penetapan_tersangka, PDO::PARAM_STR);
        $stmt1->bindParam(8, $tanggal_registrasi_tersangka, PDO::PARAM_STR);
        $stmt1->bindParam(9, $oditur_id, PDO::PARAM_STR);
        $stmt1->bindParam(10, $lama_proses_persidangan_tersangka, PDO::PARAM_STR);
        $stmt1->bindParam(11, $bap_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'perkara_persidangan_tersangka_id' => $perkara_persidangan_tersangka_id,
            'nama_perkara_persidangan_tersangka' => $nama_perkara_persidangan_tersangka,
            'nomor_perkara_persidangan_tersangka' => $nomor_perkara_persidangan_tersangka,
            'wbp_profile_id' => $wbp_profile_id,
            'wbp_perkara_id' => $wbp_perkara_id,
            'status_perkara_persidangan_tersangka' => $status_perkara_persidangan_tersangka,
            'tanggal_penetapan_tersangka' => $tanggal_penetapan_tersangka,
            'tanggal_registrasi_tersangka' => $tanggal_registrasi_tersangka,
            'oditur_id' => $oditur_id,
            'lama_proses_persidangan_tersangka' => $lama_proses_persidangan_tersangka,
            "bap_id" => $bap_id
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
