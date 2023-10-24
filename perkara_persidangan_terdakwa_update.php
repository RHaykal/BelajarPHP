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

$perkara_persidangan_terdakwa_id = trim(isset($param_POST->perkara_persidangan_terdakwa_id) ? $param_POST->perkara_persidangan_terdakwa_id : "");
$nama_perkara_persidangan_terdakwa = trim(isset($param_POST->nama_perkara_persidangan_terdakwa) ? $param_POST->nama_perkara_persidangan_terdakwa : "");
$nomor_perkara_persidangan_terdakwa = trim(isset($param_POST->nomor_perkara_persidangan_terdakwa) ? $param_POST->nomor_perkara_persidangan_terdakwa : "");
$wbp_profile_id = trim(isset($param_POST->wbp_profile_id) ? $param_POST->wbp_profile_id : "");
$wbp_perkara_id = trim(isset($param_POST->wbp_perkara_id) ? $param_POST->wbp_perkara_id : "");
$status_perkara_persidangan_terdakwa = trim(isset($param_POST->status_perkara_persidangan_terdakwa) ? $param_POST->status_perkara_persidangan_terdakwa : "");
$tanggal_penetapan_terdakwa = trim(isset($param_POST->tanggal_penetapan_terdakwa) ? $param_POST->tanggal_penetapan_terdakwa : "");
$tanggal_registrasi_terdakwa = trim(isset($param_POST->tanggal_registrasi_terdakwa) ? $param_POST->tanggal_registrasi_terdakwa : "");
$oditur_id = trim(isset($param_POST->oditur_id) ? $param_POST->oditur_id : "");
$lama_proses_persidangan_terdakwa = trim(isset($param_POST->lama_proses_persidangan_terdakwa) ? $param_POST->lama_proses_persidangan_terdakwa : "");
$bap_id = trim(isset($param_POST->bap_id) ? $param_POST->bap_id : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM perkara_persidangan_terdakwa WHERE perkara_persidangan_terdakwa_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $perkara_persidangan_terdakwa_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data not found");
    } else {
        $query3 = "UPDATE perkara_persidangan_terdakwa SET
            nama_perkara_persidangan_terdakwa = ?,
            nomor_perkara_persidangan_terdakwa = ?,
            wbp_profile_id = ?,
            wbp_perkara_id = ?,
            status_perkara_persidangan_terdakwa = ?,
            tanggal_penetapan_terdakwa = ?,
            tanggal_registrasi_terdakwa = ?,
            lama_proses_persidangan_terdakwa = ?,
            bap_id = ?";

        // Create an array to store bind parameters
        $bindParams = [
            $nama_perkara_persidangan_terdakwa,
            $nomor_perkara_persidangan_terdakwa,
            $wbp_profile_id,
            $wbp_perkara_id,
            $status_perkara_persidangan_terdakwa,
            $tanggal_penetapan_terdakwa,
            $tanggal_registrasi_terdakwa,
            $lama_proses_persidangan_terdakwa,
            $bap_id
        ];

        $query3 .= " WHERE perkara_persidangan_terdakwa_id = ?";

        $stmt3 = $conn->prepare($query3);

        // Bind parameters using a loop
        for ($i = 1; $i <= count($bindParams); $i++) {
            $stmt3->bindValue($i, $bindParams[$i - 1], PDO::PARAM_STR);
        }

        $stmt3->bindValue(count($bindParams) + 1, $perkara_persidangan_terdakwa_id, PDO::PARAM_STR);

        $stmt3->execute();

        
        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "perkara_persidangan_terdakwa_id" => $perkara_persidangan_terdakwa_id,
                    "nama_perkara_persidangan_terdakwa" => $nama_perkara_persidangan_terdakwa,
                    "nomor_perkara_persidangan_terdakwa" => $nomor_perkara_persidangan_terdakwa,
                    "wbp_profile_id" => $wbp_profile_id,
                    "wbp_perkara_id" => $wbp_perkara_id,
                    "status_perkara_persidangan_terdakwa" => $status_perkara_persidangan_terdakwa,
                    "tanggal_penetapan_terdakwa" => $tanggal_penetapan_terdakwa,
                    "tanggal_registrasi_terdakwa" => $tanggal_registrasi_terdakwa,
                    "oditur_id" => $oditur_id,
                    "lama_proses_persidangan_terdakwa" => $lama_proses_persidangan_terdakwa,
                    "bap_id" => $bap_id
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
