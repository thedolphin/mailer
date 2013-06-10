#!/bin/sh

while read email; do
echo ${email}

./sendcampaign.php \
"${email}" \
"Покоряйте новые вершины! Все самое лучшее для отдыха на природе." \
/home/dolphin/mail/20130607-camping/0607_camping_inline.html \
http://mailer.wikimart.ru/promo/0607_camping_landing.html 7 ""

done < /home/dolphin/mail/20130607-camping/0607_camping.csv
