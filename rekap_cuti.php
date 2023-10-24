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
    $filterBulan = isset($requestData['filter']['bulan']) ? $requestData['filter']['bulan'] : "";
    $filterTahun = isset($requestData['filter']['tahun']) ? $requestData['filter']['tahun'] : "";

    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'tahun';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    $allowedSortFields = ['bulan', 'tahun'];

    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'tahun';
    }

    $query1 = "SELECT DISTINCT schedule.bulan AS bulan, schedule.tahun AS tahun,
    SUM(CASE WHEN petugas_shift.status_izin = 'cuti' THEN 1 ELSE 0 END) AS cuti,
    GROUP_CONCAT(DISTINCT CASE WHEN petugas_shift.status_kehadiran = 0 AND petugas_shift.status_izin LIKE 'Cuti' THEN petugas.petugas_id ELSE NULL END) AS list_petugas_cuti
    FROM petugas_shift
    LEFT JOIN schedule ON schedule.schedule_id = petugas_shift.schedule_id
    LEFT JOIN petugas ON petugas.petugas_id = petugas_shift.petugas_id
    WHERE petugas_shift.is_deleted = 0";

    if (!empty($filterBulan)) {
        $query1 .= " AND schedule.bulan LIKE '%$filterBulan%'";
    }
    if (!empty($filterTahun)) {
        $query1 .= " AND schedule.tahun LIKE '%$filterTahun%'";
    }

    $query1 .= " GROUP BY bulan";
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
            "bulan" => $row['bulan'],
            "tahun" => $row['tahun'],
            "cuti" => $row['cuti']
        ];
    };

    $list_cuti = [];

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
                "nama_petugas" => $data['nama'],
                "tanggal_cuti" => $data['tanggal_cuti']
            ];
    
        };
    }
    


    foreach($record as &$indeks) {
        $indeks['petugas_cuti'] = $list_cuti;
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


