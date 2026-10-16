#!/usr/bin/env bash
# Publica o site estático no alojamento da Amen por FTP (com TLS quando o servidor o aceita), usando o curl
# que já vem instalado no macOS. Credenciais lidas do .env na raiz (FTP_HOST, FTP_USER, FTP_PASS, FTP_PORT e,
# opcionais, FTP_DIR e FTP_VERIFICAR_CERTIFICADO=nao); nunca são mostradas nem escritas em disco.
#
# Uso:
#   scripts/deploy.sh testar                  só leitura: testa a ligação e mostra a pasta de entrada e a pasta pública
#   scripts/deploy.sh backup                  só leitura: descarrega a pasta pública para backups/amen-<data>/
#   scripts/deploy.sh enviar [--simular]      envia todos os ficheiros do site; não apaga nada no servidor
#   scripts/deploy.sh retirar-wordpress [--simular]
#                                             move os ficheiros do WordPress da pasta pública para uma pasta
#                                             wordpress-desativado-<data> fora do site; nada é apagado
#
# Ordem para trocar o WordPress pelo site estático: testar, backup, enviar --simular, enviar,
# retirar-wordpress --simular, retirar-wordpress.
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
cd "$RAIZ"
ACAO="${1:-}"; [[ $# -gt 0 ]] && shift
SIMULAR=0
for arg in "$@"; do
  case "$arg" in
    --simular) SIMULAR=1 ;;
    *) echo "Opção desconhecida: $arg" >&2; exit 1 ;;
  esac
done
DATA="$(date +%Y%m%d-%H%M)"

# Credenciais: .env (ou .deploy.env, nome antigo)
ENVF=".env"; [[ -f "$ENVF" ]] || ENVF=".deploy.env"
[[ -f "$ENVF" ]] || { echo "Falta o ficheiro .env na raiz (ver .env.example)." >&2; exit 1; }
# Num repositório Git, o .env tem de estar ignorado (numa cópia descarregada em ZIP não há Git, logo não há risco)
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  git check-ignore -q "$ENVF" || { echo "O $ENVF não está no .gitignore. Parado por segurança." >&2; exit 1; }
fi
command -v curl >/dev/null || { echo "Falta o curl." >&2; exit 1; }

ler() { { grep -E "^$1=" "$ENVF" || true; } | tail -n 1 | cut -d= -f2- | tr -d '\r' | sed -e 's/^["'\'']//' -e 's/["'\'']$//'; }
FTP_HOST="$(ler FTP_HOST)"; FTP_USER="$(ler FTP_USER)"; FTP_PASS="$(ler FTP_PASS)"
FTP_PORT="$(ler FTP_PORT)"; FTP_DIR="$(ler FTP_DIR)"; VERIFICAR="$(ler FTP_VERIFICAR_CERTIFICADO)"
[[ -n "$FTP_HOST" && -n "$FTP_USER" && -n "$FTP_PASS" ]] || { echo "Faltam FTP_HOST, FTP_USER ou FTP_PASS no $ENVF." >&2; exit 1; }
FTP_PORT="${FTP_PORT:-21}"
[[ "$FTP_PORT" == "22" ]] && { echo "Este script usa FTP (porta 21); SFTP não é suportado." >&2; exit 1; }
BASE="ftp://$FTP_HOST:$FTP_PORT"

# As credenciais seguem para o curl por um ficheiro de configuração virtual (não aparecem na lista de processos)
esc() { printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'; }
CRED="user = \"$(esc "$FTP_USER"):$(esc "$FTP_PASS")\""
TLS="--ssl"; [[ "$VERIFICAR" == "nao" ]] && TLS="--ssl --insecure"
ftp() { curl --silent --show-error $TLS --connect-timeout 20 -K <(printf '%s\n' "$CRED") "$@"; }
# Igual, com uma lista de transferências (pares url/output ou upload-file/url) no mesmo ficheiro virtual
ftp_lote() { local lote="$1"; shift; curl --silent --show-error $TLS --connect-timeout 20 -K <(printf '%s\n' "$CRED"; cat "$lote") "$@"; }

# Codifica um caminho para URL, byte a byte, mantendo letras, números, "/", "-", ".", "_" e "~"
# (via od, para funcionar igual no bash 3.2 do macOS e com nomes acentuados)
enc() {
  local hex h out=""
  hex="$(printf '%s' "$1" | od -An -v -tx1 | tr -d ' \n')"
  while [[ -n "$hex" ]]; do
    h="${hex:0:2}"; hex="${hex:2}"
    case "$h" in
      2[dDeEfF]|3[0-9]|4[1-9a-fA-F]|5[0-9aAfF]|6[1-9a-fA-F]|7[0-9aAeE]) out="$out$(printf "\\x$h")" ;;
      *) out="$out%$(printf '%s' "$h" | tr 'a-f' 'A-F')" ;;
    esac
  done
  printf '%s' "$out"
}
url() { if [[ -z "$1" ]]; then printf '%s/' "$BASE"; else printf '%s/%s' "$BASE" "$(enc "$1")"; fi; }

# Lista uma pasta remota: linhas "tipo<TAB>nome" (d = pasta, - = ficheiro, l = atalho)
listar() {
  ftp "$(url "$1")/" | tr -d '\r' | awk '{
    t = substr($1, 1, 1); n = ""
    for (i = 9; i <= NF; i++) n = n (i > 9 ? " " : "") $i
    sub(/ -> .*/, "", n)
    if (n != "" && n != "." && n != "..") print t "\t" n
  }'
}
listar_rec() {
  local dir="$1" t n
  listar "$dir" | while IFS="$(printf '\t')" read -r t n; do
    case "$t" in
      d) listar_rec "$dir/$n" ;;
      -) printf '%s\n' "$dir/$n" ;;
    esac
  done
}

# Pasta pública: FTP_DIR, ou public_html/httpdocs/www/htdocs na pasta de entrada, ou a própria pasta de entrada
detetar_publica() {
  if [[ -n "$FTP_DIR" ]]; then printf '%s' "${FTP_DIR%/}"; return; fi
  local nomes
  nomes="$(listar "" | cut -f2)"
  for d in public_html httpdocs www htdocs; do
    if grep -qx "$d" <<<"$nomes"; then printf '%s' "$d"; return; fi
  done
  if grep -qxE "index\.(php|html)|wp-config\.php" <<<"$nomes"; then printf '%s' ""; return; fi
  printf '%s' "?"
}
exigir_publica() {
  PUB="$(detetar_publica)"
  [[ "$PUB" == "?" ]] && { echo "Pasta pública não detetada: definir FTP_DIR no .env." >&2; exit 1; }
  PUB_ROTULO="${PUB:-(pasta de entrada)}"
}
caminho() { if [[ -z "$PUB" ]]; then printf '%s' "$1"; else printf '%s/%s' "$PUB" "$1"; fi; }

# Ficheiros do WordPress na pasta pública (o .htaccess não entra: depois do envio é o do site estático)
WP_NOMES='^(index\.php|license\.txt|licenca\.txt|readme\.html|leiame\.html|xmlrpc\.php|wp-.+)$'

case "$ACAO" in
  testar)
    echo "== Ligação FTP a $FTP_HOST, porta $FTP_PORT (credenciais do $ENVF, não mostradas)"
    echo "== Pasta de entrada:"; ftp "$(url "")" | tr -d '\r'
    exigir_publica
    echo "== Pasta pública: $PUB_ROTULO"; ftp "$(url "$PUB")/" | tr -d '\r'
    ;;

  backup)
    exigir_publica
    DEST="backups/amen-$DATA"; mkdir -p "$DEST"
    echo "== A listar $PUB_ROTULO (pode demorar uns minutos num WordPress)..."
    LISTA="$DEST/.ficheiros.txt"
    if [[ -z "$PUB" ]]; then listar_rec "" | sed 's#^/##' > "$LISTA"; else listar_rec "$PUB" > "$LISTA"; fi
    echo "   $(wc -l < "$LISTA" | tr -d ' ') ficheiros"
    LOTE="$(mktemp)"; trap 'rm -f "$LOTE"' EXIT
    while IFS= read -r f; do
      rel="${f#"$PUB"/}"
      printf 'url = "%s"\noutput = "%s"\n' "$(esc "$(url "$f")")" "$(esc "$DEST/$rel")" >> "$LOTE"
    done < "$LISTA"
    echo "== A descarregar para $DEST (só leitura no servidor)..."
    ftp_lote "$LOTE" --create-dirs
    # Alguns servidores escondem os ficheiros começados por ponto na listagem: garantir o .htaccess
    [[ -f "$DEST/.htaccess" ]] || ftp -o "$DEST/.htaccess" "$(url "$(caminho .htaccess)")" 2>/dev/null || rm -f "$DEST/.htaccess"
    echo "Cópia feita: $DEST ($(du -sh "$DEST" | cut -f1)). A pasta backups/ não vai para o Git."
    echo "Base de dados: exportar também no phpMyAdmin (Exportar > Rápido > SQL) e guardar junto desta cópia."
    ;;

  enviar)
    exigir_publica
    LISTA="$(find . \( -name .git -o -name .claude -o -name scripts -o -name backups -o -name originais -o -name node_modules \) -prune -o \
      -type f ! -name '.git*' ! -name '.env*' ! -name '.deploy.env*' ! -name '.DS_Store' ! -name 'README.md' ! -name 'CLAUDE.md' \
      -print | sed 's#^\./##' | sort)"
    echo "== Ficheiros do site a enviar para $PUB_ROTULO: $(wc -l <<<"$LISTA" | tr -d ' ')"
    if [[ $SIMULAR -eq 1 ]]; then sed 's/^/   /' <<<"$LISTA"; echo "Simulação: nada foi enviado."; exit 0; fi
    LOTE="$(mktemp)"; trap 'rm -f "$LOTE"' EXIT
    while IFS= read -r f; do
      printf 'upload-file = "%s"\nurl = "%s"\n' "$(esc "$f")" "$(esc "$(url "$(caminho "$f")")")" >> "$LOTE"
    done <<<"$LISTA"
    ftp_lote "$LOTE" --ftp-create-dirs
    echo "Enviado. Abrir o site e confirmar antes de retirar o WordPress."
    ;;

  retirar-wordpress)
    exigir_publica
    NOMES="$(listar "$PUB" | cut -f2)"
    grep -qx "index.html" <<<"$NOMES" || { echo "Não há index.html em $PUB_ROTULO: enviar primeiro o site (scripts/deploy.sh enviar)." >&2; exit 1; }
    ITENS="$(grep -E "$WP_NOMES" <<<"$NOMES" || true)"
    [[ -n "$ITENS" ]] || { echo "Não há ficheiros do WordPress em $PUB_ROTULO."; exit 0; }
    DEST="wordpress-desativado-$DATA"
    [[ "$PUB" == /* ]] && DEST="/$DEST"
    BLOQUEAR=0; [[ -z "$PUB" ]] && BLOQUEAR=1   # a pasta de entrada é a pasta pública: o destino fica dentro dela
    echo "== Ficheiros do WordPress em $PUB_ROTULO que serão movidos para $DEST:"
    sed 's/^/   /' <<<"$ITENS"
    echo "== Ficam no lugar (não são do WordPress):"
    { grep -vE "$WP_NOMES" <<<"$NOMES" || true; } | sed 's/^/   /'
    [[ $BLOQUEAR -eq 1 ]] && echo "(A pasta de destino fica dentro da pasta pública; será bloqueada ao acesso pela web.)"
    if [[ $SIMULAR -eq 1 ]]; then echo "Simulação: nada foi movido."; exit 0; fi
    read -r -p "Escrever RETIRAR para mover estes ficheiros: " ok
    [[ "$ok" == "RETIRAR" ]] || { echo "Cancelado."; exit 1; }
    ARGS=( -Q "MKD $DEST" )
    while IFS= read -r i; do ARGS+=( -Q "RNFR $(caminho "$i")" -Q "RNTO $DEST/$i" ); done <<<"$ITENS"
    ftp "${ARGS[@]}" --list-only "$(url "")" > /dev/null
    if [[ $BLOQUEAR -eq 1 ]]; then
      printf 'Require all denied\n' | ftp -T - "$(url "$DEST")/.htaccess"
    fi
    echo "WordPress movido para $DEST. Para repor, mover os ficheiros de volta para $PUB_ROTULO."
    ;;

  *)
    sed -n '2,15p' "$0" | sed 's/^# \{0,1\}//'
    exit 1
    ;;
esac
