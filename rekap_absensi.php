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
    $requestData = json_decode(file_get_contents("php://input"), true);
    $pageSize = isset($requestData['pageSize']) ? (int) $requestData['pageSize'] : 10;
    $page = isset($requestData['page']) ? (int) $requestData['page'] : 1;
    $filterTanggal = isset($requestData['filter']['tanggal']) ? $requestData['filter']['tanggal'] : "";
    $filterBulan = isset($requestData['filter']['bulan']) ? $requestData['filter']['bulan'] : "";
    $filterTahun = isset($requestData['filter']['tahun']) ? $requestData['filter']['tahun'] : "";

    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'tahun';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    $allowedSortFields = ['tanggal', 'bulan', 'tahun'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'tahun';
    }

    $query1 = "SELECT schedule.tanggal AS tanggal,
    schedule.bulan AS bulan,
    schedule.tahun AS tahun,
    COUNT(petugas_shift.petugas_shift_id) AS total_petugas,
    SUM(CASE WHEN petugas_shift.status_kehadiran = 1 THEN 1 ELSE 0 END) AS hadir,
    SUM(CASE WHEN petugas_shift.status_izin LIKE 'Izin' THEN 1 ELSE 0 END) AS izin,
    SUM(CASE WHEN petugas_shift.status_izin LIKE 'Sakit' THEN 1 ELSE 0 END) AS sakit,
    SUM(CASE WHEN petugas_shift.status_izin LIKE 'Absen' THEN 1 ELSE 0 END) AS Absen,
    SUM(CASE WHEN petugas_shift.status_izin LIKE 'Cuti' THEN 1 ELSE 0 END) AS cuti,
    GROUP_CONCAT(CASE WHEN petugas_shift.status_kehadiran = 1 THEN petugas.petugas_id ELSE NULL END) AS list_petugas_hadir,
    GROUP_CONCAT(CASE WHEN petugas_shift.status_kehadiran = 0 AND petugas_shift.status_izin LIKE 'Izin' THEN petugas.petugas_id ELSE NULL END) AS list_petugas_izin,
    GROUP_CONCAT(CASE WHEN petugas_shift.status_kehadiran = 0 AND petugas_shift.status_izin LIKE 'Sakit' THEN petugas.petugas_id ELSE NULL END) AS list_petugas_sakit,
    GROUP_CONCAT(CASE WHEN petugas_shift.status_kehadiran = 0 AND petugas_shift.status_izin = NULL THEN petugas.petugas_id ELSE NULL END) AS list_petugas_alpha,
    GROUP_CONCAT(CASE WHEN petugas_shift.status_kehadiran = 0 AND petugas_shift.status_izin LIKE 'Cuti' THEN petugas.petugas_id ELSE NULL END) AS list_petugas_cuti
    FROM
    petugas_shift
    LEFT JOIN schedule ON schedule.schedule_id = petugas_shift.schedule_id
    LEFT JOIN petugas ON petugas.petugas_id = petugas_shift.petugas_id
    WHERE petugas_shift.is_deleted = 0";

    if (!empty($filterTanggal)) {
        $query1 .= " AND schedule.tanggal LIKE '%$filterTanggal%'";
    }
    if (!empty($filterBulan)) {
        $query1 .= " AND schedule.bulan LIKE '%$filterBulan%'";
    }
    if (!empty($filterTahun)) {
        $query1 .= " AND schedule.tahun LIKE '%$filterTahun%'";
    }

    $query1 .= " GROUP BY schedule.tanggal";
    $query1 .= " ORDER BY $sortField $sortOrder";

    $countQuery = "SELECT COUNT(*) total FROM ($query1) subquery";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Apply pagination
    $totalPages = ceil($totalRecords / $pageSize);
    $start = ($page - 1) * $pageSize;
    $query1 .= " LIMIT $start, $pageSize";

    $stmt = $conn->prepare($query1);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $record = [];
    
    foreach ($res as $row) {
        $record[] = [
            "tanggal" => $row['tanggal'],
            "bulan" => $row['bulan'],
            "tahun" => $row['tahun'],
            "Total Petugas" => $row['total_petugas'], 
            "hadir" => $row['hadir'],
            "izin" => $row['izin'],
            "sakit" => $row['sakit'],
            "Absen" => $row['Absen'],
            "cuti" => $row['cuti']
        ];
    };

    $list_hadir = [];
    $list_izin= [];
    $list_sakit = [];
    $list_cuti = [];
    $list_alpha = [];

    if(isset($row['list_petugas_hadir'])) {
        $list_petugas_hadir = explode(',', $row['list_petugas_hadir']);

        if(count($list_petugas_hadir) == 1 && $list_petugas_hadir[0] == null)
            unset($list_petugas_hadir[0]);

        foreach ($list_petugas_hadir as $index => $petugas_id) {
            $getNama1 = "SELECT nama FROM petugas WHERE petugas_id = '$petugas_id'";
            $stmt = $conn->prepare($getNama1);
            $stmt->execute();
            $nama1 = $stmt->fetch(PDO::FETCH_ASSOC)['nama'];

            $list_hadir[] = [
                "petugas_id" => $petugas_id,
                "nama_petugas" => $nama1
            ];

        };
    }

    if(isset($row['list_petugas_izin'])) {
        $list_petugas_izin = explode(',', $row['list_petugas_izin']);

        if(count($list_petugas_izin) == 1 && $list_petugas_izin[0] == null)
            unset($list_petugas_izin[0]);
    
        foreach ($list_petugas_izin as $index => $petugas_id) {
            $getNama2 = "SELECT nama FROM petugas WHERE petugas_id = '$petugas_id'";
            $stmt = $conn->prepare($getNama2);
            $stmt->execute();
            $nama2 = $stmt->fetch(PDO::FETCH_ASSOC)['nama'];
    
            $list_izin[] = [
                "petugas_id" => $petugas_id,
                "nama_petugas" => $nama2
            ];
    
        };
    }

    if(isset($row['list_petugas_sakit'])) {
        $list_petugas_sakit = explode(',', $row['list_petugas_sakit']);

        if(count($list_petugas_sakit) == 1 && $list_petugas_sakit[0] == null)
            unset($list_petugas_sakit[0]);
    
        foreach ($list_petugas_sakit as $index => $petugas_id) {
            $getNama3 = "SELECT nama FROM petugas WHERE petugas_id = '$petugas_id'";
            $stmt = $conn->prepare($getNama3);
            $stmt->execute();
            $nama3 = $stmt->fetch(PDO::FETCH_ASSOC)['nama'];
    
            $list_sakit[] = [
                "petugas_id" => $petugas_id,
                "nama_petugas" => $nama3
            ];
    
        };
    }

    if(isset($row['list_petugas_cuti'])) {
        $list_petugas_cuti = explode(',', $row['list_petugas_cuti']);
        
        if(isset($list_petugas_cuti) && $list_petugas_cuti[0] == null) {
            unset($list_petugas_cuti[0]);
        };
        foreach ($list_petugas_cuti as $index => $petugas_id) {
            $getNama4 = "SELECT petugas.nama, GROUP_CONCAT(DISTINCT schedule.tanggal) AS tanggal_cuti
            FROM petugas 
            LEFT JOIN petugas_shift ON petugas_shift.petugas_id = petugas.petugas_id
            LEFT JOIN schedule ON schedule.schedule_id = petugas_shift.schedule_id 
            WHERE petugas.petugas_id = '$petugas_id'";
            $stmt = $conn->prepare($getNama4);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            // $tanggal_cuti = $stmt->fetch(PDO::FETCH_ASSOC)['tanggal_cuti'];
    
            $list_cuti[] = [
                "petugas_id" => $petugas_id,
                "nama_petugas" => $data['nama']
            ];
    
        };
    }

    if(isset($row['list_petugas_alpha'])) {
        $list_petugas_alpha = explode(',', $row['list_petugas_alpha']);

        if(count($list_petugas_alpha) == 1 && $list_petugas_alpha[0] == null)
            unset($list_petugas_alpha[0]);
    
        foreach ($list_petugas_alpha as $index => $petugas_id) {
            $getNama5 = "SELECT nama FROM petugas WHERE petugas_id = '$petugas_id'";
            $stmt = $conn->prepare($getNama5);
            $stmt->execute();
            $nama5 = $stmt->fetch(PDO::FETCH_ASSOC)['nama'];
    
            $list_alpha[] = [
                "petugas_id" => $petugas_id,
                "nama_petugas" => $nama5
            ];
    
        };
    }

    foreach($record as &$indeks) {
        $indeks['petugas_hadir'] = $list_hadir;
        $indeks['petugas_izin'] = $list_izin;
        $indeks['petugas_sakit'] = $list_sakit;
        $indeks['petugas_cuti'] = $list_cuti;
        $indeks['petugas_alpha'] = $list_alpha;
    };

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


