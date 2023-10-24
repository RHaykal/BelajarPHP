<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    // header("Access-Control-Allow-Headers: Token");
    // header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
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
    $filterTanggal = isset($requestData['filter']['tanggal']) ? $requestData['filter']['tanggal'] : "";
    $filterBulan = isset($requestData['filter']['bulan']) ? $requestData['filter']['bulan'] : "";
    $filterTahun = isset($requestData['filter']['tahun']) ? $requestData['filter']['tahun'] : "";
    $filterNamaShift = isset($requestData['filter']['nama_shift']) ? $requestData['filter']['nama_shift'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'tanggal';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'ASC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['tanggal', 'bulan', 'tahun', 'nama_shift'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'tanggal'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions
    $query = "SELECT schedule.*,
    shift.nama_shift
    FROM schedule
    JOIN shift ON shift.shift_id = schedule.shift_id
    WHERE schedule.is_deleted = 0";

    //ORDER BY nama_ruangan_otmil ASC

    // checking if the giving parameter below is empty or not. if not empty, it will be inserted in the query as a filter

    if (!empty($filterTanggal)) {
        $dateFilter = explode('-', $filterTanggal);
    
        if (count($dateFilter) == 2) {
            // Filter tanggal adalah rentang
            $startDate = (int)$dateFilter[0];
            $endDate = (int)$dateFilter[1];
    
            // Tukar tanggal awal dan akhir jika tanggal awal lebih besar dari tanggal akhir
            if ($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
    
            $query .= " AND schedule.tanggal >= $startDate AND schedule.tanggal <= $endDate";
        } elseif (count($dateFilter) == 1) {
            // Filter tanggal adalah nilai tunggal
            $specificDate = (int)$dateFilter[0];
            $query .= " AND schedule.tanggal = $specificDate";
        }
    }

    
    // if (!empty($filterTanggal)) {
    //     $dateFilter = explode('-', $filterTanggal);
        
    //     if (count($dateFilter) == 2) {
    //         // Filter tanggal adalah rentang
    //         $startDate = (int)$dateFilter[0];
    //         $endDate = (int)$dateFilter[1];
        
    //         $query .= " AND (schedule.tanggal >= $startDate AND schedule.tanggal <= 31) OR (schedule.tanggal >= 1 AND schedule.tanggal <= $endDate)";
    //     } elseif (count($dateFilter) == 1) {
    //         // Filter tanggal adalah nilai tunggal
    //         $specificDate = (int)$dateFilter[0];
    //         $query .= " AND schedule.tanggal = $specificDate";
    //     }
    // }

    if (!empty($filterBulan)) {
        $query .= " AND schedule.bulan LIKE '%$filterBulan%'";
    }
    if (!empty($filterTahun)) {
        $query .= " AND schedule.tahun LIKE '%$filterTahun%'";
    }
    if (!empty($filterNamaShift)) {
        $query .= " AND shift.nama_shift LIKE '%$filterNamaShift%'";
    }

    $query .= " GROUP BY schedule_id";
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
            "schedule_id" => $row['schedule_id'],
            "tanggal" => $row['tanggal'],
            "bulan" => $row['bulan'],
            "tahun" => $row['tahun'],
            "shift_id" => $row['shift_id'],
            "nama_shift" => $row['nama_shift'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "is_deleted" => $row['is_deleted'],
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
