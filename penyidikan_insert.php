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
} else {
    $param_POST = $_POST;
}

$penyidikan_id = generateUUID();
$nomor_penyidikan = isset($param_POST->nomor_penyidikan) ? trim($param_POST->nomor_penyidikan) : "";
$wbp_profile_id = isset($param_POST->wbp_profile_id) ? trim($param_POST->wbp_profile_id) : "";
$kasus_id = trim(isset($param_POST->kasus_id) ? $param_POST->kasus_id : "");
$alasan_penyidikan = trim(isset($param_POST->alasan_penyidikan) ? $param_POST->alasan_penyidikan : "");
$lokasi_penyidikan = trim(isset($param_POST->lokasi_penyidikan) ? $param_POST->lokasi_penyidikan : "");
$waktu_penyidikan = trim(isset($param_POST->waktu_penyidikan) ? $param_POST->waktu_penyidikan : date("Y:m:d H:i:s"));
$agenda_penyidikan =  trim( isset($param_POST->agenda_penyidikan) ? $param_POST->agenda_penyidikan : "");
$hasil_penyidikan = trim(isset($param_POST->hasil_penyidikan) ? $param_POST->hasil_penyidikan : "");
$created_at = date('Y-m-d H:i:s');

// pivot penyidikan jaksa
$jaksa_penyidik_ids = (empty($param_POST->jaksa_penyidik_id)) ? [] : $param_POST->jaksa_penyidik_id;
$role_ketua_jaksa_id = isset($param_POST->role_ketua_jaksa_id) ? trim($param_POST->role_ketua_jaksa_id) : "";

// pivot penyidikan saksi
$saksi_ids = isset($param_POST->saksi_id) ? $param_POST->saksi_id : [];


// histori penyidikan
$histori_penyidikan_id = generateUUID();
$hasil_penyidikan = (empty($param_POST->hasil_penyidikan)) ? '' : trim($param_POST->hasil_penyidikan);
$lama_masa_tahanan = (empty($param_POST->lama_masa_tahanan)) ? '' : trim($param_POST->lama_masa_tahanan);

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    tokenAuth($conn, 'admin');

    $query = "SELECT * from penyidikan WHERE 
        nomor_penyidikan = ? AND
        wbp_profile_id = ? AND 
        kasus_id = ? AND
        alasan_penyidikan = ? AND
        lokasi_penyidikan = ? AND
        waktu_penyidikan = ? AND
        agenda_penyidikan = ? AND 
        hasil_penyidikan = ? AND
        created_at = ?";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $nomor_penyidikan, PDO::PARAM_STR);
    $stmt->bindValue(2, $wbp_profile_id, PDO::PARAM_STR);
    $stmt->bindValue(3, $kasus_id, PDO::PARAM_STR);
    $stmt->bindValue(4, $alasan_penyidikan, PDO::PARAM_STR);
    $stmt->bindValue(5, $lokasi_penyidikan, PDO::PARAM_STR);
    $stmt->bindValue(6, $waktu_penyidikan, PDO::PARAM_STR);
    $stmt->bindValue(7, $agenda_penyidikan, PDO::PARAM_STR);
    $stmt->bindValue(8, $hasil_penyidikan, PDO::PARAM_STR);
    $stmt->bindValue(9, $created_at, PDO::PARAM_STR);
    
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        throw new Exception("Data already exists");
    } else {
        $query2 = "INSERT INTO penyidikan (
            penyidikan_id,
            nomor_penyidikan,
            wbp_profile_id,
            kasus_id,
            alasan_penyidikan,
            lokasi_penyidikan,
            waktu_penyidikan,
            agenda_penyidikan,
            hasil_penyidikan,
            created_at
        ) VALUES (?, ? ,?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($query2);   
        $stmt2->bindValue(1, $penyidikan_id, PDO::PARAM_STR);
        $stmt2->bindValue(2, $nomor_penyidikan, PDO::PARAM_STR);
        $stmt2->bindValue(3, $wbp_profile_id, PDO::PARAM_STR);
        $stmt2->bindValue(4, $kasus_id, PDO::PARAM_STR);
        $stmt2->bindValue(5, $alasan_penyidikan, PDO::PARAM_STR);
        $stmt2->bindValue(6, $lokasi_penyidikan, PDO::PARAM_STR);
        $stmt2->bindValue(7, $waktu_penyidikan, PDO::PARAM_STR);
        $stmt2->bindValue(8, $agenda_penyidikan, PDO::PARAM_STR);
        $stmt2->bindValue(9, $hasil_penyidikan, PDO::PARAM_STR);
        $stmt2->bindValue(10, $created_at, PDO::PARAM_STR);
        $stmt2->execute();

        foreach ($jaksa_penyidik_ids as $jaksa_penyidik_id) {
            $pivot_penyidikan_jaksa_id = generateUUID(); // Unique UUID for each sidang_hakim record
            $query3 = "INSERT INTO pivot_penyidikan_jaksa (
                pivot_penyidikan_jaksa_id,
                penyidikan_id,
                role_ketua,
                jaksa_penyidik_id
            ) VALUES (?, ?, ?, ?)";
            $stmt3 = $conn->prepare($query3);

            $role = ($jaksa_penyidik_id == $role_ketua_jaksa_id) ? 1 : 0;
        
            $stmt3->execute([
                $pivot_penyidikan_jaksa_id,
                $penyidikan_id,
                $role,
                $jaksa_penyidik_id
            ]);
        }

        foreach ($saksi_ids as $saksi) {
            $pivot_penyidikan_saksi_id = generateUUID(); // Unique UUID for each sidang_hakim record
            $query4 = "INSERT INTO pivot_penyidikan_saksi (
                pivot_penyidikan_saksi_id,
                penyidikan_id,
                saksi_id
            ) VALUES (?, ?, ?)";
            $stmt4 = $conn->prepare($query4);
        
            $stmt4->execute([
                $pivot_penyidikan_saksi_id,
                $penyidikan_id,
                $saksi
            ]);
        }

        $query5 = "INSERT INTO histori_penyidikan(
            histori_penyidikan_id,
            penyidikan_id,
            hasil_penyidikan,
            lama_masa_tahanan
        ) VALUES (?, ?, ?, ?)";       
   
        $stmt5 = $conn->prepare($query5);
    
    
        $stmt5->execute([
            $histori_penyidikan_id,
            $penyidikan_id,
            $hasil_penyidikan,
            $lama_masa_tahanan
        ]);

        $result = [
            "status" => "OK",
            "message" => "Data berhasil disimpan",
            "records" => [
                "penyidikan_id" => $penyidikan_id,
                "nomor_penyidikan" => $nomor_penyidikan,
                "wbp_profile_id" => $wbp_profile_id,
                "kasus_id" => $kasus_id,
                "alasan_penyidikan" => $alasan_penyidikan,
                "lokasi_penyidikan" => $lokasi_penyidikan,
                "waktu_penyidikan" => $waktu_penyidikan,
                "agenda_penyidikan" => $agenda_penyidikan,
                "hasil_penyidikan" => $hasil_penyidikan,
                "created_at" => $created_at,
                "pivot_penyidikan_jaksa_id" => $pivot_penyidikan_jaksa_id,
                "pivot_penyidikan_saksi_id" => $pivot_penyidikan_saksi_id,
                "histori_penyidikan_id" => $histori_penyidikan_id
            ]
        ];
    }
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>
