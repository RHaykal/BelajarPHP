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
    header("Access-Control-Allow-Headers: Token");
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$aktivitas_pengunjung_id = generateUUID();
// $nama = trim(isset($param_POST->nama) ? $param_POST->nama : "");
$nama_aktivitas_pengunjung = isset($param_POST->nama_aktivitas_pengunjung) ? trim($param_POST->nama_aktivitas_pengunjung) : "";
$waktu_mulai_kunjungan = trim(isset($param_POST->waktu_mulai_kunjungan) ? $param_POST->waktu_mulai_kunjungan : "");
$waktu_selesai_kunjungan = trim(isset($param_POST->waktu_selesai_kunjungan) ? $param_POST->waktu_selesai_kunjungan : "");
$tujuan_kunjungan = trim(isset($param_POST->tujuan_kunjungan) ? $param_POST->tujuan_kunjungan : "");
$ruangan_otmil_id =  trim( isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : "");
$ruangan_lemasmil_id = trim(isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : "");
$petugas_id = trim(isset($param_POST->petugas_id) ? $param_POST->petugas_id : "");
$pengunjung_id = trim(isset($param_POST->pengunjung_id) ? $param_POST->pengunjung_id : "");
$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM aktivitas_pengunjung WHERE 
        nama_aktivitas_pengunjung = ? AND 
        waktu_mulai_kunjungan = ? AND 
        waktu_selesai_kunjungan = ? AND 
        tujuan_kunjungan = ? AND 
        ruangan_otmil_id = ? AND 
        ruangan_lemasmil_id = ? AND 
        petugas_id = ? AND
        pengunjung_id = ?
        ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nama_aktivitas_pengunjung, PDO::PARAM_STR);
    $stmt->bindValue(2, $waktu_mulai_kunjungan, PDO::PARAM_STR);
    $stmt->bindValue(3, $waktu_selesai_kunjungan, PDO::PARAM_STR);
    $stmt->bindValue(4, $tujuan_kunjungan, PDO::PARAM_STR);
    $stmt->bindValue(5, $ruangan_otmil_id, PDO::PARAM_STR);
    $stmt->bindValue(6, $ruangan_lemasmil_id, PDO::PARAM_STR);
    $stmt->bindValue(7, $petugas_id, PDO::PARAM_STR);
    $stmt->bindValue(8, $pengunjung_id, PDO::PARAM_STR);

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $query3 = "INSERT INTO aktivitas_pengunjung (
            aktivitas_pengunjung_id,
            nama_aktivitas_pengunjung,
            waktu_mulai_kunjungan,
            waktu_selesai_kunjungan,
            tujuan_kunjungan,
            ruangan_otmil_id,
            ruangan_lemasmil_id,
            petugas_id,
            pengunjung_id
        ) VALUES (?,?,?,?,?,?,?,?,?)";

        $stmt3 = $conn->prepare($query3);   
        $stmt3->bindValue(1, $aktivitas_pengunjung_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $nama_aktivitas_pengunjung, PDO::PARAM_STR);
        $stmt3->bindValue(3, $waktu_mulai_kunjungan, PDO::PARAM_STR);
        $stmt3->bindValue(4, $waktu_selesai_kunjungan, PDO::PARAM_STR);
        $stmt3->bindValue(5, $tujuan_kunjungan, PDO::PARAM_STR);
        $stmt3->bindValue(6, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(7, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(8, $petugas_id, PDO::PARAM_STR);
        $stmt3->bindValue(9, $pengunjung_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Successfully registered",
            "records" => [
                [
                    "aktivitas_pengunjung_id" => $aktivitas_pengunjung_id,
                    "nama_aktivitas_pengunjung" => $nama_aktivitas_pengunjung,
                    "waktu_mulai_kunjungan" => $waktu_mulai_kunjungan,
                    "waktu_selesai_kunjungan" => $waktu_selesai_kunjungan,
                    "tujuan_kunjungan" => $tujuan_kunjungan,
                    "ruangan_otmil_id" => $ruangan_otmil_id,
                    "ruangan_lemasmil_id" => $ruangan_lemasmil_id,
                    "petugas_id" => $petugas_id,
                    "pengunjung_id" => $pengunjung_id
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
