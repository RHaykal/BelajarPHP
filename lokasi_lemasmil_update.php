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

$lokasi_lemasmil_id = isset($param_PUT->lokasi_lemasmil_id) ? trim($param_PUT->lokasi_lemasmil_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $check_query = "SELECT * FROM lokasi_lemasmil WHERE lokasi_lemasmil_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$lokasi_lemasmil_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Initialize the update query and parameter bindings
        $update_query = "UPDATE lokasi_lemasmil SET ";
        $update_params = [];

        // Check and update each field individually
        if (isset($param_PUT->nama_lokasi_lemasmil)) {
            $update_query .= "nama_lokasi_lemasmil = ?, ";
            $update_params[] = trim($param_PUT->nama_lokasi_lemasmil);
        }

        if (isset($param_PUT->latitude)) {
            $update_query .= "latitude = ?, ";
            $update_params[] = trim($param_PUT->latitude);
        }

        if (isset($param_PUT->longitude)) {
            $update_query .= "longitude = ?, ";
            $update_params[] = trim($param_PUT->longitude);
        }   

        // Remove the trailing comma and space
        $update_query = rtrim($update_query, ", ");

        $update_query .= " WHERE lokasi_lemasmil_id = ?";
        $update_params[] = $lokasi_lemasmil_id;

        // Execute the update query with parameter bindings
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute($update_params);

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "lokasi_lemasmil_id" => $lokasi_lemasmil_id,
                    "nama_lokasi_lemasmil" => $nama_lokasi_lemasmil,
                    "latitude" => $latitude,
                    "longitude" => $longitude
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
