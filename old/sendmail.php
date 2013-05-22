<?php

define('CR', "\r\n");

$message = file_get_contents('php://stdin');

$lastheader = false;

list($headerstext, $bodytext) = preg_split('/\R{2}/', $body, 2);

$tok = strtok($headerstext, CR);

while ($tok !== false) {

    if (preg_match('/^([A-Za-z\-]+): (.+)$/', $tok, $matches)) {
        $headers[$matches[1]] = $matches[2];
        $lastheader = $matches[1];
    }

    if (preg_match('/^\s+(.+)$/', $tok, $matches) && $lastheader) {
        $headers[$lastheader] .= ' ' . $matches[1];
    }

    $tok = strtok(CR);
}

$body = preg_replace('/\R/u', CR, $bodytext);

$postfields['Headers'] = serialize($headers);
$postfields['Body'] = $body;

$curl = curl_init($url);
curl_setopt_array($curl, array(
    CURLOPT_POST => true,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_TIMEOUT => 3,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Wikimart Sendmail',
    CURLOPT_POSTFIELDS => $postfields,
    CURLOPT_HTTPHEADER => array('Connection: close')));

$httpbody = curl_exec($curl);
$curlerror = curl_error($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

return ! ($curlerror || $httpcode >= 500);
