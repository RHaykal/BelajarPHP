<?php
session_start();
// Allow requests from any origin
header("Access-Control-Allow-Origin: *");

// Set allowed HTTP methods
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Set allowed headers
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

// Allow credentials (if needed)
header("Access-Control-Allow-Credentials: true");

// Specify the max age for preflight requests (in seconds)
header("Access-Control-Max-Age: 3600");

// Set the content type for the response
header("Content-Type: application/json; charset=UTF-8");

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
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$imageData = trim(isset($param_POST->imageData) ? $param_POST->imageData : "");

$pageSize = isset($requestData['pagination']['pageSize']) ? (int) $requestData['pagination']['pageSize'] : 10;
$page = isset($requestData['pagination']['currentPage']) ? (int) $requestData['pagination']['currentPage'] : 1;

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // tokenAuth($conn, 'admin');

    $dataImageExploded = explode(',', $imageData);

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
        "dbIDs" => array("1", "2", "22", "101", "testcideng"),
        // ID of face database
        "imageData" => $formattedImage // Face image in base64 format           
    );
    $json_data_insert = json_encode($data_array_insert);

    $make_call_search = callAPI('POST', 'https://faceengine.deepcam.cn/pipeline/api/face/match', $json_data_insert);
    $responseSearchFR = $make_call_search;

    // print_r($responseSearchFR);
    $formattedResponseInsert = json_decode($responseSearchFR, true);
    if ($formattedResponseInsert['code'] != '1000') {
        throw new Exception("Can not found the data on FR database");
    } else {
        $imageID = $formattedResponseInsert["data"]["imageID"];

        $query = "SELECT 
    wbp_profile.wbp_profile_id,
    wbp_profile.nama, 
    wbp_profile.pangkat_id, 
    wbp_profile.kesatuan_id,
    wbp_profile.tempat_lahir, 
    wbp_profile.tanggal_lahir, 
    wbp_profile.jenis_kelamin,
    wbp_profile.provinsi_id, 
    wbp_profile.kota_id, 
    wbp_profile.alamat,
    wbp_profile.agama_id, 
    wbp_profile.status_kawin_id, 
    wbp_profile.pendidikan_id,
    wbp_profile.bidang_keahlian_id, 
    wbp_profile.nama_kontak_keluarga,
    wbp_profile.hubungan_kontak_keluarga,
    wbp_profile.nomor_kontak_keluarga,
    wbp_profile.foto_wajah,
    wbp_profile.nomor_tahanan, 
    wbp_profile.foto_wajah_fr,
    wbp_profile.wbp_perkara_id,
    wbp_profile.is_isolated, 
    wbp_profile.is_sick,
    wbp_profile.wbp_sickness, 
    wbp_profile.created_at, 
    wbp_profile.updated_at,
    wbp_profile.gelang_id, 
    wbp_profile.matra_id,
    wbp_profile.nrp,
    pangkat.nama_pangkat, 
    kesatuan.nama_kesatuan,
    lokasi_kesatuan.nama_lokasi_kesatuan, 
    provinsi.nama_provinsi,
    kota.nama_kota, 
    agama.nama_agama, 
    status_kawin.nama_status_kawin,
    pendidikan.nama_pendidikan, 
    bidang_keahlian.nama_bidang_keahlian, 
    wbp_perkara1.kategori_perkara_id AS kategori_perkara_id1, 
    wbp_perkara1.jenis_perkara_id AS jenis_perkara_id1,
    wbp_perkara1.vonis_tahun, 
    wbp_perkara1.vonis_bulan, 
    wbp_perkara1.vonis_hari,
    wbp_perkara1.tanggal_ditahan_otmil, 
    wbp_perkara1.tanggal_ditahan_lemasmil,
    wbp_perkara1.lokasi_otmil_id, 
    wbp_perkara1.lokasi_lemasmil_id,
    wbp_perkara1.residivis, 
    wbp_perkara1.wbp_profile_id AS wbp_profile_id1, 
    pangkat.nama_pangkat AS nama_pangkat1,
    kesatuan.nama_kesatuan AS nama_kesatuan1, 
    lokasi_kesatuan.nama_lokasi_kesatuan AS nama_lokasi_kesatuan1,
    provinsi.nama_provinsi AS nama_provinsi1, 
    kota.nama_kota AS nama_kota1, 
    agama.nama_agama AS nama_agama1,
    status_kawin.nama_status_kawin AS nama_status_kawin1, 
    pendidikan.nama_pendidikan AS nama_pendidikan1,
    bidang_keahlian.nama_bidang_keahlian AS nama_bidang_keahlian1, 
    wbp_perkara1.vonis_tahun AS vonis_tahun1,
    wbp_perkara1.vonis_bulan AS vonis_bulan1, 
    wbp_perkara1.vonis_hari AS vonis_hari1, 
    wbp_perkara1.tanggal_ditahan_otmil AS tanggal_ditahan_otmil1,
    wbp_perkara1.tanggal_ditahan_lemasmil AS tanggal_ditahan_lemasmil1, 
    -- wbp_perkara1.jenis_perkara_id AS jenis_perkara_id1, 
    jenis_perkara.nama_jenis_perkara AS nama_jenis_perkara1,
    wbp_perkara1.residivis AS residivis1,
    gelang.DMAC, 
    gelang.nama_gelang, 
    gelang.tanggal_pasang,
    gelang.tanggal_aktivasi, 
    hunian_wbp_otmil.nama_hunian_wbp_otmil, 
    hunian_wbp_lemasmil.nama_hunian_wbp_lemasmil, 
    hunian_wbp_otmil.lokasi_otmil_id AS lokasi_otmil_id1, 
    hunian_wbp_lemasmil.lokasi_lemasmil_id AS lokasi_lemasmil_id1,
    akses_ruangan.nama_gateway,
    kategori_perkara1.nama_kategori_perkara AS nama_kategori_perkara1,
    matra.nama_matra,
    CASE 
        WHEN lokasi_otmil.nama_lokasi_otmil IS NOT NULL THEN
            CONCAT('Otmil ', lokasi_otmil.nama_lokasi_otmil)
        ELSE 
            CONCAT('Lemasmil ', lokasi_lemasmil.nama_lokasi_lemasmil)
    END AS lokasi_tahanan
FROM wbp_profile
LEFT JOIN pangkat ON pangkat.pangkat_id = wbp_profile.pangkat_id
LEFT JOIN kesatuan ON kesatuan.kesatuan_id = wbp_profile.kesatuan_id
LEFT JOIN provinsi ON provinsi.provinsi_id = wbp_profile.provinsi_id
LEFT JOIN kota ON kota.kota_id = wbp_profile.kota_id
LEFT JOIN agama ON agama.agama_id = wbp_profile.agama_id
LEFT JOIN status_kawin ON status_kawin.status_kawin_id = wbp_profile.status_kawin_id
LEFT JOIN lokasi_kesatuan ON kesatuan.lokasi_kesatuan_id = lokasi_kesatuan.lokasi_kesatuan_id
LEFT JOIN pendidikan ON pendidikan.pendidikan_id = wbp_profile.pendidikan_id
LEFT JOIN bidang_keahlian ON bidang_keahlian.bidang_keahlian_id = wbp_profile.bidang_keahlian_id
LEFT JOIN hunian_wbp_otmil ON hunian_wbp_otmil.hunian_wbp_otmil_id = wbp_profile.hunian_wbp_otmil_id
LEFT JOIN hunian_wbp_lemasmil ON hunian_wbp_lemasmil.hunian_wbp_lemasmil_id = wbp_profile.hunian_wbp_lemasmil_id
LEFT JOIN lokasi_otmil ON lokasi_otmil.lokasi_otmil_id = hunian_wbp_otmil.lokasi_otmil_id
LEFT JOIN lokasi_lemasmil ON lokasi_lemasmil.lokasi_lemasmil_id = hunian_wbp_lemasmil.lokasi_lemasmil_id
LEFT JOIN wbp_perkara AS wbp_perkara1 ON wbp_perkara1.wbp_profile_id = wbp_profile.wbp_profile_id
LEFT JOIN jenis_perkara ON wbp_perkara1.jenis_perkara_id = jenis_perkara.jenis_perkara_id
LEFT JOIN kategori_perkara AS kategori_perkara1 ON jenis_perkara.kategori_perkara_id = kategori_perkara1.kategori_perkara_id
LEFT JOIN kategori_perkara AS kategori_perkara2 ON wbp_perkara1.kategori_perkara_id = kategori_perkara2.kategori_perkara_id
LEFT JOIN gelang ON wbp_profile.gelang_id = gelang.gelang_id
LEFT JOIN wbp_perkara AS wbp_perkara2 ON wbp_perkara2.wbp_profile_id = wbp_profile.wbp_profile_id
LEFT JOIN akses_ruangan ON wbp_profile.wbp_profile_id = akses_ruangan.wbp_profile_id
LEFT JOIN matra ON wbp_profile.matra_id = matra.matra_id
WHERE  wbp_profile.foto_wajah_fr = ?";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $imageID, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $record = [];

            foreach ($res as $row) {
                $wbp_profile_id = $row['wbp_profile_id'];

                // Query for akses_ruangan_lemasmil
                $akses_ruangan_lemasmil_query = "SELECT akses_ruangan.ruangan_lemasmil_id
                    FROM akses_ruangan 
                    WHERE akses_ruangan.wbp_profile_id = :wbp_profile_id 
                      AND akses_ruangan.ruangan_lemasmil_id IS NOT NULL 
                      AND (akses_ruangan.ruangan_otmil_id IS NULL OR akses_ruangan.ruangan_otmil_id = '')";

                $akses_ruangan_lemasmil_stmt = $conn->prepare($akses_ruangan_lemasmil_query);
                $akses_ruangan_lemasmil_stmt->bindParam(':wbp_profile_id', $wbp_profile_id, PDO::PARAM_STR);
                $akses_ruangan_lemasmil_stmt->execute();
                $akses_ruangan_lemasmil_res = $akses_ruangan_lemasmil_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Query for akses_ruangan_otmil
                $akses_ruangan_otmil_query = "SELECT akses_ruangan.ruangan_otmil_id
                    FROM akses_ruangan 
                    WHERE akses_ruangan.wbp_profile_id = :wbp_profile_id 
                      AND akses_ruangan.ruangan_otmil_id IS NOT NULL 
                      AND (akses_ruangan.ruangan_lemasmil_id IS NULL OR akses_ruangan.ruangan_lemasmil_id = '')";


                $akses_ruangan_otmil_stmt = $conn->prepare($akses_ruangan_otmil_query);
                $akses_ruangan_otmil_stmt->bindParam(':wbp_profile_id', $wbp_profile_id, PDO::PARAM_STR);
                $akses_ruangan_otmil_stmt->execute();
                $akses_ruangan_otmil_res = $akses_ruangan_otmil_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Initialize default values for akses_ruangan_lemasmil and akses_ruangan_otmil
                $akses_ruangan_lemasmil = [];
                $akses_ruangan_otmil = [];

                // Check if data exists for akses_ruangan_lemasmil
                if (!empty($akses_ruangan_lemasmil_res)) {
                    foreach ($akses_ruangan_lemasmil_res as $lemasmil_row) {
                        $ruangan_lemasmil_id = $lemasmil_row['ruangan_lemasmil_id'];
                        $query = "SELECT ruangan_lemasmil.nama_ruangan_lemasmil, 
                                     lokasi_lemasmil.nama_lokasi_lemasmil,
                                     lokasi_lemasmil.lokasi_lemasmil_id
                              FROM ruangan_lemasmil
                              LEFT JOIN lokasi_lemasmil ON lokasi_lemasmil.lokasi_lemasmil_id = ruangan_lemasmil.lokasi_lemasmil_id
                              WHERE ruangan_lemasmil.ruangan_lemasmil_id = :ruangan_lemasmil_id";

                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':ruangan_lemasmil_id', $ruangan_lemasmil_id, PDO::PARAM_STR);
                        $stmt->execute();
                        $res = $stmt->fetch(PDO::FETCH_ASSOC);

                        // Create an object with specific fields and add it to the array
                        $ruangan_lemasmil_data = [
                            "nama_ruangan_lemasmil" => $res['nama_ruangan_lemasmil'],
                            "nama_lokasi_lemasmil" => $res['nama_lokasi_lemasmil'],
                            "ruangan_lemasmil_id" => $lemasmil_row['ruangan_lemasmil_id'],
                            "lokasi_lemasmil_id" => $res['lokasi_lemasmil_id'],
                            // Adjust the field name as needed
                        ];

                        $akses_ruangan_lemasmil[] = $ruangan_lemasmil_data;
                    }
                }



                if (!empty($akses_ruangan_otmil_res)) {
                    foreach ($akses_ruangan_otmil_res as $otmil_row) {
                        $ruangan_otmil_id = $otmil_row['ruangan_otmil_id'];
                        $query = "SELECT ruangan_otmil.nama_ruangan_otmil, 
                                     lokasi_otmil.nama_lokasi_otmil,
                                     lokasi_otmil.lokasi_otmil_id
                              FROM ruangan_otmil
                              LEFT JOIN lokasi_otmil ON lokasi_otmil.lokasi_otmil_id = ruangan_otmil.lokasi_otmil_id
                              WHERE ruangan_otmil.ruangan_otmil_id = :ruangan_otmil_id";

                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':ruangan_otmil_id', $ruangan_otmil_id, PDO::PARAM_STR);
                        $stmt->execute();
                        $res = $stmt->fetch(PDO::FETCH_ASSOC);

                        // Create an object with specific fields and add it to the array
                        // var_dump($akses_ruangan_lemasmil);
                        // var_dump($akses_ruangan_otmil);
                        // var_dump($row);

                        $ruangan_otmil_data = [
                            "nama_ruangan_otmil" => $res['nama_ruangan_otmil'],
                            "nama_lokasi_otmil" => $res['nama_lokasi_otmil'],
                            "ruangan_otmil_id" => $otmil_row['ruangan_otmil_id'],
                            "lokasi_otmil_id" => $res['lokasi_otmil_id'],
                            // Adjust the field name as needed
                        ];

                        $akses_ruangan_otmil[] = $ruangan_otmil_data;
                    }
                }
                $recordData = [
                    "wbp_profile_id" => $row['wbp_profile_id'],
                    "nama" => $row['nama'],
                    "pangkat_id" => $row['pangkat_id'],
                    "nama_pangkat" => isset($row['nama_pangkat']) ? $row['nama_pangkat'] : null,
                    "kesatuan_id" => $row['kesatuan_id'],
                    "nama_kesatuan" => isset($row['nama_kesatuan']) ? $row['nama_kesatuan'] : null,
                    "nama_lokasi_kesatuan" => isset($row['nama_lokasi_kesatuan']) ? $row['nama_lokasi_kesatuan'] : null,
                    "tanggal_lahir" => $row['tanggal_lahir'],
                    "jenis_kelamin" => $row['jenis_kelamin'],
                    "tempat_lahir" => $row['tempat_lahir'],
                    "provinsi_id" => $row['provinsi_id'],
                    "nama_provinsi" => isset($row['nama_provinsi']) ? $row['nama_provinsi'] : null,
                    "kota_id" => $row['kota_id'],
                    "nama_kota" => isset($row['nama_kota']) ? $row['nama_kota'] : null,
                    "agama_id" => $row['agama_id'],
                    "nama_agama" => isset($row['nama_agama']) ? $row['nama_agama'] : null,
                    "status_kawin_id" => $row['status_kawin_id'],
                    "nama_status_kawin" => isset($row['nama_status_kawin']) ? $row['nama_status_kawin'] : null,
                    "pendidikan_id" => $row['pendidikan_id'],
                    "nama_pendidikan" => isset($row['nama_pendidikan']) ? $row['nama_pendidikan'] : null,
                    "bidang_keahlian_id" => $row['bidang_keahlian_id'],
                    "nama_bidang_keahlian" => isset($row['nama_bidang_keahlian']) ? $row['nama_bidang_keahlian'] : null,
                    "nama_kontak_keluarga" => $row['nama_kontak_keluarga'],
                    "hubungan_kontak_keluarga" => $row['hubungan_kontak_keluarga'],
                    "nomor_kontak_keluarga" => $row['nomor_kontak_keluarga'],
                    "lokasi_otmil_id" => isset($row['lokasi_otmil_id1']) ? $row['lokasi_otmil_id1'] : null,
                    "lokasi_lemasmil_id" => isset($row['lokasi_lemasmil_id1']) ? $row['lokasi_lemasmil_id1'] : null,
                    "lokasi_tahanan" => isset($row['lokasi_tahanan']) ? $row['lokasi_tahanan'] : null,
                    "alamat" => $row['alamat'],
                    "foto_wajah" => $row['foto_wajah'],
                    "dmac" => $row['DMAC'],
                    "nama_gelang" => isset($row['nama_gelang']) ? $row['nama_gelang'] : null,
                    "is_isolated" => $row['is_isolated'],
                    "is_sick" => $row['is_sick'],
                    "wbp_sickness" => isset($row['wbp_sickness']) ? $row['wbp_sickness'] : null,

                    "nomor_tahanan" => isset($row['nomor_tahanan']) ? $row['nomor_tahanan'] : null,
                    // "daftar_akses_ruangan_id" => isset($row['daftar_akses_ruangan_id']) ? $row['daftar_akses_ruangan_id'] : null,
                    "kategori_perkara_id" => isset($row['kategori_perkara_id1']) ? $row['kategori_perkara_id1'] : null,
                    "nama_kategori_perkara" => isset($row['nama_kategori_perkara1']) ? $row['nama_kategori_perkara1'] : null,
                    "jenis_perkara_id" => isset($row['jenis_perkara_id1']) ? $row['jenis_perkara_id1'] : null,
                    "nama_jenis_perkara" => isset($row['nama_jenis_perkara1']) ? $row['nama_jenis_perkara1'] : null,
                    "vonis_tahun" => isset($row['vonis_tahun']) ? $row['vonis_tahun'] : null,
                    "vonis_bulan" => isset($row['vonis_bulan']) ? $row['vonis_bulan'] : null,
                    "vonis_hari" => isset($row['vonis_hari']) ? $row['vonis_hari'] : null,
                    "tanggal_ditahan_otmil" => isset($row['tanggal_ditahan_otmil']) ? $row['tanggal_ditahan_otmil'] : null,
                    "tanggal_ditahan_lemasmil" => isset($row['tanggal_ditahan_lemasmil']) ? $row['tanggal_ditahan_lemasmil'] : null,
                    "residivis" => isset($row['residivis']) ? $row['residivis'] : null,
                    "nama_hunian_wbp_otmil" => isset($row['nama_hunian_wbp_otmil']) ? $row['nama_hunian_wbp_otmil'] : null,
                    "nama_hunian_wbp_lemasmil" => isset($row['nama_hunian_wbp_lemasmil']) ? $row['nama_hunian_wbp_lemasmil'] : null,
                    "nama_matra" => $row['nama_matra'],
                    "nrp" => $row['nrp'],

                    // Check if these fields exist before including them
                    "akses_ruangan_lemasmil" => isset($akses_ruangan_lemasmil) ? $akses_ruangan_lemasmil : null,
                    "akses_ruangan_otmil" => isset($akses_ruangan_otmil) ? $akses_ruangan_otmil : null,


                    // "nama_gateway" => isset($row['nama_gateway']) ? $row['nama_gateway'] : null
                ];

                $record[] = $recordData;
            }

            // Prepare the JSON response with pagination information


            $result = [
                "status" => "OK",
                "message" => "Data Successfully retrieved",
                "records" => $record
            ];
        } else {

            // Construct the SQL query with filtering conditions
            $query = "SELECT petugas.*, pangkat.nama_pangkat,
    kesatuan.nama_kesatuan,lokasi_kesatuan.nama_lokasi_kesatuan,
    provinsi.nama_provinsi, kota.nama_kota, agama.nama_agama,
    status_kawin.nama_status_kawin, pendidikan.nama_pendidikan,
    bidang_keahlian.nama_bidang_keahlian,grup_petugas.nama_grup_petugas,
    matra.nama_matra,
 CASE 
    WHEN lokasi_otmil.nama_lokasi_otmil IS NOT NULL THEN
    CONCAT('Otmil ', lokasi_otmil.nama_lokasi_otmil)
    ELSE 
    CONCAT('Lemasmil ', lokasi_lemasmil.nama_lokasi_lemasmil)
END AS lokasi_tugas

FROM petugas
LEFT JOIN lokasi_otmil ON petugas.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
LEFT JOIN lokasi_lemasmil ON petugas.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
LEFT JOIN pangkat ON petugas.pangkat_id = pangkat.pangkat_id
LEFT JOIN kesatuan ON petugas.kesatuan_id = kesatuan.kesatuan_id
LEFT JOIN lokasi_kesatuan ON kesatuan.lokasi_kesatuan_id = lokasi_kesatuan.lokasi_kesatuan_id
LEFT JOIN provinsi ON petugas.provinsi_id = provinsi.provinsi_id
LEFT JOIN kota ON petugas.kota_id = kota.kota_id
LEFT JOIN agama ON petugas.agama_id = agama.agama_id
LEFT JOIN status_kawin ON petugas.status_kawin_id = status_kawin.status_kawin_id
LEFT JOIN pendidikan ON petugas.pendidikan_id = pendidikan.pendidikan_id
LEFT JOIN bidang_keahlian ON petugas.bidang_keahlian_id = bidang_keahlian.bidang_keahlian_id
LEFT JOIN grup_petugas ON petugas.grup_petugas_id = grup_petugas.grup_petugas_id
LEFT JOIN matra ON matra.matra_id = petugas.matra_id
WHERE petugas.is_deleted = 0";



            $stmt = $conn->prepare($query);
            $stmt->execute();
         if ($stmt->rowCount() > 0) {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $record = [];

            foreach ($res as $row) {
                $recordData = [
                    "petugas_id" => $row['petugas_id'],
                    "nrp" => $row['nrp'],
                    "nama" => $row['nama'],
                    "pangkat_id" => $row['pangkat_id'],
                    "nama_pangkat" => $row['nama_pangkat'],
                    "kesatuan_id" => $row['kesatuan_id'],
                    "nama_kesatuan" => $row['nama_kesatuan'],
                    "nama_lokasi_kesatuan" => $row['nama_lokasi_kesatuan'],
                    "tempat_lahir" => $row['tempat_lahir'],
                    "tanggal_lahir" => $row['tanggal_lahir'],
                    "jenis_kelamin" => $row['jenis_kelamin'],
                    "provinsi_id" => $row['provinsi_id'],
                    "nama_provinsi" => $row['nama_provinsi'],
                    "kota_id" => $row['kota_id'],
                    "nama_kota" => $row['nama_kota'],
                    "alamat" => $row['alamat'],
                    "agama_id" => $row['agama_id'],
                    "nama_agama" => $row['nama_agama'],
                    "status_kawin_id" => $row['status_kawin_id'],
                    "nama_status_kawin" => $row['nama_status_kawin'],
                    "pendidikan_id" => $row['pendidikan_id'],
                    "nama_pendidikan" => $row['nama_pendidikan'],
                    "bidang_keahlian_id" => $row['bidang_keahlian_id'],
                    "nama_bidang_keahlian" => $row['nama_bidang_keahlian'],
                    "jabatan" => $row['jabatan'],
                    "divisi" => $row['divisi'],
                    "nomor_petugas" => $row['nomor_petugas'],
                    "lokasi_otmil_id" => $row['lokasi_otmil_id'],
                    "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
                    "lokasi_tugas" => $row['lokasi_tugas'],
                    "foto_wajah" => $row['foto_wajah'],
                    "grup_petugas_id" => $row['grup_petugas_id'],
                    "nama_grup_petugas" => $row['nama_grup_petugas'],
                    "matra_id" => $row['matra_id'],
                    "nama_matra" => $row['nama_matra']
                ];

                $record[] = $recordData;
            }

            // Prepare the JSON response with pagination information
            $result = [
                "status" => "OK",
                "message" => "",
                "records" => $record
            ];         }else{
            throw new Exception("Can not found the data on FR database");
         }
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

