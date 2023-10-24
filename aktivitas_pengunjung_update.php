<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Headers: Token");
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_PUT = json_decode(file_get_contents("php://input"));
} else {
    $param_PUT = $_POST;
}

$aktivitas_pengunjung_id = isset($param_PUT->aktivitas_pengunjung_id) ? trim($param_PUT->aktivitas_pengunjung_id) : "";

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $check_query = "SELECT * FROM aktivitas_pengunjung WHERE aktivitas_pengunjung_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$aktivitas_pengunjung_id]);
    $existing_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        throw new Exception("Data not found");
    } else {
        // Initialize the update query and parameter bindings
        $update_query = "UPDATE aktivitas_pengunjung SET ";
        $update_params = [];

        // Check and update each field individually
        if (isset($param_PUT->aktivitas_pengunjung_id)) {
            $update_query .= "aktivitas_pengunjung_id = ?, ";
            $update_params[] = trim($param_PUT->aktivitas_pengunjung_id);
        }

        // {
        //     "aktivitas_pengunjung_id" : "jqywvrx8-xs7z-qwaz-1jw0-ouluqg1c62o1",
        //     "nama_aktivitas_pengunjung": "Memberikan apaja",
        //     "waktu_mulai_kunjungan": "2001-2-2",
        //     "waktu_selesai_kunjungan": "2001-2-2",
        //     "tujuan_kunjungan": "memberikan Minum ke wbp",
        //     "ruangan_otmil_id": "ulr1ktwz-zh7v-k43w-fgby-yxlitdfxyk0m",
        //     "petugas_id": "1yj6jbi9-a4uz-gduw-ddcn-fow1nunlf9cz"
        // }

        if (isset($param_PUT->nama_aktivitas_pengunjung)) {
            $update_query .= "nama_aktivitas_pengunjung = ?, ";
            $update_params[] = trim($param_PUT->nama_aktivitas_pengunjung);
        }

        if (isset($param_PUT->waktu_mulai_kunjungan)) {
            $update_query .= "waktu_mulai_kunjungan = ?, ";
            $update_params[] = trim($param_PUT->waktu_mulai_kunjungan);
        }

        if (isset($param_PUT->waktu_selesai_kunjungan)) {
            $update_query .= "waktu_selesai_kunjungan = ?, ";
            $update_params[] = trim($param_PUT->waktu_selesai_kunjungan);
        }

        if (isset($param_PUT->tujuan_kunjungan)) {
            $update_query .= "tujuan_kunjungan = ?, ";
            $update_params[] = trim($param_PUT->tujuan_kunjungan);
        }

        if (isset($param_PUT->ruangan_otmil_id)) {
            $update_query .= "ruangan_otmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_otmil_id);
        }

        
        if (isset($param_PUT->ruangan_lemasmil_id)) {
            $update_query .= "ruangan_lemasmil_id = ?, ";
            $update_params[] = trim($param_PUT->ruangan_lemasmil_id);
        }

        if (isset($param_PUT->petugas_id)) {
            $update_query .= "petugas_id = ?, ";
            $update_params[] = trim($param_PUT->petugas_id);
        }

        if (isset($param_PUT->pengunjung_id)) {
            $update_query .= "pengunjung_id = ?, ";
            $update_params[] = trim($param_PUT->pengunjung_id);
        }


        // Remove the trailing comma and space
        $update_query = rtrim($update_query, ", ");

        $update_query .= " WHERE aktivitas_pengunjung_id = ?";
        $update_params[] = $aktivitas_pengunjung_id;

        // Execute the update query with parameter bindings
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute($update_params);

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "aktivitas_pengunjung_id" => $aktivitas_pengunjung_id,
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
