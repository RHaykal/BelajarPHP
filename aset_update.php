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
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} 
else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$aset_id = trim(isset($param_POST->aset_id) ? $param_POST->aset_id : "");
$nama_aset = trim(isset($param_POST->nama_aset) ? $param_POST->nama_aset : "");
$tipe_aset_id = trim(isset($param_POST->tipe_aset_id) ? $param_POST->tipe_aset_id : "");
$ruangan_otmil_id = trim(isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : "");
$ruangan_lemasmil_id = trim(isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : "");
$kondisi = trim(isset($param_POST->kondisi) ? $param_POST->kondisi : "");
$keterangan = trim(isset($param_POST->keterangan) ? $param_POST->keterangan : "");
$tanggal_masuk = trim(isset($param_POST->tanggal_masuk) ? $param_POST->tanggal_masuk : "");
$foto_barang = trim(isset($param_POST->foto_barang) ? $param_POST->foto_barang : "");
$updated_at = date('Y-m-d H:i:s');
$serial_number = trim(isset($param_POST->serial_number) ? $param_POST->serial_number : "");
$model = trim(isset($param_POST->model) ? $param_POST->model : "");
$merek = trim(isset($param_POST->merek) ? $param_POST->merek : "");
$garansi = trim(isset($param_POST->garansi) ? $param_POST->garansi : "");
// echo $foto_barang;
$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM aset WHERE aset_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $aset_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) == 0) {
        throw new Exception("Aset not found");
    } else {
        $query3 = "UPDATE aset SET
            nama_aset = ?,
            tipe_aset_id = ?,
            ruangan_otmil_id = ?,
            ruangan_lemasmil_id = ?,
            kondisi = ?,
            keterangan = ?,
            tanggal_masuk = ?,
            updated_at = ?,
            serial_number = ?,
            model = ?,
            merek = ?,
            garansi = ?";

        // Create an array to store bind parameters
        $bindParams = [
            $nama_aset,
            $tipe_aset_id,
            $ruangan_otmil_id,
            $ruangan_lemasmil_id,
            $kondisi,
            $keterangan,
            $tanggal_masuk,
            $updated_at,
            $serial_number,
            $model,
            $merek,
            $garansi
        ];

        if (strpos($foto_barang, 'data:image/') === 0) {
            $query3 .= ", image = ?";
            $filename = md5($nama_aset . time());
            $photoLocation = '/siram_api/images_aset_data' . '/' . $filename . '.jpg';
            $photoResult = base64_to_jpeg($foto_barang, $photoLocation);
            $bindParams[] = $photoResult;
        }

        $query3 .= " WHERE aset_id = ?";

        $stmt3 = $conn->prepare($query3);

        // Bind parameters using a loop
        for ($i = 1; $i <= count($bindParams); $i++) {
            $stmt3->bindValue($i, $bindParams[$i - 1], PDO::PARAM_STR);
        }

        $stmt3->bindValue(count($bindParams) + 1, $aset_id, PDO::PARAM_STR);

        $stmt3->execute();

        
        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "aset_id" => $aset_id,
                    "nama_aset" => $nama_aset,
                    "tipe_aset_id" => $tipe_aset_id,
                    "ruangan_otmil_id" => $ruangan_otmil_id,
                    "ruangan_lemasmil_id" => $ruangan_lemasmil_id,
                    "kondisi" => $kondisi,
                    "keterangan" => $keterangan,
                    "tanggal_masuk" => $tanggal_masuk,
                    // "image" => $photoResult,
                    "updated_at" => $updated_at,
                    "serial_number" => $serial_number,
                    "model" => $model,
                    "merek" => $merek,
                    "garansi" => $garansi
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
