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

$histori_vonis_id = trim(isset($param_POST->histori_vonis_id) ? $param_POST->histori_vonis_id : "");
$sidang_id = trim(isset($param_POST->sidang_id) ? $param_POST->sidang_id : "");
$hasil_vonis = trim(isset($param_POST->hasil_vonis) ? $param_POST->hasil_vonis : "");
$lama_masa_tahanan = trim(isset($param_POST->lama_masa_tahanan) ? $param_POST->lama_masa_tahanan : "");
$created_at = trim(isset($param_POST->created_at) ? $param_POST->created_at : "");
$updated_at = trim(isset($param_POST->updated_at) ? $param_POST->updated_at : "");

$result = '';

try {
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $query = "SELECT * FROM histori_vonis WHERE histori_vonis_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $histori_vonis_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data not found");
    } else {
        $query3 = "UPDATE histori_vonis set
            sidang_id = ?,
            hasil_vonis = ?,
            lama_masa_tahanan = ?,
            created_at = ?,
            updated_at = ?
            WHERE histori_vonis_id = ?
        ";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindValue(1, $sidang_id, PDO::PARAM_STR);
        $stmt3->bindValue(2, $hasil_vonis, PDO::PARAM_STR);
        $stmt3->bindValue(3, $lama_masa_tahanan, PDO::PARAM_STR);
        $stmt3->bindValue(4, $created_at, PDO::PARAM_STR);
        $stmt3->bindValue(5, $updated_at, PDO::PARAM_STR);
        $stmt3->bindValue(6, $histori_vonis_id, PDO::PARAM_STR);
        $stmt3->execute();

        $result = [
            'message' => 'Data successfully updated',
            'status' => 'OK',
            'records' => [
                [
                    'histori_vonis_id' => $histori_vonis_id,
                    'sidang_id' => $sidang_id,
                    'hasil_vonis' => $hasil_vonis,
                    'lama_masa_tahanan' => $lama_masa_tahanan,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at
                ]
            ]
        ];

    }
} catch (Exception $e) {
    $result = [
        'status' => 'NO',
        'message' => $e->getMessage(),
        'records' => []
    ];
}

echo json_encode($result);
?>