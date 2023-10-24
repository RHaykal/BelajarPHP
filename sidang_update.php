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

$sidang_id = isset($param_POST->sidang_id) ? trim($param_POST->sidang_id) : "";

if (empty($sidang_id)) {
    echo json_encode([
        "status" => "NO",
        "message" => "Missing sidang_id in request",
        "records" => []
    ]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $querySidang = "SELECT * FROM sidang WHERE sidang_id = ?";
    $stmtSidang = $conn->prepare($querySidang);
    $stmtSidang->execute([$sidang_id]);
    $existingSidang = $stmtSidang->fetch(PDO::FETCH_ASSOC);

    if (!$existingSidang) {
        echo json_encode([
            "status" => "NO",
            "message" => "Sidang not found",
            "records" => []
        ]);
        exit;
    }

    $newDataSidang = [
        "nama_sidang" => isset($param_POST->nama_sidang) ? trim($param_POST->nama_sidang) : "",
        "juru_sita" => isset($param_POST->juru_sita) ? trim($param_POST->juru_sita) : "",
        "pengawas_peradilan_militer" => isset($param_POST->pengawas_peradilan_militer) ? trim($param_POST->pengawas_peradilan_militer) : "",
        "jadwal_sidang" => isset($param_POST->jadwal_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->jadwal_sidang))) : "",
        "perubahan_jadwal_sidang" => isset($param_POST->perubahan_jadwal_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->perubahan_jadwal_sidang))) : "",
        "kasus_id" => isset($param_POST->kasus_id) ? trim($param_POST->kasus_id) : "",
        "waktu_mulai_sidang" => isset($param_POST->waktu_mulai_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->waktu_mulai_sidang))) : "",
        "waktu_selesai_sidang" => isset($param_POST->waktu_selesai_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->waktu_selesai_sidang))) : "",
        "agenda_sidang" => isset($param_POST->agenda_sidang) ? trim($param_POST->agenda_sidang) : "",
        "pengadilan_militer_id" => isset($param_POST->pengadilan_militer_id) ? trim($param_POST->pengadilan_militer_id) : "",
        "hasil_keputusan_sidang" => isset($param_POST->hasil_keputusan_sidang) ? trim($param_POST->hasil_keputusan_sidang) : "",
        "jenis_persidangan_id" => isset($param_POST->jenis_persidangan_id) ? trim($param_POST->jenis_persidangan_id) : "",
        "hasil_vonis" => isset($param_POST->hasil_vonis) ? trim($param_POST->hasil_vonis) : "",
        "masa_tahanan_tahun" => isset($param_POST->masa_tahanan_tahun) ? trim($param_POST->masa_tahanan_tahun) : "",
        "masa_tahanan_bulan" => isset($param_POST->masa_tahanan_bulan) ? trim($param_POST->masa_tahanan_bulan) : "",
        "masa_tahanan_hari" => isset($param_POST->masa_tahanan_hari) ? trim($param_POST->masa_tahanan_hari) : ""
    ];

    $newDataSidang = array_filter($newDataSidang, function ($value) {
        return $value !== "";
    });

    $mergedDataSidang = $existingSidang;

    $mergedDataSidang = array_merge($mergedDataSidang, $newDataSidang);

    $queryUpdateSidang = "UPDATE sidang SET
        nama_sidang = ?,
        juru_sita = ?,
        pengawas_peradilan_militer = ?,
        jadwal_sidang = ?,
        perubahan_jadwal_sidang = ?,
        kasus_id = ?,
        waktu_mulai_sidang = ?,
        waktu_selesai_sidang = ?,
        agenda_sidang = ?,
        pengadilan_militer_id = ?,
        hasil_keputusan_sidang = ?,
        jenis_persidangan_id = ?,
        updated_at = ?
        WHERE sidang_id = ?";

    $bindParamsSidang = [
        $mergedDataSidang['nama_sidang'],
        $mergedDataSidang['juru_sita'],
        $mergedDataSidang['pengawas_peradilan_militer'],
        $mergedDataSidang['jadwal_sidang'],
        $mergedDataSidang['perubahan_jadwal_sidang'],
        $mergedDataSidang['kasus_id'],
        $mergedDataSidang['waktu_mulai_sidang'],
        $mergedDataSidang['waktu_selesai_sidang'],
        $mergedDataSidang['agenda_sidang'],
        $mergedDataSidang['pengadilan_militer_id'],
        $mergedDataSidang['hasil_keputusan_sidang'],
        $mergedDataSidang['jenis_persidangan_id'],
        date('Y-m-d H:i:s'),
        $sidang_id
    ];

    $stmtUpdateSidang = $conn->prepare($queryUpdateSidang);
    $stmtUpdateSidang->execute($bindParamsSidang);

    // update histori Sidang
    $queryUpdateHistoriSidang = "UPDATE histori_vonis SET
        hasil_vonis = ?,
        masa_tahanan_tahun = ?,
        masa_tahanan_bulan = ?,
        masa_tahanan_hari = ?
        WHERE sidang_id = ?";

    $bindParamsHistoriSidang = [
        $mergedDataSidang['hasil_vonis'],
        $mergedDataSidang['masa_tahanan_tahun'],
        $mergedDataSidang['masa_tahanan_bulan'],
        $mergedDataSidang['masa_tahanan_hari'],
        $sidang_id
    ];

    $stmtUpdateHistoriSidang = $conn->prepare($queryUpdateHistoriSidang);
    $stmtUpdateHistoriSidang->execute($bindParamsHistoriSidang);

    // update dokumen Persidangan
    $queryUpdateDokumenPersidangan = "UPDATE dokumen_persidangan SET
        nama_dokumen_persidangan = ?,
        link_dokumen_persidangan = ?
        WHERE sidang_id = ?";
    
    $bindParamsDokumenPersidangan = [
        $mergedDataSidang['nama_dokumen_persidangan'],
        $mergedDataSidang['link_dokumen_persidangan'],
        $sidang_id
    ];

    $stmtUpdateDokumenPersidangan = $conn->prepare($queryUpdateDokumenPersidangan);
    $stmtUpdateDokumenPersidangan->execute($bindParamsDokumenPersidangan);

    // pivot sidang hakim
    $deleteSidangHakim = "DELETE FROM pivot_sidang_hakim WHERE sidang_id = ?";
    $stmtDeleteSidangHakim = $conn->prepare($deleteSidangHakim);
    $stmtDeleteSidangHakim->execute([$sidang_id]);

    // check apakah delete berhasil
    $queryCheckDeleteSidangHakim = "SELECT * FROM pivot_sidang_hakim WHERE sidang_id = ?";
    $stmtCheckDeleteSidangHakim = $conn->prepare($queryCheckDeleteSidangHakim);
    $stmtCheckDeleteSidangHakim->execute([$sidang_id]);
    $existingSidangHakim = $stmtCheckDeleteSidangHakim->fetch(PDO::FETCH_ASSOC);


    if ($existingSidangHakim) {
        echo json_encode([
            "status" => "NO",
            "message" => "Failed to delete hakim",
            "records" => []
        ]);
        exit;
    }

    // insert hakim
   $pivot_sidang_hakim_id = isset($param_POST->pivot_sidang_hakim_id) ? trim($param_POST->pivot_sidang_hakim_id) : [];
   $pivot_sidang_jaksa_id = isset($param_POST->pivot_sidang_jaksa_id) ? trim($param_POST->pivot_sidang_jaksa_id) : [];
   $sidang_pengacara_id = isset($param_POST->sidang_pengacara_id) ? trim($param_POST->sidang_pengacara_id) : [];
   $pivot_sidang_saksi_id = isset($param_POST->pivot_sidang_saksi_id) ? trim($param_POST->pivot_sidang_saksi_id) : [];
   $pivot_sidang_ahli_id = isset($param_POST->pivot_sidang_ahli_id) ? trim($param_POST->pivot_sidang_ahli_id) : [];
   $role_ketua_hakim = isset($param_POST->role_ketua_hakim) ? intval($param_POST->role_ketua_hakim) : 0;
   $role_ketua_jaksa = isset($param_POST->role_ketua_jaksa) ? intval($param_POST->role_ketua_jaksa) : 0;
   
   foreach ($pivot_sidang_hakim_id as $hakim_sidang) {
        $pivot_sidang_hakim_id = generateUUID();
        $queryInsertSidangHakim = "INSERT INTO pivot_sidang_hakim(
            pivot_sidang_hakim_id,
            sidang_id,
            role_ketua,
            hakim_id
        ) VALUES (?, ?, ?, ?)";

        $stmtInsertSidangHakim = $conn->prepare($queryInsertSidangHakim);
        $stmtInsertSidangHakim->execute([
            $pivot_sidang_hakim_id,
            $sidang_id,
            $role_ketua_hakim,
            $hakim_sidang
        ]);
   }

   foreach ($sidang_jaksa_id as $jaksa_sidang) {
        $pivot_sidang_jaksa_id = generateUUID();
        $queryInsertSidangJaksa = "INSERT INTO pivot_sidang_jaksa(
            pivot_sidang_jaksa_id,
            sidang_id,
            role_ketua,
            jaksa_id
        ) VALUES (?, ?, ?, ?)";

        $stmtInsertSidangJaksa = $conn->prepare($queryInsertSidangJaksa);
        $stmtInsertSidangJaksa->execute([
            $pivot_sidang_jaksa_id,
            $sidang_id,
            $role_ketua_jaksa,
            $jaksa_sidang
        ]);
   }

    foreach ($sidang_pengacara_id as $pengacara_sidang) {
          $sidang_pengacara_id = generateUUID();
          $queryInsertSidangPengacara = "INSERT INTO sidang_pengacara(
                sidang_pengacara_id,
                sidang_id,
                nama_pengacara
          ) VALUES (?, ?, ?)";
    
          $stmtInsertSidangPengacara = $conn->prepare($queryInsertSidangPengacara);
          $stmtInsertSidangPengacara->execute([
                $sidang_pengacara_id,
                $sidang_id,
                $pengacara_sidang
          ]);
    }

    foreach ($sidang_saksi_id as $saksi_sidang) {
          $pivot_sidang_saksi_id = generateUUID();
          $queryInsertSidangSaksi = "INSERT INTO pivot_sidang_saksi(
                pivot_sidang_saksi_id,
                sidang_id,
                saksi_id
          ) VALUES (?, ?, ?)";
    
          $stmtInsertSidangSaksi = $conn->prepare($queryInsertSidangSaksi);
          $stmtInsertSidangSaksi->execute([
                $pivot_sidang_saksi_id,
                $sidang_id,
                $saksi_sidang
          ]);
    }

    foreach ($sidang_ahli_id as $ahli_sidang) {
          $pivot_sidang_ahli_id = generateUUID();
          $queryInsertSidangAhli = "INSERT INTO pivot_sidang_ahli(
                pivot_sidang_ahli_id,
                sidang_id,
                ahli_id
          ) VALUES (?, ?, ?)";
    
          $stmtInsertSidangAhli = $conn->prepare($queryInsertSidangAhli);
          $stmtInsertSidangAhli->execute([
                $pivot_sidang_ahli_id,
                $sidang_id,
                $ahli_sidang
          ]);
    }

    $result = [
        "status" => "OK",
        "message" => "Successfully updated sidang",
        "records" => [
            [
                "sidang_id" => $sidang_id,
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
