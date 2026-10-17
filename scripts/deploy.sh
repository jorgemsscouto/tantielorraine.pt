#!/usr/bin/env bash
# Publica o site estático no alojamento da Amen por FTP (com TLS quando o servidor o aceita), usando o curl
# que já vem instalado no macOS. Credenciais lidas do .env na raiz (FTP_HOST, FTP_USER, FTP_PASS, FTP_PORT e,
# opcionais, FTP_DIR e FTP_VERIFICAR_CERTIFICADO=nao; para o formulário, SMTP_USER e SMTP_PASS); nunca são
# mostradas nem escritas em disco no computador.
#
# Uso:
#   scripts/deploy.sh testar                  só leitura: testa a ligação e mostra a pasta de entrada e a pasta pública
#   scripts/deploy.sh backup                  só leitura: guarda .htaccess, wp-config.php e index.php em backups/amen-<data>/
#   scripts/deploy.sh enviar [--simular]      envia todos os ficheiros do site; não apaga nada no servidor
#   scripts/deploy.sh retirar-wordpress [--simular]
#                                             move os ficheiros do WordPress da pasta pública para uma pasta
#                                             wordpress-desativado-<data> fora do site; nada é apagado
#   scripts/deploy.sh configurar-email        põe no servidor, fora da pasta pública, a configuração de envio do
#                                             formulário (config-email.php) e envia uma mensagem de teste
#
# Ordem para trocar o WordPress pelo site estático: testar, backup, enviar --simular, enviar (guarda antes o .htaccess),
# retirar-wordpress --simular, retirar-wordpress. No alojamento da Amen (nginx, que ignora o .htaccess) o site estático
# só aparece depois de retirar o WordPress.
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
REPETIR="--retry 4 --retry-delay 10 --retry-connrefused"
ftp() { curl --silent --show-error $TLS --connect-timeout 20 $REPETIR -K <(printf '%s\n' "$CRED") "$@"; }
# Igual, com uma lista de transferências (pares url/output ou upload-file/url) no mesmo ficheiro virtual
ftp_lote() { local lote="$1"; shift; curl --silent --show-error $TLS --connect-timeout 20 $REPETIR -K <(printf '%s\n' "$CRED"; cat "$lote") "$@"; }

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

# Descarrega ficheiros da pasta pública para uma pasta local, numa só ligação; os que não existirem são ignorados
guardar_originais() {
  local dest="$1" f lote; shift
  mkdir -p "$dest"; lote="$(mktemp)"
  for f in "$@"; do printf 'url = "%s"\noutput = "%s"\n' "$(esc "$(url "$(caminho "$f")")")" "$(esc "$dest/$f")" >> "$lote"; done
  ftp_lote "$lote" 2>/dev/null || true
  rm -f "$lote"
  for f in "$@"; do [[ -s "$dest/$f" ]] && echo "   guardado: $dest/$f" || rm -f "$dest/$f"; done
}

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
    guardar_originais "$DEST" .htaccess wp-config.php index.php
    echo "Para uma cópia integral do WordPress, usar as cópias de segurança do painel da Amen."
    echo "Base de dados: exportar também no phpMyAdmin (Exportar > Rápido > SQL) e guardar junto desta cópia."
    ;;

  enviar)
    exigir_publica
    LISTA="$(find . \( -name .git -o -name .claude -o -name scripts -o -name backups -o -name originais -o -name node_modules \) -prune -o \
      -type f ! -name '.git*' ! -name '.env*' ! -name '.deploy.env*' ! -name '.DS_Store' ! -name 'README.md' ! -name 'CLAUDE.md' \
      ! -name 'config-email.php' \
      -print | sed 's#^\./##' | sort)"
    echo "== Ficheiros do site a enviar para $PUB_ROTULO: $(wc -l <<<"$LISTA" | tr -d ' ')"
    if [[ $SIMULAR -eq 1 ]]; then sed 's/^/   /' <<<"$LISTA"; echo "Simulação: nada foi enviado."; exit 0; fi
    echo "== A guardar o .htaccess atual do servidor antes de o substituir:"
    guardar_originais "backups/antes-do-envio-$DATA" .htaccess
    LOTE="$(mktemp)"; trap 'rm -f "$LOTE"' EXIT
    while IFS= read -r f; do
      printf 'upload-file = "%s"\nurl = "%s"\n' "$(esc "$f")" "$(esc "$(url "$(caminho "$f")")")" >> "$LOTE"
    done <<<"$LISTA"
    ftp_lote "$LOTE" --ftp-create-dirs
    echo "Enviado. Se ainda houver WordPress na pasta pública, o servidor continua a mostrá-lo: correr retirar-wordpress."
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

  configurar-email)
    exigir_publica
    SMTP_HOST="$(ler SMTP_HOST)"; SMTP_PORT="$(ler SMTP_PORT)"; SMTP_USER="$(ler SMTP_USER)"; SMTP_PASS="$(ler SMTP_PASS)"
    SMTP_HOST="${SMTP_HOST:-smtp-pt.securemail.pro}"; SMTP_PORT="${SMTP_PORT:-465}"
    SITE="$(ler SITE_URL)"; SITE="${SITE:-https://tantielorraine.pt}"; SITE="${SITE%/}"
    [[ -n "$SMTP_USER" && -n "$SMTP_PASS" ]] || { echo "Faltam SMTP_USER ou SMTP_PASS no $ENVF (ver .env.example)." >&2; exit 1; }
    [[ "$SMTP_PORT" =~ ^[0-9]+$ ]] || { echo "SMTP_PORT inválida no $ENVF." >&2; exit 1; }
    verificar() { curl --silent --max-time 20 "$SITE/contacto.php?verificar" 2>/dev/null || true; }
    [[ "$(verificar)" == *'"configurado"'* ]] || { echo "O $SITE/contacto.php não responde: enviar primeiro o site (scripts/deploy.sh enviar)." >&2; exit 1; }
    # Texto para uma string PHP entre plicas; o ficheiro segue para o servidor pela entrada do curl, sem passar pelo disco
    pesc() { printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e "s/'/\\\\'/g"; }
    config() {
      printf '%s\n' "<?php" "// Gerado por scripts/deploy.sh configurar-email em $DATA. Tem a palavra-passe do email: não copiar nem versionar." \
        "return [" "  'host' => '$(pesc "$SMTP_HOST")'," "  'porta' => $SMTP_PORT," \
        "  'utilizador' => '$(pesc "$SMTP_USER")'," "  'palavra_passe' => '$(pesc "$SMTP_PASS")'," "];"
    }
    DENTRO="$(caminho config-email.php)"
    FORA=""
    if [[ -n "$PUB" ]]; then PAI="${PUB%/*}"; [[ "$PAI" == "$PUB" ]] && PAI=""; FORA="${PAI:+$PAI/}config-email.php"; fi
    ONDE=""
    if [[ -n "$FORA" ]]; then
      echo "== A pôr a configuração do email fora da pasta pública ($FORA)..."
      config | ftp -T - "$(url "$FORA")"
      if [[ "$(verificar)" == *'"configurado":true'* ]]; then ONDE="$FORA"; else
        echo "   O alojamento não deixa o PHP ler fora da pasta pública; passa para $DENTRO (um .php não é mostrado pelo servidor)."
        ftp -Q "DELE $FORA" --list-only "$(url "")" > /dev/null 2>&1 || true
      fi
    fi
    if [[ -z "$ONDE" ]]; then
      config | ftp -T - "$(url "$DENTRO")"
      [[ "$(verificar)" == *'"configurado":true'* ]] || { echo "O formulário não encontra a configuração em $DENTRO." >&2; exit 1; }
      ONDE="$DENTRO"
    fi
    echo "   Configuração em $ONDE."
    echo "== A enviar uma mensagem de teste pelo formulário do site..."
    RESP="$(curl --silent --show-error --max-time 60 -H 'Accept: application/json' \
      --data-urlencode "nome=Teste do site" --data-urlencode "email=$SMTP_USER" \
      --data-urlencode "mensagem=Mensagem de teste enviada por scripts/deploy.sh configurar-email em $DATA. Pode apagar." \
      "$SITE/contacto.php" 2>&1 || true)"
    case "$RESP" in
      *'"ok":true'*) echo "Teste enviado. Confirmar que chegou às caixas de destino (ver também a pasta de spam)." ;;
      *autenticacao*) echo "O servidor de email ($SMTP_HOST) recusou o utilizador ou a palavra-passe: rever SMTP_USER e SMTP_PASS no $ENVF e repetir." >&2; exit 1 ;;
      *ligacao*) echo "O servidor do site não conseguiu ligar a $SMTP_HOST, porta $SMTP_PORT: confirmar estes dados no painel da Amen (SMTP_HOST e SMTP_PORT no $ENVF)." >&2; exit 1 ;;
      *) echo "O envio de teste falhou: $RESP" >&2; exit 1 ;;
    esac
    ;;

  *)
    sed -n '2,19p' "$0" | sed 's/^# \{0,1\}//'
    exit 1
    ;;
esac
