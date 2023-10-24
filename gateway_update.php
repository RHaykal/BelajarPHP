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

$gateway_id = isset($param_PUT->gateway_id) ? trim($param_PUT->gateway_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $check_query = "SELECT * FROM gateway WHERE gateway_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$gateway_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Initialize the update query and parameter bindings
        $update_query = "UPDATE gateway SET ";
        $update_params = [];

        // Check and update each field individually
        if (isset($param_PUT->nama_gateway)) {
            $update_query .= "nama_gateway = ?, ";
            $update_params[] = trim($param_PUT->nama_gateway);
        }

        if (isset($param_PUT->gmac)) {
            $update_query .= "gmac = ?, ";
            $update_params[] = trim($param_PUT->gmac);
        }

        if (isset($param_PUT->ruangan_otmil_id)) {
            $update_query .= "ruangan_otmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_otmil_id);
        }

        if (isset($param_PUT->ruangan_lemasmil_id)) {
            $update_query .= "ruangan_lemasmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_lemasmil_id);
        }


        if (isset($param_PUT->status_gateway)) {
            $update_query .= "status_gateway = ?, ";
            $update_params[] = trim($param_PUT->status_gateway);
        }

        // Remove the trailing comma and space
        $update_query = rtrim($update_query, ", ");

        $update_query .= " WHERE gateway_id = ?";
        $update_params[] = $gateway_id;

        // Execute the update query with parameter bindings
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute($update_params);

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "gateway_id" => $gateway_id,
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
