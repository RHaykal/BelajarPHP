<?php
ini_set('memory_limit', '-1');

/**
 * @param int $length 
 * @return string
 */
function getRandomKey($length)
{
    $ValidChar = "ABCDEFGHJKLMNPRTUVWXYZ2346789";
    $len = strlen($ValidChar);
    $result = "";
    for ($i = 0; $i < $length; $i++) {
        $result .= substr($ValidChar, rand() % $len, 1);
    }
    return $result;
}

function generateUUID()
{
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $uuid = '';
    $uuidLengths = [8, 4, 4, 4, 12];

    foreach ($uuidLengths as $length) {
        for ($i = 0; $i < $length; $i++) {
            $uuid .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $uuid .= '-';
    }

    return rtrim($uuid, '-');
}


function base64_to_image($base64_string, $filename)
{

    try {
        if (strpos($base64_string, 'http:') > -1) return $base64_string;
        else {
            //$tmp = explode(';', $base64_string);
            //$file_type = explode('/', $tmp[0]);

            $data = explode(',', $base64_string);
            $base64 = base64_decode($data[1]);

            $output_file = IMG_FOLDER . '/' . $filename;

            $result = file_put_contents('.' . $output_file, $base64);

            if (!$result) {
                return '';
            } else {
                return 'http://' . $_SERVER['SERVER_NAME'] . PROJECT_FOLDER . $output_file;    //file_url for save to db;
            }
        }
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

function convertJsonToCSV($jsonData, $csvFileName)
{
    try {
        $jsonDecoded = json_decode($jsonData, true);
        $fp = fopen($csvFileName, 'w');
        fputcsv($fp, array_keys($jsonDecoded[0]));
        foreach ($jsonDecoded as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

function rupiah($angka)
{

    $hasil_rupiah = "Rp " . number_format($angka, 2, ',', '.');
    return $hasil_rupiah;
}

function base64_to_file($base64_string, $filename)
{

    try {
        if (strpos($base64_string, 'http:') > -1) return $base64_string;
        else {
            //$tmp = explode(';', $base64_string);
            //$file_type = explode('/', $tmp[0]);

            $data = explode(',', $base64_string);
            $base64 = base64_decode($data[1]);

            $output_file = FILE_FOLDER . '/' . $filename;

            $result = file_put_contents('.' . $output_file, $base64);

            if (!$result) {
                return '';
            } else {
                return 'http://' . $_SERVER['SERVER_NAME'] . FILE_FOLDER . $output_file;    //file_url for save to db;
            }
        }
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

function get_ip_public()
{
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        $headers = $_SERVER;
    }
    if (array_key_exists('X-Forwarded-For', $headers) && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['X-Forwarded-For'];
    } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers) && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
    } else {
        $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
    return $the_ip;
}

function writeLog($filePath, $logMessage)
{
    $fp = fopen($filePath, 'a');
    if (isset($fp)) {
        fwrite($fp, '[' . date("Y-m-d H:i:s") . '] ' . $logMessage . PHP_EOL);
        fclose($fp);
    }
}

function csvToJson($fname)
{
    // open csv file
    if (!($fp = fopen($fname, 'r'))) {
        die("Can't open file...");
    }
    $key = fgetcsv($fp, "1024", ",");
    $json = array();
    while ($row = fgetcsv($fp, "1024", ",")) {
        $json[] = array_combine($key, $row);
    }
    fclose($fp);
    return $json;
}

function strip_tags_content($string)
{
    // ----- remove HTML TAGs ----- 
    $string = preg_replace('/<[^>]*>/', ' ', $string);
    // ----- remove control characters ----- 
    $string = str_replace("\r", '', $string);
    $string = str_replace("\n", ' ', $string);
    $string = str_replace("\t", ' ', $string);
    // ----- remove multiple spaces ----- 
    $string = trim(preg_replace('/ {2,}/', ' ', $string));
    return $string;
}

function csv_to_json_byheader($filename)
{
    $json = array();
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $rownum = 0;
        $header = array();
        while (($row = fgetcsv($handle, 1024, ";")) !== FALSE) {
            if ($rownum === 0) {
                for ($i = 0; $i < count($row); $i++) {
                    $header[$i] = trim($row[$i]);
                }
            } else {
                if (count($row) === count($header)) {
                    $rowJson = array();

                    // print_r($header);
                    // exit();

                    foreach ($header as $i => $head) {
                        // print_r($head);
                        // if($i==0) {
                        //     $rowJson['ID Pelanggan'] = $row[$i];
                        // }else{
                        //     $rowJson[$head] = $row[$i];
                        // }
                        $rowJson[$head] = $row[$i];
                    }
                    array_push($json, $rowJson);
                }
            }
            $rownum++;
        }
        fclose($handle);
    }
    return  $json;
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function tokenAuth($conn, $requiredRole) {
    $queryRole = "";
    if ($requiredRole == 'superadmin') {
        $queryRole = "AND ur.role_name = 'superadmin'";
    } else if ($requiredRole == 'admin') {
        $queryRole = "AND (ur.role_name = 'admin' OR ur.role_name = 'superadmin')";
    } else if ($requiredRole == 'operator') {
        $queryRole = "";
    }

    // Check if the connection is valid
    if (!$conn) {
        http_response_code(500);  // Internal Server Error
        echo json_encode(["status" => "error", "message" => "Database connection is not valid"]);
        exit;
    }

    $token = getBearerToken();

    if (!$token) {
        http_response_code(401);  // Unauthorized
        echo json_encode(["status" => "error", "message" => "Bearer token required"]);
        exit;
    }

    try {
        $query_check_token = "SELECT ul.user_login_id
        FROM user_login ul
        LEFT JOIN user u ON ul.user_id = u.user_id
        LEFT JOIN user_role ur ON u.user_role_id = ur.user_role_id 
        WHERE ul.token = ? $queryRole";

        $stmt_check_token = $conn->prepare($query_check_token);
        $stmt_check_token->execute([$token]);
        $user = $stmt_check_token->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(403);  // Forbidden
            $response = [
                "status" => "error",
                "message" => "Token Invalid",
                "records" => []
            ];
            echo json_encode($response);
            exit;
        } else {
            $query_check_expiry = "SELECT ul.user_id, ul.token_expiry, u.user_role_id, ur.role_name 
            FROM user_login ul
            LEFT JOIN user u ON ul.user_id = u.user_id
            LEFT JOIN user_role ur ON u.user_role_id = ur.user_role_id
            WHERE ul.token_expiry > NOW()";

            $stmt_check_token = $conn->prepare($query_check_expiry);
            $stmt_check_token->execute([$token]);
            $isNotExpired = $stmt_check_token->fetch(PDO::FETCH_ASSOC);

            if(!$isNotExpired) {
                http_response_code(403);  // Forbidden
                $response = [
                    "status" => "error",
                    "message" => "Token Expired, try log in again",
                    "records" => []
                ];
                echo json_encode($response);
                exit;
            }
            return $isNotExpired;
        }

        // User token and role are valid
        // return $user;
    } catch (PDOException $e) {
        http_response_code(500);  // Internal Server Error
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

function uploadFile($fileInputName, $uploadFolder, $allowedExtensions)
{
    if (!isset($_FILES[$fileInputName])) {
        return "File input not found.";
    }

    $file = $_FILES[$fileInputName];
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array($fileExtension, $allowedExtensions)) {
        return "Invalid file extension.";
    }

    if ($file['error'] === UPLOAD_ERR_OK) {
        $newFileName = getRandomKey(10) . '.' . $fileExtension;
        $newFilePath = $uploadFolder . '/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $newFilePath)) {
            return $newFilePath; // Upload successful.
        } else {
            return "Failed to move uploaded file.";
        }
    } else {
        return "File upload failed with error code: " . $file['error'];
    }
}



$hashkey = '-=[]\;,./_+{}:<>?';
