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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$kategori_perkara_id = generateUUID();
$nama_kategori_perkara = (empty($param_POST->nama_kategori_perkara)) ? '' : trim($param_POST->nama_kategori_perkara);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');
 
    $query = "SELECT * FROM kategori_perkara WHERE nama_kategori_perkara = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([ $nama_kategori_perkara]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO kategori_perkara(kategori_perkara_id, nama_kategori_perkara) VALUES (?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $kategori_perkara_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_kategori_perkara, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [['kategori_perkara_id' => $kategori_perkara_id, 'nama_kategori_perkara' => $nama_kategori_perkara]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>