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

    // Use prepared statements to prevent SQL injection
    $filterNama = isset($requestData['filter']['nama']) ? $requestData['filter']['nama'] : "";
    $filterTimeStamp = isset($requestData['filter']['timestamp']) ? $requestData['filter']['timestamp'] : "";
    $filterNamaKamera = isset($requestData['filter']['nama_kamera']) ? $requestData['filter']['nama_kamera'] : "";
    $filterNamaLokasiOtmil = isset($requestData['filter']['nama_lokasi_otmil']) ? $requestData['filter']['nama_lokasi_otmil'] : "";
    $filterNamaLokasiLemasmil = isset($requestData['filter']['nama_lokasi_lemasmil']) ? $requestData['filter']['nama_lokasi_lemasmil'] : "";
    $filterLokasiLemasmilID = isset($requestData['filter']['lokasi_lemasmil_id']) ? $requestData['filter']['lokasi_lemasmil_id'] : "";
    $filterLokasiOtmilID = isset($requestData['filter']['lokasi_otmil_id']) ? $requestData['filter']['lokasi_otmil_id'] : "";

    // Determine the sorting parameters (assuming they are passed as query parameters)
    $sortField = isset($requestData['sortBy']) ? $requestData['sortBy'] : 'timestamp';
    $sortOrder = isset($requestData['sortOrder']) ? $requestData['sortOrder'] : 'DESC';

    // Validate and sanitize sortField to prevent SQL injection
    $allowedSortFields = ['nama_kamera', 'nama', 'timestamp'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'timestamp'; // Default to waktu_mulai_kegiatan if the provided field is not allowed
    }

    // Construct the SQL query with filtering conditions using prepared statements
    $query = "SELECT kamera_log.*,
        COALESCE(wbp_profile.nama, 'tidak dikenal') AS nama_wbp,
        COALESCE(petugas.nama, 'tidak dikenal') AS nama_petugas,
        COALESCE(pengunjung.nama, 'tidak dikenal') AS nama_pengunjung,
        wbp_profile.wbp_profile_id AS wbp_id,
        wbp_profile.foto_wajah AS foto_wajah_wbp,
        petugas.petugas_id,
        petugas.foto_wajah AS foto_wajah_petugas,
        pengunjung.pengunjung_id AS data_pengunjung_id,
        pengunjung.foto_wajah AS foto_wajah_pengunjung,
        lokasi_otmil.lokasi_otmil_id ,
        lokasi_lemasmil.lokasi_lemasmil_id ,
        lokasi_otmil.nama_lokasi_otmil AS nama_lokasi_otmil,
        lokasi_lemasmil.nama_lokasi_lemasmil AS nama_lokasi_lemasmil,
        kamera.nama_kamera
        FROM kamera_log
        LEFT JOIN wbp_profile ON (
            wbp_profile.foto_wajah_fr = kamera_log.foto_wajah_fr
            AND wbp_profile.foto_wajah_fr IS NOT NULL
            AND LENGTH(wbp_profile.foto_wajah_fr) > 0
        )
        LEFT JOIN petugas ON (
            petugas.foto_wajah_fr = kamera_log.foto_wajah_fr
            AND petugas.foto_wajah_fr IS NOT NULL
            AND LENGTH(petugas.foto_wajah_fr) > 0
        )
        
        LEFT JOIN pengunjung ON (
            pengunjung.foto_wajah_fr = kamera_log.foto_wajah_fr
            AND pengunjung.foto_wajah_fr IS NOT NULL
            AND LENGTH(pengunjung.foto_wajah_fr) > 0
        )
        
        LEFT JOIN kamera ON kamera.kamera_id = kamera_log.kamera_id
        LEFT JOIN ruangan_otmil ON ruangan_otmil.ruangan_otmil_id = kamera.ruangan_otmil_id
        LEFT JOIN ruangan_lemasmil ON ruangan_lemasmil.ruangan_lemasmil_id = kamera.ruangan_lemasmil_id
        LEFT JOIN lokasi_otmil ON lokasi_otmil.lokasi_otmil_id = ruangan_otmil.lokasi_otmil_id
        LEFT JOIN lokasi_lemasmil ON lokasi_lemasmil.lokasi_lemasmil_id = ruangan_lemasmil.lokasi_lemasmil_id
        WHERE kamera_log.is_deleted = 0 ";

    // Use prepared statements to prevent SQL injection for filters
    if (!empty($filterNama)) {
        $query .= " AND (wbp_profile.nama LIKE :filterNama OR petugas.nama LIKE :filterNama OR pengunjung.nama LIKE :filterNama)";
    }

    if (!empty($filterTimeStamp)) {
        $query .= " AND kamera_log.timestamp LIKE :filterTimeStamp";
    }

    if (!empty($filterNamaKamera)) {
        $query .= " AND kamera.nama_kamera LIKE :filterNamaKamera";
    }
    if (!empty($filterNamaLokasiOtmil)) {
        $query .= " AND lokasi_otmil.nama_lokasi_otmil LIKE :filterNamaLokasiOtmil";
    }
    if (!empty($filterNamaLokasiLemasmil)) {
        $query .= " AND lokasi_lemasmil.nama_lokasi_lemasmil LIKE :filterNamaLokasiLemasmil";
    }
    if (!empty($filterLokasiLemasmilID)) {
        $query .= " AND lokasi_lemasmil.lokasi_lemasmil_id LIKE :filterLokasiLemasmilID";
    }
    if (!empty($filterLokasiOtmilID)) {
        $query .= " AND lokasi_otmil.lokasi_otmil_id LIKE :filterLokasiOtmilID";
    }

    $query .= " GROUP BY kamera_log.kamera_log_id";
    $query .= " ORDER BY $sortField $sortOrder";

    // ...
      // preparing and executing the query that has been updated with pagination feature
      $countQuery = "SELECT COUNT(*) total FROM ($query) subquery";
      $countStmt = $conn->prepare($countQuery);
      $countStmt->execute();
      $totalRecords = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
  
      // Apply pagination
      $totalPages = ceil($totalRecords / $pageSize);
      $start = ($page - 1) * $pageSize;
      $query .= " LIMIT $start, $pageSize";
  
      // executing the query to fetch all the data with filter and pagination features
      $stmt = $conn->prepare($query);
      $stmt->execute();
      $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $record = [];
  
      // loop through the result of the query and assign it to the $recordData variable
      foreach ($res as $row) {
          $petugas_id = $row['petugas_id'];
          $wbp_id = $row['wbp_id'];
            $data_pengunjung_id = $row['data_pengunjung_id'];
      
          $recordData = [
              "kamera_log_id" => $row['kamera_log_id'],
              "image" => $row['image'],
              "timestamp" => $row['timestamp'],
              "kamera_id" => $row['kamera_id'],
              "nama_kamera" => $row['nama_kamera'],
              "lokasi_id" => $row['lokasi_otmil_id'] ? $row['lokasi_otmil_id'] : $row['lokasi_lemasmil_id'],
              "tipe_lokasi" => $row['lokasi_otmil_id'] ? "otmil" : "lemasmil",
              "nama_lokasi" => $row['nama_lokasi_otmil'] ? $row['nama_lokasi_otmil'] : $row['nama_lokasi_lemasmil'],
          ];
      
          if ($wbp_id !== null) {
              $recordData["wbp_profile_id"] = $wbp_id;
              $recordData["nama_wbp"] = $row['nama_wbp'];
              $recordData["foto_wajah_wbp"] = $row['foto_wajah_wbp'];
          }
      
          if ($petugas_id !== null) {
              $recordData["petugas_id"] = $petugas_id;
              $recordData["nama_petugas"] = $row['nama_petugas'];
              $recordData["foto_wajah_petugas"] = $row['foto_wajah_petugas'];
          }
          if ($data_pengunjung_id !== null) {
              $recordData["data_pengunjung_id"] = $data_pengunjung_id;
              $recordData["nama_pengunjung"] = $row['nama_pengunjung'];
              $recordData["foto_wajah_pengunjung"] = $row['foto_wajah_pengunjung'];
          }
      
          // Set the "keterangan" field based on the conditions
          // if ($petugas_id === null && $wbp_id === null  ) {
          //     $recordData["keterangan"] = "Tidak Dikenal";
          // } elseif ($petugas_id === null && $wbp_id !== null) {
          //     $recordData["keterangan"] = "WBP";
          // } elseif ($petugas_id !== null && $wbp_id === null) {
          //     $recordData["keterangan"] = "Petugas";
          // } else {
          //     $recordData["keterangan"] = "Kesalahan Data";
          // }
          if ($wbp_id == null && $petugas_id == null && $data_pengunjung_id == null) {
              $recordData["keterangan"] = "Tidak Dikenal";
          } elseif ($wbp_id !== null && $petugas_id == null && $data_pengunjung_id == null) {
              $recordData["keterangan"] = "WBP";
          } elseif ($wbp_id == null && $petugas_id !== null && $data_pengunjung_id == null) {
              $recordData["keterangan"] = "Petugas";
          } elseif ($wbp_id == null && $petugas_id == null && $data_pengunjung_id !== null) {
              $recordData["keterangan"] = "Pengunjung";
          } else {
              $recordData["keterangan"] = "Kesalahan Data";
          }    
          $record[] = $recordData;
      }
      
      // ...
      
  
      // Prepare the JSON response with pagination information
      $response = [
          "status" => "OK",
          "message" => "",
          "records" => $record,
          "pagination" => [
              "currentPage" => $page,
              "pageSize" => $pageSize,
              "totalRecords" => $totalRecords,
              "totalPages" => $totalPages,
          ],
      ];
  
      echo json_encode($response);

} catch (Exception $e) {
    $result = [
        "status" => "error",
        "message" => $e->getMessage(),
        "records" => [],
    ];

    echo json_encode($result);
}

$stmt = null;
$conn = null;
?>
