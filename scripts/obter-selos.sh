#!/usr/bin/env bash
# Descarrega para assets/img/selos/ os selos que faltam, a partir dos ficheiros originais do site do Reino Unido.
# Os que já existem não são tocados. O Great Taste 2026 não está nesse site: tem de vir da marca
# (acrescentar à mão em assets/img/selos/great-taste-2026.png).
set -uo pipefail
cd "$(dirname "$0")/../assets/img"
mkdir -p selos
FALHAS=0
while read -r nome url; do
  [[ -s "selos/$nome" ]] && continue
  if curl -fsS --retry 3 -o "selos/$nome.tmp" "$url"; then mv "selos/$nome.tmp" "selos/$nome"; echo "   obtido: $nome"
  else rm -f "selos/$nome.tmp"; echo "   falhou: $nome" >&2; FALHAS=1; fi
done <<'LISTA'
great-taste-producer.png https://static.wixstatic.com/media/0e8816_8ed9b46635bc4fc6be9a014622398b08~mv2.png
great-taste-2019-1-estrela.png https://static.wixstatic.com/media/0e8816_31d838a940264e64a4700c7fd0a04d0d~mv2.png
great-taste-2021-1-estrela.png https://static.wixstatic.com/media/0e8816_e0d14e5540de4990a0f2005f40b02732~mv2.png
great-taste-2024-2-estrelas.png https://static.wixstatic.com/media/0e8816_94a846c201984143887fba1eddc27b0b~mv2.png
food-hygiene-rating-5.png https://static.wixstatic.com/media/0e8816_a869c19a6bcc4a19afb12e17d96ac6a1~mv2.png
LISTA
exit $FALHAS
