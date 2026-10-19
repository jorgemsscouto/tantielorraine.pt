#!/bin/bash
# Arranca o site localmente com um servidor de email falso, para testar os formulários sem enviar emails reais.
#   testes/arrancar.sh          copia o site para $PASTA/www, cria a configuração de email de teste e arranca o PHP
#                               (http://127.0.0.1:8095) e o SMTP falso (emails guardados em $PASTA/caixa)
#   testes/arrancar.sh parar    para os dois servidores
# PASTA por omissão: /tmp/tantie-teste. Precisa de php e openssl. A configuração de teste (config-email.php) fica fora
# do repositório, com credenciais falsas.
set -euo pipefail
RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
PASTA="${PASTA:-/tmp/tantie-teste}"
UTILIZADOR="info@tantielorraine.pt"
PASSE="teste-local"

parar() {
  for f in "$PASTA/php.pid" "$PASTA/smtp.pid"; do
    [[ -f "$f" ]] && kill "$(cat "$f")" 2>/dev/null || true
    rm -f "$f"
  done
}
if [[ "${1:-}" == "parar" ]]; then parar; echo "Servidores de teste parados."; exit 0; fi
parar
# tmp: contadores do limite de envios (email-comum.php usa a pasta tmp ao lado da pasta pública); recomeçam a cada arranque
mkdir -p "$PASTA/cert" "$PASTA/caixa"
rm -rf "$PASTA/tmp"; mkdir -p "$PASTA/tmp"
rm -rf "$PASTA/www"; mkdir -p "$PASTA/www"
# Cópia do site como vai para o servidor (sem repositório, testes, materiais nem credenciais)
tar -C "$RAIZ" --exclude=.git --exclude=.github --exclude=.claude --exclude=backups --exclude=testes --exclude=materiais \
  --exclude='.env*' --exclude=config-email.php -cf - . | tar -C "$PASTA/www" -xf -
if [[ ! -f "$PASTA/cert/cert.pem" ]]; then
  openssl req -x509 -newkey rsa:2048 -nodes -days 365 -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" \
    -keyout "$PASTA/cert/chave.pem" -out "$PASTA/cert/cert.pem" 2>/dev/null
fi
printf '%s\n' "<?php" "return ['host' => 'localhost', 'porta' => 4651, 'utilizador' => '$UTILIZADOR', 'palavra_passe' => '$PASSE'];" > "$PASTA/config-email.php"
nohup python3 -I "$RAIZ/testes/smtp_falso.py" "$UTILIZADOR" "$PASSE" "$PASTA/cert" "$PASTA/caixa" > "$PASTA/smtp.log" 2>&1 &
echo $! > "$PASTA/smtp.pid"
nohup php -S 127.0.0.1:8095 -t "$PASTA/www" -d "openssl.cafile=$PASTA/cert/cert.pem" > "$PASTA/php.log" 2>&1 &
echo $! > "$PASTA/php.pid"
sleep 1
echo "Site em http://127.0.0.1:8095 ; emails de teste em $PASTA/caixa ; parar com: testes/arrancar.sh parar"
