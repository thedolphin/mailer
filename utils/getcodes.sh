#!/bin/sh

for i in *; do /www/mailer/utils/getcode.pl < $i; done > allcodes
