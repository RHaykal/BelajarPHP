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

    tokenAuth($conn, 'operator');

    // Parse the incoming JSON filter parameters
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;
    $filterNamaShift = isset($requestData['filter']['nama_shift']) ? $requestData['filter']['nama_shift'] : "";
    $filterWaktuMulai = isset($requestData['filter']['waktu_mulai']) ? $requestData['filter']['waktu_mulai'] : "";
    $filterWaktuSelesai = isset($requestData['filter']['waktu_selesai']) ? $requestData['filter']['waktu_selesai'] : "";
    $filterNama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterNamaPangkat = isset($requestData['filter']['nama_pangkat']) ? $requestData['filter']['nama_pangkat'] : "";
    $filterNamaKesatuan = isset($requestData['filter']['nama_kesatuan']) ? $requestData['filter']['nama_kesatuan'] : "";
    $filterNamaLokasiKesatuan = isset($requestData['filter']['nama_lokasi_kesatuan']) ? $requestData['filter']['nama_lokasi_kesatuan'] : "";
    $filterJabatan = isset($requestData['filter']['jabatan']) ? $requestData['filter']['jabatan'] : "";
    $filterDivisi = isset($requestData['filter']['divisi']) ? $requestData['filter']['divisi'] : "";
    $filterNomorPetugas = isset($requestData['filter']['nomor_petugas']) ? $requestData['filter']['nomor_petugas'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterTanggal = isset($requestData['filter']['tanggal']) ? $requestData['filter']['tanggal'] : "";
    $filterBulan = isset($requestData['filter']['bulan']) ? $requestData['filter']['bulan'] : "";
    $filterTahun = isset($requestData['filter']['tahun']) ? $requestData['filter']['tahun'] : "";
    $filterStatusKehadiran = isset($requestData['filter']['status_kehadiran']) ? $requestData['filter']['status_kehadiran'] : "";
    $filterStatusIzin = isset($requestData['filter']['status_izin']) ? $requestData['filter']['status_izin'] : "";
    $filterNamaPenugasan = isset($requestData['filter']['nama_penugasan']) ? $requestData['filter']['nama_penugasan'] : "";
    $filterNamaRuanganOtmil = isset($requestData['filter']['nama_ruangan_otmil']) ? $requestData['filter']['nama_ruangan_otmil'] : "";
    $filterJenisRuanganOtmil = isset($requestData['filter']['jenis_ruangan_otmil']) ? $requestData['filter']['jenis_ruangan_otmil'] : "";
    $filterNamaRuanganLemasmil = isset($requestData['filter']['nama_ruangan_lemasmil']) ? $requestData['filter']['nama_ruangan_lemasmil'] : "";
    $filterJenisRuanganLemasmil = isset($requestData['filter']['jenis_ruangan_lemasmil']) ? $requestData['filter']['jenis_ruangan_lemasmil'] : "";
    $filterPetugasGrupId = isset($requestData['filter']['grup_petugas_id']) ? $requestData['filter']['grup_petugas_id'] : "";
    $filterNamaGrupPetugas = isset($requestData['filter']['nama_grup_petugas']) ? $requestData['filter']['nama_grup_petugas'] : "";
    $filterScheduleId = isset($requestData['filter']['schedule_id']) ? $requestData['filter']['schedule_id'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'tanggal';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = [
        'nama_shift', 'waktu_mulai', 'waktu_selesai', 'nama', 'nama_pangkat', 'nama_kesatuan', 'nama_lokasi_kesatuan', 'jabatan', 'divisi', 
        'nomor_petugas', 'nama_lokasi_otmil', 'nama_lokasi_lemasmil', 'tanggal', 'bulan', 'tahun', 'status_kehadiran', 'status_izin', 'nama_penugasa', 
        'nama_ruangan_otmil', 'jenis_ruangan_otmil', 'nama_ruangan_lemasmil', 'jenis_ruangan_otmil', 'status_pengganti', 'status_zona_otmil', 'status_zona_lemasmil',
        'nama_grup_petugas'
    ];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'tanggal'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT petugas_shift.*,
    shift.nama_shift,
    shift.waktu_mulai,
    shift.waktu_selesai,
    petugas.nama,
    petugas.pangkat_id,
    petugas.kesatuan_id,
    petugas.jabatan,
    petugas.divisi,
    petugas.nomor_petugas,
    petugas.grup_petugas_id,
    grup_petugas.nama_grup_petugas,
    schedule.tanggal,
    schedule.bulan,
    schedule.tahun,
    penugasan.nama_penugasan,
    ruangan_otmil.nama_ruangan_otmil,
    ruangan_otmil.jenis_ruangan_otmil,
    ruangan_lemasmil.nama_ruangan_lemasmil,
    ruangan_lemasmil.jenis_ruangan_lemasmil,
    lokasi_otmil.nama_lokasi_otmil,
    lokasi_lemasmil.nama_lokasi_lemasmil,
    pangkat.nama_pangkat,
    kesatuan.nama_kesatuan,
    kesatuan.lokasi_kesatuan_id,
    lokasi_kesatuan.nama_lokasi_kesatuan,
    ruangan_otmil.zona_id AS zona_otmil_id,
    ruangan_lemasmil.zona_id AS zona_lemasmil_id, 
    zona_otmil.nama_zona AS status_zona_otmil, 
    zona_lemasmil.nama_zona AS status_zona_lemasmil
    FROM petugas_shift
    LEFT JOIN shift ON shift.shift_id = petugas_shift.shift_id
    LEFT JOIN petugas ON petugas.petugas_id = petugas_shift.petugas_id
    LEFT JOIN schedule ON schedule.schedule_id = petugas_shift.schedule_id
    LEFT JOIN penugasan ON penugasan.penugasan_id = petugas_shift.penugasan_id
    LEFT JOIN ruangan_otmil ON ruangan_otmil.ruangan_otmil_id = petugas_shift.ruangan_otmil_id
    LEFT JOIN ruangan_lemasmil ON ruangan_lemasmil.ruangan_lemasmil_id = petugas_shift.ruangan_lemasmil_id
    LEFT JOIN lokasi_otmil ON petugas.lokasi_otmil_id = lokasi_otmil.lokasi_otmil_id
    LEFT JOIN lokasi_lemasmil ON petugas.lokasi_lemasmil_id = lokasi_lemasmil.lokasi_lemasmil_id
    LEFT JOIN pangkat ON petugas.pangkat_id = pangkat.pangkat_id
    LEFT JOIN kesatuan ON petugas.kesatuan_id = kesatuan.kesatuan_id
    LEFT JOIN lokasi_kesatuan ON kesatuan.lokasi_kesatuan_id = lokasi_kesatuan.lokasi_kesatuan_id
    LEFT JOIN zona AS zona_otmil ON zona_otmil.zona_id = ruangan_otmil.zona_id
    LEFT JOIN zona AS zona_lemasmil ON zona_lemasmil.zona_id = ruangan_lemasmil.zona_id
    LEFT JOIN grup_petugas ON petugas.grup_petugas_id = grup_petugas.grup_petugas_id
    WHERE petugas_shift.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter
    if (!empty($filterNamaShift)) {
        $query .= " AND shift.nama_shift LIKE '%$filterNamaShift%'";
    }
    if (!empty($filterWaktuMulai)) {
        $query .= " AND shift.waktu_mulai LIKE '%$filterWaktuMulai%'";
    }
    if (!empty($filterWaktuSelesai)) {
        $query .= " AND shift.waktu_selesai LIKE '%$filterWaktuSelesai%'";
    }
    if (!empty($filterNama)) {
        $query .= " AND petugas.nama LIKE '%$filterNama%'";
    }
    if (!empty($filterNamaPangkat)) {
        $query .= " AND petugas.nama_pangkat LIKE '%$filterNamaPangkat%'";
    }
    if (!empty($filterNamaLokasiKesatuan)) {
        $query .= " AND petugas.nama_lokasi_kesatuan LIKE '%$filterNamaLokasiKesatuan%'";
    }
    if (!empty($filterJabatan)) {
        $query .= " AND petugas.jabatan LIKE '%$filterJabatan%'";
    }
    if (!empty($filterDivisi)) {
        $query .= " AND petugas.divisi LIKE '%$filterDivisi%'";
    }
    if (!empty($filterNomorPetugas)) {
        $query .= " AND petugas.nomor_petugas LIKE '%$filterNomorPetugas%'";
    }
    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE '%$filterNamaLokasiOtmil%'";
    }
    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE '%$filterNamaLokasiLemasmil%'";
    }
    if (!empty($filterTanggal)) {
        $query .= " AND schedule.tanggal LIKE '%$filterTanggal%'";
    }
    if (!empty($filterBulan)) {
        $query .= " AND schedule.bulan LIKE '%$filterBulan%'";
    }
    if (!empty($filterTahun)) {
        $query .= " AND schedule.tahun LIKE '%$filterTahun%'";
    }
    if (!empty($filterStatusKehadiran)) {
        $query .= " AND petugas_shift.status_kehadiran LIKE '%$filterStatusKehadiran%'";
    }
    if (!empty($filterStatusIzin)) {
        $query .= " AND petugas_shift.status_izin LIKE '%$filterStatusIzin%'";
    }
    if (!empty($filterNamaPenugasan)) {
        $query .= " AND penugasan.nama_penugasan LIKE '%$filterNamaPenugasan%'";
    }
    if (!empty($filterNamaRuanganOtmil)) {
        $query .= " AND ruangan_otmil.nama_ruangan_otmil LIKE '%$filterNamaRuanganOtmil%'";
    }
    if (!empty($filterJenisRuanganOtmil)) {
        $query .= " AND ruangan_otmil.jenis_ruangan_otmil LIKE '%$filterJenisRuanganOtmil%'";
    }
    if (!empty($filterNamaRuanganLemasmil)) {
        $query .= " AND ruangan_lemasmil.nama_ruangan_lemasmil LIKE '%$filterNamaRuanganLemasmil%'";
    }
    if (!empty($filterJenisRuanganLemasmil)) {
        $query .= " AND ruangan_lemasmil.jenis_ruangan_lemasmil LIKE '%$filterJenisRuanganLemasmil%'";
    }

    if (!empty($filterPetugasGrupId)) {
        $query .= " AND petugas.grup_petugas_id = $filterPetugasGrupId";
    }

    if (!empty($filterNamaGrupPetugas)) {
        $query .= " AND grup_petugas.nama_grup_petugas LIKE '%$filterNamaGrupPetugas%'";
    }

    if (!empty($filterScheduleId)) {
        $query .= " AND petugas_shift.schedule_id  LIKE  '%$filterScheduleId%'";
    }

    $query .= " GROUP BY petugas_shift_id";
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
        $recordData = [
            "petugas_shift_id" => $row['petugas_shift_id'],
            "shift_id" => $row['shift_id'],
            "nama_shift" => $row['nama_shift'],
            "waktu_mulai" => $row['waktu_mulai'],
            "waktu_selesai" => $row['waktu_selesai'],
            "petugas_id" => $row['petugas_id'],
            "nama" => $row['nama'],
            "nama_pangkat" => $row['nama_pangkat'],
            "nama_kesatuan" => $row['nama_kesatuan'],
            "nama_lokasi_kesatuan" => $row['nama_lokasi_kesatuan'],
            "jabatan" => $row['jabatan'],
            "divisi" => $row['divisi'],
            "nomor_petugas" => $row['nomor_petugas'],
            "nama_lokasi_otmil" => $row['nama_lokasi_otmil'],
            "nama_lokasi_lemasmil" => $row['nama_lokasi_lemasmil'],
            "schedule_id" => $row['schedule_id'],
            "tanggal" => $row['tanggal'],
            "bulan" => $row['bulan'],
            "tahun" => $row['tahun'],
            "status_kehadiran" => $row['status_kehadiran'],
            "jam_kehadiran" => $row['jam_kehadiran'],
            "status_izin" => $row['status_izin'],
            "penugasan_id" => $row['penugasan_id'],
            "nama_penugasan" => $row['nama_penugasan'],
            "ruangan_otmil_id" => $row['ruangan_otmil_id'],
            "nama_ruangan_otmil" => $row['nama_ruangan_otmil'],
            "jenis_ruangan_otmil" => $row['jenis_ruangan_otmil'],
            "ruangan_lemasmil_id" => $row['ruangan_lemasmil_id'],
            "nama_ruangan_lemasmil" => $row['nama_ruangan_lemasmil'],
            "jenis_ruangan_lemasmil" => $row['jenis_ruangan_lemasmil'],
            "status_pengganti" => $row['status_pengganti'],
            "status_zona_otmil" => $row['status_zona_otmil'],
            "status_zona_lemasmil" => $row['status_zona_lemasmil'],
            "grup_petugas_id" => $row['grup_petugas_id'] ? $row['grup_petugas_id'] : null,
            "nama_grup_petugas" => $row['nama_grup_petugas'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
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
