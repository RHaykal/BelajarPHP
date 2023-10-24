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
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$pivot_penyidikan_jaksa_id = (empty($_POST['pivot_penyidikan_jaksa_id'])) ? trim($param_POST->pivot_penyidikan_jaksa_id) : trim($_POST['pivot_penyidikan_jaksa_id']);
$penyidikan_id = (empty($_POST['penyidikan_id'])) ? trim($param_POST->penyidikan_id) : trim($_POST['penyidikan_id']);
$role_ketua = (empty($_POST['role_ketua'])) ? trim($param_POST->role_ketua) : trim($_POST['role_ketua']);
$jaksa_penyidik_id = (empty($_POST['jaksa_penyidik_id'])) ? trim($param_POST->jaksa_penyidik_id) : trim($_POST['jaksa_penyidik_id']);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query1 = "SELECT * FROM pivot_penyidikan_jaksa WHERE pivot_penyidikan_jaksa_id = ?";
    $stmt1 = $conn->prepare($query1);
    $stmt1->bindValue(1, $pivot_penyidikan_jaksa_id, PDO::PARAM_STR);
    $stmt1->execute();
    $res1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    if (count($res1) == 0){
        throw new Exception("Jaksa Penyidik not found");
    } else {
        $query = "UPDATE pivot_penyidikan_jaksa SET
            penyidikan_id = ?,
            role_ketua = ?,
            jaksa_penyidik_id = ?
            WHERE pivot_penyidikan_jaksa_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $penyidikan_id, PDO::PARAM_STR);
        $stmt->bindValue(2, $role_ketua, PDO::PARAM_STR);
        $stmt->bindValue(3, $jaksa_penyidik_id, PDO::PARAM_STR);
        $stmt->bindValue(4, $pivot_penyidikan_jaksa_id, PDO::PARAM_STR);
        $stmt->execute();

        $result = [
            "status" => "OK",
            "message" => "Jaksa Penyidik updated successfully",
            "records" => [
                [
                    'pivot_penyidikan_jaksa_id' => $pivot_penyidikan_jaksa_id,
                    'penyidikan_id' => $penyidikan_id,
                    'role_ketua' => $role_ketua,
                    'jaksa_penyidik_id' => $jaksa_penyidik_id
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
