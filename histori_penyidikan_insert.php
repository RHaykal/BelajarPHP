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

$histori_penyidikan_id = generateUUID();
$penyidikan_id = (empty($param_POST->penyidikan_id)) ? '' : trim($param_POST->penyidikan_id);
$hasil_penyidikan = (empty($param_POST->hasil_penyidikan)) ? '' : trim($param_POST->hasil_penyidikan);
$lama_masa_tahanan = (empty($param_POST->lama_masa_tahanan)) ? '' : trim($param_POST->lama_masa_tahanan);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM histori_penyidikan HP
        WHERE 
        HP.penyidikan_id = ? AND 
        HP.hasil_penyidikan = ? AND
        HP.lama_masa_tahanan = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $penyidikan_id, 
        $hasil_penyidikan, 
        $lama_masa_tahanan
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";

    } else {
        $query1 = "INSERT INTO histori_penyidikan (
            histori_penyidikan_id, 
            penyidikan_id, 
            hasil_penyidikan, 
            lama_masa_tahanan
        ) 
        VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $histori_penyidikan_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $penyidikan_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $hasil_penyidikan, PDO::PARAM_STR);
        $stmt1->bindParam(4, $lama_masa_tahanan, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'histori_penyidikan_id' => $histori_penyidikan_id,
            'penyidikan_id' => $penyidikan_id,
            'hasil_penyidikan' => $hasil_penyidikan,
            'lama_masa_tahanan' => $lama_masa_tahanan
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
