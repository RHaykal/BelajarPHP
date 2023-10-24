<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Token, Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
        $param_POST = json_decode(file_get_contents("php://input"));
    } else {
        $param_POST = $_POST;
    }

    $kasus_id = isset($param_POST->kasus_id) ? trim($param_POST->kasus_id) : '';
    $nama_bukti_kasus = isset($param_POST->nama_bukti_kasus) ? trim($param_POST->nama_bukti_kasus) : '';
    $nomor_barang_bukti = isset($param_POST->nomor_barang_bukti) ? trim($param_POST->nomor_barang_bukti) : '';
    $dokumen_barang_bukti = isset($param_POST->dokumen_barang_bukti) ? trim($param_POST->dokumen_barang_bukti) : '';
    $image = isset($param_POST->gambar_barang_bukti) ? trim($param_POST->gambar_barang_bukti) : '';
    $keterangan = isset($param_POST->keterangan) ? trim($param_POST->keterangan) : '';
    $tanggal_diambil = isset($param_POST->tanggal_diambil) ? date("Y-m-d", strtotime(trim($param_POST->tanggal_diambil))) : '';

    $barang_bukti_kasus_id = generateUUID();

    // Check if the data already exists in the database
    $query = "SELECT * FROM barang_bukti_kasus WHERE 
        kasus_id = ? AND
        nama_bukti_kasus = ? AND
        nomor_barang_bukti = ? AND
        dokumen_barang_bukti = ? AND
        keterangan = ? AND
        tanggal_diambil = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$kasus_id, $nama_bukti_kasus, $nomor_barang_bukti, $dokumen_barang_bukti, $keterangan, $tanggal_diambil]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";
    } else {
        if (isset($param_POST->pdf_file_base64)) {
            $base64_encoded_pdf = $param_POST->pdf_file_base64;

            // Decode the base64 content to binary
            $pdf_content = base64_decode($base64_encoded_pdf);

            // Define the file path where you want to save the PDF
            $pdf_file_path = "/var/www/api/siram_api/document_barang_bukti_kasus/" . $barang_bukti_kasus_id . ".pdf";

            // Save the PDF to the specified file path
            if (file_put_contents($pdf_file_path, $pdf_content) === false) {
                $result['message'] = "Failed to save PDF file";
                $result['status'] = "Error";
                exit;
            }
        }

        // File upload and processing for image (assuming it's a base64 image)
        $fileName = md5($nama_bukti_kasus . time());
        $photoLocation = '/var/www/api/siram_api/images_barang_bukti_kasus/' . $fileName . '.jpg';
        $photoResult = base64_to_jpeg($image, $photoLocation);

        // Insert data into the database
        $query1 = "INSERT INTO barang_bukti_kasus (
            barang_bukti_kasus_id, 
            kasus_id,
            nama_bukti_kasus,
            nomor_barang_bukti,
            dokumen_barang_bukti,
            gambar_barang_bukti,
            keterangan,
            tanggal_diambil
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $barang_bukti_kasus_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $kasus_id, PDO::PARAM_STR);
        $stmt1->bindParam(3, $nama_bukti_kasus, PDO::PARAM_STR);
        $stmt1->bindParam(4, $nomor_barang_bukti, PDO::PARAM_STR);
        $stmt1->bindParam(5, $pdf_file_path, PDO::PARAM_STR);
        $stmt1->bindParam(6, $photoResult, PDO::PARAM_STR);
        $stmt1->bindParam(7, $keterangan, PDO::PARAM_STR);
        $stmt1->bindParam(8, $tanggal_diambil, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'barang_bukti_kasus_id' => $barang_bukti_kasus_id,
            'kasus_id' => $kasus_id,
            'nama_bukti_kasus' => $nama_bukti_kasus,
            'nomor_barang_bukti' => $nomor_barang_bukti,
            'dokumen_barang_bukti' => $pdf_file_path,
            'gambar_barang_bukti' => $photoResult,
            'keterangan' => $keterangan,
            'tanggal_diambil' => $tanggal_diambil
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($result);
