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

$penyidikan_id = isset($param_POST->penyidikan_id) ? trim($param_POST->penyidikan_id) : "";

if (empty($penyidikan_id)) {
    echo json_encode([
        "status" => "NO",
        "message" => "Missing penyidikan_id in request",
        "records" => []
    ]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $queryPenyidikan = "SELECT * FROM penyidikan WHERE penyidikan_id = ?";
    $stmtPenyidikan = $conn->prepare($queryPenyidikan);
    $stmtPenyidikan->execute([$penyidikan_id]);
    $existingPenyidikan = $stmtPenyidikan->fetch(PDO::FETCH_ASSOC);

    if (!$existingPenyidikan) {
        echo json_encode([
            "status" => "NO",
            "message" => "penyidikan not found",
            "records" => []
        ]);
        exit;
    }

    $newDataPenyidikan = [
        "nomor_penyidikan" => isset($param_POST->nomor_penyidikan) ? trim($param_POST->nomor_penyidikan) : "",
        "wbp_profile_id" => isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : "",
        "kasus_id" => isset($param_POST->kasus_id) ? trim($param_POST->kasus_id) : "",
        "alasan_penyidikan" => isset($param_POST->alasan_penyidikan) ?  trim($param_POST->alasan_penyidikan) : "",
        "lokasi_penyidikan" => isset($param_POST->lokasi_penyidikan) ?  trim($param_POST->lokasi_penyidikan) : "",
        "waktu_penyidikan" => isset($param_POST->waktu_penyidikan) ? trim($param_POST->waktu_penyidikan) : "",
        "agenda_penyidikan" => isset($param_POST->agenda_penyidikan) ? trim($param_POST->agenda_penyidikan) : "",
        "hasil_penyidikan" => isset($param_POST->hasil_penyidikan) ? trim($param_POST->hasil_penyidikan) : "",
        "lama_masa_tahanan" => isset($param_POST->hasil_penyidikan) ? trim($param_POST->hasil_penyidikan) : "",
    ];

    $newDataPenyidikan = array_filter($newDataPenyidikan, function ($value) {
        return $value !== "";
    });

    $mergedDataPenyidikan = $existingPenyidikan;

    $mergedDataPenyidikan = array_merge($mergedDataPenyidikan, $newDataPenyidikan);

    $queryUpdatePenyidikan = "UPDATE penyidikan SET
        nomor_penyidikan = ?,
        wbp_profile_id = ?,
        kasus_id = ?,
        alasan_penyidikan = ?,
        lokasi_penyidikan = ?,
        waktu_penyidikan = ?,
        agenda_penyidikan = ?,
        hasil_penyidikan = ?
        WHERE penyidikan_id = ?";

    $bindParamsPenyidikan = [
        $mergedDataPenyidikan['nomor_penyidikan'],
        $mergedDataPenyidikan['wbp_profile_id'],
        $mergedDataPenyidikan['kasus_id'],
        $mergedDataPenyidikan['alasan_penyidikan'],
        $mergedDataPenyidikan['lokasi_penyidikan'],
        $mergedDataPenyidikan['waktu_penyidikan'],
        $mergedDataPenyidikan['agenda_penyidikan'],
        $mergedDataPenyidikan['hasil_penyidikan'],
        $penyidikan_id
    ];

    $stmtUpdatePenyidikan = $conn->prepare($queryUpdatePenyidikan);
    $stmtUpdatePenyidikan->execute($bindParamsPenyidikan);

    // update histori penyidikan
    $queryUpdateHistoriPenyidikan = "UPDATE histori_penyidikan SET
        hasil_penyidikan = ?,
        lama_masa_tahanan = ?
        WHERE penyidikan_id = ?";

    $bindParamsHistoriPenyidikan = [
        $mergedDataPenyidikan['hasil_penyidikan'],
        $mergedDataPenyidikan['lama_masa_tahanan'],
        $penyidikan_id
    ];

    $stmtUpdateHistoriPenyidikan = $conn->prepare($queryUpdateHistoriPenyidikan);
    $stmtUpdateHistoriPenyidikan->execute($bindParamsHistoriPenyidikan);

    // pivot penyidikan jaksa
    $deletePenyidikanJaksa = "DELETE FROM pivot_penyidikan_jaksa WHERE penyidikan_id = ?";
    $stmtDeletePenyidikanJaksa = $conn->prepare($deletePenyidikanJaksa);
    $stmtDeletePenyidikanJaksa->execute([$penyidikan_id]);

    // check apakah delete berhasil
    $queryCheckDeletePenyidikanJaksa = "SELECT * FROM pivot_penyidikan_jaksa WHERE penyidikan_id = ?";
    $stmtCheckDeletePenyidikanJaksa = $conn->prepare($queryCheckDeletePenyidikanJaksa);
    $stmtCheckDeletePenyidikanJaksa->execute([$penyidikan_id]);
    $existingPivotJaksa = $stmtCheckDeletePenyidikanJaksa->fetch(PDO::FETCH_ASSOC);


    if ($existingPivotJaksa) {
        echo json_encode([
            "status" => "NO",
            "message" => "Failed to delete entry in pivot_penyidikan_jaksa",
            "records" => []
        ]);
        exit;
    }

    // pivot penyidikan saksi
    $deletePenyidikanSaksi = "DELETE FROM pivot_penyidikan_saksi WHERE penyidikan_id = ?";
    $stmtDeletePenyidikanSaksi = $conn->prepare($deletePenyidikanSaksi);
    $stmtDeletePenyidikanSaksi->execute([$penyidikan_id]);

    // check apakah delete berhasil
    $queryCheckDeletePenyidikanSaksi = "SELECT * FROM pivot_penyidikan_jaksa WHERE penyidikan_id = ?";
    $stmtCheckDeletePenyidikanSaksi = $conn->prepare($queryCheckDeletePenyidikanSaksi);
    $stmtCheckDeletePenyidikanSaksi->execute([$penyidikan_id]);
    $existingPivotSaksi = $stmtCheckDeletePenyidikanSaksi->fetch(PDO::FETCH_ASSOC);


    if ($existingPivotSaksi) {
        echo json_encode([
            "status" => "NO",
            "message" => "Failed to delete entry in pivot_penyidikan_saksi",
            "records" => []
        ]);
        exit;
    }

    // insert pivot jaksa dan saksi
    $jaksa_penyidik_ids = isset($param_POST->jaksa_penyidik_id) ? $param_POST->jaksa_penyidik_id : [];
    $saksi_ids = isset($param_POST->saksi_id) ? $param_POST->saksi_id : [];
    $role_ketua_jaksa = isset($param_POST->role_ketua_jaksa) ? trim($param_POST->role_ketua_jaksa) : "";
    
    foreach ($jaksa_penyidik_ids as $jaksa_penyidik_id) {
        $pivot_penyidikan_jaksa_id = generateUUID();
        $queryInsertJaksaPenyidik = "INSERT INTO pivot_penyidikan_jaksa(
            pivot_penyidikan_jaksa_id,
            penyidikan_id,
            role_ketua,
            jaksa_penyidik_id
        ) VALUES (?, ?, ?, ?)";

        $stmtInsertJaksaPenyidik = $conn->prepare($queryInsertJaksaPenyidik);
        
        $role = ($jaksa_penyidik_id == $role_ketua_jaksa) ? 1 : 0;

        $stmtInsertJaksaPenyidik->execute([
            $pivot_penyidikan_jaksa_id,
            $penyidikan_id,
            $role,
            $jaksa_penyidik_id
        ]);
    }

    foreach ($saksi_ids as $saksi_id) {
        $pivot_penyidikan_saksi_id = generateUUID();
        $queryInsertSaksiPenyidik = "INSERT INTO pivot_penyidikan_saksi(
            pivot_penyidikan_saksi_id,
            penyidikan_id,
            saksi_id
        ) VALUES (?, ?, ?)";

        $stmtInsertSaksiPenyidik = $conn->prepare($queryInsertSaksiPenyidik);
        $stmtInsertSaksiPenyidik->execute([
            $pivot_penyidikan_saksi_id,
            $penyidikan_id,
            $saksi_id
        ]);
    }

    $result = [
        "status" => "OK",
        "message" => "Successfully updated penyidikan",
        "records" => [
            [
                "penyidikan_id" => $penyidikan_id,
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
