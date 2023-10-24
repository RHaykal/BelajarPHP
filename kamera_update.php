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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_PUT = json_decode(file_get_contents("php://input"));
} else {
    $param_PUT = $_POST;
}

$kamera_id = isset($param_PUT->kamera_id) ? trim($param_PUT->kamera_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $check_query = "SELECT * FROM kamera WHERE kamera_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$kamera_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Initialize the update query and parameter bindings
        $update_query = "UPDATE kamera SET ";
        $update_params = [];

        // Check and update each field individually
        if (isset($param_PUT->nama_kamera)) {
            $update_query .= "nama_kamera = ?, ";
            $update_params[] = trim($param_PUT->nama_kamera);
        }

        if (isset($param_PUT->url_rtsp)) {
            $update_query .= "url_rtsp = ?, ";
            $update_params[] = trim($param_PUT->url_rtsp);
        }

        if (isset($param_PUT->ip_address)) {
            $update_query .= "ip_address = ?, ";
            $update_params[] = trim($param_PUT->ip_address);
        }

        if (isset($param_PUT->ruangan_otmil_id)) {
            $update_query .= "ruangan_otmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_otmil_id);
        }

        if (isset($param_PUT->ruangan_lemasmil_id)) {
            $update_query .= "ruangan_lemasmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_lemasmil_id);
        }

        if (isset($param_PUT->merk)) {
            $update_query .= "merk = ?, ";
            $update_params[] = trim($param_PUT->merk);
        }

        if (isset($param_PUT->model)) {
            $update_query .= "model = ?, ";
            $update_params[] = trim($param_PUT->model);
        }

        if (isset($param_PUT->status_kamera)) {
            $update_query .= "status_kamera = ?, ";
            $update_params[] = trim($param_PUT->status_kamera);
        }

        // Remove the trailing comma and space
        $update_query = rtrim($update_query, ", ");

        $update_query .= " WHERE kamera_id = ?";
        $update_params[] = $kamera_id;

        // Execute the update query with parameter bindings
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute($update_params);

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "kamera_id" => $kamera_id,
                ]
            ]
        ];
    }
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>
