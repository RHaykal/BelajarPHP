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

$hakim_id = generateUUID();
$nip = isset($param_POST->nip) ? $param_POST->nip : '';
$nama_hakim = isset($param_POST->nama_hakim) ? $param_POST->nama_hakim : '';
$alamat = isset($param_POST->alamat) ? $param_POST->alamat : '';
$departemen = isset($param_POST->departemen) ? $param_POST->departemen : '';

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM hakim 
        WHERE 
        nip = ? AND
       nama_hakim = ? AND 
       alamat = ? AND
       departemen = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nip,
        $nama_hakim,
        $alamat,
        $departemen
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO hakim 
                    (hakim_id,
                    nip,
                    nama_hakim,
                    alamat,
                    departemen)
                    VALUES (?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $hakim_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nip, PDO::PARAM_STR);
        $stmt1->bindParam(3, $nama_hakim, PDO::PARAM_STR);
        $stmt1->bindParam(4, $alamat, PDO::PARAM_STR);
        $stmt1->bindParam(5, $departemen, PDO::PARAM_STR);
    
        $stmt1->execute();

        $result['message'] = "Data successfully added";
        $result['status'] = "OK";
        $result['records'] = [[
            'hakim_id' => $hakim_id,
            'nip' => $nip,
            'nama_hakim' => $nama_hakim,
            'alamat' => $alamat,
        ]];
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
