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

$param_POST = isset ($param_POST) ? $param_POST : (object) [];

$bap_id = generateUUID();
$penyidikan_id = empty($param_POST->penyidikan_id) ? '' : trim($param_POST->penyidikan_id);
$dokumen_bap_id = empty($param_POST->dokumen_bap_id) ? '' : trim($param_POST->dokumen_bap_id);
$created_at = date("Y:m:d H:i:s");

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM bap WHERE bap_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$bap_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO bap 
                    (
                        bap_id,
                        penyidikan_id,
                        dokumen_bap_id,
                        created_at,
                        updated_at
                    )
                    VALUES(?, ?, ?, ?, ?)
        ";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $bap_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $penyidikan_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $dokumen_bap_id, PDO::PARAM_STR);
        $stmt1->bindParam(4, $created_at, PDO::PARAM_STR);
        $stmt1->bindParam(5, $updated_at, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data berhasil disimpan";
        $result['status'] = "OK";
        $result['records'] = [
            'bap_id' => $bap_id,
            'penyidikan_id' => $penyidikan_id,
            'dokumen_bap_id' => $dokumen_bap_id,
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>