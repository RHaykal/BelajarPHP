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
    $param_PUT = json_decode(file_get_contents("php://input"));
}

$year = date("Y-m-d", strtotime('+1 year'));

$user_id = (empty($_POST['user_id'])) ? trim($param_PUT->user_id) : trim($_POST['user_id']);
$username = (empty($_POST['username'])) ? trim($param_PUT->username) : trim($_POST['username']);
// $passwordRaw = (empty($_POST['password'])) ? trim($param_PUT->password) : trim($_POST['password']);
$user_role_id = (empty($_POST['user_role_id'])) ? trim($param_PUT->user_role_id) : trim($_POST['user_role_id']);
$email = (empty($_POST['email'])) ? trim($param_PUT->email) : trim($_POST['email']);
$phone = (empty($_POST['phone'])) ? trim($param_PUT->phone) : trim($_POST['phone']);
$lokasi_lemasmil_id = (empty($_POST['lokasi_lemasmil_id'])) ? trim($param_PUT->lokasi_lemasmil_id) : trim($_POST['lokasi_lemasmil_id']);
$lokasi_otmil_id = (empty($_POST['lokasi_otmil_id'])) ? trim($param_PUT->lokasi_otmil_id) : trim($_POST['lokasi_otmil_id']);
$is_suspended = (empty($_POST['is_suspended'])) ? trim($param_PUT->is_suspended) : 0;
$petugas_id = (empty($_POST['petugas_id'])) ? trim($param_PUT->petugas_id) : trim($_POST['petugas_id']);
$image = (empty($_POST['image'])) ? trim($param_PUT->image) : trim($_POST['image']);
$expiry_date = isset($param_PUT->expiry_date) ? trim($param_PUT->expiry_date) : $year;


$updated_at = date('Y-m-d H:i:s');

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'superadmin');

    // Check if the user exists
    $query = "SELECT * FROM user WHERE user_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        $result['message'] = "User not found";
    } else {
        // Update the user record
        // $password = sha1($username . $passwordRaw);

        if($image == null || $image == ""){

            $query = "UPDATE user SET 
            username = ?, 
            user_role_id = ?, 
            email = ?, 
            phone = ?, 
            lokasi_lemasmil_id = ?, 
            lokasi_otmil_id = ?, 
            is_suspended = ?, 
            petugas_id = ?, 
            expiry_date = ?, 
            updated_at = ?
            WHERE user_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            $username,
            $user_role_id,
            $email,
            $phone,
            $lokasi_lemasmil_id,
            $lokasi_otmil_id,
            $is_suspended,
            $petugas_id,
            $expiry_date,
            $updated_at,
            $user_id
        ]);
        
        // $password,

        $result['message'] = "Update data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'user_id' => $user_id,
                'username' => $username,
                'user_role_id' => $user_role_id,
                'email' => $email,
                'phone' => $phone,
                'lokasi_lemasmil_id' => $lokasi_lemasmil_id,
                'lokasi_otmil_id' => $lokasi_otmil_id,
                'is_suspended' => $is_suspended,
                'petugas_id' => $petugas_id,
                'expiry_date' => $expiry_date,
                'updated_at' => $updated_at
            ]
            // 'password' => $password,
        ];        }else{

        $filename = md5($username . time());
        $photoLocation = '/siram_api/images_user_data' . '/' . $filename . '.jpg';
        $photoResult = base64_to_jpeg($image, $photoLocation);


        $query = "UPDATE user SET 
            username = ?, 
            user_role_id = ?, 
            email = ?, 
            phone = ?, 
            lokasi_lemasmil_id = ?, 
            lokasi_otmil_id = ?, 
            is_suspended = ?, 
            petugas_id = ?, 
            expiry_date =?,
            image = ?,
            updated_at = ? 
            WHERE user_id = ?";
            // password = ?, 

        $stmt = $conn->prepare($query);
        $stmt->execute([
            $username,
            $user_role_id,
            $email,
            $phone,
            $lokasi_lemasmil_id,
            $lokasi_otmil_id,
            $is_suspended,
            $petugas_id,
            $expiry_date,
            $photoResult,
            $updated_at,
            $user_id
        ]);
        // $password,

        $result['message'] = "Update data successfully";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'user_id' => $user_id,
                'username' => $username,
                'user_role_id' => $user_role_id,
                'email' => $email,
                'phone' => $phone,
                'lokasi_lemasmil_id' => $lokasi_lemasmil_id,
                'lokasi_otmil_id' => $lokasi_otmil_id,
                'is_suspended' => $is_suspended,
                'petugas_id' => $petugas_id,
                'expiry_date' => $expiry_date,
                'updated_at' => $updated_at
            ]
        ];
        // 'password' => $password,
    }
    }
} catch (PDOException $e) {
    $result['status'] = "error";
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>
