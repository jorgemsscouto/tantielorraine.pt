# Testes dos formulários

Testa os formulários de contactos e de degustação num site local, com um servidor de email falso: nenhum email real é
enviado. Esta pasta não é publicada (`scripts/deploy.sh` não a envia). Correr antes de publicar alterações a
`contacto.php`, `degustacao.php`, `email-comum.php`, `degustacao.html`, `contactos.html` ou `assets/js/main.js`.

```
testes/arrancar.sh                       # site em http://127.0.0.1:8095, emails guardados em /tmp/tantie-teste/caixa
cd testes && npm install && npm test     # 17 verificações; termina com erro se alguma falhar
testes/arrancar.sh parar
```

Precisa de `php`, `openssl`, `python3`, `node` e do Chromium (por omissão `/opt/pw-browsers/chromium`, o das sessões na
nuvem; noutro computador, `CHROMIUM=<caminho> npm test` ou `npx playwright install chromium`).

| Ficheiro | O que faz |
|---|---|
| `arrancar.sh` | Copia o site para `/tmp/tantie-teste/www` (outra pasta com `PASTA=...`), cria uma configuração de email de teste fora do repositório, um certificado próprio e arranca o PHP e o SMTP falso |
| `smtp_falso.py` | Servidor de email falso (TLS direto na porta 4651, STARTTLS na 2525) que guarda cada mensagem num `.eml` |
| `formularios.js` | Teste no navegador: classificações só das compotas provadas, preferida e comparação com duas ou mais, ditado (voz simulada), envio nas três línguas com e sem JavaScript, destinatários e assunto dos emails, formulário de contactos |

Para ver um email de teste: abrir o `.eml` da caixa num programa de email, ou extrair a parte HTML com Python (`email.message_from_file`).
O envio real pela Amen só se confirma no site publicado, com um envio de teste.
