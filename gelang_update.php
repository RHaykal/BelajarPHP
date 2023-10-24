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

$gelang_id = isset($param_PUT->gelang_id) ? trim($param_PUT->gelang_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $check_query = "SELECT * FROM gelang WHERE gelang_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$gelang_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Initialize the update query and parameter bindings
        $update_query = "UPDATE gelang SET ";
        $update_params = [];

        // Check and update each field individually
        if (isset($param_PUT->nama_gelang)) {
            $update_query .= "nama_gelang = ?, ";
            $update_params[] = trim($param_PUT->nama_gelang);
        }

        if (isset($param_PUT->dmac)) {
            $update_query .= "dmac = ?, ";
            $update_params[] = trim($param_PUT->dmac);
        }

        if (isset($param_PUT->tanggal_pasang)) {
            $update_query .= "tanggal_pasang = ?, ";
            $update_params[] = trim($param_PUT->tanggal_pasang);
        }

        if (isset($param_PUT->tanggal_aktivasi)) {
            $update_query .= "tanggal_aktivasi = ?, ";
            $update_params[] = trim($param_PUT->tanggal_aktivasi);
        }

        if (isset($param_PUT->ruangan_otmil_id)) {
            $update_query .= "ruangan_otmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_otmil_id);
        }

        if (isset($param_PUT->ruangan_lemasmil_id)) {
            $update_query .= "ruangan_lemasmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_lemasmil_id);
        }

        if (isset($param_PUT->baterai)) {
            $update_query .= "baterai = ?, ";
            $update_params[] = trim($param_PUT->baterai);
        }

        // Remove the trailing comma and space
        $update_query = rtrim($update_query, ", ");

        $update_query .= " WHERE gelang_id = ?";
        $update_params[] = $gelang_id;

        // Execute the update query with parameter bindings
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute($update_params);

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "gelang_id" => $gelang_id,
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
