<?php
function curl_getQuery($url,$fields)
{
    $fields_string = "";

    foreach ($fields as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $data = curl_exec($ch);

    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpCode == 404) {
        echo "cURL Error: 404";
    }
    if ($curl_errno > 0) {
        echo "cURL Error ($curl_errno): $curl_error\n";
    } else {
        echo "Data received: $data\n";
    }
    curl_close($ch);
}