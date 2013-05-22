#!/usr/bin/perl

$header = 0;

while(<>) {
    if (/^Diagnostic-Code: (.+)/) {
        $code = $1;
        $header = 1;
    } elsif ($header && /^\s+(.+)/) {
        $code .= " $1";
    } else {
        $header = 0;
    }
}

print "$code\n";
