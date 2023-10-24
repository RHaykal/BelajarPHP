    <?php
    session_start();
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

    require_once('require_files.php');
    // require_once('function.php');

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

    $dokumen_persidangan_id = generateUUID();
    $nama_dokumen_persidangan = isset($param_POST->nama_dokumen_persidangan) ? trim($param_POST->nama_dokumen_persidangan) : "";

    // Handle file upload
    if (isset($_FILES['pdf_file'])) {
        $target_dir = "/var/www/api/siram_api/document_sidang/"; // Define the directory where you want to save the uploaded PDF files.

        // Create the target directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $pdf_file_name = $_FILES['pdf_file']['name'];
        $target_file = $target_dir . $pdf_file_name;

        // Check if the file is a valid PDF file.
        $fileType = pathinfo($target_file, PATHINFO_EXTENSION);
        if (strtolower($fileType) === "pdf") {
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_file)) {
                // File upload successful. Proceed to insert metadata into the database.
                $link_dokumen_persidangan = $target_file; // Update the link to the saved file path
                $nama_dokumen_persidangan = $pdf_file_name; // Update the name to the saved file name
                $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $query = "INSERT INTO dokumen_persidangan (dokumen_persidangan_id, nama_dokumen_persidangan, link_dokumen_persidangan) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(1, $dokumen_persidangan_id, PDO::PARAM_STR);
                $stmt->bindValue(2, $nama_dokumen_persidangan, PDO::PARAM_STR);
                $stmt->bindValue(3, $link_dokumen_persidangan, PDO::PARAM_STR);
                $stmt->execute();

                // Insert metadata into the database (you can use the code you already have).

                $result = [
                    "status" => "OK",
                    "message" => "Data berhasil disimpan",
                    "records" => [
                        "dokumen_persidangan_id" => $dokumen_persidangan_id,
                        "nama_dokumen_persidangan" => $nama_dokumen_persidangan,
                        "link_dokumen_persidangan" => $link_dokumen_persidangan
                    ]
                ];
            } else {
                $result = [
                    "status" => "Error",
                    "message" => "Failed to upload the PDF file."
                ];
            }
        } else {
            $result = [
                "status" => "Error",
                "message" => "Invalid file type. Please upload a PDF file."
            ];
        }
    } else {
        $result = [
            "status" => "Error",
            "message" => "No PDF file was uploaded."
        ];
    }

    echo json_encode($result);
    ?>
