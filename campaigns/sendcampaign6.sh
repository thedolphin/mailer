#!/bin/sh

while read -a email; do
echo ${email[1]}

./sendcampaign.php \
"${email[1]}" \
"Получите серьги с фианитами совершенно бесплатно! Промо-код внутри!" \
/home/dolphin/mail/20130605/201306_sunlight_plain.html \
" " 6 "${email[0]}"

done < /home/dolphin/mail/20130605/0605_sunlight.csv
