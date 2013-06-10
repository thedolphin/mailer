#!/bin/sh

while read email; do
echo ${email}

./sendcampaign.php \
"${email}" \
"Вы поймали свою удачу за хвост! Расскажите как это было!" \
/home/dolphin/mail/20130607-feedback_apr/0607_feedback_apr_inline.html \
http://mailer.wikimart.ru/promo/0607_feedback_apr.html 9 ""

done < /home/dolphin/mail/20130607-feedback_apr/0607_feedback_apr.csv

