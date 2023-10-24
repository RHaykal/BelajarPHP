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

$kasus_id = generateUUID();
$nama_kasus = (empty($param_POST->nama_kasus)) ? '' : trim($param_POST->nama_kasus);
$nomor_kasus = (empty($param_POST->nomor_kasus)) ? '' : trim($param_POST->nomor_kasus);
$wbp_profile_id = (empty($param_POST->wbp_profile_id)) ? '' : trim($param_POST->wbp_profile_id);
$kategori_perkara_id = (empty($param_POST->kategori_perkara_id)) ? '' : trim($param_POST->kategori_perkara_id);
$jenis_perkara_id = (empty($param_POST->jenis_perkara_id)) ? '' : trim($param_POST->jenis_perkara_id);
$tanggal_registrasi_kasus = (empty($param_POST->tanggal_registrasi_kasus)) ? '' : trim($param_POST->tanggal_registrasi_kasus);
$tanggal_penutupan_kasus = (empty($param_POST->tanggal_penutupan_kasus)) ? '' : trim($param_POST->tanggal_penutupan_kasus);
$status_kasus_id = (empty($param_POST->status_kasus_id)) ? '' : trim($param_POST->status_kasus_id);
$tanggal_mulai_penyidikan = (empty($param_POST->tanggal_mulai_penyidikan)) ? '' : trim($param_POST->tanggal_mulai_penyidikan);
$tanggal_mulai_sidang = (empty($param_POST->tanggal_mulai_sidang)) ? '' : trim($param_POST->tanggal_mulai_sidang);
$oditur_id = (empty($param_POST->oditur_id)) ? '' : trim($param_POST->oditur_id);
try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM kasus A
        WHERE 
        A.nama_kasus = ? AND 
        A.nomor_kasus = ? AND
        A.wbp_profile_id = ? AND 
        A.kategori_perkara_id = ? AND 
        A.jenis_perkara_id = ? AND 
        A.tanggal_registrasi_kasus = ? AND 
        A.tanggal_penutupan_kasus = ? AND
        A.status_kasus_id = ? AND
        A.tanggal_mulai_penyidikan = ? AND
        A.tanggal_mulai_sidang = ? AND
        A.oditur_id = ?
        ";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
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
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";

    } else {
        $query1 = "INSERT INTO kasus (
            kasus_id, 
            nama_kasus, 
            nomor_kasus, 
            wbp_profile_id, 
            kategori_perkara_id, 
            jenis_perkara_id, 
            tanggal_registrasi_kasus, 
            tanggal_penutupan_kasus,
            status_kasus_id,
            tanggal_mulai_penyidikan,
            tanggal_mulai_sidang,
            oditur_id
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $kasus_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_kasus, PDO::PARAM_STR);
        $stmt1->bindParam(3, $nomor_kasus, PDO::PARAM_STR);
        $stmt1->bindParam(4, $wbp_profile_id, PDO::PARAM_STR);
        $stmt1->bindParam(5, $kategori_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(6, $jenis_perkara_id, PDO::PARAM_STR);
        $stmt1->bindParam(7, $tanggal_registrasi_kasus, PDO::PARAM_STR);
        $stmt1->bindParam(8, $tanggal_penutupan_kasus, PDO::PARAM_STR);
        $stmt1->bindParam(9, $status_kasus_id, PDO::PARAM_STR);
        $stmt1->bindParam(10, $tanggal_mulai_penyidikan, PDO::PARAM_STR);
        $stmt1->bindParam(11, $tanggal_mulai_sidang, PDO::PARAM_STR);
        $stmt1->bindParam(12, $oditur_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'kasus_id' => $kasus_id,
            'nama_kasus' => $nama_kasus,
            'nomor_kasus' => $nomor_kasus,
            'wbp_profile_id' => $wbp_profile_id,
            'kategori_perkara_id' => $kategori_perkara_id,
            'jenis_perkara_id' => $jenis_perkara_id,
            'tanggal_registrasi_kasus' => $tanggal_registrasi_kasus,
            'tanggal_penutupan_kasus' => $tanggal_penutupan_kasus,
            'status_kasus_id' => $status_kasus_id,
            'tanggal_mulai_penyidikan' => $tanggal_mulai_penyidikan,
            'tanggal_mulai_sidang' => $tanggal_mulai_sidang,
            'oditur_id' => $oditur_id,
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
