<?php 
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$histori_vonis_id = generateUUID();
$sidang_id = (empty($param_POST->sidang_id)) ? '' : trim($param_POST->sidang_id);
$hasil_vonis = (empty($param_POST->hasil_vonis)) ? '' : trim($param_POST->hasil_vonis);
$lama_masa_tahanan = (empty($param_POST->lama_masa_tahanan)) ? '' : trim($param_POST->lama_masa_tahanan);
$created_at = (empty($param_POST->created_at)) ? '' : trim($param_POST->created_at);
$updated_at = (empty($param_POST->updated_at)) ? '' : trim($param_POST->updated_at);

$result = ['message' => '', 'status' => 'No', 'records' => []];

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
    $stmt->execute([$histori_vonis_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO histori_vonis 
                    (
                        histori_vonis_id,
                        sidang_id,
                        hasil_vonis,
                        lama_masa_tahanan,
                        created_at,
                        updated_at
                    )
                    VALUES(?, ?, ?, ?, ?, ?)
        ";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $histori_vonis_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $sidang_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $hasil_vonis, PDO::PARAM_STR);
        $stmt1->bindParam(4, $lama_masa_tahanan, PDO::PARAM_STR);
        $stmt1->bindParam(5, $created_at, PDO::PARAM_STR);
        $stmt1->bindParam(6, $updated_at, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data successfully added";
        $result['status'] = "Ok";
        $result['records'] = [[
            "histori_vonis_id" => $histori_vonis_id,
            "sidang_id" => $sidang_id,
            "hasil_vonis" => $hasil_vonis,
            "lama_masa_tahanan" => $lama_masa_tahanan,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = $e->getMessage();
}
echo json_encode($result);
?>