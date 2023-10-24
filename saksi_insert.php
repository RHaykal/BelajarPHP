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

$saksi_id = generateUUID();
$nama_saksi = isset($param_POST->nama_saksi) ? $param_POST->nama_saksi : '';
$no_kontak = isset($param_POST->no_kontak) ? $param_POST->no_kontak : '';
$alamat = isset($param_POST->alamat) ? $param_POST->alamat : '';
$keterangan = isset($param_POST->keterangan) ? $param_POST->keterangan : '';
$kasus_id = isset($param_POST->kasus_id) ? $param_POST->kasus_id : '';
$created_at = date('Y-m-d H:i:s');

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM saksi 
        WHERE 
        nama_saksi = ? AND
        no_kontak = ? AND
        alamat = ? AND
        keterangan = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nama_saksi,
        $no_kontak,
        $alamat,
        $keterangan
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $query1 = "INSERT INTO saksi 
                    (saksi_id,
                    nama_saksi,
                    no_kontak,
                    alamat,
                    keterangan,
                    kasus_id,
                    created_at)
                    VALUES (?,?,?,?,?,?,?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $saksi_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $nama_saksi, PDO::PARAM_STR);
        $stmt1->bindValue(3, $no_kontak, PDO::PARAM_STR);
        $stmt1->bindValue(4, $alamat, PDO::PARAM_STR);
        $stmt1->bindValue(5, $keterangan, PDO::PARAM_STR);
        $stmt1->bindValue(6, $kasus_id, PDO::PARAM_STR);
        $stmt1->bindValue(7, $created_at, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Data successfully added";
        $result['status'] = "OK";
        $result['records'] = [[
            'saksi_id' => $saksi_id,
            'nama_saksi' => $nama_saksi,
            'no_kontak' => $no_kontak,
            'alamat' => $alamat,
            'keterangan' => $keterangan,
            'kasus_id' => $kasus_id,
            'created_at' => $created_at
        ]];
    }

} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
