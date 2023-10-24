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

$jenis_persidangan_id = trim(isset($param_POST->jenis_persidangan_id) ? $param_POST->jenis_persidangan_id : "");
$nama_jenis_persidangan = trim(isset($param_POST->nama_jenis_persidangan) ? $param_POST->nama_jenis_persidangan : "");

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM jenis_persidangan WHERE jenis_persidangan_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $jenis_persidangan_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Data not found");
    } else {
        $query3 = "UPDATE jenis_persidangan set
            nama_jenis_persidangan = ?
            WHERE jenis_persidangan_id = ?
        ";

        $stmt3 = $conn->prepare($query3);
        $stmt3->bindParam(1, $nama_jenis_persidangan, PDO::PARAM_STR);
        $stmt3->bindParam(2, $jenis_persidangan_id, PDO::PARAM_STR);

        $stmt3->execute();

        $result = [
            'message' => 'Data successfully updated',
            'status' => 'OK',
            'records' => [
                [
                    'jenis_persidangan_id' => $jenis_persidangan_id,
                    'nama_jenis_persidangan' => $nama_jenis_persidangan
                ]
            ]
        ];
    }
} catch (PDOException $e) {
    $result = [
        'message' => $e->getMessage(),
        'status' => 'Error',
        'records' => []
    ];
}
    echo json_encode($result);

?>