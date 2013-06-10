#!/bin/sh

while read email; do
echo ${email}

./sendcampaign.php \
"${email}" \
"Покоряйте новые вершины! Все самое лучшее для отдыха на природе." \
/home/dolphin/mail/20130607-camping_ns/0607_camping_ns_inline.html \
http://mailer.wikimart.ru/promo/0607_camping_ns_landing.html 8 ""

done < /home/dolphin/mail/20130607-camping_ns/0607_camping_ns.csv

