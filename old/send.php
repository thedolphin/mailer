<?php

require 'common.php';

if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $client = $_SERVER['HTTP_X_REAL_IP'];
} else {
    $client = $_SERVER['REMOTE_ADDR'];
}

try {

    $config = new config();

    $mail_from  = encoded_word($config['mail']['alias']) . ' <' . $config['mail']['sender'] . '>';

    $reply_to   = isset($_POST['ReplyTo'])       ? $_POST['ReplyTo']     : $config['mail']['reply'];
    $content_t  = isset($_POST['ContentType'])   ? $_POST['ContentType'] : 'text/plain';
    $campaign   = isset($_POST['Campaign'])      ? $_POST['Campaign']    : '0';
    $headers    = isset($_POST['Headers'])       ? $_POST['Headers']     : '';
    $message_id = isset($_POST['MessageID'])     ? $_POST['MessageID']   : exec('uuidgen');

    $wait       = isset($_POST['WaitForMailer']) && $_POST['WaitForMailer'] == 'true' ? true : false;

    if (!is_numeric($campaign))
        throw new Exception('Campaign must be numeric');

    if (!isset($_POST['To']))
        throw new Exception('No recipient address');

    if (!isset($_POST['Message']))
        throw new Exception('Nothing to send');

    if (isset($_POST['Subject']))
        $subject = encoded_word($_POST['Subject']);
    else
        throw new Exception('No subject specified');

    $db = init_db($config);

    if (!$wait) {
        header('HTTP/1.1 200 Ok');
        fastcgi_finish_request();
    }

    $rcpt_to = $_POST['To'];
    $raw_rcpt_to = preg_match('/<(.+)>/', $rcpt_to, $match) ? $match[1] : $rcpt_to;

    $body = $_POST['Message'];

    $hostname = gethostname();

    $message =  "Received: from {$client} by wikimart-mailer at {$hostname}" . CR .
                "Message-Id: <wikimart-{$message_id}@{$hostname}>" . CR .
                "From: {$mail_from}" . CR .
                "To: {$rcpt_to}" . CR .
                "Reply-To: {$reply_to}" . CR .
                "Subject: {$subject}";

    if ($headers)
        $message .= CR . $headers;

    $message .= CR . CR . $body;

    $handle = popen('/usr/sbin/sendmail -t -i -f "' . $config['mail']['sender'] . '"', 'w');

    if (!$handle)
        throw new Exception("Cannot fork sendmail");

    $res = fwrite($handle, $message);

    pclose($handle);

    if (!$res)
        throw new Exception("Cannot write to sendmail");

    if(!mysql_query("INSERT INTO maillog(`email`,`message_id`, `campaign`) VALUES ('{$raw_rcpt_to}', UNHEX(REPLACE('{$message_id}', '-', '')), {$campaign})" ))
        throw new Exception("Cannot insert record to maillog" . mysql_error ($db));

    if ($wait)
        header('HTTP/1.1 200 Ok');
}

catch (Exception $e) {
    $msg = $e->getMessage();
    error_log($msg);

    if ($wait) {
        header('HTTP/1.1 500 Exception');
        print($msg);
    }
}
