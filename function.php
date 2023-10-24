<?php
function base64_to_jpeg($base64_string, $output_file) {
    // Check if the output directory exists and create it if not
    $output_directory = dirname('..'.$output_file);
    if (!is_dir($output_directory)) {
        mkdir($output_directory, 0777, true);
    }

    // Open the output file for writing
    $ifp = fopen('..'.$output_file, 'wb');
    if (!$ifp) {
        return false; // Return an error or handle the failure appropriately
    }

    // Split the string on commas
    $data = explode(',', $base64_string);

    // Handle different cases for base64 string format
    if (count($data) > 1) {
        $base64 = $data[1];
    } else {
        $base64 = $data[0];
    }

    // Check if fwrite operation is successful
    if (fwrite($ifp, base64_decode($base64)) === false) {
        fclose($ifp);
        return false; // Return an error or handle the failure appropriately
    }

    // Clean up the file resource
    fclose($ifp);

    return $output_file;
}

function callAPI($method, $url, $data){
    $curl = curl_init();
    switch ($method){
       case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;
       case "PUT":
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
          break;
          case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            if ($data) {
                // If you want to include data in the DELETE request, you can use CURLOPT_POSTFIELDS
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
       default:
          if ($data)
             $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    //    'APIKEY: 111111111111111111111',
       'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if(!$result){die("Connection Failure");}
    curl_close($curl);
    return $result;
 }
?>