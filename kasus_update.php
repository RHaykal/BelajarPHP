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
} 
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$kasus_id = trim(isset($param_POST->kasus_id) ? $param_POST->kasus_id : "");
$nama_kasus = trim(isset($param_POST->nama_kasus) ? $param_POST->nama_kasus : "");
$nomor_kasus = trim(isset($param_POST->nomor_kasus) ? $param_POST->nomor_kasus : "");
$wbp_profile_id = trim(isset($param_POST->wbp_profile_id) ? $param_POST->wbp_profile_id : "");
$kategori_perkara_id = trim(isset($param_POST->kategori_perkara_id) ? $param_POST->kategori_perkara_id : "");
$jenis_perkara_id = trim(isset($param_POST->jenis_perkara_id) ? $param_POST->jenis_perkara_id : "");
$tanggal_registrasi_kasus = trim(isset($param_POST->tanggal_registrasi_kasus) ? $param_POST->tanggal_registrasi_kasus : "");
$tanggal_penutupan_kasus = trim(isset($param_POST->tanggal_penutupan_kasus) ? $param_POST->tanggal_penutupan_kasus : "");
$status_kasus_id = trim(isset($param_POST->status_kasus_id) ? $param_POST->status_kasus_id : "");
$tanggal_mulai_penyidikan = trim(isset($param_POST->tanggal_mulai_penyidikan) ? $param_POST->tanggal_mulai_penyidikan : "");
$tanggal_mulai_sidang = trim(isset($param_POST->tanggal_mulai_sidang) ? $param_POST->tanggal_mulai_sidang : "");
$oditur_id = trim(isset($param_POST->oditur_id) ? $param_POST->oditur_id : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM kasus WHERE kasus_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $kasus_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("kasus not found");
    } else {
        $query3 = "UPDATE kasus SET
            nama_kasus = ?,
            nomor_kasus = ?,
            wbp_profile_id = ?,
            kategori_perkara_id = ?,
            jenis_perkara_id = ?,
            tanggal_registrasi_kasus = ?,
            tanggal_penutupan_kasus = ?,
            status_kasus_id = ?,
            tanggal_mulai_penyidikan = ?,
            tanggal_mulai_sidang = ?,
            oditur_id = ?";

        // Create an array to store bind parameters
        $bindParams = [
            $nama_kasus,
            $nomor_kasus,
            $wbp_profile_id,
            $kategori_perkara_id,
            $jenis_perkara_id,
            $tanggal_registrasi_kasus,
            $tanggal_penutupan_kasus,
            $status_kasus_id,
            $tanggal_mulai_penyidikan,
            $tanggal_mulai_sidang,
            $oditur_id
        ];

        $query3 .= " WHERE kasus_id = ?";

        $stmt3 = $conn->prepare($query3);

        // Bind parameters using a loop
        for ($i = 1; $i <= count($bindParams); $i++) {
            $stmt3->bindValue($i, $bindParams[$i - 1], PDO::PARAM_STR);
        }

        $stmt3->bindValue(count($bindParams) + 1, $kasus_id, PDO::PARAM_STR);

        $stmt3->execute();

        
        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "kasus_id" => $kasus_id,
                    "nama_kasus" => $nama_kasus,
                    "nomor_kasus" => $nomor_kasus,
                    "wbp_profile_id" => $wbp_profile_id,
                    "kategori_perkara_id" => $kategori_perkara_id,
                    "jenis_perkara_id" => $jenis_perkara_id,
                    "tanggal_registrasi_kasus" => $tanggal_registrasi_kasus,
                    "tanggal_penutupan_kasus" => $tanggal_penutupan_kasus,
                    "status_kasus_id" => $status_kasus_id,
                    "tanggal_mulai_penyidikan" => $tanggal_mulai_penyidikan,
                    "tanggal_mulai_sidang" => $tanggal_mulai_sidang,
                    "oditur_id" => $oditur_id,
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
