<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve user credentials from the request
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->username) && isset($data->password)) {
        $username = $data->username;
        
        $password = sha1($data->username.$data->password);

        $query = "SELECT
        U.user_id, U.username, U.email, U.phone, U.petugas_id, U.image, U.last_login,
        CASE
            WHEN U.lokasi_lemasmil_id IS NOT NULL THEN LO.nama_lokasi_otmil
            ELSE NULL
        END AS nama_lokasi_lemasmil,
        CASE
            WHEN U.lokasi_otmil_id IS NOT NULL THEN LO.nama_lokasi_otmil
            ELSE NULL
        END AS nama_lokasi_otmil,
        UR.role_name
    FROM
        user U
        JOIN user_role UR ON U.user_role_id = UR.user_role_id
        LEFT JOIN lokasi_lemasmil LL ON 
            CASE
                WHEN U.lokasi_lemasmil_id IS NOT NULL THEN U.lokasi_lemasmil_id = LL.lokasi_lemasmil_id
                ELSE TRUE
            END
        LEFT JOIN lokasi_otmil LO ON 
            CASE
                WHEN U.lokasi_otmil_id IS NOT NULL THEN U.lokasi_otmil_id = LO.lokasi_otmil_id
                ELSE TRUE
            END
    WHERE
        U.is_deleted = '0' 
        AND COALESCE(LO.is_deleted, 0) = '0' AND COALESCE(LL.is_deleted, 0) = '0' 
        AND UR.is_deleted = '0' AND U.is_suspended = '0' AND U.username = ? AND U.password = ?";
    
        
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
                        "nama_lokasi_lemasmil" => $user['nama_lokasi_lemasmil'],
                        "nama_lokasi_otmil" => $user['nama_lokasi_otmil']
                    )
                );

                echo json_encode($result);
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
