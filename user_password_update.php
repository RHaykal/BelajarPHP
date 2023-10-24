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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_PUT = json_decode(file_get_contents("php://input"));
}

$user_id = (empty($_POST['user_id'])) ? trim($param_PUT->user_id) : trim($_POST['user_id']);
$current_password_raw = (empty($_POST['password'])) ? trim($param_PUT->password) : trim($_POST['password']);
$new_password_raw = (empty($_POST['new_password'])) ? trim($param_PUT->new_password) : trim($_POST['new_password']);

$updated_at = date('Y-m-d H:i:s');

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
    $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $password = sha1($hashkey.$current_password_raw);
    $passwordNew = sha1($hashkey.$new_password_raw);
    
    // Check if the user exists
    $query = "SELECT * FROM user WHERE user_id = ? AND password = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $password]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        $result['message'] = "Password yang di input saat ini salah";
    } else {

        if ($password === $passwordNew) {
            $result['message'] = "New password cannot be the same as the old password";
        } else {
            $query = "UPDATE user SET 
            password = ?, 
            updated_at = ? 
            WHERE user_id = ? AND password = ?";

            $stmt = $conn->prepare($query);
            $stmt->execute([
                $passwordNew,
                $updated_at,
                $user_id, 
                $password
            ]);

            $result['message'] = "Password Update successfully";
            $result['status'] = "OK";
        }

    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>