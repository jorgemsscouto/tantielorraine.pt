#!/usr/bin/env bash
# Avisa os motores de busca que aceitam o protocolo IndexNow (Bing, Yandex, Seznam, Naver e outros) das páginas
# alteradas, para serem visitadas logo, sem esperar pela próxima passagem. A chave é pública por natureza e o
# ficheiro de verificação está na raiz do site (2a7da8b28ea4768cda1e5c0ec002a34c.txt).
# Uso: scripts/indexnow.sh pagina.html [...]   Sem argumentos, avisa de todas as páginas do sitemap.xml.
set -uo pipefail
cd "$(dirname "$0")/.."
SITE=https://tantielorraine.pt
CHAVE=2a7da8b28ea4768cda1e5c0ec002a34c
URLS=()
if [[ $# -eq 0 ]]; then
  while IFS= read -r u; do URLS+=("$u"); done < <(grep -o '<loc>[^<]*' sitemap.xml | sed 's/<loc>//')
else
  for f in "$@"; do
    case "$f" in
      index.html) URLS+=("$SITE/") ;;
      404.html|*/*) ;;
      *.html) URLS+=("$SITE/$f") ;;
    esac
  done
fi
[[ ${#URLS[@]} -gt 0 ]] || { echo "Nenhuma página para avisar."; exit 0; }
LISTA="$(printf '"%s",' "${URLS[@]}")"
CORPO="{\"host\":\"tantielorraine.pt\",\"key\":\"$CHAVE\",\"keyLocation\":\"$SITE/$CHAVE.txt\",\"urlList\":[${LISTA%,}]}"
CODIGO="$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 -X POST https://api.indexnow.org/indexnow \
  -H 'Content-Type: application/json; charset=utf-8' --data "$CORPO")"
case "$CODIGO" in
  200|202) echo "IndexNow: ${#URLS[@]} página(s) avisada(s)." ;;
  *) echo "::warning::IndexNow respondeu $CODIGO; as páginas serão encontradas na próxima passagem dos motores de busca." ;;
esac
exit 0
