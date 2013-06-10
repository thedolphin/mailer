<?php

define('CR', "\r\n");

class config extends ArrayObject {

    function __construct() {

        parent::__construct();

        $config = parse_ini_file('mailer.ini', true, INI_SCANNER_RAW);

        if (!$config)
            throw new Exception('Cannot read or parse "mailer.ini"');

        $this->exchangeArray($config);

    }
}

class queue {
    private $amqp_conn;
    private $amqp_pub;
    private $amqp_sub;
    private $amqp_ch;

    function __construct(&$config) {

        $this->amqp_conn = new AMQPConnection();

        $this->amqp_conn->setHost($config['amqp']['host']);
        $this->amqp_conn->setPort($config['amqp']['port']);
        $this->amqp_conn->setLogin($config['amqp']['user']);
        $this->amqp_conn->setPassword($config['amqp']['pass']);
        $this->amqp_conn->setVhost($config['amqp']['vhost']);
        if (!$this->amqp_conn->connect())
            throw new Exception('Could not connect to AMQP broker');

    }

    function _init_pub() {

        $this->amqp_pub = new AMQPExchange(new AMQPChannel($this->amqp_conn));
        $this->amqp_pub->SetName('mailq');
    }

    function _init_sub() {

        $this->amqp_sub = new AMQPQueue(new AMQPChannel($this->amqp_conn));
        $this->amqp_sub->SetName('mailq');
    }

    function enqueue($message) {
        if (!$this->amqp_pub)
            $this->_init_pub();

        if(!$this->amqp_pub->publish($message, 'mailq', 0))
            throw new Exception('AMQP: cannot publish');
    }

    function get() {
        if (!$this->amqp_sub)
            $this->_init_sub();

        return $this->amqp_sub->get();
    }

    function ack(&$message) {

        return $this->amqp_sub->ack($message->getDeliveryTag());
    }

}

function str_rand($len) {

    $letters = 'abcefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    while($len--) {
        $char = mt_rand(0, strlen($letters));
        $ret .= $letters[$char];
    }

    return $ret;
}

function encoded_word($str) {

    if(substr($str, 0, 2) == '=?') return $str;

    return '=?utf-8?B?' . base64_encode($str) . '?=';
}

function encode_url($str) {

    return str_replace(array('/', '='), array('-', '_'), base64_encode($str));
}

function decode_url($str) {

    return base64_decode(str_replace(array('-','_'), array('/', '='), $str));
}

function req($url, $postfields, &$curlerror, &$httpcode, &$httpbody) {

    $curl = curl_init($url);
    curl_setopt_array($curl, array(
        CURLOPT_POST => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 30,
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
}

function init_db(&$config) {

    if(!$db = mysql_connect ($config['db']['host'], $config['db']['user'], $config['db']['pass']))
        throw new Exception("Cannot connect to mysql");

    if(!mysql_select_db($config['db']['db'], $db))
        throw new Exception("Cannot connect to database");

    return $db;
}
