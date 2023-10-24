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
    // header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} 
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$ahli_id = trim(isset($param_POST->ahli_id) ? $param_POST->ahli_id : "");
$nama_ahli = (empty($param_POST->nama_ahli)) ? '' : trim($param_POST->nama_ahli);
$bidang_ahli = (empty($param_POST->bidang_ahli)) ? '' : trim($param_POST->bidang_ahli);
$bukti_keahlian = (empty($param_POST->bukti_keahlian)) ? '' : trim($param_POST->bukti_keahlian);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM ahli WHERE ahli_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $ahli_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Ahli not found");
    } else {
        $query3 = "UPDATE ahli SET
            nama_ahli = ?,
            bidang_ahli = ?,
            bukti_keahlian = ?
            WHERE ahli_id = ?";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $nama_ahli, PDO::PARAM_STR);
        $stmt3->bindValue(2, $bidang_ahli, PDO::PARAM_STR);
        $stmt3->bindValue(3, $bukti_keahlian, PDO::PARAM_STR);
        $stmt3->bindValue(4, $ahli_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            "status" => "OK",
            "message" => "Ahli updated successfully",
            "records" => [
                [
                    "ahli_id" => $ahli_id,
                    "nama_ahli" => $nama_ahli,
                    "bidang_ahli" => $bidang_ahli,
                    "bukti_keahlian" => $bukti_keahlian
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