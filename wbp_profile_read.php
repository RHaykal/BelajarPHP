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

    // tokenAuth($conn, 'operator');

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pagination']['pageSize']) ? (int) $requestData['pagination']['pageSize'] : 10;
    $page = isset($requestData['pagination']['currentPage']) ? (int) $requestData['pagination']['currentPage'] : 1;
    $filterNama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterIsolated = isset($requestData['filter']['is_isolated']) ? $requestData['filter']['is_isolated'] : "";
    $filterAlamat = isset($requestData['filter']['alamat']) ? $requestData['filter']['alamat'] : "";
    $filterNamaPangkat = isset($requestData['filter']['nama_pangkat']) ? $requestData['filter']['nama_pangkat'] : "";
    $filterNamaKesatuan = isset($requestData['filter']['nama_kesatuan']) ? $requestData['filter']['nama_kesatuan'] : "";
    $filterNamaLokasiKesatuan = isset($requestData['filter']['nama_lokasi_kesatuan']) ? $requestData['filter']['nama_lokasi_kesatuan'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterVonisBulan = isset($requestData['filter']['vonis_bulan']) ? $requestData['filter']['vonis_bulan'] : "";
    $filterVonisTahun = isset($requestData['filter']['vonis_tahun']) ? $requestData['filter']['vonis_tahun'] : "";
    $filterNamaKategoriPerkara = isset($requestData['filter']['nama_kategori_perkara']) ? $requestData['filter']['nama_kategori_perkara'] : "";
    $filterHunianWbpOtmil = isset($requestData['filter']['nama_hunian_wbp_otmil']) ? $requestData['filter']['nama_hunian_wbp_otmil'] : "";
    $filterHunianWbpLemasmil = isset($requestData['filter']['nama_hunian_wbp_lemasmil']) ? $requestData['filter']['nama_hunian_wbp_lemasmil'] : "";
    $filterNamaMatra = isset($requestData['filter']['nama_matra']) ? $requestData['filter']['nama_matra'] : "";
    $filterNRP = isset($requestData['filter']['nrp']) ? $requestData['filter']['nrp'] : "";
    $filterTanggalDitahanOtmil = isset($requestData['filter']['tanggal_ditahan_otmil']) ? $requestData['filter']['tanggal_ditahan_otmil'] : "";
    $filterTanggalDitahanLemasmil = isset($requestData['filter']['tanggal_ditahan_lemasmil']) ? $requestData['filter']['tanggal_ditahan_lemasmil'] : "";
    $filterTanggalPenetapanTersangka = isset($requestData['filter']['tanggal_penetapan_tersangka']) ? $requestData['filter']['tanggal_penetapan_tersangka'] : "";
    $filterTanggalPenetapanTerdakwa = isset($requestData['filter']['tanggal_penetapan_terdakwa']) ? $requestData['filter']['tanggal_penetapan_terdakwa'] : "";
    $filterTanggalPenetapanTerpidana = isset($requestData['filter']['tanggal_penetapan_terpidana']) ? $requestData['filter']['tanggal_penetapan_terpidana'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'nama';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $validSortFields = ['nama', 'nama_pangkat', 'nama_kesatuan', 'nama_lokasi_kesatuan', 'nama_lokasi_otmil', 'nama_lokasi_lemasmil', 'vonis_bulan', 'vonis_tahun', 'nama_kategori_perkara'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'nama';
    }

    // Construct the SQL query with filtering conditions
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
        wbp_profile.foto_wajah,
        wbp_profile.nomor_tahanan,
        wbp_profile.residivis,
        wbp_profile.status_wbp_kasus_id,
        wbp_profile.created_at as created_at_wbp_profile,
        wbp_profile.updated_at as updated_at_wbp_profile,
        wbp_profile.is_deleted as is_deleted_wbp_profile,
        wbp_profile.foto_wajah_fr,
        wbp_profile.is_isolated, 
        wbp_profile.is_sick,
        wbp_profile.wbp_sickness,
        wbp_profile.gelang_id,
        wbp_profile.hunian_wbp_lemasmil_id,
        wbp_profile.hunian_wbp_otmil_id,
        wbp_profile.nama_kontak_keluarga,
        wbp_profile.hubungan_kontak_keluarga,
        wbp_profile.nomor_kontak_keluarga, 
        wbp_profile.matra_id,
        wbp_profile.nrp,
        wbp_profile.tanggal_ditahan_otmil,
        wbp_profile.tanggal_ditahan_lemasmil,
        wbp_profile.tanggal_penetapan_tersangka,
        wbp_profile.tanggal_penetapan_terdakwa,
        wbp_profile.tanggal_penetapan_terpidana,

        -- pangkat
        pangkat.pangkat_id as pangkat_id_wbp_profile,
        pangkat.nama_pangkat as nama_pangkat,

        -- kesatuan
        kesatuan.kesatuan_id as kesatuan_id_wbp_profile,
        kesatuan.nama_kesatuan,
        kesatuan.lokasi_kesatuan_id,

        -- lokasi kesatuan berdasarkan kesatuan
        lokasi_kesatuan.lokasi_kesatuan_id as lokasi_kesatuan_id_kesatuan,
        lokasi_kesatuan.nama_lokasi_kesatuan,

        -- provinsi
        provinsi.provinsi_id as provinsi_id_wbp_profile,
        provinsi.nama_provinsi,

        -- kota
        kota.kota_id as kota_id_wbp_profile,
        kota.nama_kota,

        -- agama
        agama.agama_id as agama_id_wbp_profile,
        agama.nama_agama,

        -- status kawin
        status_kawin.status_kawin_id as status_kawin_id_wbp_profile,
        status_kawin.nama_status_kawin,

        -- pendidikan
        pendidikan.pendidikan_id as pendidikan_id_wbp_profile,
        pendidikan.nama_pendidikan,
        pendidikan.tahun_lulus,

        -- bidang keahlian
        bidang_keahlian.bidang_keahlian_id as bidang_keahlian_id_wbp_profile,
        bidang_keahlian.nama_bidang_keahlian,

        -- status wbp kasus id
        status_wbp_kasus.status_wbp_kasus_id,
        status_wbp_kasus.nama_status_wbp_kasus,
        status_wbp_kasus.created_at as created_at_status_wbp_kasus,
        status_wbp_kasus.updated_at as updated_at_status_wbp_kasus,

        
        -- table kasus
        kasus.kasus_id ,
        kasus.nama_kasus,
        kasus.wbp_profile_id as wbp_profile_id_kasus,
        kasus.jenis_perkara_id,
        kasus.tanggal_registrasi_kasus,
        kasus.tanggal_penutupan_kasus,
        kasus.status_kasus_id,
        kasus.tanggal_mulai_penyidikan,
        kasus.tanggal_mulai_sidang,
        kasus.oditur_id,
        kasus.cretead_at as created_at_kasus,
        kasus.updated_at as updated_at_kasus,

        -- table jenis_perkara pada kasus
        jenis_perkara.jenis_perkara_id as jenis_perkara_id_kasus,
        jenis_perkara.kategori_perkara_id,
        jenis_perkara.nama_jenis_perkara,
        jenis_perkara.pasal,
        jenis_perkara.vonis_tahun_perkara,
        jenis_perkara.vonis_bulan_perkara,
        jenis_perkara.vonis_hari_perkara,

        -- table kategori_perkara pada jenis_perkara
        kategori_perkara.kategori_perkara_id as kategori_perkara_id_jenis_perkara,
        kategori_perkara.nama_kategori_perkara,

        -- gelang
        gelang.DMAC,
        gelang.nama_gelang,
        gelang.tanggal_pasang,
        gelang.tanggal_aktivasi,

        -- hunian
        hunian_wbp_otmil.nama_hunian_wbp_otmil, 
        hunian_wbp_lemasmil.nama_hunian_wbp_lemasmil, 
        hunian_wbp_otmil.lokasi_otmil_id,
        hunian_wbp_lemasmil.lokasi_lemasmil_id,

        -- matra
        matra.matra_id as matra_id_wbp_profile,
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
    LEFT JOIN jenis_perkara ON wbp_perkara1.jenis_perkara_id = jenis_perkara.jenis_perkara_id
    LEFT JOIN kategori_perkara ON jenis_perkara.kategori_perkara_id = kategori_perkara.kategori_perkara_id
    LEFT JOIN gelang ON wbp_profile.gelang_id = gelang.gelang_id
    LEFT JOIN akses_ruangan ON wbp_profile.wbp_profile_id = akses_ruangan.wbp_profile_id
    LEFT JOIN matra ON wbp_profile.matra_id = matra.matra_id
    WHERE wbp_profile.is_deleted = 0";



    if (!empty($filterNama)) {
        $query .= " AND wbp_profile.nama LIKE '%$filterNama%'";
    }
    if (!empty($filterIsolated)) {
        $query .= " AND wbp_profile.is_isolated LIKE '%$filterIsolated%'";
    }
    if (!empty($filterAlamat)) {
        $query .= " AND wbp_profile.alamat LIKE '%$filterAlamat%'";
    }
    if (!empty($filterNamaPangkat)) {
        $query .= " AND pangkat.nama_pangkat LIKE '%$filterNamaPangkat%'";
    }
    if (!empty($filterNamaKesatuan)) {
        $query .= " AND kesatuan.nama_kesatuan LIKE '%$filterNamaKesatuan%'";
    }
    if (!empty($filterNamaLokasiKesatuan)) {
        $query .= " AND lokasi_kesatuan.nama_lokasi_kesatuan LIKE '%$filterNamaLokasiKesatuan%'";
    }
    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }
    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }

    if (!empty($filterVonisBulan)) {
        $query .= " AND wbp_perkara1.vonis_bulan LIKE '%$filterVonisBulan%'";
    }

    if (!empty($filterVonisTahun)) {
        $query .= " AND wbp_perkara1.vonis_tahun LIKE '%$filterVonisTahun%'";
    }

    if (!empty($filterNamaKategoriPerkara)) {
        $query .= " AND kategori_perkara1.nama_kategori_perkara LIKE '%$filterNamaKategoriPerkara%'";
    }

    if (!empty($filterHunianWbpOtmil)) {
        $query .= " AND hunian_wbp_otmil.nama_hunian_wbp_otmil LIKE '%$filterHunianWbpOtmil%'";
    }

    if (!empty($filterHunianWbpLemasmil)) {
        $query .= " AND hunian_wbp_lemasmil.nama_hunian_wbp_lemasmil LIKE '%$filterHunianWbpLemasmil%'";
    }
    if (!empty($filterNamaMatra)) {
        $query .= " AND matra.nama_matra = $filterNamaMatra";
    }
    if (!empty($filterNRP)) {
        $query .= " AND petugas.nrp LIKE '%$filterNRP%'";
    }
    if (!empty($filterTanggalDitahanOtmil)) {
        $query .= " AND wbp_profile.tanggal_ditahan_otmil LIKE '%$filterTanggalDitahanOtmil%'";
    }
    if (!empty($filterTanggalDitahanLemasmil)) {
        $query .= " AND wbp_profile.tanggal_ditahan_lemasmil LIKE '%$filterTanggalDitahanLemasmil%'";
    }
    if (!empty($filterTanggalPenetapanTersangka)) {
        $query .= " AND wbp_profile.tanggal_penetapan_tersangka LIKE '%$filterTanggalPenetapanTersangka%'";
    }
    if (!empty($filterTanggalPenetapanTerdakwa)) {
        $query .= " AND wbp_profile.tanggal_penetapan_terdakwa LIKE '%$filterTanggalPenetapanTerdakwa%'";
    }
    if (!empty($filterTanggalPenetapanTerpidana)) {
        $query .= " AND wbp_profile.tanggal_penetapan_terpidana LIKE '%$filterTanggalPenetapanTerpidana%'";
    }

    $query .= " GROUP BY wbp_profile.wbp_profile_id";
    $query .= " ORDER BY $sortField $sortOrder";

    // Add pagination
    $countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $totalPages = ceil($totalRecords / $pageSize);
    $start = ($page - 1) * $pageSize;
    $query .= " LIMIT $start, $pageSize";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // $queryAkasesTidakBoleh = "SELECT 
    // wbp_profile.wbp_profile_id,
    // wbp_profile.nama,
    // LEFT JOIN akses_ruangan ON wbp_profile.wbp_profile_id = akses_ruangan.wbp_profile_id
    // WHERE akses_ruangan.wbp_profile_id = :wbp_profile_id

    // $stmtAkasesTidakBoleh = $conn->prepare($queryAkasesTidakBoleh);
    // $stmtAkasesTidakBoleh->bindParam(':wbp_profile_id', $wbp_profile_id, PDO::PARAM_STR);
    // $stmtAkasesTidakBoleh->execute();
    // $resAkasesTidakBoleh = $stmtAkasesTidakBoleh->fetchAll(PDO::FETCH_ASSOC);

    // echo json_encode($resAkasesTidakBoleh);

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
            "nama_pangkat" => $row['nama_pangkat'],
            "kesatuan_id" => $row['kesatuan_id'],
            "nama_kesatuan" => $row['nama_kesatuan'],
            "lokasi_kesatuan_id" => $row['lokasi_kesatuan_id'],
            "nama_lokasi_kesatuan" => $row['nama_lokasi_kesatuan'],
            "provinsi_id" => $row['provinsi_id'],
            "nama_provinsi" => $row['nama_provinsi'],
            "kota_id" => $row['kota_id'],
            "nama_kota" => $row['nama_kota'],
            "tempat_lahir" => $row['tempat_lahir'],
            "tanggal_lahir" => $row['tanggal_lahir'],
            "jenis_kelamin" => $row['jenis_kelamin'],
            "agama_id" => $row['agama_id'],
            "nama_agama" => $row['nama_agama'],
            "status_kawin_id" => $row['status_kawin_id'],
            "nama_status_kawin" => $row['nama_status_kawin'],
            "pendidikan_id" => $row['pendidikan_id'],
            "nama_pendidikan" => $row['nama_pendidikan'],
            "tahun_lulus" => $row['tahun_lulus'],
            "bidang_keahlian_id" => $row['bidang_keahlian_id'],
            "nama_bidang_keahlian" => $row['nama_bidang_keahlian'],
            "foto_wajah" => $row['foto_wajah'],
            "nomor_tahanan" => $row['nomor_tahanan'],
            "residivis" => $row['residivis'],
            "status_wbp_kasus_id" => $row['status_wbp_kasus_id'],
            "nama_status_wbp_kasus" => $row['nama_status_wbp_kasus'],
            "created_at_wbp_profile" => $row['created_at_wbp_profile'],
            "updated_at_wbp_profile" => $row['updated_at_wbp_profile'],
            "is_deleted_wbp_profile" => $row['is_deleted_wbp_profile'],
            "foto_wajah_fr" => $row['foto_wajah_fr'],
            "is_isolated" => $row['is_isolated'],
            "is_sick" => $row['is_sick'],
            "wbp_sickness" => $row['wbp_sickness'],
            "gelang_id" => $row['gelang_id'],
            "hunian_wbp_lemasmil_id" => $row['hunian_wbp_lemasmil_id'],
            "hunian_wbp_otmil_id" => $row['hunian_wbp_otmil_id'],
            "nama_hunian_wbp_lemasmil" => $row['nama_hunian_wbp_lemasmil'],
            "nama_hunian_wbp_otmil" => $row['nama_hunian_wbp_otmil'],
            "lokasi_otmil_id" => $row['lokasi_otmil_id'],
            "lokasi_lemasmil_id" => $row['lokasi_lemasmil_id'],
            "nama_kontak_keluarga" => $row['nama_kontak_keluarga'],
            "hubungan_kontak_keluarga" => $row['hubungan_kontak_keluarga'],
            "nomor_kontak_keluarga" => $row['nomor_kontak_keluarga'],
            "matra_id" => $row['matra_id'],
            "nama_matra" => $row['nama_matra'],
            "nrp" => $row['nrp'],
            "tanggal_ditahan_otmil" => $row['tanggal_ditahan_otmil'],
            "tanggal_ditahan_lemasmil" => $row['tanggal_ditahan_lemasmil'],
            "tanggal_penetapan_tersangka" => $row['tanggal_penetapan_tersangka'],
            "tanggal_penetapan_terdakwa" => $row['tanggal_penetapan_terdakwa'],
            "tanggal_penetapan_terpidana" => $row['tanggal_penetapan_terpidana'],
            "kasus_id" => $row['kasus_id'],
            "nama_kasus" => $row['nama_kasus'],
            "jenis_perkara_id" => $row['jenis_perkara_id'],
            "nama_jenis_perkara" => $row['nama_jenis_perkara'],
            "pasal" => $row['pasal'],
            "vonis_tahun_perkara" => $row['vonis_tahun_perkara'],
            "vonis_bulan_perkara" => $row['vonis_bulan_perkara'],
            "vonis_hari_perkara" => $row['vonis_hari_perkara'],
            "tanggal_registrasi_kasus" => $row['tanggal_registrasi_kasus'],
            "tanggal_penutupan_kasus" => $row['tanggal_penutupan_kasus'],
            "status_kasus_id" => $row['status_kasus_id'],
            "tanggal_mulai_penyidikan" => $row['tanggal_mulai_penyidikan'],
            "tanggal_mulai_sidang" => $row['tanggal_mulai_sidang'],
            "oditur_id" => $row['oditur_id'],
            "created_at_kasus" => $row['created_at_kasus'],
            "updated_at_kasus" => $row['updated_at_kasus'],
            "kategori_perkara_id_jenis_perkara" => $row['kategori_perkara_id_jenis_perkara'],
            "nama_kategori_perkara" => $row['nama_kategori_perkara'],
            "DMAC" => $row['DMAC'],
            "nama_gelang" => $row['nama_gelang'],
            "tanggal_pasang" => $row['tanggal_pasang'],
            "tanggal_aktivasi" => $row['tanggal_aktivasi'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "lokasi_tahanan" => $row['lokasi_tahanan'],
            "akses_ruangan_lemasmil" => $akses_ruangan_lemasmil,
            "akses_ruangan_otmil" => $akses_ruangan_otmil,
            // Check if these fields exist before including them
            "akses_ruangan_lemasmil" => isset($akses_ruangan_lemasmil) ? $akses_ruangan_lemasmil : null,
            "akses_ruangan_otmil" => isset($akses_ruangan_otmil) ? $akses_ruangan_otmil : null,


            // "nama_gateway" => isset($row['nama_gateway']) ? $row['nama_gateway'] : null
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
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];

    echo json_encode($result);
}

$stmt = null;
$conn = null;
