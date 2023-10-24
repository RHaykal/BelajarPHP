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
    $param_PUT = json_decode(file_get_contents("php://input"));
} else {
    $param_PUT = $_POST;
}

$kegiatan_id = trim(isset($param_PUT->kegiatan_id) ? $param_PUT->kegiatan_id : "");
$nama_kegiatan = trim(isset($param_PUT->nama_kegiatan) ? $param_PUT->nama_kegiatan : "");
$ruangan_otmil_id = trim(isset($param_PUT->ruangan_otmil_id) ? $param_PUT->ruangan_otmil_id : "");
$ruangan_lemasmil_id = trim(isset($param_PUT->ruangan_lemasmil_id) ? $param_PUT->ruangan_lemasmil_id : "");
$status_kegiatan = trim(isset($param_PUT->status_kegiatan) ? $param_PUT->status_kegiatan : "");
$waktu_mulai_kegiatan = trim(isset($param_PUT->waktu_mulai_kegiatan) ? $param_PUT->waktu_mulai_kegiatan : "");
$waktu_selesai_kegiatan = trim(isset($param_PUT->waktu_selesai_kegiatan) ? $param_PUT->waktu_selesai_kegiatan : "");

$peserta = isset($param_PUT->peserta) ? $param_PUT->peserta : [];

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // Check if the record with the given kegiatan_id exists
    $check_query = "SELECT * FROM kegiatan WHERE kegiatan_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$kegiatan_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Update the record
        $update_query = "UPDATE kegiatan SET
          
            is_deleted = 1
            WHERE kegiatan_id = ?";

        $update_stmt = $conn->prepare($update_query);
 
        $update_stmt->bindParam(1, $kegiatan_id, PDO::PARAM_STR);
        $update_stmt->execute();

     

        // Insert new kegiatan_wbp records
        foreach ($peserta as $wbp_profile_id_peserta) {
            // Assuming $wbp_profile_id_peserta is the primary key (or unique identifier) of the kegiatan_wbp record
        
            $deleteQuery = "UPDATE kegiatan_wbp 
                            SET is_deleted = 1
                            WHERE kegiatan_id = ? AND wbp_profile_id = ?";
        
            $delete_kegiatan_wbp_stmt = $conn->prepare($deleteQuery);
            $delete_kegiatan_wbp_stmt->execute([
                $kegiatan_id,
                $wbp_profile_id_peserta
            ]);
        }
        

        $result['message'] = "Data successfully deleted";
        $result['status'] = "OK";
        $result['records'] = [
            [
              
            ]
        ];
    }
} catch (Exception $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>
