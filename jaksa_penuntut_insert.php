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

$jaksa_penuntut_id = generateUUID();
$nip = isset($param_POST->nip) ? $param_POST->nip : '';
$nama_jaksa = isset($param_POST->nama_jaksa) ? $param_POST->nama_jaksa : '';
$alamat = isset($param_POST->alamat) ? $param_POST->alamat : '';

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM jaksa_penuntut 
        WHERE 
        nip = ? AND
        nama_jaksa = ? AND 
        alamat = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nip,
        $nama_jaksa,
        $alamat,
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO jaksa_penuntut 
                    (jaksa_penuntut_id,
                    nip,
                    nama_jaksa,
                    alamat)
                    VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $jaksa_penuntut_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nip, PDO::PARAM_STR);
        $stmt1->bindParam(3, $nama_jaksa, PDO::PARAM_STR);
        $stmt1->bindParam(4, $alamat, PDO::PARAM_STR);
    
        $stmt1->execute();

        $result['message'] = "Data successfully added";
        $result['status'] = "OK";
        $result['records'] = [[
            'jaksa_penuntut_id' => $jaksa_penuntut_id,
            'nip' => $nip,
            'nama_jaksa' => $nama_jaksa,
            'alamat' => $alamat,
        ]];
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
