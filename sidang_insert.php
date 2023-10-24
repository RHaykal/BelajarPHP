<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");

require_once('require_files.php');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Headers: Origin, X-Api-Key, X-Requested-With, Content-Type, Accept, Authorization");
    exit(0);
}

if (stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {
    $param_POST = json_decode(file_get_contents("php://input"));
} else {
    $param_POST = $_POST;
}

$sidang_id = generateUUID(); // You should define this function somewhere.

// Use the ternary operator to set default values if parameters are not provided.
$nama_sidang = isset($param_POST->nama_sidang) ? trim($param_POST->nama_sidang) : "";

// Retrieve the arrays for hakim_id, jaksa_id, and pengacara
$hakim_ids = isset($param_POST->hakim_id) ? $param_POST->hakim_id : [];
$jaksa_ids = isset($param_POST->jaksa_penuntut_id) ? $param_POST->jaksa_penuntut_id : [];
$pengacaras = isset($param_POST->pengacara) ? $param_POST->pengacara : [];
$saksis = isset($param_POST->saksi) ? $param_POST->saksi : [];
$ahlis = isset($param_POST->ahli) ? $param_POST->ahli : [];


$juru_sita = isset($param_POST->juru_sita) ? trim($param_POST->juru_sita) : "";
$pengawas_peradilan_militer = isset($param_POST->pengawas_peradilan_militer) ? trim($param_POST->pengawas_peradilan_militer) : "";
$jadwal_sidang = isset($param_POST->jadwal_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->jadwal_sidang))): "";
$perubahan_jadwal_sidang = isset($param_POST->perubahan_jadwal_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->perubahan_jadwal_sidang))): "";
$kasus_id = isset($param_POST->kasus_id) ? trim($param_POST->kasus_id) : "";
$waktu_mulai_sidang = isset($param_POST->waktu_mulai_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->waktu_mulai_sidang))): "";
$waktu_selesai_sidang = isset($param_POST->waktu_selesai_sidang) ? date('Y-m-d H:i:s', strtotime(trim($param_POST->waktu_selesai_sidang))): "";
$agenda_sidang = isset($param_POST->agenda_sidang) ? trim($param_POST->agenda_sidang) : "";
$pengadilan_militer_id = isset($param_POST->pengadilan_militer_id) ? trim($param_POST->pengadilan_militer_id) : "";
$hasil_keputusan_sidang = isset($param_POST->hasil_keputusan_sidang) ? trim($param_POST->hasil_keputusan_sidang) : "";
$jenis_persidangan_id = isset($param_POST->jenis_persidangan_id) ? trim($param_POST->jenis_persidangan_id) : "";

// histori vonis
$histori_vonis_id = generateUUID();
$hasil_vonis = isset($param_POST->hasil_vonis) ? trim($param_POST->hasil_vonis) : "";
$masa_tahanan_tahun = isset($param_POST->masa_tahanan_tahun) ? trim($param_POST->masa_tahanan_tahun) : "";
$masa_tahanan_bulan = isset($param_POST->masa_tahanan_bulan) ? trim($param_POST->masa_tahanan_bulan) : "";
$masa_tahanan_hari = isset($param_POST->masa_tahanan_hari) ? trim($param_POST->masa_tahanan_hari) : "";

// dokumen persidangan
$dokumen_persidangan_id = generateUUID();
$nama_dokumen_persidangan = isset($param_POST->nama_dokumen_persidangan) ? trim($param_POST->nama_dokumen_persidangan) : "";
// $link_dokumen_persidangan = isset($param_POST->link_dokumen_persidangan) ? trim($param_POST->link_dokumen_persidangan) : "";
$created_at = date('Y-m-d H:i:s');

$result = '';

try {
    $conn = new PDO("mysql:host=$MySQL_HOST;dbname=$MySQL_DB;charset=utf8", $MySQL_USER, $MySQL_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // tokenAuth($conn, 'admin');

    $query1 = "INSERT INTO sidang (
        sidang_id,
        nama_sidang,
        juru_sita,
        pengawas_peradilan_militer,
        jadwal_sidang,
        perubahan_jadwal_sidang,
        kasus_id,
        waktu_mulai_sidang,
        waktu_selesai_sidang,
        agenda_sidang,
        pengadilan_militer_id,
        hasil_keputusan_sidang,
        jenis_persidangan_id,
        created_at      
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt1 = $conn->prepare($query1);

    $stmt1->execute([
        $sidang_id,
        $nama_sidang,
        $juru_sita,
        $pengawas_peradilan_militer,
        $jadwal_sidang,
        $perubahan_jadwal_sidang,
        $kasus_id,
        $waktu_mulai_sidang,
        $waktu_selesai_sidang,
        $agenda_sidang,
        $pengadilan_militer_id,
        $hasil_keputusan_sidang,
        $jenis_persidangan_id
    ]);


    foreach ($hakim_ids as $hakim_id) {
        $pivot_sidang_hakim_id = generateUUID(); // Unique UUID for each sidang_hakim record
        $role_ketua_hakim = isset($param_POST->role_ketua_hakim) ? trim($param_POST->role_ketua_hakim) : "";
        $query2 = "INSERT INTO pivot_sidang_hakim (
            pivot_sidang_hakim_id,
            sidang_id,
            role_ketua,
            hakim_id
        ) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($query2);
    
        $stmt2->execute([
            $pivot_sidang_hakim_id,
            $sidang_id,
            $role_ketua_hakim,
            $hakim_id
        ]);
    }
    
    // Loop through jaksa_ids and insert records
    foreach ($jaksa_ids as $jaksa_id) {
        $pivot_sidang_jaksa_id = generateUUID();
        $role_ketua_jaksa = isset($param_POST->role_ketua_jaksa) ? trim($param_POST->role_ketua_jaksa) : "";
    
        $query3 = "INSERT INTO pivot_sidang_jaksa (
            pivot_sidang_jaksa_id,
            sidang_id,
            role_ketua,
            jaksa_penuntut_id
        ) VALUES (?, ?, ?, ?)";
    
        $stmt3 = $conn->prepare($query3);
    
        $stmt3->execute([
            $pivot_sidang_jaksa_id,
            $sidang_id,
            $role_ketua_jaksa,
            $jaksa_id
        ]);
    }
    
    
    
    // Loop through pengacaras and insert records
    foreach ($pengacaras as $pengacara) {
        $sidang_pengacara_id = generateUUID(); // Unique UUID for each sidang_pengacara record
        $query4 = "INSERT INTO sidang_pengacara (
            sidang_pengacara_id,
            sidang_id,
            nama_pengacara
        ) VALUES (?, ?, ?)";
    
        $stmt4 = $conn->prepare($query4);
    
        $stmt4->execute([
            $sidang_pengacara_id,
            $sidang_id,
            $pengacara
        ]);
    }

    foreach ($saksis as $saksi) {
        $pivot_sidang_saksi_id = generateUUID(); // Unique UUID for each sidang_saksi record
        $query5 = "INSERT INTO pivot_sidang_saksi (
            pivot_sidang_saksi_id,
            sidang_id,
            saksi_id
        ) VALUES (?,?,?)";
    
        $stmt5 = $conn->prepare($query5);
    
        $stmt5->execute([
            $pivot_sidang_saksi_id,
            $sidang_id,
            $saksi
        ]);
    }

    foreach($ahlis as $ahli) {
        $pivot_sidang_ahli_id = generateUUID(); // Unique UUID for each sidang_ahli record
        $query6 = "INSERT INTO pivot_sidang_ahli (
            pivot_sidang_ahli_id,
            sidang_id,
            ahli_id
        ) VALUES (?,?,?)";
    
        $stmt6 = $conn->prepare($query6);
    
        $stmt6->execute([
            $pivot_sidang_ahli_id,
            $sidang_id,
            $ahli
        ]);
    }

    // histori vonis
    $query6 = "INSERT INTO histori_vonis(
         histori_vonis_id,
        sidang_id,
        hasil_vonis,
        masa_tahanan_tahun,
        masa_tahanan_bulan,
        masa_tahanan_hari
    ) VALUES (?, ?, ?, ?, ?, ?)";       

    $stmt6 = $conn->prepare($query6);


    $stmt6->execute([
        $histori_vonis_id,
        $sidang_id,
        $hasil_vonis,
        $masa_tahanan_tahun,
        $masa_tahanan_bulan,
        $masa_tahanan_hari
    ]);

    //dokumen_persidangan_base64
    if (isset($param_POST->pdf_file_base64)) {
        $base64_encoded_pdf = $param_POST->pdf_file_base64;
    
        // Decode the base64 content to binary
        $pdf_content = base64_decode($base64_encoded_pdf);
    
        // Define the file path where you want to save the PDF
        $pdf_file_path = "/var/www/api/siram_api/document_sidang/" . $nama_dokumen_persidangan . ".pdf";
    
        // Save the PDF to the specified file path
        file_put_contents($pdf_file_path, $pdf_content);

        $query = "INSERT INTO dokumen_persidangan (dokumen_persidangan_id, nama_dokumen_persidangan, link_dokumen_persidangan) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $dokumen_persidangan_id, PDO::PARAM_STR);
        $stmt->bindValue(2, $nama_dokumen_persidangan, PDO::PARAM_STR);
        $stmt->bindValue(3, $pdf_file_path, PDO::PARAM_STR);
        $stmt->execute();
    }

    // dokumen persidangan
    // $uploadedDokumenPath = uploadFile('dokumen_persidangan_file', 'dokumen_persidangan_folder', ['pdf', 'docx']);
    // $nama_dokumen_persidangan = isset($param_POST->nama_dokumen_persidangan) ? trim($param_POST->nama_dokumen_persidangan) : "";

    // if ($uploadedDokumenPath) {
    //     $query7 = "INSERT INTO dokumen_persidangan (
    //         dokumen_persidangan_id,
    //         nama_dokumen_persidangan,
    //         link_dokumen_persidangan,
    //         sidang_id,
    //         created_at
    //     ) VALUES (?, ?, ?, ?, NOW())";

    //     $stmt7 = $conn->prepare($query7);

    //     $stmt7->execute([
    //         $dokumen_persidangan_id,
    //         $nama_dokumen_persidangan,
    //         $uploadedDokumenPath, // Simpan link file yang diunggah
    //         $sidang_id
    //     ]);
    // } else {
    //     // Handle error jika file tidak dapat diunggah
    //     $result = [
    //         "status" => "NO",
    //         "message" => "Failed to upload dokumen_persidangan file.",
    //         "records" => []
    //     ];

    //     echo json_encode($result);
    //     exit;
    // }

    // The rest of your code for histori_vonis and dokumen_persidangan

    $result = [
        "status" => "OK",
        "message" => "Successfully",
        "records" => [
            [
                "sidang_id" => $sidang_id,	
                "created_at" => $created_at
            ]
        ]
    ];
} catch (Exception $e) {
    $result = [
        "status" => "NO",
        "message" => $e->getMessage(),
        "records" => []
    ];
}

echo json_encode($result);
?>
