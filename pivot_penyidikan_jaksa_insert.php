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

$pivot_penyidikan_jaksa_id = generateUUID();
$penyidikan_id = (empty($param_POST->penyidikan_id)) ? '' : trim($param_POST->penyidikan_id);
$role_ketua = (empty($param_POST->role_ketua)) ? 0 : trim($param_POST->role_ketua);
$jaksa_penyidik_id = (empty($param_POST->jaksa_penyidik_id)) ? '' : trim($param_POST->jaksa_penyidik_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM pivot_penyidikan_jaksa A
        WHERE 
        A.penyidikan_id = ? AND 
        A.role_ketua = ? AND
        A.jaksa_penyidik_id = ?
        ";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $penyidikan_id, 
        $role_ketua, 
        $jaksa_penyidik_id
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";

    } else {
        $query1 = "INSERT INTO pivot_penyidikan_jaksa (
            pivot_penyidikan_jaksa_id, 
            penyidikan_id, 
            role_ketua, 
            jaksa_penyidik_id
        ) 
        VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $pivot_penyidikan_jaksa_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $penyidikan_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $role_ketua, PDO::PARAM_STR);
        $stmt1->bindParam(4, $jaksa_penyidik_id, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'pivot_penyidikan_jaksa_id' => $pivot_penyidikan_jaksa_id,
            'penyidikan_id' => $penyidikan_id,
            'role_ketua' => $role_ketua,
            'jaksa_penyidik_id' => $jaksa_penyidik_id
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
