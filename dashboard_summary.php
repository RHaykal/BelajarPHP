<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

try {
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD 
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    $filterData = json_decode(file_get_contents('php://input'), true); // Assuming JSON is sent via POST

    if (isset($filterData['filter'])) {
        $filterLokasiOtmil = isset($filterData['filter']['lokasi_otmil_id']) ? $filterData['filter']['lokasi_otmil_id'] : "";
        $filterLokasiLemasmil = isset($filterData['filter']['lokasi_lemasmil_id']) ? $filterData['filter']['lokasi_lemasmil_id'] : "";
    }


    // Before executing the query, add debugging output
    // echo "SQL Query: " . $queryWBP . "<br>";
    // echo "Filter Values: ";
    // print_r([
    //     ":lokasi_otmil_id" => $filterLokasiOtmil,
    //     ":lokasi_lemasmil_id" => $filterLokasiLemasmil
    // ]);

    // Execute the query and continue with the rest of your code

    $records = [];
    if (!empty($filterLokasiOtmil)) {
        $queryLocationNameOtmil = "SELECT nama_lokasi_otmil FROM lokasi_otmil WHERE lokasi_otmil_id = :lokasi_otmil_id";
        $stmtOtmil = $conn->prepare($queryLocationNameOtmil);
        $stmtOtmil->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
        $stmtOtmil->execute();
        $lokasiOtmil = $stmtOtmil->fetch(PDO::FETCH_ASSOC);
        $records['lokasi_otmil'] = $lokasiOtmil['nama_lokasi_otmil'];
    }
    
    if (!empty($filterLokasiLemasmil)) {
        $queryLocationNameLemasmil = "SELECT nama_lokasi_lemasmil FROM lokasi_lemasmil WHERE lokasi_lemasmil_id = :lokasi_lemasmil_id";
        $stmtLemasmil = $conn->prepare($queryLocationNameLemasmil);
        $stmtLemasmil->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
        $stmtLemasmil->execute();
        $lokasiLemasmil = $stmtLemasmil->fetch(PDO::FETCH_ASSOC);
        $records['lokasi_lemasmil'] = $lokasiLemasmil['nama_lokasi_lemasmil'];
    }
    


    $queryWBPtotal = "SELECT COUNT(*) as total_wbp FROM wbp_profile 
    LEFT JOIN wbp_perkara ON wbp_perkara.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN lokasi_otmil ON wbp_perkara.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON wbp_perkara.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE wbp_profile.is_deleted = 0";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $queryWBPtotal .= " AND wbp_perkara.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $queryWBPtotal .= " AND wbp_perkara.lokasi_lemasmil_id LIKE '%$filterLokasiOtmil%'";
    }

    $stmt = $conn->prepare($queryWBPtotal);
    $stmt->execute();
    $totalWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_wbp'] = $totalWBP['total_wbp'];

    $queryWBPsick = "SELECT COUNT(*) as sick_wbp FROM wbp_profile 
    LEFT JOIN wbp_perkara ON wbp_perkara.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN lokasi_otmil ON wbp_perkara.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON wbp_perkara.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE wbp_profile.is_deleted = 0 AND wbp_profile.is_sick = 1";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $queryWBPsick .= " AND wbp_perkara.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $queryWBPsick .= " AND wbp_perkara.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    $stmt = $conn->prepare($queryWBPsick);
    $stmt->execute();
    $sickWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['sick_wbp'] = $sickWBP['sick_wbp'];

    $queryWBPisolated = "SELECT COUNT(*) as isolated_wbp FROM wbp_profile 
    LEFT JOIN wbp_perkara ON wbp_perkara.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN lokasi_otmil ON wbp_perkara.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON wbp_perkara.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE wbp_profile.is_deleted = 0 AND wbp_profile.is_sick = 1";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $queryWBPisolated .= " AND wbp_perkara.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $queryWBPisolated .= " AND wbp_perkara.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    $stmt = $conn->prepare($queryWBPisolated);
    $stmt->execute();
    $isolatedWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['isolated_wbp'] = $isolatedWBP['isolated_wbp'];

    $querytotalkamera = "SELECT COUNT(*) as total_kamera FROM kamera 
    LEFT JOIN ruangan_otmil ON kamera.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON kamera.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE kamera.is_deleted = 0 ";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalkamera .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalkamera .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
        //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
        // }
        // if (!empty($filterLokasiLemasmil)) {
            //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
            // }
            
    $stmt = $conn->prepare($querytotalkamera);
    $stmt->execute();
    $isolatedWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_kamera'] = $isolatedWBP['total_kamera'];

    $querytotalkameraaktif = "SELECT COUNT(*) as kamera_aktif FROM kamera 
    LEFT JOIN ruangan_otmil ON kamera.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON kamera.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE kamera.is_deleted = 0 AND kamera.status_kamera = 'aktif'";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalkameraaktif .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalkameraaktif .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
        //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
        // }
        // if (!empty($filterLokasiLemasmil)) {
            //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
            // }
            
    $stmt = $conn->prepare($querytotalkameraaktif);
    $stmt->execute();
    $totalkameraaktif = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['kamera_aktif'] = $totalkameraaktif['kamera_aktif'];

    $querytotalkameranonaktif = "SELECT COUNT(*) as kamera_nonaktif FROM kamera 
    LEFT JOIN ruangan_otmil ON kamera.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON kamera.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE kamera.is_deleted = 0 AND kamera.status_kamera = 'nonaktif'";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalkameranonaktif .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalkameranonaktif .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
    //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
    // }
    // if (!empty($filterLokasiLemasmil)) {
        //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
        // }
        
    $stmt = $conn->prepare($querytotalkameranonaktif);
    $stmt->execute();
    $totalkameranonaktif = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['kamera_nonaktif'] = $totalkameranonaktif['kamera_nonaktif'];


    $querytotalkamerarusak = "SELECT COUNT(*) as kamera_rusak FROM kamera 
    LEFT JOIN ruangan_otmil ON kamera.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON kamera.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE kamera.is_deleted = 0 AND kamera.status_kamera = 'rusak'";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalkamerarusak .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalkamerarusak .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
    //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
    // }
    // if (!empty($filterLokasiLemasmil)) {
        //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
        // }
        
    $stmt = $conn->prepare($querytotalkamerarusak);
    $stmt->execute();
    $totalkamerarusak = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['kamera_rusak'] = $totalkamerarusak['kamera_rusak'];

    $querytotalgateway = "SELECT COUNT(*) as total_gateway FROM gateway 
    LEFT JOIN ruangan_otmil ON gateway.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gateway.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gateway.is_deleted = 0 ";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgateway .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgateway .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
    //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
    // }
    // if (!empty($filterLokasiLemasmil)) {
        //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
        // }
        
    $stmt = $conn->prepare($querytotalgateway);
    $stmt->execute();
    $isolatedWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_gateway'] = $isolatedWBP['total_gateway'];

    $querytotalgatewayaktif = "SELECT COUNT(*) as gateway_aktif FROM gateway 
    LEFT JOIN ruangan_otmil ON gateway.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gateway.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gateway.is_deleted = 0 AND gateway.status_gateway = 'aktif'";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgatewayaktif .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgatewayaktif .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
    //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
    // }
    // if (!empty($filterLokasiLemasmil)) {
        //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
        // }
        
    $stmt = $conn->prepare($querytotalgatewayaktif);
    $stmt->execute();
    $totalgatewayaktif = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['gateway_aktif'] = $totalgatewayaktif['gateway_aktif'];

    $querytotalgatewaynonaktif = "SELECT COUNT(*) as gateway_nonaktif FROM gateway 
    LEFT JOIN ruangan_otmil ON gateway.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gateway.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gateway.is_deleted = 0 AND gateway.status_gateway = 'nonaktif'";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgatewaynonaktif .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgatewaynonaktif .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
        //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
        // }
        // if (!empty($filterLokasiLemasmil)) {
            //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
            // }
            
    $stmt = $conn->prepare($querytotalgatewaynonaktif);
    $stmt->execute();
    $totalgatewaynonaktif = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['gateway_nonaktif'] = $totalgatewaynonaktif['gateway_nonaktif'];


    $querytotalgatewayrusak = "SELECT COUNT(*) as gateway_rusak FROM gateway 
    LEFT JOIN ruangan_otmil ON gateway.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gateway.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gateway.is_deleted = 0 AND gateway.status_gateway = 'rusak'";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgatewayrusak .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgatewayrusak .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    
    // if (!empty($filterLokasiOtmil)) {
        //     $stmt->bindParam(":lokasi_otmil_id", $filterLokasiOtmil);
        // }
        // if (!empty($filterLokasiLemasmil)) {
            //     $stmt->bindParam(":lokasi_lemasmil_id", $filterLokasiLemasmil);
            // }
            
    $stmt = $conn->prepare($querytotalgatewayrusak);
    $stmt->execute();
    $totalgatewayrusak = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['gateway_rusak'] = $totalgatewayrusak['gateway_rusak'];


    $querytotalgelang = "SELECT COUNT(*) as total_gelang FROM gelang 
    LEFT JOIN ruangan_otmil ON gelang.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gelang.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gelang.is_deleted = 0 ";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgelang .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgelang .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    $stmt = $conn->prepare($querytotalgelang);
    $stmt->execute();
    $isolatedWBP = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_gelang'] = $isolatedWBP['total_gelang'];

    $querytotalgelangaktif = "SELECT COUNT(*) as gelang_aktif FROM gelang 
    LEFT JOIN ruangan_otmil ON gelang.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gelang.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gelang.is_deleted = 0 AND gelang.baterai > 20";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgelangaktif .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgelangaktif .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }
            
    $stmt = $conn->prepare($querytotalgelangaktif);
    $stmt->execute();
    $totalgelangaktif = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['gelang_aktif'] = $totalgelangaktif['gelang_aktif'];

    $querytotalgelanglowpower = "SELECT COUNT(*) as gelang_low_power FROM gelang 
    LEFT JOIN ruangan_otmil ON gelang.ruangan_otmil_id = ruangan_otmil.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON gelang.ruangan_lemasmil_id = ruangan_lemasmil.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON ruangan_otmil.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON ruangan_lemasmil.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE gelang.is_deleted = 0 AND gelang.baterai <= 20";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalgelanglowpower .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalgelanglowpower .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    $stmt = $conn->prepare($querytotalgelanglowpower);
    $stmt->execute();
    $totalgelangnonaktif = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['gelang_low_power'] = $totalgelangnonaktif['gelang_low_power'];


    $querytotalperkara  = "SELECT COUNT(*) as total_perkara FROM wbp_perkara
    LEFT JOIN wbp_profile ON wbp_perkara.wbp_profile_id = wbp_profile.wbp_profile_id
    LEFT JOIN lokasi_otmil ON wbp_perkara.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON wbp_perkara.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    WHERE wbp_perkara.is_deleted = 0";

    // Add filter conditions
    if (!empty($filterLokasiOtmil)) {
        $querytotalperkara .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
    }
    if (!empty($filterLokasiLemasmil)) {
        $querytotalperkara .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
    }

    $stmt = $conn->prepare($querytotalperkara);
    $stmt->execute();
    $totalperkara = $stmt->fetch(PDO::FETCH_ASSOC);

    $records['total_perkara'] = $totalperkara['total_perkara'];

    $queryperkarkategori = "SELECT kategori_perkara_id FROM kategori_perkara WHERE kategori_perkara.is_deleted = 0";

    $stmt = $conn->prepare($queryperkarkategori);

    $stmt->execute();

    $perkarkategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($perkarkategori as $key => $value) {
        $kategori_perkara_id = $value['kategori_perkara_id'];
        $queryperkarakategoritotal = "SELECT COUNT(*) as total_perkara FROM wbp_perkara
        LEFT JOIN wbp_profile ON wbp_perkara.wbp_profile_id = wbp_profile.wbp_profile_id
        LEFT JOIN lokasi_otmil ON wbp_perkara.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
        LEFT JOIN lokasi_lemasmil ON wbp_perkara.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
        WHERE wbp_perkara.is_deleted = 0 AND wbp_perkara.kategori_perkara_id LIKE '%$kategori_perkara_id%'";

        // Add filter conditions
        if (!empty($filterLokasiOtmil)) {
            $queryperkarakategoritotal .= " AND lokasi_otmil.lokasi_otmil_id LIKE '%$filterLokasiOtmil%'";
        }
        if (!empty($filterLokasiLemasmil)) {
            $queryperkarakategoritotal .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE '%$filterLokasiLemasmil%'";
        }

        $stmt = $conn->prepare($queryperkarakategoritotal);
        $stmt->execute();
        $perkarakategoritotal = $stmt->fetch(PDO::FETCH_ASSOC);

        $query_nama_kategori_perkara = "SELECT nama_kategori_perkara FROM kategori_perkara WHERE kategori_perkara_id LIKE '%$kategori_perkara_id%'";

        $stmt = $conn->prepare($query_nama_kategori_perkara);

        $stmt->execute();
        $nama_kategori_perkara = $stmt->fetch(PDO::FETCH_ASSOC);

        $records['perkara'][$nama_kategori_perkara['nama_kategori_perkara']] = $perkarakategoritotal['total_perkara'];
        
    }

    $response = [
        "status" => "OK",
        "message" => "Data Dashboard Summary berhasil diambil",
        "records" => $records
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => 0
    ];

    echo json_encode($result);
}

$stmt = null;
$conn = null;
?>

