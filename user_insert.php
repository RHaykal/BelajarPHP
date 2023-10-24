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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$user_id = generateUUID();
$year = date("Y-m-d", strtotime('+1 year'));
// $username       = (empty($_POST['username'])) ? trim($param_POST->username) : trim($_POST['username']);
$passwordRaw    = (empty($_POST['password'])) ? trim($param_POST->password) : trim($_POST['password']);
$user_role_id   = (empty($_POST['user_role_id'])) ? trim($param_POST->user_role_id) : trim($_POST['user_role_id']);
$email         = (empty($_POST['email'])) ? trim($param_POST->email) : trim($_POST['email']);
$phone         = (empty($_POST['phone'])) ? trim($param_POST->phone) : trim($_POST['phone']);
$lokasi_lemasmil_id         = (empty($_POST['lokasi_lemasmil_id'])) ? trim($param_POST->lokasi_lemasmil_id) : "";
$lokasi_otmil_id         = (empty($_POST['lokasi_otmil_id'])) ? trim($param_POST->lokasi_otmil_id) : "";
$is_suspended         = (empty($_POST['is_suspended'])) ? trim($param_POST->is_suspended) : 0;
$petugas_id         = (empty($_POST['petugas_id'])) ? trim($param_POST->petugas_id) : trim($_POST['petugas_id']);
$image        = (empty($_POST['image'])) ? trim($param_POST->image) : trim($_POST['image']);
$expiry_date = isset($param_POST->expiry_date) ? trim($param_POST->expiry_date) : $year;

// $expiry_date = (empty($_POST['expiry_date'])) ? date('Y-m-d', strtotime('+1 years')) : trim($_POST['expiry_date']);
// $expiry_date = isset($_POST['expiry_date']) ? trim($param_POST->expiry_date) : "hehe";

// $username       = isset($_POST['username']) ? $_POST['username'] : "";
// $passwordRaw    = isset($_POST['password']) ? $_POST['password'] : "";
// $user_role_id   = isset($_POST['user_role_id']) ? $_POST['user_role_id'] : "";
// $email         = isset($_POST['email']) ? $_POST['email'] : "";
// $phone         = isset($_POST['phone']) ? $_POST['phone'] : "";
// $lokasi_lemasmil_id         = isset($_POST['lokasi_lemasmil_id']) ? $_POST['lokasi_lemasmil_id'] : "";
// $lokasi_otmil_id         = isset($_POST['lokasi_otmil_id']) ? $_POST['lokasi_otmil_id'] : "";
// $is_suspended         = isset($_POST['is_suspended']) ? $_POST['is_suspended'] : 0;
// $petugas_id         = isset($_POST['petugas_id']) ? $_POST['petugas_id'] : "";
// $image        = isset($_POST['image']) ? $_POST['image'] : "";

$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');


$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'superadmin');
 
    $password = sha1($hashkey.$passwordRaw);

    $query = "SELECT * FROM user
     WHERE is_deleted = 0
     AND password = ? AND user_role_id = ? AND email = ? AND phone = ? AND lokasi_lemasmil_id = ? AND lokasi_otmil_id = ? AND petugas_id = ? ";
    $stmt = $conn->prepare($query);
    $stmt = $conn->prepare($query);
    $stmt->execute([$password, $user_role_id, $email, $phone, $lokasi_lemasmil_id, $lokasi_otmil_id, $petugas_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        $filename = md5($hashkey.time());
        $photoLocation = '/siram_api/images_user_data'.'/'.$filename.'.jpg';
        $photoResult = base64_to_jpeg($image, $photoLocation);   

        $query1 = "INSERT INTO user (user_id, password, user_role_id, email, phone, lokasi_lemasmil_id, lokasi_otmil_id, is_suspended, petugas_id, image, created_at, updated_at, expiry_date)
                                VALUES (?,          ?,       ?,          ?,     ?,          ?,                 ?,              ?,           ?,      ?,      ?,             ?,        ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindValue(1, $user_id, PDO::PARAM_STR);
        $stmt1->bindValue(2, $password, PDO::PARAM_STR);
        $stmt1->bindValue(3, $user_role_id, PDO::PARAM_STR);
        $stmt1->bindValue(4, $email, PDO::PARAM_STR);
        $stmt1->bindValue(5, $phone, PDO::PARAM_STR);
        $stmt1->bindValue(6, $lokasi_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindValue(7, $lokasi_otmil_id, PDO::PARAM_STR);
        $stmt1->bindValue(8, $is_suspended, PDO::PARAM_STR);
        $stmt1->bindValue(9, $petugas_id, PDO::PARAM_STR);
        $stmt1->bindValue(10, $photoResult, PDO::PARAM_STR);
        $stmt1->bindValue(11, $created_at, PDO::PARAM_STR);
        $stmt1->bindValue(12, $updated_at, PDO::PARAM_STR);
        $stmt1->bindValue(13, $expiry_date, PDO::PARAM_STR);

        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'user_id' => $user_id,
            'password' => $password,
            'user_role_id' => $user_role_id,
            'email' => $email,
            'phone' => $phone,
            'lokasi_lemasmil_id' => $lokasi_lemasmil_id,
            'lokasi_otmil_id' => $lokasi_otmil_id,
            'is_suspended' => $is_suspended,
            'petugas_id' => $petugas_id,
            'image' => $photoResult,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'expiry_date' => $expiry_date
        ]];
    }
} catch (PDOException $e) {
    $result['status'] = "error";
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>