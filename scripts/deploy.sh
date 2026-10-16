#!/usr/bin/env bash
# Publica o site no alojamento por FTP com TLS (FTPS), espelhando só os ficheiros públicos.
# Requer lftp e um ficheiro .deploy.env na raiz (ver .deploy.env.example).
# Uso: scripts/deploy.sh             publica (envia ficheiros novos e alterados)
#      scripts/deploy.sh --simular   mostra o que mudaria, sem enviar
#      scripts/deploy.sh --apagar    publica e apaga no servidor o que já não existe aqui
#                                    (usar só depois de confirmar com --simular --apagar)
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
cd "$RAIZ"

if [[ ! -f .deploy.env ]]; then
  echo "Falta .deploy.env (copiar de .deploy.env.example e preencher)." >&2
  exit 1
fi
# shellcheck disable=SC1091
source .deploy.env
: "${FTP_HOST:?}" "${FTP_USER:?}" "${FTP_PASS:?}" "${FTP_DIR:?}"

command -v lftp >/dev/null || { echo "Instalar lftp primeiro." >&2; exit 1; }

OPCOES=""
for arg in "$@"; do
  case "$arg" in
    --simular) OPCOES="$OPCOES --dry-run" ;;
    --apagar)  OPCOES="$OPCOES --delete" ;;
    *) echo "Opção desconhecida: $arg" >&2; exit 1 ;;
  esac
done

lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<LFTP
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ssl:verify-certificate yes
mirror --reverse --verbose $OPCOES \
  --exclude-glob .git/ \
  --exclude-glob .claude/ \
  --exclude-glob .git* \
  --exclude-glob .deploy.env* \
  --exclude-glob .DS_Store \
  --exclude-glob scripts/ \
  --exclude-glob originais/ \
  --exclude-glob README.md \
  --exclude-glob CLAUDE.md \
  --exclude-glob .well-known/ \
  --exclude-glob cgi-bin/ \
  . "$FTP_DIR"
bye
LFTP
