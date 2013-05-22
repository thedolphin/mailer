#!/bin/sh

while read email; do
    echo ${email}

./sendcampaign.php \
"${email}" \
"Эксклюзивные цены на автокресла только на Викимарте!" \
/home/dolphin/mail/20130521/20130521_carseat_ns_plain.html \
http://mailer.wikimart.ru/promo/20130521_carseat_ns.html 4

done < /home/dolphin/mail/20130521/carseat_ns.csv

