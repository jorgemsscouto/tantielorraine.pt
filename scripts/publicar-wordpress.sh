#!/usr/bin/env bash
# Envia o tema WordPress (wordpress/tantie-lorraine) para o alojamento por FTP, FTPS ou SFTP.
# Lê as credenciais do ficheiro .env na raiz (FTP_HOST, FTP_USER, FTP_PASS, FTP_PORT) e nunca as mostra.
# Requer lftp (macOS: brew install lftp).
#
# Uso:
#   scripts/publicar-wordpress.sh testar    só leitura: testa a ligação e mostra a pasta pública e wp-content/themes
#   scripts/publicar-wordpress.sh simular   só leitura: mostra o que seria enviado, sem enviar
#   scripts/publicar-wordpress.sh enviar    envia o tema para <pasta pública>/wp-content/themes/tantie-lorraine
#                                           (pede confirmação; nunca apaga ficheiros no servidor)
#
# Pasta pública: detetada automaticamente (public_html, httpdocs, www ou htdocs); para a fixar, definir FTP_DIR no .env.
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
cd "$RAIZ"
ACAO="${1:-testar}"
TEMA="wordpress/tantie-lorraine"

[[ -f .env ]] || { echo "Falta o ficheiro .env na raiz do projeto." >&2; exit 1; }
command -v lftp >/dev/null || { echo "Falta o lftp (macOS: brew install lftp)." >&2; exit 1; }
git check-ignore -q .env 2>/dev/null || { echo "O .env não está no .gitignore. Parado por segurança." >&2; exit 1; }

# Lê só as variáveis necessárias, sem executar o .env nem mostrar valores
ler() { { grep -E "^$1=" .env || true; } | tail -n 1 | cut -d= -f2- | sed -e 's/^["'\'']//' -e 's/["'\'']$//'; }
FTP_HOST="$(ler FTP_HOST)"; FTP_USER="$(ler FTP_USER)"; FTP_PASS="$(ler FTP_PASS)"
FTP_PORT="$(ler FTP_PORT)"; FTP_DIR="$(ler FTP_DIR)"
[[ -n "$FTP_HOST" && -n "$FTP_USER" && -n "$FTP_PASS" ]] || { echo "Faltam FTP_HOST, FTP_USER ou FTP_PASS no .env." >&2; exit 1; }
FTP_PORT="${FTP_PORT:-21}"

if [[ "$FTP_PORT" == "22" ]]; then URL="sftp://$FTP_HOST"; else URL="ftp://$FTP_HOST"; fi
export LFTP_PASSWORD="$FTP_PASS"

correr() {
  lftp --env-password -u "$FTP_USER" -p "$FTP_PORT" "$URL" <<LFTP
set cmd:fail-exit yes
set net:max-retries 2
set net:timeout 20
set ftp:ssl-allow yes
set ftp:passive-mode yes
set ssl:verify-certificate yes
$1
bye
LFTP
}

detetar_publica() {
  if [[ -n "$FTP_DIR" ]]; then echo "$FTP_DIR"; return; fi
  local lista
  lista="$(correr "cls -1 /")"
  for d in public_html httpdocs www htdocs; do
    if grep -qx "$d/\?" <<<"$lista"; then echo "/$d"; return; fi
  done
  echo ""
}

case "$ACAO" in
  testar)
    echo "== Ligação: ${URL%%://*} na porta $FTP_PORT (credenciais do .env, não mostradas)"
    echo "== Raiz da conta:"
    correr "cls -la /"
    PUB="$(detetar_publica)"
    if [[ -z "$PUB" ]]; then echo "Pasta pública não detetada: definir FTP_DIR no .env."; exit 0; fi
    echo "== Pasta pública: $PUB"
    correr "cls -la $PUB"
    echo "== Temas instalados ($PUB/wp-content/themes):"
    correr "cls -la $PUB/wp-content/themes" || echo "(sem wp-content/themes: o WordPress pode não estar instalado nesta pasta)"
    ;;
  simular|enviar)
    PUB="$(detetar_publica)"
    [[ -n "$PUB" ]] || { echo "Pasta pública não detetada: definir FTP_DIR no .env." >&2; exit 1; }
    DESTINO="$PUB/wp-content/themes/tantie-lorraine"
    if [[ "$ACAO" == "simular" ]]; then
      echo "== Simulação: $TEMA -> $DESTINO (nada é enviado nem apagado)"
      correr "mirror --reverse --dry-run --verbose --exclude-glob .DS_Store $TEMA $DESTINO"
    else
      echo "Vai enviar $TEMA para $DESTINO. Ficheiros do tema com o mesmo nome são substituídos; nada é apagado."
      read -r -p "Escrever ENVIAR para confirmar: " ok
      [[ "$ok" == "ENVIAR" ]] || { echo "Cancelado."; exit 1; }
      correr "mirror --reverse --verbose --exclude-glob .DS_Store $TEMA $DESTINO"
      echo "Enviado. Ativar em Aparência > Temas."
    fi
    ;;
  *) echo "Ação desconhecida: $ACAO (testar | simular | enviar)" >&2; exit 1 ;;
esac
