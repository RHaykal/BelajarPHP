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

$wbp_profile_id = isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : "";

if (empty($wbp_profile_id)) {
    echo json_encode([
        "status" => "NO",
        "message" => "Missing wbp_profile_id in request",
        "records" => []
    ]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // tokenAuth($conn, 'admin');

    // Check if the record exists in wbp_profile
    $queryProfile = "SELECT * FROM wbp_profile WHERE wbp_profile_id = ?";
    $stmtProfile = $conn->prepare($queryProfile);
    $stmtProfile->execute([$wbp_profile_id]);
    $existingProfile = $stmtProfile->fetch(PDO::FETCH_ASSOC);

    if (!$existingProfile) {
        // echo json_encode([
        //     "status" => "NO",
        //     "message" => "WBP profile with ID $wbp_profile_id not found",
        //     "records" => []
        // ]);
        // exit;
        throw new Exception("WBP profile with ID $wbp_profile_id not found");
    }

    // Merge existing data with new data for wbp_profile
    $newDataProfile = [
        "nama" => isset($param_POST->nama) ? trim($param_POST->nama) : "",
        "pangkat_id" => isset($param_POST->pangkat_id) ? trim($param_POST->pangkat_id) : "",
        "kesatuan_id" => isset($param_POST->kesatuan_id) ? trim($param_POST->kesatuan_id) : "",
        "tempat_lahir" => isset($param_POST->tempat_lahir) ? trim($param_POST->tempat_lahir) : "",
        "tanggal_lahir" => isset($param_POST->tanggal_lahir) ? trim($param_POST->tanggal_lahir) : "",
        "jenis_kelamin" => isset($param_POST->jenis_kelamin) ? trim($param_POST->jenis_kelamin) : "",
        "provinsi_id" => isset($param_POST->provinsi_id) ? trim($param_POST->provinsi_id) : "",
        "kota_id" => isset($param_POST->kota_id) ? trim($param_POST->kota_id) : "",
        "alamat" => isset($param_POST->alamat) ? trim($param_POST->alamat) : "",
        "agama_id" => isset($param_POST->agama_id) ? trim($param_POST->agama_id) : "",
        "status_kawin_id" => isset($param_POST->status_kawin_id) ? trim($param_POST->status_kawin_id) : "",
        "pendidikan_id" => isset($param_POST->pendidikan_id) ? trim($param_POST->pendidikan_id) : "",
        "bidang_keahlian_id" => isset($param_POST->bidang_keahlian_id) ? trim($param_POST->bidang_keahlian_id) : "",
        "status_kontak_keluarga" => isset($param_POST->status_kontak_keluarga) ? trim($param_POST->status_kontak_keluarga) : "",
        "hubungan_kontak_keluarga" => isset($param_POST->hubungan_kontak_keluarga) ? trim($param_POST->hubungan_kontak_keluarga) : "",
        "nomor_kontak_keluarga" => isset($param_POST->nomor_kontak_keluarga) ? trim($param_POST->nomor_kontak_keluarga) : "",
        "foto_wajah" => isset($param_POST->foto_wajah) ? trim($param_POST->foto_wajah) : "",
        "nomor_tahanan" => isset($param_POST->nomor_tahanan) ? trim($param_POST->nomor_tahanan) : "",
        // "foto_wajah_fr" => isset($param_POST->foto_wajah_fr) ? trim($param_POST->foto_wajah_fr) : "",
        "is_isolated" => isset($param_POST->is_isolated) ? trim($param_POST->is_isolated) : "",
        "is_sick" => isset($param_POST->is_sick) ? trim($param_POST->is_sick) : "",
        "wbp_sickness" => isset($param_POST->wbp_sickness) ? trim($param_POST->wbp_sickness) : "",
        "gelang_id" => isset($param_POST->gelang_id) ? trim($param_POST->gelang_id) : "",
        "akses_ruangan_otmil_id" => isset($param_POST->akses_ruangan_otmil_id) ? $param_POST->akses_ruangan_otmil_id : [],
        "akses_ruangan_lemasmil_id" => isset($param_POST->akses_ruangan_lemasmil_id) ? $param_POST->akses_ruangan_lemasmil_id : [],
        "DMAC" => isset($param_POST->dmac) ? trim($param_POST->dmac) : "",
        "kategori_perkara_id" => isset($param_POST->kategori_perkara_id) ? trim($param_POST->kategori_perkara_id) : null,
        "jenis_perkara_id" => isset($param_POST->jenis_perkara_id) ? trim($param_POST->jenis_perkara_id) : null,
        "vonis_hari" => isset($param_POST->vonis_hari) ? trim($param_POST->vonis_hari) : null,
        "vonis_bulan" => isset($param_POST->vonis_bulan) ? trim($param_POST->vonis_bulan) : null,
        "vonis_tahun" => isset($param_POST->vonis_tahun) ? trim($param_POST->vonis_tahun) : null,
        "lokasi_otmil_id" => isset($param_POST->lokasi_otmil_id) ? trim($param_POST->lokasi_otmil_id) : null,
        "lokasi_lemasmil_id" => isset($param_POST->lokasi_lemasmil_id) ? trim($param_POST->lokasi_lemasmil_id) : null,
        "tanggal_ditahan_otmil" => isset($param_POST->tanggal_ditahan_otmil) ? trim($param_POST->tanggal_ditahan_otmil) : null,
        "tanggal_ditahan_lemasmil" => isset($param_POST->tanggal_ditahan_lemasmil) ? trim($param_POST->tanggal_ditahan_lemasmil) : null,
        "residivis" => isset($param_POST->residivis) ? trim($param_POST->residivis) : null,
        "hunian_wbp_lemasmil_id" => isset($param_POST->hunian_wbp_lemasmil_id) ? trim($param_POST->hunian_wbp_lemasmil_id) : "",
        "hunian_wbp_otmil_id" => isset($param_POST->hunian_wbp_otmil_id) ? trim($param_POST->hunian_wbp_otmil_id) : "",
        "matra_id" => isset($param_POST->matra_id) ? trim($param_POST->matra_id) : "",
        "nrp" => isset($param_POST->nrp) ? trim($param_POST->nrp) : ""
    ];

    $nrp = isset($param_POST->nrp) ? trim($param_POST->nrp) : "";
    $nama = isset($param_POST->nama) ? trim($param_POST->nama) : "";
    $foto_wajah = isset($param_POST->foto_wajah) ? trim($param_POST->foto_wajah) : "";
    // $foto_wajah_fr = isset($param_POST->foto_wajah_fr) ? trim($param_POST->foto_wajah_fr) : "";
    // print_r($existingProfile);
    $foto_wajah_fr = $existingProfile['foto_wajah_fr'];
        $updated_at = date('Y-m-d H:i:s');

    $new_foto_wajah_fr = $nrp . $nama . uniqid();
    $new_foto_wajah_fr = str_replace(' ', '', $new_foto_wajah_fr);

    // Remove empty values from the new data for wbp_profile
    $newDataProfile = array_filter($newDataProfile, function ($value) {
        return $value !== "";
    });

    // Initialize mergedDataProfile with existing data
    $mergedDataProfile = $existingProfile;

    // Merge the new data with existing data for wbp_profile
    $mergedDataProfile = array_merge($mergedDataProfile, $newDataProfile);
   

    // Update the existing record in wbp_profile
    $queryUpdateProfile = "UPDATE wbp_profile SET
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
        -- foto_wajah = ?,
        nomor_tahanan = ?,
        -- foto_wajah_fr = ?,
        is_isolated = ?,
        is_sick = ?,
        wbp_sickness = ?,
        gelang_id = ?,
        nama_kontak_keluarga = ?,
        hubungan_kontak_keluarga = ?,
        nomor_kontak_keluarga = ?,
        hunian_wbp_lemasmil_id = ?,
        hunian_wbp_otmil_id = ?,
        matra_id = ?,
        nrp = ?,
        updated_at = ? ";



    $bindParamsProfile = [
        $mergedDataProfile['nama'],
        $mergedDataProfile['pangkat_id'],
        $mergedDataProfile['kesatuan_id'],
        $mergedDataProfile['tempat_lahir'],
        $mergedDataProfile['tanggal_lahir'],
        $mergedDataProfile['jenis_kelamin'],
        $mergedDataProfile['provinsi_id'],
        $mergedDataProfile['kota_id'],
        $mergedDataProfile['alamat'],
        $mergedDataProfile['agama_id'],
        $mergedDataProfile['status_kawin_id'],
        $mergedDataProfile['pendidikan_id'],
        $mergedDataProfile['bidang_keahlian_id'],
        // $mergedDataProfile['foto_wajah'],
        $mergedDataProfile['nomor_tahanan'],
        // $new_foto_wajah_fr,
        $mergedDataProfile['is_isolated'],
        $mergedDataProfile['is_sick'],
        $mergedDataProfile['wbp_sickness'],
        $mergedDataProfile['gelang_id'],
        $mergedDataProfile['nama_kontak_keluarga'],
        $mergedDataProfile['hubungan_kontak_keluarga'],
        $mergedDataProfile['nomor_kontak_keluarga'],
        $mergedDataProfile['hunian_wbp_lemasmil_id'],
        $mergedDataProfile['hunian_wbp_otmil_id'],
        $mergedDataProfile['matra_id'],
        $mergedDataProfile['nrp'],
        $updated_at
    ];


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

        $queryUpdateProfile .= ", foto_wajah = ?";
        $queryUpdateProfile .= ", foto_wajah_fr = ?";
        $filename = md5($nama . $updated_at);
        $photoLocation = '/siram_api/images_wbp_data' . '/' . $filename . '.jpg';
        $photoResult = base64_to_jpeg($foto_wajah, $photoLocation);
        $bindParamsProfile[] = $photoResult;
        $bindParamsProfile[] = $new_foto_wajah_fr;

    }

    $queryUpdateProfile .= " WHERE wbp_profile_id = ?";
    $bindParamsProfile[] = $wbp_profile_id;

    $stmtUpdateProfile = $conn->prepare($queryUpdateProfile);
    $stmtUpdateProfile->execute($bindParamsProfile);

    // Update the wbp_perkara table
    $queryUpdatePerkara = "UPDATE wbp_perkara SET
        kategori_perkara_id = ?,
        jenis_perkara_id = ?,
        vonis_hari = ?,
        vonis_bulan = ?,
        vonis_tahun = ?,
        lokasi_otmil_id = ?,
        lokasi_lemasmil_id = ?,
        tanggal_ditahan_otmil = ?,
        tanggal_ditahan_lemasmil = ?,
        residivis = ?
        WHERE wbp_profile_id = ?";

    $bindParamsPerkara = [
        $mergedDataProfile['kategori_perkara_id'],
        $mergedDataProfile['jenis_perkara_id'],
        $mergedDataProfile['vonis_hari'],
        $mergedDataProfile['vonis_bulan'],
        $mergedDataProfile['vonis_tahun'],
        $mergedDataProfile['lokasi_otmil_id'],
        $mergedDataProfile['lokasi_lemasmil_id'],
        $mergedDataProfile['tanggal_ditahan_otmil'],
        $mergedDataProfile['tanggal_ditahan_lemasmil'],
        $mergedDataProfile['residivis'],
        $wbp_profile_id
    ];

    $stmtUpdatePerkara = $conn->prepare($queryUpdatePerkara);
    $stmtUpdatePerkara->execute($bindParamsPerkara);

    // akses_ruangan

    $deleteaksesruangan = "DELETE FROM akses_ruangan WHERE wbp_profile_id = ?";
    $stmt4 = $conn->prepare($deleteaksesruangan);
    $stmt4->execute([$wbp_profile_id]);


    //check apakah delete akses ruangan berhasil
    $queryCheckAksesRuangan = "SELECT * FROM akses_ruangan WHERE wbp_profile_id = ?";
    $stmtCheckAksesRuangan = $conn->prepare($queryCheckAksesRuangan);
    $stmtCheckAksesRuangan->execute([$wbp_profile_id]);
    $existingAksesRuangan = $stmtCheckAksesRuangan->fetch(PDO::FETCH_ASSOC);

    if ($existingAksesRuangan) {
        echo json_encode([
            "status" => "NO",
            "message" => "Failed to delete akses ruangan",
            "records" => []
        ]);
        exit;
    }

    $akses_ruangan_otmil_id = isset($param_POST->akses_ruangan_otmil_id) ? $param_POST->akses_ruangan_otmil_id : [];
    $akses_ruangan_lemasmil_id = isset($param_POST->akses_ruangan_lemasmil_id) ? $param_POST->akses_ruangan_lemasmil_id : [];

    $DMAC = isset($param_POST->dmac) ? trim($param_POST->dmac) : "";
    $nama_gateway = isset($param_POST->nama_gateway) ? trim($param_POST->nama_gateway) : "";

    foreach ($akses_ruangan_otmil_id as $akses_otmil) {
        $akses_ruangan_id = generateUUID();
        $query5 = "INSERT INTO akses_ruangan(
            akses_ruangan_id,
            dmac,
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
            dmac,
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

    // $queryUpdateAksesRuangan = "UPDATE akses_ruangan SET
    //       akses_ruangan_id,
    //         dmac,
    //         nama_gateway,
    //         ruangan_otmil_id,
    //         ruangan_lemasmil_id
    //         WHERE wbp_profile_id = ?";

    // $bindParamsAksesRuangan = [
    //     $mergedDataProfile['akses_ruangan_id'],
    //     $mergedDataProfile['dmac'],
    //     $mergedDataProfile['nama_gateway'],
    //     $mergedDataProfile['ruangan_otmil_id'],
    //     $mergedDataProfile['ruangan_lemasmil_id'],
    //     $wbp_profile_id
    // ];

    // $stmtUpdateAksesRuangan = $conn->prepare($queryUpdateAksesRuangan);
    // $stmtUpdateAksesRuangan->execute($bindParamsAksesRuangan);



    $result = [
        "status" => "OK",
        "message" => "Successfully updated WBP profile and related records",
        "records" => [
            [
                "wbp_profile_id" => $wbp_profile_id,
                "updated_at" => date('Y-m-d H:i:s'),
                // Include the updated fields here if needed
            ]
        ]
    ];
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>

