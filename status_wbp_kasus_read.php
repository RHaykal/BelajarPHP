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
        if (isset($filter->nama_status_wbp_kasus)) {
            $filterConditions[] = "nama_status_wbp_kasus LIKE :nama_status_wbp_kasus";
        }
        // Add conditions for other fields (alamat, kota, provinsi) in a similar manner
    }

    // Build the WHERE clause with filter conditions
    $whereClause = '';
    if (!empty($filterConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $filterConditions);
    }

    // Modify your SQL query to include pagination and filtering
    $query = "SELECT * FROM status_wbp_kasus";

    // Append the filter conditions to the query
    if (!empty($whereClause)) {
        $query .= $whereClause;
    }

    $query .= " LIMIT :offset, :pageSize";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

    // Bind filter parameters if they exist
    if ($filter) {
        if (isset($filter->nama_status_wbp_kasus)) {
            $nama_status_wbp_kasus = '%' . $filter->nama_status_wbp_kasus . '%';
            $stmt->bindParam(':nama_status_wbp_kasus', $nama_status_wbp_kasus, PDO::PARAM_STR);
        }
        // Bind parameters for other fields (alamat, kota, provinsi) in a similar manner
    }

    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate the total number of filtered records
    $totalCountQuery = "SELECT COUNT(*) FROM status_wbp_kasus";

    // Append filter conditions to the total count query
    if (!empty($whereClause)) {
        $totalCountQuery .= $whereClause;
    }

    $stmt = $conn->prepare($totalCountQuery);

    // Bind filter parameters if they exist
    if ($filter) {
        if (isset($filter->nama_status_wbp_kasus)) {
            $stmt->bindParam(':nama_status_wbp_kasus', $nama_status_wbp_kasus, PDO::PARAM_STR);
        }
        // Bind parameters for other fields (alamat, kota, provinsi) in a similar manner
    }

    $stmt->execute();
    $totalCount = $stmt->fetchColumn();

    // Create a response object with pagination info
    $result = array(
        "status" => "OK",
        "message" => "Berhasil mengambil data",
        "records" => $res,
        "pagination" => array(
            "currentPage" => $page,
            "pageSize" => $pageSize,
            "totalRecords" => $totalCount,
            "totalPages" => ceil($totalCount / $pageSize)
        )
    );

    echo json_encode($result);
} catch (PDOException $e) {
    // Handle database errors
    $result = array(
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => []
    );
    echo json_encode($result);
} finally {
    // Close the database connection
    if ($conn) {
        $conn = null;
    }
}
?>
