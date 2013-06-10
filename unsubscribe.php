<?php

header('HTTP/1.1 200 Ok');
header('Content-Type: text/html; charset=utf-8');

?>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Запрос на отмену подписки</title>
</head>
<body>
<?php

try {

    if (isset($_GET['id']) && preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/', $_GET['id'], $matches)) {

        $message_id = $matches[1];

        require 'common.php';

        $config = new config();

        $db = init_db($config);

        if(!$res = mysql_query("SELECT email FROM maillog WHERE message_id = UNHEX(REPLACE('{$message_id}', '-', ''))" ))
            throw new Exception("Cannot select email from maillog" . mysql_error ($db));

        $row = mysql_fetch_row($res);
        $email = $row ? $row[0] : $row;

        if($email) {
            if(isset($_GET['unsub']) && $_GET['unsub'] == 'yes') {

                if(!mysql_query("UPDATE maillog SET unsubscribe = 1 where message_id = UNHEX(REPLACE('{$message_id}', '-', ''))" ))
                    throw new Exception("Cannot update maillog" . mysql_error ($db));

                print("Подписка отменена для адреса {$email}");

            } else {

                print("Пожалуйста, подтвердите запрос на отмену подписки для адреса {$email}.<ul><li><a href=\"http://mailer.wikimart.ru/unsubscribe?id={$message_id}&unsub=yes\">Да, я хочу отписаться от рассылки!</a></li><li><a href=\"http://wikimart.ru\">Нет, я хочу получать спецпредложения от Викимарта</a></li></ul>");
            }

        } else {

            print('К сожалению, идентификатор письма не найден.<br>Вы можете обратиться письмом по адресу <a href="mailto:support@wikimart.ru">support@wikimart.ru</a> для отмены подписки');

        }
    } else {

        print('К сожалению, произошла ошибка обработки запроса.<br>Вы можете обратиться письмом по адресу <a href="mailto:support@wikimart.ru">support@wikimart.ru</a> для отмены подписки или повторить запрос позже');

    }
}

catch(Exception $e) {

    print('К сожалению, произошла ошибка обработки запроса.<br>Вы можете обратиться письмом по адресу <a href="mailto:support@wikimart.ru">support@wikimart.ru</a> для отмены подписки или повторить запрос позже');

    error_log($e->getMessage());
}
?>
</body>
</html>
