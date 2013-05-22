<?php

try {

    require 'common.php';

    $link = decode_url(str_replace('/follow/', '', $_SERVER['DOCUMENT_URI']));

    if (!$link)
        $link = 'http://wikimart.ru';

    $message_id = isset($_GET['id']) ? $_GET['id'] : false;

    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $message_id))
        $message_id = false;

    $config = new config();

    if ($message_id) {

        $db = init_db($config);

        if(!mysql_query("UPDATE maillog SET clicked = 1 where message_id = UNHEX(REPLACE('{$message_id}', '-', ''))" ))
            throw new Exception("Cannot update maillog" . mysql_error ($db));

    }

    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $link);
}

catch(Exception $e) {
    header('HTTP/1.1 500 Server Error');
    error_log($e->getMessage());
}
