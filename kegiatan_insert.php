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

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$kegiatan_id = generateUUID();
$nama_kegiatan = (empty($param_POST->nama_kegiatan)) ? '' : trim($param_POST->nama_kegiatan);
$ruangan_otmil_id = (empty($param_POST->ruangan_otmil_id)) ? '' : trim($param_POST->ruangan_otmil_id);
$ruangan_lemasmil_id = (empty($param_POST->ruangan_lemasmil_id)) ? '' : trim($param_POST->ruangan_lemasmil_id);
$status_kegiatan = (empty($param_POST->status_kegiatan)) ? '' : trim($param_POST->status_kegiatan);
$waktu_mulai_kegiatan = (empty($param_POST->waktu_mulai_kegiatan)) ? '' : trim($param_POST->waktu_mulai_kegiatan);
$waktu_selesai_kegiatan = (empty($param_POST->waktu_selesai_kegiatan)) ? '' : trim($param_POST->waktu_selesai_kegiatan);

// $kegiatan_wbp_id = generateUUID();
// $wbp_profile_id = (empty($param_POST->wbp_profile_id)) ? '' : trim($param_POST->wbp_profile_id);

$peserta = (empty($param_POST->peserta)) ? [] : $param_POST->peserta;


$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    // echo $kegiatan_id; 
    // echo $nama_kegiatan; 
    // echo $ruangan_otmil_id; 
    // echo $ruangan_lemasmil_id; 
    // echo $status_kegiatan; 
    // echo $waktu_mulai_kegiatan;
    // echo $waktu_selesai_kegiatan; 

    $query = "SELECT * FROM kegiatan
        WHERE 
        nama_kegiatan = ? AND
        ruangan_otmil_id = ? AND
        ruangan_lemasmil_id = ? AND
        status_kegiatan = ? AND
        waktu_mulai_kegiatan = ? AND
        waktu_selesai_kegiatan = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $nama_kegiatan, PDO::PARAM_STR);
    $stmt->bindParam(2, $ruangan_otmil_id, PDO::PARAM_STR);
    $stmt->bindParam(3, $ruangan_lemasmil_id, PDO::PARAM_STR);
    $stmt->bindParam(4, $status_kegiatan, PDO::PARAM_STR);
    $stmt->bindParam(5, $waktu_mulai_kegiatan, PDO::PARAM_STR);
    $stmt->bindParam(6, $waktu_selesai_kegiatan, PDO::PARAM_STR);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        // echo 'hehe';
        $query1 = "INSERT INTO kegiatan (
            kegiatan_id, 
            nama_kegiatan, 
            ruangan_otmil_id, 
            ruangan_lemasmil_id, 
            status_kegiatan, 
            waktu_mulai_kegiatan, 
            waktu_selesai_kegiatan
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $kegiatan_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_kegiatan, PDO::PARAM_STR);
        $stmt1->bindParam(3, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(4, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(5, $status_kegiatan, PDO::PARAM_STR);
        $stmt1->bindParam(6, $waktu_mulai_kegiatan, PDO::PARAM_STR);
        $stmt1->bindParam(7, $waktu_selesai_kegiatan, PDO::PARAM_STR);
        $stmt1->execute();

        foreach ($peserta as $wbp_profile_id_peserta) {
            $kegiatan_wbp_id = generateUUID();

            $query5 = "INSERT INTO kegiatan_wbp(
                kegiatan_wbp_id,
                kegiatan_id,
                wbp_profile_id
            ) VALUES (?,?,?)";

            $stmt5 = $conn->prepare($query5);
            $stmt5->execute([
                $kegiatan_wbp_id,
                $kegiatan_id,
                $wbp_profile_id_peserta
            ]);
        }
        // $query2 = "INSERT INTO kegiatan_wbp (kegiatan_wbp_id, wbp_profile_id, kegiatan_id)
        // VALUES (?, ?, ?)";
        // $stmt2 = $conn->prepare($query2);
        // $stmt2->bindParam(1, $kegiatan_wbp_id, PDO::PARAM_STR);
        // $stmt2->bindParam(2, $wbp_profile_id, PDO::PARAM_STR);
        // $stmt2->bindParam(3, $kegiatan_id, PDO::PARAM_STR);

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'kegiatan_id' => $kegiatan_id,
                'nama_kegiatan' => $nama_kegiatan,
                'ruangan_otmil_id' => $ruangan_otmil_id,
                'ruangan_lemasmil_id' => $ruangan_lemasmil_id,
                'status_kegiatan' => $status_kegiatan,
                'waktu_mulai_kegiatan' => $waktu_mulai_kegiatan,
                'waktu_selesai_kegiatan' => $waktu_selesai_kegiatan,
                'status_kegiatan' => $status_kegiatan,
                'kegiatan_wbp_id' => $kegiatan_wbp_id,
                // 'wbp_profile_id' => $wbp_profile_id
            ]
        ];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>

