#!/usr/bin/php
<?php

require '../common.php';

/*
    Additional headers
                "X-Report-Abuse-To: <mailto:{$config['mail']['abuse']}?subject=abuse-{$message_id}>\n" .
                "List-Unsubscribe: <mailto:{$config['mail']['sender']}?subject=unsubscribe-{$message_id}>\n" .
*/

$config = new config();
$queue = new queue($config);

$message_id = exec('uuidgen');
$body       = file_get_contents($argv[3]);

$body = str_replace('*|MC:SUBJECT|*', $argv[2], $body);
$body = str_replace('*|ARCHIVE|*', $argv[4], $body);

if (preg_match_all( "/<\s*a\s+href\s*=\s*[\"']{1}(.+?)[\"|']{1}/i", $body, $match)) {

    foreach ($match[1] as $link) {
        if (strstr($link, 'http://') && strstr($link, 'wikimart.ru'))
            $body = str_replace($link, 'http://mailer.wikimart.ru/follow/' . encode_url($link) . "?id={$message_id}", $body);
    }
}

$body = str_replace('*|UNSUB|*', "http://mailer.wikimart.ru/unsubscribe?id={$message_id}", $body);
$body = str_replace('*|EMPTYGIF|*', "<img src=\"http://mailer.wikimart.ru/onepx.gif?id={$message_id}\">", $body);

$boundary = str_rand(32);

$mail = array(
    'headers' => array(
        'To' => $argv[1],
        'Subject' => $argv[2],
        'Content-Type' => 'text/html; charset="utf-8"',
        'Reply-To' => 'promo@wikimart.ru',
        'Message-Id' => $message_id,
        'MIME-Version' => '1.0',
        'Content-Type' => "multipart/alternative; boundary=\"{$boundary}\"",
        'Content-Transfer-Encoding' => '7Bit'),
    'campaign' => $argv[5],
    'sender' => gethostname()
    );

$boundary = '--' . $boundary;

$mailbody = 'This is a multi-part message in MIME format' . CR . CR . $boundary . CR;

$mailbody .= 'Content-Type: text/plain; charset="utf-8"; format="fixed"' . CR .
         'Content-Transfer-Encoding: base64' . CR . CR;

$mailbody .= chunk_split(base64_encode('Если пистьмо отобразилось некорректно или ваш почтовый клиент не поддерживает письма в html-формате, то Вы можете увидеть наше специальное предложение по адресу ' . $argv[4])) . CR;

$mailbody .= $boundary . CR .
        'Content-Type: text/html; charset="utf-8"' . CR .
        'Content-Transfer-Encoding: base64' . CR . CR;

$mailbody .= chunk_split(base64_encode($body)) . CR;

$mailbody .= $boundary . '--' . CR . CR;

$mail['data'] = $mailbody;

$queue->enqueue(serialize($mail));
