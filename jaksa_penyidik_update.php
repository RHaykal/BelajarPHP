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

$jaksa_penyidik_id = (empty($_POST['jaksa_penyidik_id'])) ? trim($param_POST->jaksa_penyidik_id) : trim($_POST['jaksa_penyidik_id']);
$nip = (empty($_POST['nip'])) ? trim($param_POST->nip) : trim($_POST['nip']);
$nama_jaksa = (empty($_POST['nama_jaksa'])) ? trim($param_POST->nama_jaksa) : trim($_POST['nama_jaksa']);
$alamat = (empty($_POST['alamat'])) ? trim($param_POST->alamat) : trim($_POST['alamat']);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query1 = "SELECT * FROM jaksa_penyidik WHERE jaksa_penyidik_id = ?";
    $stmt1 = $conn->prepare($query1);
    $stmt1->bindValue(1, $jaksa_penyidik_id, PDO::PARAM_STR);
    $stmt1->execute();
    $res1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    if (count($res1) == 0){
        throw new Exception("Jaksa Penyidik not found");
    } else {
        $query = "UPDATE jaksa_penyidik SET
            nip = ?,
            nama_jaksa = ?,
            alamat = ?
            WHERE jaksa_penyidik_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $nip, PDO::PARAM_STR);
        $stmt->bindValue(2, $nama_jaksa, PDO::PARAM_STR);
        $stmt->bindValue(3, $alamat, PDO::PARAM_STR);
        $stmt->bindValue(4, $jaksa_penyidik_id, PDO::PARAM_STR);
        $stmt->execute();

        $result = [
            "status" => "OK",
            "message" => "Jaksa Penyidik updated successfully",
            "records" => [
                [
                    'jaksa_penyidik_id' => $jaksa_penyidik_id,
                    'nip' => $nip,
                    'nama_jaksa' => $nama_jaksa,
                    'alamat' => $alamat
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
