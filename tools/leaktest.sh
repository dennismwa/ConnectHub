#!/usr/bin/env bash
# Guest privacy test: no sharp photo paths and no contact details may appear in any public page.
# Usage: CH_CONFIG=... tools/leaktest.sh http://127.0.0.1:8200 <dbname>
B="${1:?base url}"; DB="${2:?db}"
J=$(mktemp); fails=0
ids=$(mysql "$DB" -N -e "select id from users where role in ('user','host') and status='active' order by id limit 12")
hosts=$(mysql "$DB" -N -e "select user_id from host_profiles where approval_status='approved' limit 4")
pages="/ /browse /browse/nairobi /browse/mombasa /hosts /pricing /faq /blog"
for i in $ids; do pages="$pages /u/$i"; done
for h in $hosts; do pages="$pages /hosts/$h"; done
check() { # url
  local body code
  body=$(curl -s -b "$J" -c "$J" -w '\n%{http_code}' "$B$1"); code=${body##*$'\n'}; body=${body%$'\n'*}
  # a visitor must never get a sharp (_l / _m) photo path, a contact detail or a raw id document
  if echo "$body" | grep -qE 'photos/[0-9]{4}/[0-9]{2}/[0-9]+_[0-9a-f]+_(l|m)\.(jpg|webp)'; then echo "LEAK sharp photo in $1 ($code)"; fails=$((fails+1)); fi
  if echo "$body" | grep -qiE 'leak[0-9]+@leakcheck|leakig[0-9]+|07[0-9]{0,2}1{4,}|01[0-9]{0,2}2{4,}'; then echo "LEAK contact in $1 ($code)"; fails=$((fails+1)); fi
  if echo "$body" | grep -qiE 'storage/private|selfie_|id_document'; then echo "LEAK private path in $1"; fails=$((fails+1)); fi
}
for p in $pages; do check "$p"; done
# Sharp photos are only served by /media/photo. With the blur switches on (the default) a visitor must be refused for every
# gate link found on a public page, and the stored files must not be downloadable directly.
gate_open=0
for p in $pages; do
  for u in $(curl -s -b "$J" -c "$J" "$B$p" | sed 's/\\u0026/\&/g; s/&amp;/\&/g' | grep -oE '/media/photo\?[^"'"'"' <>)]+' | sort -u); do
    code=$(curl -s -o /dev/null -w '%{http_code}' -b "$J" "$B$u"); if [ "$code" = "200" ]; then echo "LEAK gate link opens for a visitor: $u (on $p)"; gate_open=$((gate_open+1)); fi
  done
done
fails=$((fails+gate_open))
direct=0
for f in $(mysql "$DB" -N -e "select large_path from user_photos limit 25"; mysql "$DB" -N -e "select thumb_path from user_photos limit 25"); do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$B/uploads/$f"); case "$code" in 403|404) ;; *) echo "LEAK direct file download works: /uploads/$f ($code)"; direct=$((direct+1));; esac
done
fails=$((fails+direct))
echo "  sharp photo gate: $gate_open visitor-openable link(s), $direct direct download(s)"
# JSON endpoints a guest could poke
for u in "/api/explorer/deck" "/api/hosts/search" "/api/members"; do code=$(curl -s -o /dev/null -w '%{http_code}' -b "$J" "$B$u"); echo "  $u -> $code"; done
echo "$(echo $pages | wc -w) public pages scanned, $fails leak(s)"
rm -f "$J"; exit $((fails>0))
