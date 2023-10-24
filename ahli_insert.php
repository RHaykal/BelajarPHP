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

$ahli_id = generateUUID();
$nama_ahli = (empty($param_POST->nama_ahli)) ? '' : trim($param_POST->nama_ahli);
$bidang_ahli = (empty($param_POST->bidang_ahli)) ? '' : trim($param_POST->bidang_ahli);
$bukti_keahlian = (empty($param_POST->bukti_keahlian)) ? '' : trim($param_POST->bukti_keahlian);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM ahli A
        WHERE 
        A.nama_ahli = ? AND 
        A.bidang_ahli = ? AND
        A.bukti_keahlian = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $nama_ahli, 
        $bidang_ahli, 
        $bukti_keahlian
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO ahli (
            ahli_id, 
            nama_ahli, 
            bidang_ahli, 
            bukti_keahlian
        ) 
        VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $ahli_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_ahli, PDO::PARAM_STR);
        $stmt1->bindParam(3, $bidang_ahli, PDO::PARAM_STR);
        $stmt1->bindParam(4, $bukti_keahlian, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data successfully added";
        $result['status'] = "Ok";
        $result['records'] = [[
            'ahli_id' => $ahli_id,
            'nama_ahli' => $nama_ahli,
            'bidang_ahli' => $bidang_ahli,
            'bukti_keahlian' => $bukti_keahlian
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = $e->getMessage();
}
echo json_encode($result);
?>