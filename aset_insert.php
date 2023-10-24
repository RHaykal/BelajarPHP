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

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
}

$param_POST = isset($param_POST) ? $param_POST : (object) [];

$aset_id = generateUUID();
$nama_aset = (empty($param_POST->nama_aset)) ? '' : trim($param_POST->nama_aset);
$tipe_aset_id = (empty($param_POST->tipe_aset_id)) ? '' : trim($param_POST->tipe_aset_id);
$ruangan_otmil_id = (empty($param_POST->ruangan_otmil_id)) ? '' : trim($param_POST->ruangan_otmil_id);
$ruangan_lemasmil_id = (empty($param_POST->ruangan_lemasmil_id)) ? '' : trim($param_POST->ruangan_lemasmil_id);
$kondisi = (empty($param_POST->kondisi)) ? '' : trim($param_POST->kondisi);
$keterangan = (empty($param_POST->keterangan)) ? '' : trim($param_POST->keterangan);
$tanggal_masuk = (empty($param_POST->tanggal_masuk)) ? '' : trim($param_POST->tanggal_masuk);
$image = (empty($param_POST->foto_barang)) ? '' : trim($param_POST->foto_barang);
$serial_number = (empty($param_POST->serial_number)) ? '' : trim($param_POST->serial_number);
$model = (empty($param_POST->model)) ? '' : trim($param_POST->model);
$merek = (empty($param_POST->merek)) ? '' : trim($param_POST->merek);
$garansi = (empty($param_POST->garansi)) ? '' : trim($param_POST->garansi);

$result = ['message' => '', 'status' => 'No', 'records' => []];

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    tokenAuth($conn, 'admin');

    $query = "SELECT * FROM aset A
        WHERE 
        A.nama_aset = ? AND 
        A.tipe_aset_id = ? AND
        A.ruangan_otmil_id = ? AND 
        A.ruangan_lemasmil_id = ? AND 
        A.kondisi = ? AND 
        A.keterangan = ? AND 
        A.tanggal_masuk = ? AND
        A.serial_number = ? AND
        A.model = ? AND
        A.merek = ? AND
        A.garansi =?
        ";

    $stmt = $conn->prepare($query);
    $stmt->execute([ 
        $nama_aset, 
        $tipe_aset_id, 
        $ruangan_otmil_id, 
        $ruangan_lemasmil_id, 
        $kondisi, 
        $keterangan, 
        $tanggal_masuk,
        $serial_number,
        $model,
        $merek,
        $garansi
    ]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        $result['message'] = "Data already exists";

    } else {
        $filename = md5($nama_aset.time());
        $photoLocation = '/siram_api/images_aset_data'.'/'.$filename.'.jpg';
        $photoResult = base64_to_jpeg($image, $photoLocation);   

        $query1 = "INSERT INTO aset (
            aset_id, 
            nama_aset, 
            tipe_aset_id, 
            ruangan_otmil_id, 
            ruangan_lemasmil_id, 
            kondisi, 
            keterangan, 
            tanggal_masuk,
            image,
            serial_number,
            model,
            merek,
            garansi
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($query1);
        $stmt1->bindParam(1, $aset_id, PDO::PARAM_STR);
        $stmt1->bindParam(2, $nama_aset, PDO::PARAM_STR);
        $stmt1->bindParam(3, $tipe_aset_id, PDO::PARAM_STR);
        $stmt1->bindParam(4, $ruangan_otmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(5, $ruangan_lemasmil_id, PDO::PARAM_STR);
        $stmt1->bindParam(6, $kondisi, PDO::PARAM_STR);
        $stmt1->bindParam(7, $keterangan, PDO::PARAM_STR);
        $stmt1->bindParam(8, $tanggal_masuk, PDO::PARAM_STR);
        $stmt1->bindParam(9, $photoResult, PDO::PARAM_STR);
        $stmt1->bindParam(10, $serial_number, PDO::PARAM_STR);
        $stmt1->bindParam(11, $model, PDO::PARAM_STR);
        $stmt1->bindParam(12, $merek, PDO::PARAM_STR);
        $stmt1->bindParam(13, $garansi, PDO::PARAM_STR);
        $stmt1->execute();

        $result['message'] = "Insert data successfully";
        $result['status'] = "OK";
        $result['records'] = [[
            'aset_id' => $aset_id,
            'nama_aset' => $nama_aset,
            'tipe_aset_id' => $tipe_aset_id,
            'ruangan_otmil_id' => $ruangan_otmil_id,
            'ruangan_lemasmil_id' => $ruangan_lemasmil_id,
            'kondisi' => $kondisi,
            'keterangan' => $keterangan,
            'tanggal_masuk' => $tanggal_masuk,
            'image' => $photoResult,
            'serial_number' => $serial_number,
            'model' => $model,
            'merek' => $merek,
            'garansi' => $garansi
        ]];
    }
} catch (PDOException $e) {
    $result['message'] = "Database error: " . $e->getMessage();
}
echo json_encode($result);
?>
