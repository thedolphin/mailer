<?php

$empty_gif = "GIF89a\x01\x00\x01\x00\x80\x01\x00\x00\x00\x00\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x01\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x4C\x01\x00\x3B";

header('HTTP/1.1 200 Ok');
header('Content-type: image/gif');
print($empty_gif);

/*
fastcgi_finish_request();
*/

try {
    if (isset($_GET['id']) && preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/', $_GET['id'], $matches)) {

        $message_id = $matches[1];

        require 'common.php';

        $config = new config();

        $db = init_db($config);

        if(!mysql_query("UPDATE maillog SET opened = 1 where message_id = UNHEX(REPLACE('{$message_id}', '-', ''))" ))
            throw new Exception("Cannot update maillog" . mysql_error ($db));

    }
}

catch(Exception $e) {
    error_log($e->getMessage());
}
