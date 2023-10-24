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
    $param_POST = $_POST;
}

$petugas_id = generateUUID();
// $nama = trim(isset($param_POST->nama) ? $param_POST->nama : "");
$nama = isset($param_POST->nama) ? trim($param_POST->nama) : "";
$pangkat_id = trim(isset($param_POST->pangkat_id) ? $param_POST->pangkat_id : "");
$kesatuan_id = trim(isset($param_POST->kesatuan_id) ? $param_POST->kesatuan_id : "");
$tempat_lahir = trim(isset($param_POST->tempat_lahir) ? $param_POST->tempat_lahir : "");
$tanggal_lahir = trim(isset($param_POST->tanggal_lahir) ? $param_POST->tanggal_lahir : "");
$jenis_kelamin = trim(isset($param_POST->jenis_kelamin) ? $param_POST->jenis_kelamin : 0);
$provinsi_id = trim(isset($param_POST->provinsi_id) ? $param_POST->provinsi_id : "");
$kota_id = trim(isset($param_POST->kota_id) ? $param_POST->kota_id : "");
$alamat = trim(isset($param_POST->alamat) ? $param_POST->alamat : "");
$agama_id = trim(isset($param_POST->agama_id) ? $param_POST->agama_id : "");
$status_kawin_id = trim(isset($param_POST->status_kawin_id) ? $param_POST->status_kawin_id : "");
$pendidikan_id = trim(isset($param_POST->pendidikan_id) ? $param_POST->pendidikan_id : "");
$bidang_keahlian_id = trim(isset($param_POST->bidang_keahlian_id) ? $param_POST->bidang_keahlian_id : "");
$foto_wajah = trim(isset($param_POST->foto_wajah) ? $param_POST->foto_wajah : "");
$jabatan = isset($param_POST->jabatan) ? trim($param_POST->jabatan) : "";
$divisi = isset($param_POST->divisi) ? trim($param_POST->divisi) : "";
$nrp = isset($param_POST->nrp) ? trim($param_POST->nrp) : "";
// $nomor_petugas = isset($param_POST->nomor_petugas) ? trim($param_POST->nomor_petugas) : "";
$lokasi_otmil_id = isset($param_POST->lokasi_otmil_id) ? trim($param_POST->lokasi_otmil_id) : "";
$lokasi_lemasmil_id = isset($param_POST->lokasi_lemasmil_id) ? trim($param_POST->lokasi_lemasmil_id) : "";
$matra_id = isset($param_POST->matra_id) ? trim($param_POST->matra_id) : "";

$created_at = date('Y-m-d H:i:s');

$foto_wajah_fr = $nrp . $nama . uniqid();
$foto_wajah_fr = str_replace(' ', '', $foto_wajah_fr);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM petugas WHERE 
        nama = ? AND 
        pangkat_id = ? AND 
        kesatuan_id = ? AND 
        tempat_lahir = ? AND 
        tanggal_lahir = ? AND 
        jenis_kelamin = ? AND 
        provinsi_id = ? AND 
        kota_id = ? AND 
        alamat = ? AND 
        agama_id = ? AND 
        status_kawin_id = ? AND 
        pendidikan_id = ? AND 
        bidang_keahlian_id = ? AND 
        jabatan = ? AND
        divisi = ? AND
        nrp = ? AND
        lokasi_otmil_id = ? AND
        lokasi_lemasmil_id = ? AND
        matra_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nama, PDO::PARAM_STR);
    $stmt->bindValue(2, $pangkat_id, PDO::PARAM_STR);
    $stmt->bindValue(3, $kesatuan_id, PDO::PARAM_STR);
    $stmt->bindValue(4, $tempat_lahir, PDO::PARAM_STR);
    $stmt->bindValue(5, $tanggal_lahir, PDO::PARAM_STR);
    $stmt->bindValue(6, $jenis_kelamin, PDO::PARAM_STR);
    $stmt->bindValue(7, $provinsi_id, PDO::PARAM_STR);
    $stmt->bindValue(8, $kota_id, PDO::PARAM_STR);
    $stmt->bindValue(9, $alamat, PDO::PARAM_STR);
    $stmt->bindValue(10, $agama_id, PDO::PARAM_STR);
    $stmt->bindValue(11, $status_kawin_id, PDO::PARAM_STR);
    $stmt->bindValue(12, $pendidikan_id, PDO::PARAM_STR);
    $stmt->bindValue(13, $bidang_keahlian_id, PDO::PARAM_STR);
    $stmt->bindValue(14, $jabatan, PDO::PARAM_STR);
    $stmt->bindValue(15, $divisi, PDO::PARAM_STR);
    $stmt->bindValue(16, $nrp, PDO::PARAM_STR);
    $stmt->bindValue(17, $lokasi_otmil_id, PDO::PARAM_STR);
    $stmt->bindValue(18, $lokasi_lemasmil_id, PDO::PARAM_STR);
    $stmt->bindValue(19, $matra_id, PDO::PARAM_STR);


    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $dataImageExploded = explode(',', $foto_wajah);

        $formattedImage = '';
        // Handle different cases for base64 string format
        if (count($dataImageExploded) > 1) {
            $formattedImage = $dataImageExploded[1];
        } else {
            $formattedImage = $dataImageExploded[0];
        }

        $data_array_insert = array(
            "groupID" => "1",
            // ID of face group 
            "dbID" => "testcideng",
            // ID of face database
            "imageID" => $foto_wajah_fr,
            // ID of face image
            "imageData" => $formattedImage // Face image in base64 format           
        );
        $json_data_insert = json_encode($data_array_insert);

        $make_call_insert = callAPI('POST', 'https://faceengine.deepcam.cn/pipeline/api/face/add', $json_data_insert);
        $responseInsertFR = $make_call_insert;
        // print_r($responseInsertFR);
        $formattedResponseInsert = json_decode($responseInsertFR, true);
        if ($formattedResponseInsert['code'] != '1000') {
            throw new Exception("Failed to insert FR");
        } else {
            $query3 = "INSERT INTO petugas(
            petugas_id,
            nama, 
            pangkat_id, 
            kesatuan_id, 
            tempat_lahir, 
            tanggal_lahir, 
            jenis_kelamin, 
            provinsi_id, 
            kota_id, 
            alamat, 
            agama_id, 
            status_kawin_id, 
            pendidikan_id, 
            bidang_keahlian_id, 
            foto_wajah, 
            jabatan,
            divisi,
            nrp,
            lokasi_otmil_id,
            lokasi_lemasmil_id,
            matra_id,
            foto_wajah_fr,
            created_at
        ) VALUE (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $filename = md5($nama . time());
            $photoLocation = '/siram_api/images_petugas_data' . '/' . $filename . '.jpg';
            $photoResult = base64_to_jpeg($foto_wajah, $photoLocation);
            $stmt3 = $conn->prepare($query3);
            $stmt3->bindValue(1, $petugas_id, PDO::PARAM_STR);
            $stmt3->bindValue(2, $nama, PDO::PARAM_STR);
            $stmt3->bindValue(3, $pangkat_id, PDO::PARAM_STR);
            $stmt3->bindValue(4, $kesatuan_id, PDO::PARAM_STR);
            $stmt3->bindValue(5, $tempat_lahir, PDO::PARAM_STR);
            $stmt3->bindValue(6, $tanggal_lahir, PDO::PARAM_STR);
            $stmt3->bindValue(7, $jenis_kelamin, PDO::PARAM_STR);
            $stmt3->bindValue(8, $provinsi_id, PDO::PARAM_STR);
            $stmt3->bindValue(9, $kota_id, PDO::PARAM_STR);
            $stmt3->bindValue(10, $alamat, PDO::PARAM_STR);
            $stmt3->bindValue(11, $agama_id, PDO::PARAM_STR);
            $stmt3->bindValue(12, $status_kawin_id, PDO::PARAM_STR);
            $stmt3->bindValue(13, $pendidikan_id, PDO::PARAM_STR);
            $stmt3->bindValue(14, $bidang_keahlian_id, PDO::PARAM_STR);
            $stmt3->bindValue(15, $photoResult, PDO::PARAM_STR);
            $stmt3->bindValue(16, $jabatan, PDO::PARAM_STR);
            $stmt3->bindValue(17, $divisi, PDO::PARAM_STR);
            $stmt3->bindValue(18, $nrp, PDO::PARAM_STR);
            $stmt3->bindValue(19, $lokasi_otmil_id, PDO::PARAM_STR);
            $stmt3->bindValue(20, $lokasi_lemasmil_id, PDO::PARAM_STR);
            $stmt3->bindValue(21, $matra_id, PDO::PARAM_STR);
            $stmt3->bindValue(22, $foto_wajah_fr, PDO::PARAM_STR);
            $stmt3->bindValue(23, $created_at, PDO::PARAM_STR);
            $stmt3->execute();

            $result = [
                "status" => "OK",
                "message" => "Successfully registered",
                "records" => [
                    [
                        "petugas_id" => $petugas_id,
                        "nrp" => $nrp,
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
                        "foto_wajah" => $photoResult,
                        "jabatan" => $jabatan,
                        "divisi" => $divisi,
                        "lokasi_otmil_id" => $lokasi_otmil_id,
                        "lokasi_lemasmil_id" => $lokasi_lemasmil_id,
                        "matra_id" => $matra_id,
                        "created_at" => $created_at
                    ]
                ]
            ];
        }
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

