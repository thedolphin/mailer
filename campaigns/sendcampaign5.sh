#!/bin/sh

while read -a email; do
echo ${email}

./sendcampaign.php \
"${email[0]}" \
"Только для Вас красивые и элегантные сумки со скидкой 20%!" \
/home/dolphin/mail/20130604/20130604_leoventoni_plain.html \
http://mailer.wikimart.ru/promo/20130604_leoventoni.html 5 \
"${email[1]}"

done < /home/dolphin/mail/20130604/0604_bags.csv
