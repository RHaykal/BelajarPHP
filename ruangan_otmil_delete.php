<?php 
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, PUT, GET, OPTIONS"); // Add PUT method
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$ruangan_otmil_id = (empty($param_POST->ruangan_otmil_id)) ? '' : trim($param_POST->ruangan_otmil_id); // Add ruangan_otmil_id

$nama_ruangan_otmil = (empty($param_POST->nama_ruangan_otmil)) ? '' : trim($param_POST->nama_ruangan_otmil);
$jenis_ruangan_otmil = (empty($param_POST->jenis_ruangan_otmil)) ? '' : trim($param_POST->jenis_ruangan_otmil);
$lokasi_otmil_id = (empty($param_POST->lokasi_otmil_id)) ? '' : trim($param_POST->lokasi_otmil_id);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record with the given ruangan_otmil_id exists
    $check_query = "SELECT * FROM ruangan_otmil WHERE ruangan_otmil_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$ruangan_otmil_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        $result['message'] = "Data not found";
    } else {
        // Update the record
        $delete_query = "UPDATE ruangan_otmil SET
         
            is_deleted = 1
            WHERE ruangan_otmil_id = ?";

        $update_stmt = $conn->prepare($delete_query);
        $update_stmt->bindParam(1, $ruangan_otmil_id, PDO::PARAM_STR);
        $update_stmt->execute();

        $result['message'] = "Delete data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'ruangan_otmil_id' => $ruangan_otmil_id,
                'nama_ruangan_otmil' => $nama_ruangan_otmil,
                'jenis_ruangan_otmil' => $jenis_ruangan_otmil,
                'lokasi_otmil_id' => $lokasi_otmil_id
            ]
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
