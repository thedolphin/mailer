#!/usr/bin/php
<?php

require 'common.php';

/*
$mail = array(
        'headers' => array(),
        'data' => $message,
        'campaign' => 0
    )

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


        $query = "SELECT bounced, unsubscribe FROM maillog WHERE email = '{$escaped_rcpt_to}' and (bounced = 1 or unsubscribe = 1)";

        if(!$res = mysql_query($query))
            throw new Exception("Cannot select email from maillog: " . mysql_error ($db) . "\nQuery: $query");

        while ($row = mysql_fetch_row($res)) {

            if ($row[0] == 1) {
                print "Won't sent to {$raw_rcpt_to}: message to this email was bounced lately\n";
                $queue->ack($message);
                continue 2;
            }

            if ($row[1] == 1 && $mail['campaign'] > 0) {
                print "Won't send ro {$raw_rcpt_to}: recipient unsubscribed from mailing\n";
                $queue->ack($message);
                continue 2;
            }
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
            if(substr($mail['headers']['Subject'], 0, 2) != '=?')
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

        $campaign = $mail['campaign'];

        if(!mysql_query("INSERT INTO maillog(`email`,`message_id`, `campaign`) VALUES ('{$escaped_rcpt_to}', UNHEX(REPLACE('{$message_id}', '-', '')), {$campaign})" ))
            throw new Exception("Cannot insert record to maillog" . mysql_error ($db));

        print "Mail for {$mail['headers']['To']} enqueued, campaign {$mail['campaign']}\n";


    }
}

catch (Exception $e) {
    $msg = $e->getMessage();
    error_log($msg);
}
