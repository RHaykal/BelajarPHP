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
    // Connect to your database
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'operator');

    // Read JSON data from the request body
    $json_data = file_get_contents("php://input");
    $request_data = json_decode($json_data);

    // Check if the JSON data contains pagination parameters
    $page = isset($request_data->page) ? (int) $request_data->page : 1; // Current page
    $pageSize = isset($request_data->pageSize) ? (int) $request_data->pageSize : 10; // Number of records per page

    // Calculate the offset based on the current page and page size
    $offset = ($page - 1) * $pageSize;

    // Extract filter parameters from the "filter" object
    $filter = isset($request_data->filter) ? $request_data->filter : null;

    // Prepare the filter conditions
    $filterConditions = array();
    if ($filter) {
        if (isset($filter->nama_lokasi_kesatuan)) {
            $filterConditions[] = "nama_lokasi_kesatuan LIKE :nama_lokasi_kesatuan";
        }
    }

    // Build the WHERE clause with filter conditions
    $whereClause = '';
    if (!empty($filterConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $filterConditions);
    }

    // Modify your SQL query to include pagination and filtering
    $query = "SELECT * FROM lokasi_kesatuan WHERE is_deleted = 0";

    // Append the filter conditions t   o the query
    if (!empty($filterConditions)) {
        $query .= " AND " . implode(" AND ", $filterConditions);
    }

    $query .= " ORDER BY nama_lokasi_kesatuan ASC LIMIT :offset, :pageSize";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

    // Bind filter parameters if they exist
    if ($filter) {
        if (isset($filter->nama_lokasi_kesatuan)) {
            $nama_lokasi_kesatuan = '%' . $filter->nama_lokasi_kesatuan . '%';
            $stmt->bindParam(':nama_lokasi_kesatuan', $nama_lokasi_kesatuan, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the result
    $record = $res;

    // Calculate the total number of filtered records
    $totalCountQuery = "SELECT COUNT(*) FROM lokasi_kesatuan WHERE is_deleted = 0";

    // Append filter conditions to the total count query
    if (!empty($filterConditions)) {
        $totalCountQuery .= " AND " . implode(" AND ", $filterConditions);
    }

    $stmt = $conn->prepare($totalCountQuery);

    // Bind filter parameters if they exist
    if ($filter) {
        if (isset($filter->nama_lokasi_kesatuan)) {
            $stmt->bindParam(':nama_lokasi_kesatuan', $nama_lokasi_kesatuan, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $totalCount = $stmt->fetchColumn();

    // Create a response object with pagination info
    $result = array(
        "status" => "OK",
        "message" => "",
        "records" => $record,
        "pagination" => array(
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "totalRecords" => $totalCount,
            "totalPages" => ceil($totalCount / $pageSize)
        )
    );

    echo json_encode($result);
} catch (Exception $e) {
    $result = '{"status":"error", "message":"' . $e->getMessage() . '", "records":[]}';
    echo $result;
} finally {
    // Close the database connection
    if ($conn) {
        $conn = null;
    }
    if ($stmt) {
        $stmt = null;
    }
}
?>