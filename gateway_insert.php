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

$gateway_id = generateUUID();
$nama_gateway = isset($param_POST->nama_gateway) ? trim($param_POST->nama_gateway) : "";
$gmac = trim(isset($param_POST->gmac) ? $param_POST->gmac : "");
$ruangan_otmil_id = trim(isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : "");
$ruangan_lemasmil_id =  trim( isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : "");
$status_gateway = trim(isset($param_POST->status_gateway) ? $param_POST->status_gateway : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM gateway WHERE 
        nama_gateway = ? AND 
        gmac = ? AND 
        ruangan_otmil_id = ? AND 
        ruangan_lemasmil_id = ? AND 
        status_gateway = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nama_gateway, PDO::PARAM_STR);
    $stmt->bindValue(2, $gmac, PDO::PARAM_STR);
    $stmt->bindValue(3, $ruangan_otmil_id, PDO::PARAM_STR);
    $stmt->bindValue(4, $ruangan_lemasmil_id, PDO::PARAM_STR);
    $stmt->bindValue(5, $status_gateway, PDO::PARAM_STR);
    
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $query3 = "INSERT INTO gateway (
            gateway_id,
            nama_gateway,
            gmac,
            ruangan_otmil_id,
            ruangan_lemasmil_id,
            status_gateway
        ) VALUES (?, ?, ?, ?, ?, ?)";

  
        $stmt3 = $conn->prepare($query3);   
        $stmt3->bindValue(1, $gateway_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $nama_gateway, PDO::PARAM_STR);
        $stmt3->bindValue(3, $gmac, PDO::PARAM_STR);
        $stmt3->bindValue(4, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(5, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt3->bindValue(6, $status_gateway, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Data berhasil disimpan",
            "records" => [
                "gateway_id" => $gateway_id,
                "nama_gateway" => $nama_gateway,
                "gmac" => $gmac,
                "ruangan_otmil_id" => $ruangan_otmil_id,
                "ruangan_lemasmil_id" => $ruangan_lemasmil_id,
                "status_gateway" => $status_gateway
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
