# tantielorraine.pt

Versão portuguesa do site da Tantie Lorraine's (marca de molhos picantes e compotas de malagueta de Trindade e Tobago, feitos à mão em Surrey), baseada no site do Reino Unido, [tantielorraine.co.uk](https://www.tantielorraine.co.uk). Site estático em HTML e CSS, sem dependências nem passo de compilação, construído sobre o design system da marca.

O site é só de apresentação: não há venda nem preços. A página Produtos mostra a gama com o botão "Brevemente". Entidade responsável em Portugal: GADGETMÉTODO, LDA (NIPC 513978011), Rua das Cruzes, n.º 625, 4100-195 Porto.

## Gama em Portugal

| Ref. | Produto | Fotografia |
|---|---|---|
| SBJ | Scotch Bonnet Jam (com selo Great Taste na tampa) | `assets/img/produtos/sbj.webp` |
| ACJ | Apple Chilli Jam | `assets/img/produtos/acj.webp` |
| RPCL | Red Pepper Chilli Lime Jam | `assets/img/produtos/rpcl.webp` |
| TCJ | Tomato Chilli Jam | `assets/img/produtos/tcj.webp` |

## Dados dos produtos

Peso, ingredientes, alergénios e informação nutricional (por 100 g) vêm das fichas técnicas de rótulo da marca (Project.Kitchen, 15 de outubro de 2026), traduzidos para português. Os ingredientes a negrito são os que a ficha assinala como fonte de sulfitos. A ficha da SBJ é a versão "copy using mash" (com pasta fermentada de Scotch Bonnet) e indica 1210 kJ / 264 kcal, valores incoerentes entre si (1210 kJ correspondem a 289 kcal). A pedido, o site mostra 1210 kJ / 289 kcal (17/10/2026); a marca deve confirmar o valor e corrigir o rótulo, porque o frasco continua a indicar 264 kcal.

## Imagens

Logótipo e fotografias são os do site do Reino Unido (inventário de 16 de outubro de 2026), recortados e convertidos para WebP. Cada imagem substitui automaticamente o espaço reservado; se um ficheiro faltar, aparece o espaço reservado, sem ícone de imagem partida. O favicon (`assets/img/favicon.png`, `favicon.ico` na raiz e `assets/img/apple-touch-icon.png`) é o sol do logótipo oficial em `chilli-red`, como no separador do site do Reino Unido.

| Ficheiro | Onde aparece |
|---|---|
| `assets/img/logo.png` | Cabeçalho e rodapé (enquanto não existir, aparece o nome em texto) |
| `assets/img/hero.webp` | Fundo desfocado do topo da página inicial (só as filas de cima da pirâmide de frascos: RPCL, ACJ, TCJ) |
| `assets/img/produtos/<ref>.webp` | Cartões de produto (ver tabela acima) |
| `assets/img/inicio-tantie.webp` | Página inicial, bloco "Quem é a Tantie?" |
| `assets/img/sobre.webp` | Página História (retrato 4:5) |
| `assets/img/eventos.webp` | Página Eventos (pelau de uma sessão Limin with Tantie) |
| `assets/img/selos/great-taste-producer.png` | Centro do cabeçalho e rodapé |
| `assets/img/selos/food-hygiene-rating-5.png`, `great-taste-2019-1-estrela.png`, `great-taste-2021-1-estrela.png`, `great-taste-2024-2-estrelas.png` | Rodapé, sob "Distinções da marca no Reino Unido" |
| `assets/img/selos/great-taste-2026.png` | Rodapé |

Todos os selos Great Taste (cabeçalho, rodapé e email de confirmação) ligam ao [diretório de produtores da Guild of Fine Food](https://gff.co.uk/directory/producer/?id=25813). O Food Hygiene Rating não, porque não é atribuído pela Guild. O email de confirmação usa o logótipo e os selos que estiverem no servidor.

Os selos são ficheiros originais, usados sem alterações (a Food Standards Agency não permite alterar as imagens da classificação e só deixa mostrar a classificação em vigor). Enquanto um ficheiro faltar, o selo não aparece. Os quatro primeiros estão no site do Reino Unido:

```bash
mkdir -p assets/img/selos && cd assets/img/selos
curl -fsS -o great-taste-producer.png 'https://static.wixstatic.com/media/0e8816_8ed9b46635bc4fc6be9a014622398b08~mv2.png'
curl -fsS -o great-taste-2019-1-estrela.png 'https://static.wixstatic.com/media/0e8816_31d838a940264e64a4700c7fd0a04d0d~mv2.png'
curl -fsS -o great-taste-2021-1-estrela.png 'https://static.wixstatic.com/media/0e8816_e0d14e5540de4990a0f2005f40b02732~mv2.png'
curl -fsS -o great-taste-2024-2-estrelas.png 'https://static.wixstatic.com/media/0e8816_94a846c201984143887fba1eddc27b0b~mv2.png'
curl -fsS -o food-hygiene-rating-5.png 'https://static.wixstatic.com/media/0e8816_a869c19a6bcc4a19afb12e17d96ac6a1~mv2.png'
cd ../../..
```

O de 2026 tem de vir da marca (kit de vencedor da Guild of Fine Food).

Formato recomendado: WebP, até cerca de 300 KB; produtos quadrados.

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `index.html` | Início: hero, mensagem principal, produtos em destaque, apresentação, testemunhos |
| `produtos.html` | Uma secção por referência: fotografia, peso, descrição, ingredientes, alergénios e informação nutricional |
| `eventos.html` | Aulas de cozinha |
| `historia.html` | História da marca e da fundadora |
| `contactos.html`, `privacidade.html`, `404.html` | Contactos (com o formulário de pedido de informação), informação legal, erro |
| `contacto.php` | Recebe o formulário e envia, por SMTP da Amen, o pedido para info@tantielorraine.pt e gadgetmetodo@gmail.com e uma confirmação de receção a quem escreveu |
| `favicon.ico` | Favicon para os browsers que o pedem na raiz |
| `assets/css/tokens.css`, `components.css` | Design system da marca (cores, tipografia, componentes `.tl-*`) |
| `assets/css/fonts.css`, `assets/fonts/` | Anton, Poppins e Work Sans alojadas no próprio site (licença OFL) |
| `assets/css/site.css` | Composição das páginas, só com variáveis do design system |
| `assets/js/main.js` | Menu em ecrãs pequenos, ano no rodapé e envio do formulário sem sair da página |
| `.claude/skills/tantie-lorraine-design-system/` | Design system original (regras, tokens, exemplos) |
| `.htaccess` | Regras para Apache (HTTPS, domínio sem "www", página 404, cache, cabeçalhos de segurança). Sem efeito no alojamento da Amen (ver abaixo); mantido para o caso de mudança de servidor |
| `scripts/deploy.sh` | Publicação por FTP/FTPS com o curl: testar, backup, enviar, retirar o WordPress, configurar o email |
| `scripts/obter-selos.sh` | Descarrega os selos em falta a partir dos originais do site do Reino Unido |
| `.github/workflows/publicar.yml` | Publicação automática na Amen a cada alteração no ramo `main` |

## Por fazer antes de publicar

- Textos marcados com `[A PREENCHER: ...]` (contorno tracejado no navegador): `grep -rn "A PREENCHER" --include=*.html .`
- Autorização escrita da Lorraine para usar a marca, o logótipo, as fotografias, os textos e os selos dos prémios, incluindo o direito de imagem do retrato dela.
- Ficheiros dos selos em `assets/img/selos/` (ver Imagens), incluindo o de 2026, a pedir à marca.
- Confirmar com a marca os kcal da SBJ e que a classificação Food Hygiene Rating 5 continua em vigor.

## Ver localmente

```bash
python3 -m http.server 8000
```

Para experimentar o formulário é preciso PHP (`php -S localhost:8000`); sem `config-email.php`, o envio dá a mensagem de erro.

Abrir http://localhost:8000.

## Publicar (alojamento da Amen)

### Automática, a partir do GitHub

Cada alteração no ramo `main` publica o site sozinha (`.github/workflows/publicar.yml`, separador Actions do GitHub): obtém os selos que faltam (`scripts/obter-selos.sh`), envia o site e atualiza no servidor a configuração do email do formulário. Não apaga nada no servidor.

Configuração, uma única vez, em Settings > Secrets and variables > Actions > New repository secret:

| Segredo | Valor |
|---|---|
| `FTP_HOST` | Servidor FTP da Amen |
| `FTP_USER` | Utilizador FTP |
| `FTP_PASS` | Palavra-passe FTP |
| `SMTP_PASS` | Palavra-passe da caixa info@tantielorraine.pt |

Os segredos ficam cifrados no GitHub e não aparecem nos registos. Sem eles, a publicação é saltada com um aviso. Para publicar sem alterar nada, ou para enviar uma mensagem de teste pelo formulário: Actions > Publicar na Amen > Run workflow (opção "Enviar uma mensagem de teste"). Ao mudar uma palavra-passe, atualizar o segredo e correr de novo.

### A partir do computador

Usa o `curl` que já vem com o macOS (não é preciso instalar nada) e um `.env` na raiz com os dados FTP (modelo em `.env.example`; o `.env` está no `.gitignore` e o script nunca mostra nem grava as credenciais). Funciona por FTP, com TLS quando o servidor o aceita.

### Primeira vez: trocar o WordPress pelo site estático

1. `scripts/deploy.sh testar`: só leitura; confirma a ligação e mostra a pasta pública.
2. `scripts/deploy.sh backup`: só leitura; guarda `.htaccess`, `wp-config.php` e `index.php` em `backups/amen-<data>/` (fora do Git), numa só ligação. Para uma cópia integral do WordPress, usar as cópias de segurança do painel da Amen; exportar também a base de dados no phpMyAdmin (Exportar > Rápido > SQL). Não se descarrega o WordPress todo por FTP: o servidor recusa ligações quando se abrem centenas seguidas.
3. `scripts/deploy.sh enviar --simular` e depois `scripts/deploy.sh enviar`: envia todos os ficheiros do site. Não apaga nada; antes de enviar, guarda o `.htaccess` atual em `backups/antes-do-envio-<data>/`. Enquanto o `index.php` do WordPress estiver na pasta, o servidor continua a mostrar o WordPress.
4. `scripts/deploy.sh retirar-wordpress --simular` e depois `scripts/deploy.sh retirar-wordpress`: move os ficheiros do WordPress para `wordpress-desativado-<data>/`, fora da pasta pública (pede para escrever RETIRAR, em maiúsculas). A partir daqui o site estático fica no ar.
5. Abrir o site (Cmd+Shift+R) e confirmar. Depois, no painel da Amen, apagar a pasta `wordpress-desativado-<data>/` (tem o `wp-config.php`, com a palavra-passe da base de dados) e a base de dados do WordPress.

Feito em 17/10/2026.

### Atualizações seguintes

`scripts/deploy.sh enviar --simular` e `scripts/deploy.sh enviar`. Ficheiros que deixem de existir no projeto não são apagados no servidor; apagar à mão, se necessário.

### Se algo correr mal

- Erro de certificado na ligação FTP: acrescentar `FTP_VERIFICAR_CERTIFICADO=nao` ao `.env`.
- Repor o WordPress: mover o conteúdo de `wordpress-desativado-<data>/` de volta para a pasta pública e repor o `.htaccess` guardado em `backups/antes-do-envio-<data>/`.
- "Connection time-out": o servidor está a limitar ligações; esperar 10 minutos e repetir (o script já tenta de novo sozinho algumas vezes).

### Formulário de pedido de informação

1. No `.env`, preencher `SMTP_USER` (info@tantielorraine.pt) e `SMTP_PASS` (palavra-passe dessa caixa de correio). Por omissão, o servidor de envio é `smtp-pt.securemail.pro`, porta 465, segundo a ajuda da Amen; se o painel indicar outros, definir `SMTP_HOST` e `SMTP_PORT`.
2. `scripts/deploy.sh enviar` (põe o `contacto.php` no servidor).
3. `scripts/deploy.sh configurar-email`: cria `config-email.php` no servidor, fora da pasta pública (se o alojamento não deixar o PHP ler lá, fica na pasta pública, onde o servidor não mostra o conteúdo de ficheiros `.php`), e envia uma mensagem de teste pelo formulário. A palavra-passe não passa pelo disco do computador nem vai para o Git.
4. Ao mudar a palavra-passe do email: atualizar o `.env` e repetir o passo 3.

Os pedidos chegam com o remetente info@tantielorraine.pt e "Responder para" o email de quem escreveu. Quem escreve recebe uma confirmação de receção (HTML com logótipo e selos, mais versão em texto); por segurança, não repete a mensagem e só usa o nome se parecer um nome. Limites: 3 pedidos por dia para o mesmo email e 100 por dia no total. Proteção contra robôs sem serviços de terceiros: um campo escondido e um tempo mínimo de preenchimento. Erros de envio ficam no registo de erros do PHP no servidor (pasta `logs`).

### Comportamento do servidor da Amen (confirmado em 17/10/2026)

- Os ficheiros são servidos diretamente pelo nginx, que não lê o `.htaccess`: não se aplicam o `DirectoryIndex`, os redirecionamentos, a página de erro, a cache nem os cabeçalhos de segurança aí definidos. Alterações destas pedem-se à Amen ou fazem-se no painel.
- `http://` redireciona para `https://` (301), por configuração do servidor.
- `www.tantielorraine.pt` responde com o próprio site (200), sem redirecionar para `tantielorraine.pt`. Todas as páginas têm `<link rel="canonical">` para o domínio sem "www", o que evita conteúdo duplicado nos motores de busca. Pendente: pedir à Amen um redirecionamento 301 de "www" para o domínio sem "www".
- Endereços inexistentes devolvem o código 404; falta confirmar se mostram a página `404.html` do site ou a página genérica do servidor.

## Opções técnicas

- Fontes alojadas no site em vez do Google Fonts, sem mapas incorporados nem analytics: não há transferência de dados para terceiros e não é preciso aviso de cookies.
- As fontes DIN Neuzeit Grotesk, Avenir e DIN Next do site do Reino Unido são licenciadas pela Wix e não estão incluídas; o site usa as alternativas definidas no design system.
- Um único formulário (pedido de informação), tratado pelo próprio alojamento: sem reCAPTCHA nem outros serviços de terceiros, sem cookies e sem guardar dados no site.
