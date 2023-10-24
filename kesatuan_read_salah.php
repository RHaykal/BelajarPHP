<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input from the request body
    $input = file_get_contents('php://input');
    
    // Check if the input is valid JSON
    $params = json_decode($input);
    
    if ($params !== null) {
        // Check if 'a' parameter is present in the JSON payload
        if (isset($params->a)) {
            $a = $params->a;
            
            // Process other parameters like pageSize, pageIndex, sorts, and filters here

            $result = ['message' => 'Data received successfully', 'status' => 'OK', 'a' => $a];
        } else {
            $result = ['message' => 'Parameter "a" is missing', 'status' => 'Error'];
        }
    } else {
        $result = ['message' => 'Invalid JSON format', 'status' => 'Error'];
    }
} else {
    $result = ['message' => 'Unsupported request method', 'status' => 'Error'];
}

// Set the response content type to JSON
header('Content-Type: application/json');

// Return the result as JSON
echo json_encode($result);
?>
