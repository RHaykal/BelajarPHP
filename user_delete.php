<?php 
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: PUT, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    $param_PUT = json_decode(file_get_contents("php://input"));
}

$user_id = (empty($_POST['user_id'])) ? trim($param_PUT->user_id) : trim($_POST['user_id']);

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
     

        $query = "UPDATE user SET 
            is_deleted = 1,
            updated_at = ? 
            WHERE user_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([
            $updated_at,
            $user_id
        ]);

        $result['message'] = "Delete user data with user_id = $user_id success";
        $result['status'] = "OK";
        $result['records'] = [
            [
                'user_id' => $user_id
            ]
        ];
    
    }
} catch (PDOException $e) {
    $result['status'] = "error";
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
?>
