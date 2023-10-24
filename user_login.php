<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('require_files.php');

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    header("Access-Control-Allow-Headers: Content-Type");
    $param_POST = json_decode(file_get_contents("php://input"));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve user credentials from the request
    $username = (empty($_POST['nrp'])) ? trim($param_POST->nrp) : trim($_POST['nrp']);
    $passwordRaw = (empty($_POST['password'])) ? trim($param_POST->password) : trim($_POST['password']);
    $password = sha1($hashkey . $passwordRaw);

    if (!empty($username) && !empty($password)) {
        $query = "SELECT
        U.user_id, U.username, U.email, U.phone, U.petugas_id, U.last_login, U.expiry_date, U.lokasi_lemasmil_id, U.lokasi_otmil_id,
        LL.nama_lokasi_lemasmil AS nama_lokasi_lemasmil,
        LO.nama_lokasi_otmil AS nama_lokasi_otmil,
        UR.role_name,
        P.nama,
        P.nrp,
        P.foto_wajah
        FROM
        user U
        JOIN user_role UR ON U.user_role_id = UR.user_role_id
        LEFT JOIN lokasi_lemasmil LL ON U.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
        LEFT JOIN lokasi_otmil LO ON U.lokasi_otmil_id = LO.lokasi_otmil_id
        LEFT JOIN petugas P ON U.petugas_id = P.petugas_id
        WHERE
        U.is_deleted = '0'
        AND COALESCE(LO.is_deleted, 0) = '0'
        AND COALESCE(LL.is_deleted, 0) = '0'
        AND UR.is_deleted = '0'
        AND U.is_suspended = '0'
        AND P.nrp = ?
        AND U.password = ?";

        try {
            $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare($query);
            $stmt->execute(array(
                $username,
                $password
            ));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if($user['expiry_date'] >= date("Y-m-d")) {
                    // Generate a token
                    $token = createToken($user);

                    // Update last login and log user login
                    $userId = $user['user_id'];
                    $userLogId = generateUUID();

                    // Update last login
                    $query_last_login = "UPDATE user SET last_login = NOW() WHERE user_id = ?";
                    $stmt_last_login = $conn->prepare($query_last_login);
                    $stmt_last_login->execute(array($user['user_id']));

                    // Insert user login log
                    $query_user_log = "INSERT INTO user_log (user_log_id, nama_user_log, timestamp, user_id) VALUES (?, 'Login', NOW(), ?)";
                    $stmt_user_log = $conn->prepare($query_user_log);
                    $stmt_user_log->execute(array($userLogId, $userId));

                    // Calculate token expiration (2 days from now)
                    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+2 days'));

                    // Get user_login_id from the user_login table
                    $query_select_user_login_id = "SELECT user_login_id FROM user_login WHERE user_id = ?";
                    $stmt_select_user_login_id = $conn->prepare($query_select_user_login_id);
                    $stmt_select_user_login_id->execute(array($user['user_id']));
                    $userLoginId = $stmt_select_user_login_id->fetchColumn();

                    if (!$userLoginId) {
                        // Generate a unique user_login_id using UUID
                        $userLoginId = generateUUID();

                        // Jika user_login_id belum ada, tambahkan data baru ke user_login
                        $query_insert_user_login = "INSERT INTO user_login (user_login_id, user_id, token, token_expiry) VALUES (?, ?, ?, ?)";
                        $stmt_insert_user_login = $conn->prepare($query_insert_user_login);
                        $stmt_insert_user_login->execute(array($userLoginId, $user['user_id'], $token, $tokenExpiry));
                    } else {
                        // Jika user_login_id sudah ada, perbarui token dan token_expiry
                        $query_update_user_login = "UPDATE user_login SET token = ?, token_expiry = ? WHERE user_login_id = ?";
                        $stmt_update_user_login = $conn->prepare($query_update_user_login);
                        $stmt_update_user_login->execute(array($token, $tokenExpiry, $userLoginId));
                    }


                    $auth = array(
                        "token" => $token,
                        "token_expiry" => $tokenExpiry
                    );

                    $user = array(
                        "user_id" => $user['nrp'],
                        "role_name" => $user['role_name'],
                        "petugas_id" => $user['petugas_id'],
                        "nama_petugas" => $user['nama'],
                        "email" => $user['email'],
                        "phone" => $user['phone'],
                        "image" => $user['foto_wajah'],
                        "last_login" => $user['last_login'],
                        "nama_lokasi_lemasmil" => $user['nama_lokasi_lemasmil'],
                        "nama_lokasi_otmil" => $user['nama_lokasi_otmil'],
                        "lokasi_lemasmil_id" => $user['lokasi_lemasmil_id'],
                        "lokasi_otmil_id" => $user['lokasi_otmil_id'],
                        "expiry_date" => $user['expiry_date']
                    );
                

                    // Return the token in the response
                    $result = array(
                        "status" => "success",
                        "message" => "User authenticated successfully",
                        "user" => $user,	
                        "auth" => $auth
                    );

                    echo json_encode($result);
                } else {
                    $result = array(
                        "status" => "error",
                        "message" => "Akun sudah expired, hubungi administrator anda",
                        "user_id" => null
                    );
            
                    echo json_encode($result);
                }

            } else {
                // Invalid username or password
                $result = array(
                    "status" => "error",
                    "message" => "Invalid nrp or password",
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

    echo json_encode($result);
}

// Function to create a token
function createToken($user) {
    $data = array(
        "user_id" => $user['user_id'],
        "role_name" => $user['role_name'],
        "username" => $user['username'],
        "email" => $user['email'],
        // Add other user data here
    );

    $expire = time() + (60 * 60 * 24 * 2); // 2 days expiration

    $token = array(
        "data" => $data,
        "expire" => $expire
    );

    // Use JSON Web Tokens (JWT) library for a secure token generation

    // In this example, we'll just use a simple hash
    $token = sha1(json_encode($token));

    return $token;
}
?>
