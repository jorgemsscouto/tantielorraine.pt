#!/usr/bin/env bash
# Publica o site estático no alojamento da Amen por FTP, FTPS ou SFTP.
# Credenciais lidas do .env na raiz (FTP_HOST, FTP_USER, FTP_PASS, FTP_PORT e, opcionais, FTP_DIR e
# FTP_VERIFICAR_CERTIFICADO=nao); nunca são mostradas. Requer lftp (macOS: brew install lftp).
#
# Uso:
#   scripts/deploy.sh testar                       só leitura: testa a ligação e mostra a raiz e a pasta pública
#   scripts/deploy.sh backup                       só leitura: descarrega a pasta pública para backups/amen-<data>/
#   scripts/deploy.sh enviar [--simular]           envia o site (ficheiros novos e alterados); não apaga nada
#   scripts/deploy.sh enviar --apagar [--simular]  envia e apaga no servidor ficheiros do site que já não existem aqui
#                                                  (nunca toca em ficheiros do WordPress, .well-known, cgi-bin nem cópias)
#   scripts/deploy.sh retirar-wordpress [--simular]
#                                                  move os ficheiros do WordPress da pasta pública para uma pasta
#                                                  wordpress-desativado-<data> fora do site; nada é apagado
#
# Ordem para trocar o WordPress pelo site estático: testar, backup, enviar --simular, enviar,
# retirar-wordpress --simular, retirar-wordpress.
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
cd "$RAIZ"
ACAO="${1:-}"; shift || true
SIMULAR=0; APAGAR=0
for arg in "$@"; do
  case "$arg" in
    --simular) SIMULAR=1 ;;
    --apagar) APAGAR=1 ;;
    *) echo "Opção desconhecida: $arg" >&2; exit 1 ;;
  esac
done
DATA="$(date +%Y%m%d-%H%M)"

# Credenciais: .env (ou .deploy.env, nome antigo), sempre ignorado pelo Git
ENVF=".env"; [[ -f "$ENVF" ]] || ENVF=".deploy.env"
[[ -f "$ENVF" ]] || { echo "Falta o ficheiro .env na raiz (ver .env.example)." >&2; exit 1; }
# Num repositório Git, o .env tem de estar ignorado (numa cópia descarregada em ZIP não há Git, logo não há risco)
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  git check-ignore -q "$ENVF" || { echo "O $ENVF não está no .gitignore. Parado por segurança." >&2; exit 1; }
fi
command -v lftp >/dev/null || { echo "Falta o lftp (macOS: brew install lftp)." >&2; exit 1; }

ler() { { grep -E "^$1=" "$ENVF" || true; } | tail -n 1 | cut -d= -f2- | sed -e 's/^["'\'']//' -e 's/["'\'']$//'; }
FTP_HOST="$(ler FTP_HOST)"; FTP_USER="$(ler FTP_USER)"; FTP_PASS="$(ler FTP_PASS)"
FTP_PORT="$(ler FTP_PORT)"; FTP_DIR="$(ler FTP_DIR)"; VERIFICAR="$(ler FTP_VERIFICAR_CERTIFICADO)"
[[ -n "$FTP_HOST" && -n "$FTP_USER" && -n "$FTP_PASS" ]] || { echo "Faltam FTP_HOST, FTP_USER ou FTP_PASS no $ENVF." >&2; exit 1; }
FTP_PORT="${FTP_PORT:-21}"
if [[ "$FTP_PORT" == "22" ]]; then URL="sftp://$FTP_HOST"; else URL="ftp://$FTP_HOST"; fi
[[ "$VERIFICAR" == "nao" ]] && CERT=no || CERT=yes
export LFTP_PASSWORD="$FTP_PASS"

correr() {
  lftp --env-password -u "$FTP_USER" -p "$FTP_PORT" "$URL" <<LFTP
set cmd:fail-exit yes
set net:max-retries 2
set net:timeout 20
set ftp:ssl-allow yes
set ftp:passive-mode yes
set ssl:verify-certificate $CERT
$1
bye
LFTP
}

# Pasta pública: FTP_DIR, ou public_html/httpdocs/www/htdocs na raiz, ou a própria raiz se tiver o site
detetar_publica() {
  if [[ -n "$FTP_DIR" ]]; then echo "$FTP_DIR"; return; fi
  local lista
  lista="$(correr "cls -1 /")"
  for d in public_html httpdocs www htdocs; do
    if grep -qx "$d/\?" <<<"$lista"; then echo "/$d"; return; fi
  done
  if grep -qxE "(index\.(php|html)|wp-config\.php)" <<<"$lista"; then echo "/"; return; fi
  echo ""
}
exigir_publica() {
  PUB="$(detetar_publica)"
  [[ -n "$PUB" ]] || { echo "Pasta pública não detetada: definir FTP_DIR no .env." >&2; exit 1; }
  PUB_="${PUB%/}"
}

# Ficheiros do WordPress na pasta pública (o .htaccess não entra: depois do envio é o do site estático)
WP_NOMES='^(index\.php|license\.txt|licenca\.txt|readme\.html|leiame\.html|xmlrpc\.php|wp-[^/]+)/?$'

case "$ACAO" in
  testar)
    echo "== Ligação: ${URL%%://*} na porta $FTP_PORT (credenciais do $ENVF, não mostradas)"
    echo "== Raiz da conta:"; correr "cls -la /"
    exigir_publica
    echo "== Pasta pública: $PUB"; correr "cls -la $PUB"
    ;;

  backup)
    exigir_publica
    DEST="backups/amen-$DATA"
    echo "== A descarregar $PUB para $DEST (só leitura no servidor)"
    mkdir -p "$DEST"
    correr "mirror --verbose --parallel=3 $PUB $DEST"
    echo "Cópia feita: $DEST ($(du -sh "$DEST" | cut -f1)). A pasta backups/ não vai para o Git."
    echo "Base de dados: exportar também no phpMyAdmin (Exportar > Rápido > SQL) e guardar junto desta cópia."
    ;;

  enviar)
    exigir_publica
    OPC="--reverse --verbose"
    [[ $SIMULAR -eq 1 ]] && OPC="$OPC --dry-run"
    [[ $APAGAR -eq 1 ]] && OPC="$OPC --delete"
    EXCL=""
    for g in .git/ '.git*' .claude/ '.env*' '.deploy.env*' .DS_Store scripts/ backups/ originais/ README.md CLAUDE.md \
             .well-known/ cgi-bin/ 'wordpress-desativado*' 'wp-*' index.php xmlrpc.php license.txt licenca.txt \
             readme.html leiame.html .user.ini php.ini error_log; do
      EXCL="$EXCL --exclude-glob '$g'"
    done
    [[ $SIMULAR -eq 1 ]] && echo "== Simulação: nada é enviado nem apagado" || echo "== A enviar o site para $PUB"
    correr "mirror $OPC $EXCL . $PUB"
    [[ $SIMULAR -eq 1 ]] || echo "Enviado. Abrir o site e confirmar antes de retirar o WordPress."
    ;;

  retirar-wordpress)
    exigir_publica
    LISTA="$(correr "cls -1 $PUB" | sed 's#[/@]$##')"
    grep -qx "index.html" <<<"$LISTA" || { echo "Não há index.html em $PUB: enviar primeiro o site (scripts/deploy.sh enviar)." >&2; exit 1; }
    ITENS="$(grep -E "$WP_NOMES" <<<"$LISTA" || true)"
    [[ -n "$ITENS" ]] || { echo "Não há ficheiros do WordPress em $PUB."; exit 0; }
    if [[ "$PUB_" == "" ]]; then
      DEST="/wordpress-desativado-$DATA"   # a conta FTP só vê a pasta pública: o destino fica dentro dela, bloqueado
      BLOQUEAR=1
    else
      DEST="/wordpress-desativado-$DATA"; BLOQUEAR=0
    fi
    echo "== Ficheiros do WordPress em $PUB que serão movidos para $DEST:"
    sed 's/^/   /' <<<"$ITENS"
    echo "== Ficam no lugar (não são do WordPress):"
    { grep -vE "$WP_NOMES" <<<"$LISTA" | grep -vxE '\.|\.\.' || true; } | sed 's/^/   /'
    [[ $BLOQUEAR -eq 1 ]] && echo "(A pasta de destino fica dentro da pasta pública; será bloqueada ao acesso pela web.)"
    if [[ $SIMULAR -eq 1 ]]; then echo "Simulação: nada foi movido."; exit 0; fi
    read -r -p "Escrever RETIRAR para mover estes ficheiros: " ok
    [[ "$ok" == "RETIRAR" ]] || { echo "Cancelado."; exit 1; }
    CMDS="mkdir -p $DEST"
    while IFS= read -r i; do CMDS="$CMDS
mv $PUB_/$i $DEST/$i"; done <<<"$ITENS"
    if [[ $BLOQUEAR -eq 1 ]]; then
      TMP="$(mktemp)"; printf 'Require all denied\n' > "$TMP"
      CMDS="$CMDS
put $TMP -o $DEST/.htaccess"
    fi
    correr "$CMDS"
    [[ $BLOQUEAR -eq 1 ]] && rm -f "$TMP"
    echo "WordPress movido para $DEST. Para repor, mover os ficheiros de volta para $PUB."
    ;;

  *)
    sed -n '2,19p' "$0" | sed 's/^# \{0,1\}//'
    exit 1
    ;;
esac
