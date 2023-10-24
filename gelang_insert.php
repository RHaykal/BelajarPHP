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
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$gelang_id = generateUUID();
$nama_gelang = isset($param_POST->nama_gelang) ? trim($param_POST->nama_gelang) : "";
$dmac = trim(isset($param_POST->dmac) ? $param_POST->dmac : "");
$tanggal_pasang = trim(isset($param_POST->tanggal_pasang) ? $param_POST->tanggal_pasang : "");
$tanggal_aktivasi = trim(isset($param_POST->tanggal_aktivasi) ? $param_POST->tanggal_aktivasi : "");
$ruangan_otmil_id = trim(isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : "");
$ruangan_lemasmil_id =  trim( isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : "");
$baterai = trim(isset($param_POST->baterai) ? $param_POST->baterai : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM gelang WHERE 
        nama_gelang = ? AND 
        dmac = ? AND
        tanggal_pasang = ? AND
        tanggal_aktivasi = ? AND
        ruangan_otmil_id = ? AND
        ruangan_lemasmil_id = ? AND 
        baterai = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nama_gelang, PDO::PARAM_STR);
    $stmt->bindValue(2, $dmac, PDO::PARAM_STR);
    $stmt->bindValue(3, $tanggal_pasang, PDO::PARAM_STR);
    $stmt->bindValue(4, $tanggal_aktivasi, PDO::PARAM_STR);
    $stmt->bindValue(5, $ruangan_otmil_id, PDO::PARAM_STR);
    $stmt->bindValue(6, $ruangan_lemasmil_id, PDO::PARAM_STR);
    $stmt->bindValue(7, $baterai, PDO::PARAM_STR);
    
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $query3 = "INSERT INTO gelang (
            gelang_id,
            nama_gelang,
            dmac,
            tanggal_pasang,
            tanggal_aktivasi,
            ruangan_otmil_id,
            ruangan_lemasmil_id,
            baterai
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

  
        $stmt3 = $conn->prepare($query3);   
        $stmt3->bindValue(1, $gelang_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $nama_gelang, PDO::PARAM_STR);
        $stmt3->bindValue(3, $dmac, PDO::PARAM_STR);
        $stmt3->bindValue(4, $tanggal_pasang, PDO::PARAM_STR);
        $stmt3->bindValue(5, $tanggal_aktivasi, PDO::PARAM_STR);
        $stmt3->bindValue(6, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(7, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(8, $baterai, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Data berhasil disimpan",
            "records" => [
                "gelang_id" => $gelang_id,
                "nama_gelang" => $nama_gelang,
                "dmac" => $dmac,
                "tanggal_pasang" => $tanggal_pasang,
                "tanggal_aktivasi" => $tanggal_aktivasi,
                "ruangan_otmil_id" => $ruangan_otmil_id,
                "ruangan_lemasmil_id" => $ruangan_lemasmil_id,
                "baterai" => $baterai
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
