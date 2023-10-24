<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('require_files.php');

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") == 0) {
    header("Access-Control-Allow-Headers: Content-Type");
    $param_POST = json_decode(file_get_contents("php://input"));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve user credentials from the request
    $username       = (empty($_POST['username'])) ? trim($param_POST->username) : trim($_POST['username']);
    $passwordRaw    = (empty($_POST['password'])) ? trim($param_POST->password) : trim($_POST['password']);

    // $otmil_id = '1tcb4qwu-tkxh-lgfb-9e6f-xm1k3zcu0vo'; //salah
    $otmil_id = '1tcb4qwu-tkxh-lgfb-9e6f-xm1k3zcu0vou'; //bener

    $password       = sha1($username.$passwordRaw);

    // $data = json_decode(file_get_contents("php://input"));

    if (!empty($username) && !empty($password)) {
        $query = "SELECT
        U.user_id, U.username, U.email, U.phone, U.petugas_id, U.image, U.last_login,  U.lokasi_otmil_id,
        LO.nama_lokasi_otmil AS nama_lokasi_otmil,
        UR.role_name
    FROM
        user U
    JOIN user_role UR ON U.user_role_id = UR.user_role_id
    LEFT JOIN lokasi_otmil LO ON U.lokasi_otmil_id = LO.lokasi_otmil_id
    WHERE
        U.is_deleted = '0'
        AND COALESCE(LO.is_deleted, 0) = '0'
        AND UR.is_deleted = '0'
        AND U.is_suspended = '0'
        AND U.username = ?
        AND U.password = ?";
    
        
        try {
            $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare($query);
            $stmt->execute(array(
                $username,
                $password // Use the actual password provided by the client
            ));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userId = $user['user_id'];
                $userLogId = generateUUID();
                if ($user['lokasi_otmil_id'] != $otmil_id) {
                    $query_user_log = "INSERT INTO user_log (user_log_id, nama_user_log, timestamp, user_id) VALUES (?, 'Login gagal', NOW(), ?)";
    
                    $stmt_user_log = $conn->prepare($query_user_log);
    
                    $stmt_user_log->execute(array(
                        $userLogId,
                        $userId
                    ));
                    $result = array(
                        "status" => "error",
                        "message" => "Petugas tidak terdaftar di Otmil Jakarta",
                        // "user_id" => $userId
                    );
        
                    echo json_encode($result);
                    exit();
                }else{

                    $query_last_login = "UPDATE user SET last_login = NOW() WHERE user_id = ?";
                    $stmt_last_login = $conn->prepare($query_last_login);
                    $stmt_last_login->execute(array(
                        $user['user_id']
                    ));
    
                    $query_user_log = "INSERT INTO user_log (user_log_id, nama_user_log, timestamp, user_id) VALUES (?, 'Login', NOW(), ?)";
    
                    $stmt_user_log = $conn->prepare($query_user_log);
    
                    $stmt_user_log->execute(array(
                        $userLogId,
                        $userId
                    ));
    
    
                    // User authenticated successfully
                    $result = array(
                        "status" => "success",
                        "message" => "User authenticated successfully",
                        "data" => array( // Use "array()" instead of "{" for data
                            "user_id" => $user['user_id'],
                            "role_name" => $user['role_name'],
                            "username" => $user['username'],
                            "email" => $user['email'],
                            "phone" => $user['phone'],
                            "petugas_id" => $user['petugas_id'],
                            "image" => $user['image'],
                            "last_login" => $user['last_login'],
                            "nama_lokasi_otmil" => $user['nama_lokasi_otmil'],
                            "lokasi_otmil_id" => $user['lokasi_otmil_id']
                        )
                    );
    
                    echo json_encode($result);
                }

            } else {
                // Invalid username or password
                $result = array(
                    "status" => "error",
                    "message" => "Invalid username or password",
                    "user_id" => null
                );

                echo json_encode($result);
            }
        } catch (PDOException $e) {
            // Database error
            $result = array(
                "status" => "error",
                "message" => "Database error: " . $e->getMessage(),
                "user_id" => null
            );

            echo json_encode($result);
        }
    } else {
        // Invalid request format
        $result = array(
            "status" => "error",
            "message" => "Invalid request format",
            "user_id" => null
        );

        echo json_encode($result);
    }
} else {
    // Invalid request method
    $result = array(
        "status" => "error",
        "message" => "Invalid request method",
        "user_id" => null
    );

    // echo json_encode($_SERVER['REQUEST_METHOD']);
    echo json_encode($result);
}
?>
