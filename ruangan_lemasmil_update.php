<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    // header("Access-Control-Allow-Headers: Token");
    // header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$ruangan_lemasmil_id = (empty($param_POST->ruangan_lemasmil_id)) ? '' : trim($param_POST->ruangan_lemasmil_id); // Add ruangan_lemasmil_id

$nama_ruangan_lemasmil = (empty($param_POST->nama_ruangan_lemasmil)) ? '' : trim($param_POST->nama_ruangan_lemasmil);
$jenis_ruangan_lemasmil = (empty($param_POST->jenis_ruangan_lemasmil)) ? '' : trim($param_POST->jenis_ruangan_lemasmil);
$lokasi_lemasmil_id = (empty($param_POST->lokasi_lemasmil_id)) ? '' : trim($param_POST->lokasi_lemasmil_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record with the given ruangan_lemasmil_id exists
    $check_query = "SELECT * FROM ruangan_lemasmil WHERE ruangan_lemasmil_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$ruangan_lemasmil_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        $result['message'] = "Data not found";
    } else {
        // Update the record
        $update_query = "UPDATE ruangan_lemasmil SET
            nama_ruangan_lemasmil = ?,
            jenis_ruangan_lemasmil = ?,
            lokasi_lemasmil_id = ?
            WHERE ruangan_lemasmil_id = ?";

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(1, $nama_ruangan_lemasmil, PDO::PARAM_STR);
        $update_stmt->bindParam(2, $jenis_ruangan_lemasmil, PDO::PARAM_STR);
        $update_stmt->bindParam(3, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $update_stmt->bindParam(4, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $update_stmt->execute();

        $result['message'] = "Update data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'ruangan_lemasmil_id' => $ruangan_lemasmil_id,
                'nama_ruangan_lemasmil' => $nama_ruangan_lemasmil,
                'jenis_ruangan_lemasmil' => $jenis_ruangan_lemasmil,
                'lokasi_lemasmil_id' => $lokasi_lemasmil_id
            ]
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
