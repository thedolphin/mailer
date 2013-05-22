#!/usr/bin/php
<?
/*

main.cf:
transport_maps = hash:/etc/postfix/transport

transport:
bounce@mailer.wikimart.ru bouncer:

master.cf:
bouncer   unix   -       n       n       -       -       pipe flags=R user=www argv=/www/mailer/bouncer.php

*/

require 'common.php';

try {

    $config = new config();

    $body = file_get_contents("php://stdin");

    if (preg_match('/Message-Id: <wikimart-([0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{12})/', $body, $match)) {

        $message_id = $match[1];

        $db = init_db($config);

        if(!mysql_query("UPDATE maillog SET bounced = 1 where message_id = UNHEX(REPLACE('{$message_id}', '-', ''))" ))
            throw new Exception("Cannot update maillog" . mysql_error ($db));
    }

    $path = $config['bounce']['storage'];
    if (!$message_id) $path .= '/unknown';
    $path .= '/' . date("Ymd");

    if (!is_dir($path))
        if (!mkdir($path, 0700, true))
            throw new Exception("Cannot create dir {$path}");

    file_put_contents($path .'/'. date("His") .'.'. ($message_id ? $message_id : str_rand(8)), $body);
}

catch (Exception $e) {
    error_log($e->getMessage());
}
