<?php
// Replace XXXXXX_XXXX with the name of the header you need in UPPERCASE (and with '-' replaced by '_')
// $headerStringValue = $_SERVER['HTTP_AUTHORIZATION'];
// echo $headerStringValue;
function getRequestHeaders() {
    $headers = array();
    foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;
}

$headers = getRequestHeaders();

foreach ($headers as $header => $value) {
    echo "$header: $value <br />\n";
}
// echo $_SERVER['HTTP_POSTMAN_TOKEN'];