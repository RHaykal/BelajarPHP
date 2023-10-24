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
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$wbp_profile_id = generateUUID(); // You should define this function somewhere.

// Use the ternary operator to set default values if parameters are not provided.
$nama = isset($param_POST->nama) ? trim($param_POST->nama) : "";
$pangkat_id = isset($param_POST->pangkat_id) ? trim($param_POST->pangkat_id) : "";
$kesatuan_id = isset($param_POST->kesatuan_id) ? trim($param_POST->kesatuan_id) : "";
$tempat_lahir = isset($param_POST->tempat_lahir) ? trim($param_POST->tempat_lahir) : "";
$tanggal_lahir = isset($param_POST->tanggal_lahir) ? trim($param_POST->tanggal_lahir) : "";
$jenis_kelamin = isset($param_POST->jenis_kelamin) ? trim($param_POST->jenis_kelamin) : "";
$provinsi_id = isset($param_POST->provinsi_id) ? trim($param_POST->provinsi_id) : "";
$kota_id = isset($param_POST->kota_id) ? trim($param_POST->kota_id) : "";
$alamat = isset($param_POST->alamat) ? trim($param_POST->alamat) : "";
$agama_id = isset($param_POST->agama_id) ? trim($param_POST->agama_id) : "";
$status_kawin_id = isset($param_POST->status_kawin_id) ? trim($param_POST->status_kawin_id) : "";
$pendidikan_id = isset($param_POST->pendidikan_id) ? trim($param_POST->pendidikan_id) : "";
$bidang_keahlian_id = isset($param_POST->bidang_keahlian_id) ? trim($param_POST->bidang_keahlian_id) : "";
$foto_wajah = isset($param_POST->foto_wajah) ? trim($param_POST->foto_wajah) : "";
$nomor_tahanan = isset($param_POST->nomor_tahanan) ? trim($param_POST->nomor_tahanan) : "";
$residivis = isset($param_POST->residivis) ? trim($param_POST->residivis) : "";
$status_wbp_kasus_id = isset($param_POST->status_wbp_kasus_id) ? trim($param_POST->status_wbp_kasus_id) : "";
// $foto_wajah_fr = isset($param_POST->foto_wajah_fr) ? trim($param_POST->foto_wajah_fr) : "";
$is_isolated = isset($param_POST->is_isolated) ? trim($param_POST->is_isolated) : "";
$is_sick = isset($param_POST->is_sick) ? trim($param_POST->is_sick) : 0;
$nama_kontak_keluarga = isset($param_POST->nama_kontak_keluarga) ? trim($param_POST->nama_kontak_keluarga) : "";
$hubungan_kontak_keluarga = isset($param_POST->hubungan_kontak_keluarga) ? trim($param_POST->hubungan_kontak_keluarga) : "";
$nomor_kontak_keluarga = isset($param_POST->nomor_kontak_keluarga) ? trim($param_POST->nomor_kontak_keluarga) : "";
$wbp_sickness = isset($param_POST->wbp_sickness) ? trim($param_POST->wbp_sickness) : "";
$matra_id = isset($param_POST->matra_id) ? trim($param_POST->matra_id) : "";
$nrp = isset($param_POST->nrp) ? trim($param_POST->nrp) : "";

if (!empty($tanggal_lahir) && strtotime($tanggal_lahir)) {
    $tanggal_lahir = date('Y-m-d', strtotime($tanggal_lahir));
} else {
    $tanggal_lahir = null;
}


$foto_wajah_fr = $nrp . $nama . uniqid();
$foto_wajah_fr = str_replace(' ', '', $foto_wajah_fr);


$wbp_perkara_id = generateUUID(); // You should define this function somewhere.
$kategori_perkara_id = isset($param_POST->kategori_perkara_id) ? trim($param_POST->kategori_perkara_id) : "";
$jenis_perkara_id = isset($param_POST->jenis_perkara_id) ? trim($param_POST->jenis_perkara_id) : "";
$vonis_tahun = isset($param_POST->vonis_tahun) ? trim($param_POST->vonis_tahun) : "";
$vonis_bulan = isset($param_POST->vonis_bulan) ? trim($param_POST->vonis_bulan) : "";
$vonis_hari = isset($param_POST->vonis_hari) ? trim($param_POST->vonis_hari) : "";
$tanggal_ditahan_otmil = isset($param_POST->tanggal_ditahan_otmil) ? trim($param_POST->tanggal_ditahan_otmil) : "";
$tanggal_ditahan_lemasmil = isset($param_POST->tanggal_ditahan_lemasmil) ? trim($param_POST->tanggal_ditahan_lemasmil) : "";
$lokasi_otmil_id = isset($param_POST->lokasi_otmil_id) ? trim($param_POST->lokasi_otmil_id) : "";
$lokasi_lemasmil_id = isset($param_POST->lokasi_lemasmil_id) ? trim($param_POST->lokasi_lemasmil_id) : "";
$residivis = isset($param_POST->residivis) ? trim($param_POST->residivis) : "";

$gelang_id = isset($param_POST->gelang_id) ? trim($param_POST->gelang_id) : "";

$DMAC = isset($param_POST->DMAC) ? trim($param_POST->DMAC) : "";
$nama_gateway = isset($param_POST->nama_gateway) ? trim($param_POST->nama_gateway) : "";

$hunian_wbp_otmil_id = isset($param_POST->hunian_wbp_otmil_id) ? trim($param_POST->hunian_wbp_otmil_id) : "";
$hunian_wbp_lemasmil_id = isset($param_POST->hunian_wbp_lemasmil_id) ? trim($param_POST->hunian_wbp_lemasmil_id) : "";

$akses_ruangan_lemasmil_id = isset($param_POST->akses_ruangan_lemasmil_id) ? $param_POST->akses_ruangan_lemasmil_id : [];
$akses_ruangan_otmil_id = isset($param_POST->akses_ruangan_otmil_id) ? $param_POST->akses_ruangan_otmil_id : [];

$created_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM wbp_profile WHERE 
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
        nama_kontak_keluarga = ? AND
        hubungan_kontak_keluarga = ? AND
        nomor_kontak_keluarga = ? AND
        nomor_tahanan = ? AND
        matra_id = ? AND
        nrp = ? ";

    $stmt = $conn->prepare($query);
    $stmt->execute([
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
        $nama_kontak_keluarga,
        $hubungan_kontak_keluarga,
        $nomor_kontak_keluarga,
        $nomor_tahanan,
        $matra_id,
        $nrp
    ]);

    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("This WBP is already in use");
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
            $query3 = "INSERT INTO wbp_profile(
            wbp_profile_id,
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
            nama_kontak_keluarga,
            hubungan_kontak_keluarga,
            nomor_kontak_keluarga,
            foto_wajah, 
            nomor_tahanan, 
            foto_wajah_fr, 
            is_isolated, 
            is_sick, 
            wbp_sickness, 
            updated_at, 
            gelang_id, 
            hunian_wbp_lemasmil_id, 
            hunian_wbp_otmil_id, 
            created_at,
            matra_id,
            nrp
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";


            $filename = md5($nama . $created_at);
            $photoLocation = '/siram_api/images_wbp_data' . '/' . $filename . '.jpg';
            $photoResult = base64_to_jpeg($foto_wajah, $photoLocation);
            $stmt3 = $conn->prepare($query3);

            // Make sure you have 16 placeholders (?,?,?,...), one for each value you're binding.
            $stmt3->execute([
                $wbp_profile_id,
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
                $nama_kontak_keluarga,
                $hubungan_kontak_keluarga,
                $nomor_kontak_keluarga,
                $photoResult,
                $nomor_tahanan,
                $foto_wajah_fr,
                $is_isolated,
                $is_sick,
                $wbp_sickness,
                $created_at,
                $gelang_id,
                $hunian_wbp_lemasmil_id,
                $hunian_wbp_otmil_id,
                $created_at,
                $matra_id,
                $nrp
            ]);
            $query4 = "INSERT INTO wbp_perkara(
            wbp_perkara_id,
            kategori_perkara_id,
            jenis_perkara_id,
            vonis_tahun, 
            vonis_bulan,
            vonis_hari, 
            tanggal_ditahan_otmil, 
            tanggal_ditahan_lemasmil, 
            lokasi_otmil_id, 
            lokasi_lemasmil_id, 
            residivis,
            wbp_profile_id
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";



            $stmt4 = $conn->prepare($query4);

            // Make sure you have 16 placeholders (?,?,?,...), one for each value you're binding.
            $stmt4->execute([
                $wbp_perkara_id,
                $kategori_perkara_id,
                $jenis_perkara_id,
                $vonis_tahun,
                $vonis_bulan,
                $vonis_hari,
                $tanggal_ditahan_otmil,
                $tanggal_ditahan_lemasmil,
                $lokasi_otmil_id,
                $lokasi_lemasmil_id,
                $residivis,
                $wbp_profile_id
            ]);

            $ruangan_otmil_ids = isset($param_POST->ruangan_otmil_id) ? $param_POST->ruangan_otmil_id : [];
            $ruangan_lemasmil_ids = isset($param_POST->ruangan_lemasmil_id) ? $param_POST->ruangan_lemasmil_id : [];

            foreach ($akses_ruangan_otmil_id as $akses_otmil) {
                $akses_ruangan_id = generateUUID();
                $query5 = "INSERT INTO akses_ruangan(
                akses_ruangan_id,
                DMAC,
                nama_gateway,
                ruangan_otmil_id,
                ruangan_lemasmil_id,
                wbp_profile_id
            ) VALUES (?,?,?,?,?,?)";

                $stmt5 = $conn->prepare($query5);
                $stmt5->execute([
                    $akses_ruangan_id,
                    $DMAC,
                    $nama_gateway,
                    $akses_otmil,
                    null,
                    $wbp_profile_id
                ]);
            }
            foreach ($akses_ruangan_lemasmil_id as $akses_lemasmil) {
                $akses_ruangan_id = generateUUID();

                $query5 = "INSERT INTO akses_ruangan(
                akses_ruangan_id,
                DMAC,
                nama_gateway,
                ruangan_otmil_id,
                ruangan_lemasmil_id,
                wbp_profile_id
            ) VALUES (?,?,?,?,?,?)";

                $stmt5 = $conn->prepare($query5);

                $stmt5->execute([
                    $akses_ruangan_id,
                    $DMAC,
                    $nama_gateway,
                    null,
                    // Nama ini tetap null untuk kolom ruangan_otmil_id
                    $akses_lemasmil,
                    $wbp_profile_id
                ]);
            }
            $result = [
                "status" => "OK",
                "message" => "Successfully registered new WBP",
                "records" => [
                    [
                        "wbp_profile_id" => $wbp_profile_id,
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
                        "nama_kontak_keluarga" => $nama_kontak_keluarga,
                        "hubungan_kontak_keluarga" => $hubungan_kontak_keluarga,
                        "nomor_kontak_keluarga" => $nomor_kontak_keluarga,
                        "foto_wajah" => $photoResult,
                        "nomor_tahanan" => $nomor_tahanan,
                        "foto_wajah_fr" => $foto_wajah_fr,
                        "is_isolated" => $is_isolated,
                        "is_sick" => $is_sick,
                        "wbp_sickness" => $wbp_sickness,
                        "created_at" => $created_at,
                        "gelang_id" => $gelang_id,
                        "wbp_perkara_id" => $wbp_perkara_id,
                        "kategori_perkara_id" => $kategori_perkara_id,
                        "jenis_perkara_id" => $jenis_perkara_id,
                        "vonis_tahun" => $vonis_tahun,
                        "vonis_bulan" => $vonis_bulan,
                        "vonis_hari" => $vonis_hari,
                        "tanggal_ditahan_otmil" => $tanggal_ditahan_otmil,
                        "tanggal_ditahan_lemasmil" => $tanggal_ditahan_lemasmil,
                        "lokasi_otmil_id" => $lokasi_otmil_id,
                        "lokasi_lemasmil_id" => $lokasi_lemasmil_id,
                        "residivis" => $residivis,
                        // "akses_ruangan_id" => $akses_ruangan_id,
                        "DMAC" => $DMAC,
                        "nama_gateway" => $nama_gateway,
                        "akses_ruangan_otmil_id" => $akses_ruangan_otmil_id,
                        "akses_ruangan_lemasmil_id" => $akses_ruangan_lemasmil_id,
                        "created_at" => $created_at,
                        "matra_id" => $matra_id,
                        "nrp" => $nrp
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

