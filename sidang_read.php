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

try {
    $conn = new PDO(
        "mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8",
        $MySQL_USER,
        $MySQL_PASSWORD
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;
    $filterNamaSidang = isset($requestData['filter']['nama_sidang']) ? $requestData['filter']['nama_sidang'] : "";
    $filterNamaJenisPersidangan = isset($requestData['filter']['nama_jenis_persidangan']) ? $requestData['filter']['nama_jenis_persidangan'] : "";
    $filterNamaWBP = isset($requestData['filter']['nama_wbp']) ? $requestData['filter']['nama_wbp'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama_sidang';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_sidang'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'nama_sidang'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT sidang.sidang_id,
    sidang.nama_sidang,
    sidang.juru_sita,
    sidang.pengawas_peradilan_militer,
    sidang.jadwal_sidang,
    sidang.perubahan_jadwal_sidang,
    sidang.kasus_id,
    sidang.waktu_mulai_sidang,
    sidang.waktu_selesai_sidang,
    sidang.pengadilan_militer_id,
    sidang.agenda_sidang,
    sidang.hasil_keputusan_sidang,
    sidang.jenis_persidangan_id,
    sidang.created_at as created_at_sidang,
    sidang.updated_at as updated_at_sidang,
    -- kasus
    kasus.nama_kasus,
    kasus.nomor_kasus,
    kasus.wbp_profile_id as wbp_profile_id_kasus,
    kasus.jenis_perkara_id as jenis_perkara_id_kasus,
    kasus.tanggal_registrasi_kasus,
    kasus.oditur_id as oditur_id_kasus,
    kasus.created_at as created_at_kasus,
    kasus.updated_at as updated_at_kasus,
    wbp_profile_kasus.nama AS nama_wbp,
    wbp_profile_kasus.nrp AS nrp_wbp,
    wbp_profile_kasus.nomor_tahanan AS nomor_tahanan_wbp,
    jenis_perkara_kasus.kategori_perkara_id as kategori_perkara_id_kasus,
    jenis_perkara_kasus.nama_jenis_perkara as nama_jenis_perkara_kasus,
    jenis_perkara_kasus.pasal as pasal_kasus,
    jenis_perkara_kasus.vonis_tahun_perkara as vonis_tahun_perkara_kasus,
    jenis_perkara_kasus.vonis_bulan_perkara as vonis_bulan_perkara_kasus,
    jenis_perkara_kasus.vonis_hari_perkara as vonis_hari_perkara_kasus,
    kategori_perkara_kasus.nama_kategori_perkara as nama_kategori_perkara_kasus,
    oditur_kasus.nama_oditur AS nama_oditur_kasus,

    -- pengadilan militer
    pengadilan_militer.nama_pengadilan_militer,
    pengadilan_militer.provinsi_id,
     provinsi.nama_provinsi,
     pengadilan_militer.kota_id,
     kota.nama_kota,
     pengadilan_militer.latitude,
     pengadilan_militer.longitude,
    --  jenis persidangan
     jenis_persidangan.nama_jenis_persidangan,
    -- histori vonis
    histori_vonis.sidang_id as sidang_id_vonis,
    histori_vonis.hasil_vonis,
    histori_vonis.masa_tahanan_tahun,
    histori_vonis.masa_tahanan_bulan,
    histori_vonis.masa_tahanan_hari,
    -- dokumen persidangan
    dokumen_persidangan.sidang_id as sidang_id_dokumen_persidangan,
    dokumen_persidangan.nama_dokumen_persidangan,
    dokumen_persidangan.link_dokumen_persidangan
FROM sidang
LEFT JOIN kasus ON sidang.kasus_id = kasus.kasus_id
LEFT JOIN wbp_profile AS wbp_profile_kasus ON kasus.wbp_profile_id = wbp_profile_kasus.wbp_profile_id
LEFT JOIN jenis_perkara AS jenis_perkara_kasus ON kasus.jenis_perkara_id = jenis_perkara_kasus.jenis_perkara_id
LEFT JOIN kategori_perkara AS kategori_perkara_kasus ON jenis_perkara_kasus.kategori_perkara_id = kategori_perkara_kasus.kategori_perkara_id
LEFT JOIN oditur AS oditur_kasus ON kasus.oditur_id = oditur_kasus.oditur_id
LEFT JOIN pengadilan_militer ON sidang.pengadilan_militer_id = pengadilan_militer.pengadilan_militer_id
LEFT JOIN provinsi ON pengadilan_militer.provinsi_id = provinsi.provinsi_id
LEFT JOIN kota ON pengadilan_militer.kota_id = kota.kota_id
LEFT JOIN jenis_persidangan ON sidang.jenis_persidangan_id = jenis_persidangan.jenis_persidangan_id
LEFT JOIN histori_vonis ON sidang.sidang_id = histori_vonis.sidang_id
LEFT JOIN dokumen_persidangan ON dokumen_persidangan.sidang_id = sidang.sidang_id
WHERE sidang.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    
    if (!empty($filterNamaSidang)) {
        $query .= " AND sidang.nama_sidang LIKE '%$filterNamaSidang%'";
    }

    if (!empty($filterNamaJenisPersidangan)) {
        $query .= " AND jenis_persidangan.nama_jenis_persidangan LIKE '%$filterNamaJenisPersidangan%'";
    }

    if (!empty($filterNamaWBP)) {
        $query .= " AND wbp_profile_kasus.nama LIKE '%$filterNamaWBP%'";
    }

    $query .= " GROUP BY sidang.sidang_id";
    $query .= " ORDER BY $sortField $sortOrder";

    // preparing and executing the query that has been updated with pagination feature
    $countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Apply pagination
    $totalPages = ceil($totalRecords / $pageSize);
    $start = ($page - 1) * $pageSize;
    $query .= " LIMIT $start, $pageSize";

    // executing query to fecth all the data with filter and pagination feature
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];

    //loop through the result of the query and assigned it on $recordData variable
    foreach ($res as $row) {

        $sidang_id = $row['sidang_id'];

        //sidang_hakim
        $pivot_sidang_hakim = "SELECT pivot_sidang_hakim.pivot_sidang_hakim_id,
        pivot_sidang_hakim.role_ketua,
        hakim.hakim_id,
        hakim.nip as nip_hakim,
        hakim.nama_hakim,
        hakim.alamat,
        hakim.departemen as departemen_hakim
        FROM pivot_sidang_hakim
        LEFT JOIN hakim ON pivot_sidang_hakim.hakim_id = hakim.hakim_id
        WHERE pivot_sidang_hakim.sidang_id = :sidang_id
        AND pivot_sidang_hakim.hakim_id IS NOT NULL
        AND (pivot_sidang_hakim.hakim_id IS NOT NULL OR pivot_sidang_hakim.hakim_id != '')";


        $pivot_sidang_hakim_stmt = $conn->prepare($pivot_sidang_hakim);
        $pivot_sidang_hakim_stmt->bindParam(':sidang_id', $sidang_id, PDO::PARAM_STR);
        $pivot_sidang_hakim_stmt->execute();
        $pivot_sidang_hakim_res = $pivot_sidang_hakim_stmt->fetchAll(PDO::FETCH_ASSOC);

        //sidang_jaksa
        $pivot_sidang_jaksa = "SELECT pivot_sidang_jaksa.pivot_sidang_jaksa_id,
        pivot_sidang_jaksa.role_ketua,
        jaksa_penuntut.jaksa_penuntut_id,
        jaksa_penuntut.nip as nip_jaksa,
        jaksa_penuntut.nama_jaksa,
        jaksa_penuntut.alamat
        FROM pivot_sidang_jaksa
        LEFT JOIN jaksa_penuntut ON pivot_sidang_jaksa.jaksa_penuntut_id = jaksa_penuntut.jaksa_penuntut_id
        WHERE pivot_sidang_jaksa.sidang_id = :sidang_id
        AND pivot_sidang_jaksa.jaksa_penuntut_id IS NOT NULL
        AND (pivot_sidang_jaksa.jaksa_penuntut_id IS NOT NULL OR pivot_sidang_jaksa.jaksa_penuntut_id != '')";

        $pivot_sidang_jaksa_stmt = $conn->prepare($pivot_sidang_jaksa);
        $pivot_sidang_jaksa_stmt->bindParam(':sidang_id', $sidang_id, PDO::PARAM_STR);
        $pivot_sidang_jaksa_stmt->execute();
        $pivot_sidang_jaksa_res = $pivot_sidang_jaksa_stmt->fetchAll(PDO::FETCH_ASSOC);

        //sidang_pengacara
        $sidang_pengacara = "SELECT sidang_pengacara.nama_pengacara
        FROM sidang_pengacara
        WHERE sidang_pengacara.sidang_id = :sidang_id
        AND sidang_pengacara.nama_pengacara IS NOT NULL
        AND (sidang_pengacara.nama_pengacara IS NOT NULL OR sidang_pengacara.nama_pengacara != '')";

        $sidang_pengacara_stmt = $conn->prepare($sidang_pengacara);
        $sidang_pengacara_stmt->bindParam(':sidang_id', $sidang_id, PDO::PARAM_STR);
        $sidang_pengacara_stmt->execute();
        $sidang_pengacara_res = $sidang_pengacara_stmt->fetchAll(PDO::FETCH_ASSOC);

        //saksi
        $pivot_sidang_saksi = "SELECT pivot_sidang_saksi.pivot_sidang_saksi_id,
        saksi.saksi_id,
        saksi.nama_saksi
        FROM pivot_sidang_saksi
        LEFT JOIN saksi ON pivot_sidang_saksi.saksi_id = saksi.saksi_id
        WHERE pivot_sidang_saksi.sidang_id = :sidang_id
        AND pivot_sidang_saksi.saksi_id IS NOT NULL
        AND (pivot_sidang_saksi.saksi_id IS NOT NULL OR pivot_sidang_saksi.saksi_id != '')";

        $pivot_sidang_saksi_stmt = $conn->prepare($pivot_sidang_saksi);
        $pivot_sidang_saksi_stmt->bindParam(':sidang_id', $sidang_id, PDO::PARAM_STR);
        $pivot_sidang_saksi_stmt->execute();
        $pivot_sidang_saksi_res = $pivot_sidang_saksi_stmt->fetchAll(PDO::FETCH_ASSOC);

        //ahli
        $pivot_sidang_ahli = "SELECT pivot_sidang_ahli.pivot_sidang_ahli_id,
        ahli.ahli_id,
        ahli.nama_ahli
        FROM pivot_sidang_ahli
        LEFT JOIN ahli ON pivot_sidang_ahli.ahli_id = ahli.ahli_id
        WHERE pivot_sidang_ahli.sidang_id = :sidang_id
        AND pivot_sidang_ahli.ahli_id IS NOT NULL
        AND (pivot_sidang_ahli.ahli_id IS NOT NULL OR pivot_sidang_ahli.ahli_id != '')";

        $pivot_sidang_ahli_stmt = $conn->prepare($pivot_sidang_ahli);
        $pivot_sidang_ahli_stmt->bindParam(':sidang_id', $sidang_id, PDO::PARAM_STR);
        $pivot_sidang_ahli_stmt->execute();
        $pivot_sidang_ahli_res = $pivot_sidang_ahli_stmt->fetchAll(PDO::FETCH_ASSOC);



        // record data

        // $sidang_hakim = ["sidang_hakim" => $sidang_hakim_res];
        // $sidang_jaksa = ["sidang_jaksa" => $sidang_jaksa_res];
        // $sidang_pengacara = ["sidang_pengacara" => $sidang_pengacara_res];
        // $sidang_saksi = [ "sidang_saksi" => $sidang_saksi_res];




        $recordData = [
            "sidang_id" => $row['sidang_id'],
            "nama_sidang" => $row['nama_sidang'],
            "juru_sita" => $row['juru_sita'],
            "pengawas_peradilan_militer" => $row['pengawas_peradilan_militer'],
            "jadwal_sidang" => $row['jadwal_sidang'],
            "perubahan_jadwal_sidang" => $row['perubahan_jadwal_sidang'],
            "kasus_id" => $row['kasus_id'],
            "waktu_mulai_sidang" => $row['waktu_mulai_sidang'],
            "waktu_selesai_sidang" => $row['waktu_selesai_sidang'],
            "agenda_sidang" => $row['agenda_sidang'],
            "pengadilan_militer_id" => $row['pengadilan_militer_id'],
            "hasil_keputusan_sidang" => $row['hasil_keputusan_sidang'],
            "jenis_persidangan_id" => $row['jenis_persidangan_id'],
            "created_at_sidang" => $row['created_at_sidang'],
            "updated_at_sidang" => $row['updated_at_sidang'],
            "nama_kasus" => $row['nama_kasus'],
            "nomor_kasus" => $row['nomor_kasus'],
            "wbp_profile_id_kasus" => $row['wbp_profile_id_kasus'],
            "jenis_perkara_id_kasus" => $row['jenis_perkara_id_kasus'],
            "tanggal_registrasi_kasus" => $row['tanggal_registrasi_kasus'],
            "oditur_id_kasus" => $row['oditur_id_kasus'],
            "created_at_kasus" => $row['created_at_kasus'],
            "updated_at_kasus" => $row['updated_at_kasus'],
            "nama_wbp" => $row['nama_wbp'],
            "nrp_wbp" => $row['nrp_wbp'],
            "nomor_tahanan_wbp" => $row['nomor_tahanan_wbp'],
            "kategori_perkara_id_kasus" => $row['kategori_perkara_id_kasus'],
            "nama_jenis_perkara_kasus" => $row['nama_jenis_perkara_kasus'],
            "pasal_kasus" => $row['pasal_kasus'],
            "vonis_tahun_perkara_kasus" => $row['vonis_tahun_perkara_kasus'],
            "vonis_bulan_perkara_kasus" => $row['vonis_bulan_perkara_kasus'],
            "vonis_hari_perkara_kasus" => $row['vonis_hari_perkara_kasus'],
            "nama_kategori_perkara_kasus" => $row['nama_kategori_perkara_kasus'],
            "nama_oditur_kasus" => $row['nama_oditur_kasus'],
            "nama_pengadilan_militer" => $row['nama_pengadilan_militer'],
            "provinsi_id" => $row['provinsi_id'],
            "nama_provinsi" => $row['nama_provinsi'],
            "kota_id" => $row['kota_id'],
            "nama_kota" => $row['nama_kota'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude'],
            "nama_jenis_persidangan" => $row['nama_jenis_persidangan'],
            "sidang_hakim" => isset($pivot_sidang_hakim_res) ? $pivot_sidang_hakim_res : null,
            "sidang_jaksa" => isset($pivot_sidang_jaksa_res) ? $pivot_sidang_jaksa_res : null,
            "sidang_pengacara" => isset($sidang_pengacara_res) ? $sidang_pengacara_res : null,
            "sidang_saksi" => isset($pivot_sidang_saksi_res) ? $pivot_sidang_saksi_res : null,
            "sidang_ahli" => isset($pivot_sidang_ahli_res) ? $pivot_sidang_ahli_res : null,
            "hasil_vonis" => $row['hasil_vonis'],
            "masa_tahanan_tahun" => $row['masa_tahanan_tahun'],
            "masa_tahanan_bulan" => $row['masa_tahanan_bulan'],
            "masa_tahanan_hari" => $row['masa_tahanan_hari']
        ];
        $record[] = $recordData;
    }

    // Prepare the JSON response with pagination information
    $response = [
        "status" => "OK",
        "message" => "",
        "records" => $record,
        "pagination" => [
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "totalRecords" => $totalRecords,
            "totalPages" => $totalPages
        ]
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => []
    ];

    echo json_encode($result);
}

$stmt = null;
$conn = null;
?>
