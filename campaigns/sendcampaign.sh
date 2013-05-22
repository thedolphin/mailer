#!/bin/sh

while read email; do
    echo ${email}

./sendcampaign.php \
"${email}" \
"Эксклюзивные цены на автокресла только на Викимарте!" \
/home/dolphin/mail/20130521/20130521_carseat_plain.html \
http://mailer.wikimart.ru/promo/20130521_carseat.html 3

done < /home/dolphin/mail/20130521/carseat.csv

