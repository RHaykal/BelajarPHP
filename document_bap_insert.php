<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$dokumen_bap_id = generateUUID();
$nama_dokumen_bap = isset($param_POST->nama_dokumen_bap) ? trim($param_POST->nama_dokumen_bap) : "";

// Check if base64 encoded PDF content is provided
if (isset($param_POST->link_dokumen_bap)) {
    $base64_encoded_pdf = $param_POST->link_dokumen_bap;

    // Decode the base64 content to binary
    $pdf_content = base64_decode($base64_encoded_pdf);

    // Define the file path where you want to save the PDF
    $pdf_file_path = "/var/www/api/siram_api/document_bap/" . $dokumen_bap_id . ".pdf";

    // Save the PDF to the specified file path
    file_put_contents($pdf_file_path, $pdf_content);

    // Insert metadata into the database
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

    $query = "INSERT INTO dokumen_bap (dokumen_bap_id, nama_dokumen_bap, link_dokumen_bap) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $dokumen_bap_id, PDO::PARAM_STR);
    $stmt->bindValue(2, $nama_dokumen_bap, PDO::PARAM_STR);
    $stmt->bindValue(3, $pdf_file_path, PDO::PARAM_STR);
    $stmt->execute();

    $result = [
        "status" => "OK",
        "message" => "Data berhasil disimpan",
        "records" => [
            "dokumen_bap_id" => $dokumen_bap_id,
            "nama_dokumen_bap" => $nama_dokumen_bap,
            "link_dokumen_bap" => $pdf_file_path
        ]
    ];
} else {
    $result = [
        "status" => "Error",
        "message" => "No PDF file content provided."
    ];
}

echo json_encode($result);
?>
