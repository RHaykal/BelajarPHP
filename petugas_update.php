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
} else {
    parse_str(file_get_contents("php://input"), $param_POST);
}

$petugas_id = isset($param_POST->petugas_id) ? trim($param_POST->petugas_id) : "";
$nama = isset($param_POST->nama) ? trim($param_POST->nama) : "";
$pangkat_id = isset($param_POST->pangkat_id) ? trim($param_POST->pangkat_id) : "";
$kesatuan_id = isset($param_POST->kesatuan_id) ? trim($param_POST->kesatuan_id) : "";
$tempat_lahir = isset($param_POST->tempat_lahir) ? trim($param_POST->tempat_lahir) : "";
$tanggal_lahir = isset($param_POST->tanggal_lahir) ? trim($param_POST->tanggal_lahir) : "";
$jenis_kelamin = isset($param_POST->jenis_kelamin) ? trim($param_POST->jenis_kelamin) : 0;
$provinsi_id = isset($param_POST->provinsi_id) ? trim($param_POST->provinsi_id) : "";
$kota_id = isset($param_POST->kota_id) ? trim($param_POST->kota_id) : "";
$alamat = isset($param_POST->alamat) ? trim($param_POST->alamat) : "";
$agama_id = isset($param_POST->agama_id) ? trim($param_POST->agama_id) : "";
$status_kawin_id = isset($param_POST->status_kawin_id) ? trim($param_POST->status_kawin_id) : "";
$pendidikan_id = isset($param_POST->pendidikan_id) ? trim($param_POST->pendidikan_id) : "";
$bidang_keahlian_id = isset($param_POST->bidang_keahlian_id) ? trim($param_POST->bidang_keahlian_id) : "";
$foto_wajah = isset($param_POST->foto_wajah) ? trim($param_POST->foto_wajah) : "";
$jabatan = isset($param_POST->jabatan) ? trim($param_POST->jabatan) : "";
$divisi = isset($param_POST->divisi) ? trim($param_POST->divisi) : "";
// $nomor_petugas = isset($param_POST->nomor_petugas) ? trim($param_POST->nomor_petugas) : "";
$lokasi_otmil_id = isset($param_POST->lokasi_otmil_id) ? trim($param_POST->lokasi_otmil_id) : "";
$lokasi_lemasmil_id = isset($param_POST->lokasi_lemasmil_id) ? trim($param_POST->lokasi_lemasmil_id) : "";
$matra_id = isset($param_POST->matra_id) ? trim($param_POST->matra_id) : "";
$nrp = isset($param_POST->nrp) ? trim($param_POST->nrp) : "";
$grup_petugas_id = isset($param_POST->grup_petugas_id) ? trim($param_POST->grup_petugas_id) : "";

$updated_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM petugas WHERE petugas_id = ? AND is_deleted = 0";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $petugas_id, PDO::PARAM_STR);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $foto_wajah_fr = $res['foto_wajah_fr'];
    $new_foto_wajah_fr = $nrp . $nama . uniqid();
    $new_foto_wajah_fr = str_replace(' ', '', $new_foto_wajah_fr);


    if (count($res) == 0) {
        throw new Exception("Petugas not found");
    } else {
        $query3 = "UPDATE petugas SET
            nama = ?,
            pangkat_id = ?,
            kesatuan_id = ?,
            tempat_lahir = ?,
            tanggal_lahir = ?,
            jenis_kelamin = ?,
            provinsi_id = ?,
            kota_id = ?,
            alamat = ?,
            agama_id = ?,
            status_kawin_id = ?,
            pendidikan_id = ?,
            bidang_keahlian_id = ?,
            jabatan = ?,
            divisi = ?,
            lokasi_otmil_id = ?,
            lokasi_lemasmil_id = ?,
            matra_id = ?,
            nrp = ?,
            grup_petugas_id = ?,
            updated_at = ?";

        $bindParams = [
            $nama,
            $pangkat_id,
            $kesatuan_id,
            $tempat_lahir,
            $tanggal_lahir,
            $jenis_kelamin,
            $provinsi_id,
            $kota_id,
            $alamat,
            $agama_id,
            $status_kawin_id,
            $pendidikan_id,
            $bidang_keahlian_id,
            $jabatan,
            $divisi,
            $lokasi_otmil_id,
            $lokasi_lemasmil_id,
            $matra_id,
            $nrp,
            $grup_petugas_id,
            $updated_at
        ];

        // if (str_starts_with($foto_wajah, 'data:image/')) {
        //     $query3 .= ", foto_wajah = ?";
        //     $filename = md5($nama . $updated_at);
        //     $photoLocation = '/siram_api/images_petugas_data' . '/' . $filename . '.jpg';
        //     $photoResult = base64_to_jpeg($foto_wajah, $photoLocation);
        //     $bindParams[] = $photoResult;
        // }

        // Check if $foto_wajah is a data URI (starts with 'data:image/')
        if (strpos($foto_wajah, 'data:image/') === 0) {
            $dataImageExploded = explode(',', $foto_wajah);

            $formattedImage = '';
            // Handle different cases for base64 string format
            if (count($dataImageExploded) > 1) {
                $formattedImage = $dataImageExploded[1];
            } else {
                $formattedImage = $dataImageExploded[0];
            }
            
            // $imageIDArray = ["23456789supardi2"];
            $imageIDArray = [$foto_wajah_fr];
    
            $data_array_delete = array(
                "groupID" => "1",
                // ID of face group 
                "dbID" => "testcideng",
                // ID of face database
                "imageIDs" => $imageIDArray               
            );
            $json_data_delete = json_encode($data_array_delete);
            // $json_data_delete = json_encode($data_array_delete);
    
            $make_call_delete = callAPI('DELETE', 'https://faceengine.deepcam.cn/pipeline/api/face/delete', $json_data_delete);
            $responseDeleteFR = $make_call_delete;
    
            // print_r($responseDeleteFR);
            $formattedResponseDelete = json_decode($responseDeleteFR, true);
            if ($formattedResponseDelete['code'] != '1000') {
                throw new Exception("Failed to delete FR");
            }
    
            $data_array_insert = array(
                "groupID" => "1",
                // ID of face group 
                "dbID" => "testcideng",
                // ID of face database
                "imageID" => $new_foto_wajah_fr,               // ID of face image
                "imageData" => $formattedImage // Face image in base64 format           
            );
            $json_data_insert = json_encode($data_array_insert);
    
            $make_call_insert = callAPI('POST', 'https://faceengine.deepcam.cn/pipeline/api/face/add', $json_data_insert);
            $responseInsertFR = $make_call_insert;
            // print_r($responseInsertFR);
            $formattedResponseInsert = json_decode($responseInsertFR, true);
            if ($formattedResponseInsert['code'] != '1000') {
                throw new Exception("Failed to insert FR");
            }

            $query3 .= ", foto_wajah = ?";
            $query3 .= ", foto_wajah_fr = ?";
            $filename = md5($nama . $updated_at);
            $photoLocation = '/siram_api/images_petugas_data' . '/' . $filename . '.jpg';
            $photoResult = base64_to_jpeg($foto_wajah, $photoLocation);
            $bindParams[] = $photoResult;
            $bindParams[] = $new_foto_wajah_fr;
        }

        $query3 .= " WHERE petugas_id = ?";
        $bindParams[] = $petugas_id;

        $stmt3 = $conn->prepare($query3);
        $stmt3->execute($bindParams);

        $result = [
            "status" => "OK",
            "message" => "Successfully updated",
            "records" => [
                [
                    "petugas_id" => $petugas_id,
                    "nama" => $nama,
                    "pangkat_id" => $pangkat_id,
                    "kesatuan_id" => $kesatuan_id,
                    "tempat_lahir" => $tempat_lahir,
                    "tanggal_lahir" => $tanggal_lahir,
                    "jenis_kelamin" => $jenis_kelamin,
                    "provinsi_id" => $provinsi_id,
                    "kota_id" => $kota_id,
                    "alamat" => $alamat,
                    "agama_id" => $agama_id,
                    "status_kawin_id" => $status_kawin_id,
                    "pendidikan_id" => $pendidikan_id,
                    "bidang_keahlian_id" => $bidang_keahlian_id,
                    "foto_wajah" => $photoResult ?? '',
                    "jabatan" => $jabatan,
                    "divisi" => $divisi,
                    "lokasi_otmil_id" => $lokasi_otmil_id,
                    "lokasi_lemasmil_id" => $lokasi_lemasmil_id,
                    "matra_id" => $matra_id,
                    "nrp" => $nrp,
                    'grup_petugas_id' => $grup_petugas_id,
                    "updated_at" => $updated_at
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