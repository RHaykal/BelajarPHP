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

$histori_penyidikan_id = trim(isset($param_POST->histori_penyidikan_id) ? $param_POST->histori_penyidikan_id : "");
$penyidikan_id = trim(isset($param_POST->penyidikan_id) ? $param_POST->penyidikan_id : "");
$hasil_penyidikan = trim(isset($param_POST->hasil_penyidikan) ? $param_POST->hasil_penyidikan : "");
$lama_masa_tahanan = trim(isset($param_POST->lama_masa_tahanan) ? $param_POST->lama_masa_tahanan : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM histori_penyidikan WHERE histori_penyidikan_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $histori_penyidikan_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Aset not found");
    } else {
        $query3 = "UPDATE histori_penyidikan SET
            penyidikan_id = ?,
            hasil_penyidikan = ?,
            lama_masa_tahanan = ?";

        // Create an array to store bind parameters
        $bindParams = [
            $penyidikan_id,
            $hasil_penyidikan,
            $lama_masa_tahanan
        ];

        $query3 .= " WHERE histori_penyidikan_id = ?";

        $stmt3 = $conn->prepare($query3);

        // Bind parameters using a loop
        for ($i = 1; $i <= count($bindParams); $i++) {
            $stmt3->bindValue($i, $bindParams[$i - 1], PDO::PARAM_STR);
        }

        $stmt3->bindValue(count($bindParams) + 1, $histori_penyidikan_id, PDO::PARAM_STR);

        $stmt3->execute();

        
        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "histori_penyidikan_id" => $histori_penyidikan_id,
                    "penyidikan_id" => $penyidikan_id,
                    "hasil_penyidikan" => $hasil_penyidikan,
                    "lama_masa_tahanan" => $lama_masa_tahanan
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
