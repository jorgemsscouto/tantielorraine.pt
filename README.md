# tantielorraine.pt

Versão portuguesa do site da Tantie Lorraine's (marca de molhos picantes e compotas de malagueta de Trinidad e Tobago, feitos à mão em Surrey, Londres), baseada no site do Reino Unido, [tantielorraine.co.uk](https://www.tantielorraine.co.uk). Site estático em HTML e CSS, sem dependências nem passo de compilação, construído sobre o design system da marca.

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
| `assets/img/ingredientes/<ingrediente>.webp` | Círculo com o ingrediente principal no canto das fotografias de produto: `scotch-bonnet` (SBJ), `maca-verde` (ACJ), `lima` (RPCL), `tomate-cereja` (TCJ). Fotografias enviadas pelo utilizador a 17/10/2026; confirmar que há licença para uso comercial |
| `assets/img/selos/great-taste-producer.png` | Rodapé |
| `assets/img/selos/food-hygiene-rating-5.png`, `great-taste-2019-1-estrela.png`, `great-taste-2021-1-estrela.png`, `great-taste-2024-2-estrelas.png` | Rodapé, sob "Distinções da marca no Reino Unido" |
| `assets/img/selos/great-taste-2026.png` | Centro do cabeçalho e rodapé (uma estrela). Recortado da imagem enviada pelo utilizador, com o fundo tornado transparente; trocar pelo ficheiro original da Guild of Fine Food quando houver |
| `assets/img/partilha.jpg` | Pré-visualização nas partilhas em redes sociais e mensagens (1200 x 630, logótipo e Scotch Bonnet Jam) |

Todos os selos Great Taste (cabeçalho, rodapé e email de confirmação) ligam ao [diretório de produtores da Guild of Fine Food](https://gff.co.uk/directory/producer/?id=25813). O Food Hygiene Rating não, porque não é atribuído pela Guild. O email de confirmação usa o logótipo e os selos que estiverem no servidor.

Os selos são ficheiros originais, usados sem alterações (a Food Standards Agency não permite alterar as imagens da classificação e só deixa mostrar a classificação em vigor); o de 2026 só teve o fundo tornado transparente. Enquanto um ficheiro faltar, o selo não aparece. Os cinco do site do Reino Unido são obtidos sozinhos em cada publicação (`scripts/obter-selos.sh`); um ficheiro com o mesmo nome em `assets/img/selos/` tem prioridade (instruções no `README.md` dessa pasta).

Formato recomendado: WebP, até cerca de 300 KB; produtos quadrados.

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `index.html` | Início: hero, mensagem principal, produtos em destaque, apresentação, testemunhos |
| `produtos.html` | Uma secção por referência: fotografia, peso, descrição, ingredientes, alergénios e informação nutricional |
| `historia.html` | História da marca e da fundadora |
| `en/`, `es/` | Versões em inglês e espanhol, geradas por `scripts/idiomas.py` (não editar à mão) |
| `scripts/idiomas.py` | Gera `/en/` e `/es/`, o seletor de língua, o hreflang e o `sitemap.xml` a partir das páginas portuguesas e da tabela de traduções |
| `publicacoes.html` | Publicações do Instagram da marca, resumidas em português, com ligação para cada uma |
| `contactos.html`, `privacidade.html`, `404.html` | Contactos (com o formulário de pedido de informação), informação legal, erro |
| `robots.txt`, `sitemap.xml`, `llms.txt` | Indicações para motores de busca e assistentes de IA (ver abaixo) |
| `degustacao.html` | Formulário de opinião de degustação (fora do menu, do sitemap e do `llms.txt`, com `noindex`; ligação no fim de Produtos e códigos QR) |
| `contacto.php` | Recebe o pedido de informação e envia-o, por SMTP da Amen, para info@tantielorraine.pt, mais uma confirmação de receção a quem escreveu |
| `degustacao.php` | Recebe a opinião de degustação, envia-a para info@tantielorraine.pt e envia um agradecimento a quem respondeu |
| `email-comum.php` | Código comum aos dois formulários: destinatários, configuração, envio por SMTP, limites de envios e aspeto dos emails (faixas vermelhas, logótipo e selos) |
| `assets/img/email/<ref>.jpg` | Frascos para os emails da degustação (192 x 192, em JPG porque o Outlook não mostra WebP) |
| `assets/img/ingredientes/mini/<ingrediente>.webp` | Ingrediente principal em miniatura no questionário de degustação (128 x 128, a partir das fotografias dos ingredientes) |
| `favicon.ico` | Favicon para os browsers que o pedem na raiz |
| `assets/css/tokens.css`, `components.css` | Design system da marca (cores, tipografia, componentes `.tl-*`) |
| `assets/css/fonts.css`, `assets/fonts/` | Anton, Poppins e Work Sans alojadas no próprio site (licença OFL) |
| `assets/css/site.css` | Composição das páginas, só com variáveis do design system |
| `assets/js/main.js` | Menu em ecrãs pequenos, ano no rodapé, envio dos formulários sem sair da página, perguntas da degustação conforme as compotas provadas, aviso de cookies e Google Analytics com consentimento |
| `.claude/skills/tantie-lorraine-design-system/` | Design system original (regras, tokens, exemplos) |
| `.htaccess` | Regras para Apache (HTTPS, domínio sem "www", página 404, cache, cabeçalhos de segurança). Sem efeito no alojamento da Amen (ver abaixo); mantido para o caso de mudança de servidor |
| `scripts/deploy.sh` | Publicação por FTP/FTPS com o curl: testar, backup, enviar, retirar o WordPress, configurar o email |
| `scripts/retirados.txt` | Ficheiros retirados do site, apagados no servidor a cada publicação (por agora, a página Eventos) |
| `scripts/obter-selos.sh` | Descarrega os selos em falta a partir dos originais do site do Reino Unido |
| `.github/workflows/publicar.yml` | Publicação automática na Amen a cada alteração no ramo `main` |
| `scripts/indexnow.sh`, `<chave>.txt` | Aviso aos motores de busca das páginas alteradas (IndexNow); o ficheiro `.txt` com nome hexadecimal na raiz é a chave pública de verificação |

## Por fazer antes de publicar

- Textos marcados com `[A PREENCHER: ...]` (contorno tracejado no navegador): `grep -rn "A PREENCHER" --include=*.html .`
- Autorização escrita da Lorraine para usar a marca, o logótipo, as fotografias, os textos e os selos dos prémios, incluindo o direito de imagem do retrato dela.
- Ficheiro original do selo Great Taste 2026 (o atual foi recortado de uma imagem), a pedir à marca.
- Confirmar com a marca os kcal da SBJ e que a classificação Food Hygiene Rating 5 continua em vigor.

## Línguas

Português (principal), inglês e espanhol. As páginas portuguesas são a fonte; as outras são geradas:

```bash
python3 scripts/idiomas.py
```

O script traduz cada página portuguesa com a tabela de traduções que tem dentro (fragmentos em português e a tradução em inglês e espanhol), troca as ligações pelas da língua, traduz os dados estruturados e acrescenta a todas as páginas o seletor PT · EN · ES e as ligações hreflang. Se uma página portuguesa mudar e a tabela não acompanhar, ou se sobrar texto em português numa tradução, o script para sem escrever nada e diz o que falta. Depois, fazer commit de tudo: a publicação automática envia as três línguas.

| Português | Inglês | Espanhol |
|---|---|---|
| `/` | `/en/` | `/es/` |
| `/historia.html` | `/en/story.html` | `/es/historia.html` |
| `/produtos.html` | `/en/products.html` | `/es/productos.html` |
| `/publicacoes.html` | `/en/posts.html` | `/es/publicaciones.html` |
| `/contactos.html` | `/en/contact.html` | `/es/contacto.html` |
| `/privacidade.html` | `/en/privacy.html` | `/es/privacidad.html` |
| `/degustacao.html` | `/en/tasting.html` | `/es/degustacion.html` |

Os formulários enviam a língua da página (`idioma`): a confirmação do pedido de informação e o agradecimento da degustação seguem nessa língua; os emails para a equipa são em português e indicam-na. O aviso de cookies também acompanha a língua. Os ingredientes em inglês seguem o texto das fichas técnicas originais; a tradução espanhola usa "chile" para malagueta e "mermelada de chile" para "chilli jam".

## Motores de busca e assistentes de IA

O que o site já faz para aparecer bem em pesquisas tradicionais e em respostas de assistentes de IA (ChatGPT, Claude, Perplexity, Copilot, Gemini e vistas geradas por IA do Google):

- Conteúdo em HTML simples, legível sem JavaScript, com títulos, descrições e endereço canónico em todas as páginas.
- Dados estruturados (JSON-LD, schema.org) em todas as páginas: organização (GADGETMÉTODO, LDA, morada, contactos), marca, site, os quatro produtos (referência, peso, descrição, prémio da SBJ), perguntas frequentes e publicações.
- Perguntas frequentes na página de produtos, escritas só com factos já publicados no site: é o formato que os assistentes de IA mais citam.
- `robots.txt` aberto a todos os motores e assistentes de IA, com os principais indicados explicitamente; `sitemap.xml` com datas; meta `robots` com `max-snippet:-1` e `max-image-preview:large`, para permitir excertos e imagens completos.
- `llms.txt` (proposta de norma em llmstxt.org): resumo factual do site para modelos de linguagem. Nem todos os motores o usam; custa pouco manter.
- IndexNow: a cada publicação, o Bing e os outros motores aderentes são avisados das páginas alteradas.
- Imagem de partilha (Open Graph) e cartão para redes sociais.

Ao mudar um facto (gama, pesos, prémios, contactos), atualizar em conjunto a página, as perguntas frequentes, os dados estruturados e o `llms.txt`, para não haver versões contraditórias.

O Google Analytics mede as visitas (incluindo as que chegam de assistentes de IA, como chatgpt.com ou perplexity.ai, em Aquisição de tráfego), mas não influencia a indexação nem a posição nas pesquisas. Para isso conta o Google Search Console.

Por fazer (exige a conta do utilizador): registar o site no Google Search Console, de preferência como propriedade de domínio (registo TXT no DNS, no painel da Amen) ou, em alternativa, com a meta tag de verificação; depois, no Bing Webmaster Tools, importar a partir do Google. Submeter `https://tantielorraine.pt/sitemap.xml` em ambos. No Google Analytics, confirmar em Administração > Recolha e modificação de dados > Retenção de dados que o prazo é 2 meses (é o que diz a política de privacidade).

## Ver localmente

```bash
python3 -m http.server 8000
```

Para experimentar o formulário é preciso PHP (`php -S localhost:8000`); sem `config-email.php`, o envio dá a mensagem de erro.

Abrir http://localhost:8000.

## Publicar (alojamento da Amen)

### Automática, a partir do GitHub

Cada alteração no ramo `main` publica o site sozinha (`.github/workflows/publicar.yml`, separador Actions do GitHub): obtém os selos que faltam (`scripts/obter-selos.sh`), envia o site, apaga no servidor os ficheiros listados em `scripts/retirados.txt` e atualiza a configuração do email do formulário. Não apaga mais nada no servidor.

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

`scripts/deploy.sh enviar --simular` e `scripts/deploy.sh enviar`. Ficheiros que deixem de existir no projeto só são apagados no servidor se forem acrescentados a `scripts/retirados.txt` (um caminho por linha, relativo à pasta pública).

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

### Formulário de degustação

Página `/degustacao.html` (e `/en/tasting.html`, `/es/degustacion.html`), com uma versão curta da ficha em papel "Degustação de Chilli Jams". Serve para as degustações (código QR) e para quem prova em casa (ligação no fim de Produtos). Está fora do menu, do sitemap e do `llms.txt` e tem `noindex`, para não aparecer nos motores de busca.

- Pede o local da degustação, as compotas provadas e, no fim, nome, email e telefone (opcional). Obrigatórios: local, pelo menos uma compota, a nota global de cada compota provada, nome e email.
- Só aparecem as perguntas das compotas marcadas: sabor principal, nota global e se compraria (de 1 a 5), nível de picante e melhor utilização. A escolha da preferida e a comparação final só aparecem com duas ou mais. Junto de cada nome, a fotografia do ingrediente principal e uma linha com os ingredientes que dão o nome (por exemplo, "Maçã e malagueta"), para quem não conhece as siglas. Sem JavaScript aparece tudo e o servidor só conta as compotas marcadas.
- Os campos de texto têm um botão "Ditar" (microfone): o reconhecimento de voz do navegador escreve o que a pessoa diz e o texto pode ser corrigido antes do envio. O site não recebe áudio. Funciona no Chrome, Edge e Safari (incluindo telemóveis); no Firefox e em alguns navegadores dentro de aplicações o botão não aparece, e fica o ditado do teclado do telemóvel. Descrito no ponto 2 da política de privacidade.
- Envia dois emails: para info@tantielorraine.pt, em português, com os dados de quem respondeu, as notas em círculos, os comentários e, no fim, duas linhas separadas por ponto e vírgula para colar no Excel (Dados > Texto para Colunas; as colunas são sempre as mesmas, por isso as opiniões podem ser juntadas numa só folha); "Responder" responde a quem deu a opinião. Para quem respondeu, um agradecimento na língua da página com as notas globais, a preferida e um botão para a gama; não repete texto escrito pela pessoa, para o formulário não servir para enviar mensagens a terceiros.
- Códigos QR: usar o endereço com `?local=`, por exemplo `https://tantielorraine.pt/degustacao.html?local=Mercado do Bolhão` (até 80 caracteres). O local fica preenchido e pode ser corrigido; quem responde em casa escreve-o.
- Limites: 3 opiniões por dia com o mesmo email e 300 por dia no total; a mesma proteção contra robôs do pedido de informação.
- As chaves das perguntas têm de coincidir entre `degustacao.html` e `degustacao.php`; ao mudar uma pergunta, mudar as duas e a tabela de traduções em `scripts/idiomas.py`.

### Comportamento do servidor da Amen (confirmado em 17/10/2026)

- Os ficheiros são servidos diretamente pelo nginx, que não lê o `.htaccess`: não se aplicam o `DirectoryIndex`, os redirecionamentos, a página de erro, a cache nem os cabeçalhos de segurança aí definidos. Alterações destas pedem-se à Amen ou fazem-se no painel.
- `http://` redireciona para `https://` (301), por configuração do servidor.
- `www.tantielorraine.pt` responde com o próprio site (200), sem redirecionar para `tantielorraine.pt`. Todas as páginas têm `<link rel="canonical">` para o domínio sem "www", o que evita conteúdo duplicado nos motores de busca. Pendente: pedir à Amen um redirecionamento 301 de "www" para o domínio sem "www".
- Endereços inexistentes devolvem o código 404; falta confirmar se mostram a página `404.html` do site ou a página genérica do servidor.

## Opções técnicas

- Fontes alojadas no site em vez do Google Fonts e sem mapas incorporados. O único recurso de terceiros é o Google Analytics 4 (G-PMGEGTHM1W), carregado por `assets/js/main.js` só depois de aceite no aviso de cookies; recusar ou retirar o consentimento (botão "Cookies" no rodapé) não carrega nada da Google e apaga os cookies `_ga`. Política de privacidade, ponto 6.
- As fontes DIN Neuzeit Grotesk, Avenir e DIN Next do site do Reino Unido são licenciadas pela Wix e não estão incluídas; o site usa as alternativas definidas no design system.
- Dois formulários (pedido de informação e opinião de degustação), tratados pelo próprio alojamento: sem reCAPTCHA nem outros serviços de terceiros, sem cookies e sem guardar dados no site.
