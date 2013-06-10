#!/usr/bin/php
<?php

require 'common.php';

/*

Message in amqp mailqueue must be serialized array.

$mail = array(
        'headers' => array(),
        'data' => $message,
        'campaign' => 0
    )

'headers' field:

To:         required, email address, may be with alias, i.e. "user@host.dom" or "User Name <user@host.dom>", may be encoded, will be encoded otherwise
Subject:    required, may be encoded, will be encoded otherwise
Reply-To:   default used if omited
Date:       current date used if omited
Message-Id: tracking UUID, may be omited in case of single email, must exist in case of campaign when used in mail body
From:       will be replaced anyway with address of bounce catcher

Any other headers will be added without modifications

'data' field: contains message body, each line must end with '\r\n', otherwise DKIM signing will fail.

'campaign' filed: distinguishing number to be stored in database.
If more than 0, the recipient address will be checked against unsubscribed list.
If 0 or omited, the mail considered to be regular.

Each mail recipient address will be checked against bounced list

*/

try {

    $config = new config();
    $queue = new queue($config);

    $hostname = gethostname();
    $db = init_db($config);

    while($message = $queue->get()) {

        $mail_text = '';

        if (!$mail = unserialize($message->getBody()))
            throw new Exception('Cannot unserialize message');

        if (!isset($mail['headers']['To']))
            throw new Exception('No recipient address');

        $raw_rcpt_to = preg_match('/<(.+)>/', $mail['headers']['To'], $match) ? $match[1] : $mail['headers']['To']; 
        $raw_rcpt_to = strtolower(trim($raw_rcpt_to));
        $escaped_rcpt_to = mysql_real_escape_string($raw_rcpt_to);

        if (isset($mail['campaign'])) {
            if (!is_numeric($mail['campaign']))
                throw new Exception('Campaign must be numeric');
        } else {
            $mail['campaign'] = 0;
        }

        $q = "SELECT id FROM maillog WHERE email = '{$escaped_rcpt_to}' and bounced = 1 LIMIT 1";

        if ($mail['campaign'] > 0)
            $q .= " UNION SELECT id FROM maillog WHERE email = '{$escaped_rcpt_to}' and unsubscribe = 1 LIMIT 1";

        if (!$res = mysql_query($q))
            throw new Exception("Cannot select email from maillog: " . mysql_error ($db));

        if (mysql_num_rows($res) > 0) {
            print "Won't send to {$raw_rcpt_to}: message to this email was bounced lately or recipient unsubscribed from mailing\n";
            $queue->ack($message);
            continue;
        }

        if (!isset($mail['headers']['Reply-To']))   $mail['headers']['Reply-To'] = $config['mail']['reply'];
        if (!isset($mail['headers']['Date']))       $mail['headers']['Date'] = date(DATE_RFC822);

        if (isset($mail['headers']['Message-Id']))
            $message_id = $mail['headers']['Message-Id'];
        else
            $message_id = exec('uuidgen');

        $mail['headers']['Message-Id'] = "<wikimart-{$message_id}@{$hostname}>";

        if (!isset($mail['data']))
            throw new Exception('Nothing to send');

        if (isset($mail['headers']['Subject'])) {
            $mail['headers']['Subject'] = encoded_word($mail['headers']['Subject']);
        } else
            throw new Exception('No subject specified');


        $mail['headers']['From']  = encoded_word($config['mail']['alias']) . ' <' . $config['mail']['sender'] . '>';

        $client = isset($mail['sender']) ? $mail['sender'] : 'queue';

        $mail['headers']['Received'] = "from {$client} by wikimart-mailer at {$hostname}";

        foreach ($mail['headers'] as $name => $value) {
            $mail_text .= $name .': '. $value . CR;
        }

        $mail_text .= CR . $mail['data'];

        $handle = popen('/usr/sbin/sendmail -t -i -f "' . $config['mail']['sender'] . '"', 'w');

        if (!$handle)
            throw new Exception("Cannot fork sendmail");

        $res = fwrite($handle, $mail_text);

        pclose($handle);

        if (!$res)
            throw new Exception("Cannot write to sendmail");

        $queue->ack($message);

        print "Mail for {$mail['headers']['To']} enqueued, campaign {$mail['campaign']}\n";

        $campaign = $mail['campaign'];

        if(!mysql_query("INSERT INTO maillog(`email`,`message_id`, `campaign`) VALUES ('{$escaped_rcpt_to}', UNHEX(REPLACE('{$message_id}', '-', '')), {$campaign})" ))
            throw new Exception("Cannot insert record to maillog" . mysql_error ($db));

    }
}

catch (Exception $e) {
    $msg = $e->getMessage();
    error_log($msg);
}
