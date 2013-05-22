<?php

class message {
    public $headers;
    public $body;

    function __construct($body) {

        $header = true;
        $lastheader = false;

        list($headers, $this->body) = preg_split('/\R{2}/', $body, 2);

        $tok = strtok($headers, "\n\r");

        while (($tok !== false) && $header) {

            if (preg_match('/^([A-Za-z\-]+): (.+)$/', $tok, $matches)) {
                $this->headers[$matches[1]] = $matches[2];
                $lastheader = $matches[1];
            }

            if (preg_match('/^\s+(.+)$/', $tok, $matches) && $lastheader) {
                $this->headers[$lastheader] .= ' ' . $matches[1];
            }

            $tok = strtok("\n\r");
        }

        if (isset($this->headers['Content-Type']) && preg_match('/multipart.*boundary="(.+?)"/', $this->headers['Content-Type'], $matches)) {
            print "MULTIPART: {$matches[1]}\n";
        }
    }
}


$email = new message(file_get_contents('/www/bounced/20130426/182339.4afba91e-ae5f-11e2-83be-00163e18f7a8'));

print_r($email->headers);
