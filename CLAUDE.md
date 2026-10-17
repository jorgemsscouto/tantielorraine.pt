# tantielorraine.pt

Versão portuguesa do site da Tantie Lorraine's (tantielorraine.co.uk). Site estático em HTML, CSS e JavaScript simples, sem frameworks nem passo de compilação. Ver `README.md` para estrutura, pendentes e publicação.

## Design system

Usar sempre a skill `tantie-lorraine-design-system` (em `.claude/skills/`). Em resumo: só variáveis de `tokens.css`, tipografia pelas classes `.tl-text-*`, componentes `.tl-*` de `components.css`, vermelho só em blocos inteiros e só os selos indicados nas regras abaixo. O logótipo é o ficheiro oficial do site do Reino Unido (`assets/img/logo.png`), nunca desenhado.

Adaptações deste site ao sistema (manter):
- Texto em português de Portugal (o sistema prevê inglês britânico para o site do Reino Unido).
- Fontes servidas localmente (`assets/css/fonts.css`), sem o `@import` do Google Fonts.
- Títulos em Anton com entrelinha 1,2 (em vez de 1), para os acentos das maiúsculas não tocarem na linha de cima.
- Títulos grandes reduzem em ecrãs estreitos com `clamp()`, com o valor do token como máximo.
- `assets/css/tokens.css` e `components.css` são cópias do sistema; se o sistema mudar, atualizar a partir da skill.

## Regras

- Português de Portugal em todo o conteúdo e nas mensagens de commit. Sem travessão longo.
- Site em três línguas (pedido do utilizador, 17/10/2026): português, principal e fonte; inglês britânico em `/en/` (en-GB); espanhol de Espanha em `/es/` (es-ES). As páginas de `/en/` e `/es/`, o seletor de língua, o hreflang e o `sitemap.xml` são gerados por `python3 scripts/idiomas.py` a partir das páginas portuguesas e da tabela de traduções no próprio script: nunca editar `/en/` nem `/es/` à mão. Depois de alterar texto numa página portuguesa, atualizar a tabela e correr o script (falha se faltar uma tradução ou sobrar português). Ingredientes em inglês fiéis às fichas técnicas originais (em inglês).
- Onde se refere Surrey, escrever "Surrey, Londres" (em inglês, "Surrey, London"), decisão do utilizador, 17/10/2026.
- O país escreve-se "Trinidad e Tobago" (inglês "Trinidad and Tobago", espanhol "Trinidad y Tobago") e a ilha "Trinidad", nunca "Trindade" (decisão do utilizador, 17/10/2026, embora "Trindade e Tobago" seja a forma corrente em português de Portugal).
- Gama em Portugal: só quatro referências, SBJ (Scotch Bonnet Jam), ACJ (Apple Chilli Jam), RPCL (Red Pepper Chilli Lime Jam) e TCJ (Tomato Chilli Jam). Não mencionar outros produtos da marca (molhos, geleias, relishes, temperos, pastelaria, bebidas, conjuntos) em nenhuma página. Exceção decidida pelo utilizador (17/10/2026): `publicacoes.html` reproduz publicações do Instagram da marca e pode referi-los, com a nota de que nem todos estão à venda em Portugal.
- A referência aparece no cartão (`Ref. SBJ`, em `plum-grey`) e no atributo `data-ref` do `<article>`.
- Dados de produto (peso, ingredientes, alergénios, nutrição) só a partir das fichas técnicas de rótulo da marca; não arredondar nem recalcular valores. Exceção decidida pelo utilizador: energia da SBJ, 1210 kJ / 289 kcal (a ficha diz 264 kcal; ver README).
- Selos (decisão do utilizador, 17/10/2026, contra a regra geral do design system): Great Taste 2026 (uma estrela) ao centro do cabeçalho; no rodapé, sob "Distinções da marca no Reino Unido", Food Hygiene Rating 5 e Great Taste Producer, 2019, 2021, 2024 e 2026. Todos os selos Great Taste, no site e no email de confirmação, ligam a https://gff.co.uk/directory/producer/?id=25813 (o Food Hygiene Rating não tem ligação). Só ficheiros originais em `assets/img/selos/`, sem alterações. Não acrescentar outros selos sem pedido.
- Nomes de produto em inglês em todas as línguas, como no Reino Unido (nomes de marca); descrições traduzidas.
- Não inventar testemunhos, prémios, características de produto ou receitas: usar só o que está confirmado no site do Reino Unido ou foi fornecido pela marca, e marcar o resto com `<span class="a-preencher">[A PREENCHER: ...]</span>`.
- Site só de apresentação: sem preços, sem carrinho e sem página de entregas. Produtos com botão "Brevemente" desativado.
- Cabeçalho e rodapé repetidos em todas as páginas `.html`: qualquer alteração aplica-se a todas. Página ou ficheiro retirado: apagar do repositório e acrescentar o caminho a `scripts/retirados.txt`, para ser apagado no servidor. Página nova: acrescentar ao menu, ao rodapé se aplicável, ao `sitemap.xml` e ao `llms.txt`, com dados estruturados (JSON-LD) no `<head>`.
- Ligações internas com caminho absoluto (`/produtos.html`, `/assets/...`).
- Sem ligações para o site do Reino Unido (tantielorraine.co.uk). O hreflang liga só as três versões deste site.
- Menu, por esta ordem: História, Produtos, Publicações, Contactos, seguido do seletor PT · EN · ES (abaixo de 1000px, menu compacto). Sem páginas de Mercados, Receitas nem Eventos (Eventos retirada a pedido do utilizador, 17/10/2026). Rodapé: História, Produtos, Publicações, Contactos, Privacidade, Livro de Reclamações.
- Motores de busca e IA (ver README): os factos repetidos na página, nas perguntas frequentes de `produtos.html`, nos dados estruturados e no `llms.txt` têm de coincidir; ao mudar um, mudar todos. Perguntas frequentes só com factos já publicados.
- Não acrescentar recursos de terceiros (fontes externas, mapas incorporados, analytics, píxeis) sem atualizar `privacidade.html` e avaliar a necessidade de aviso de cookies. Único em uso: Google Analytics 4 (G-PMGEGTHM1W), carregado por `assets/js/main.js` só depois de aceite no aviso de cookies (aceitar e recusar com o mesmo destaque; "Cookies" no rodapé para mudar a escolha); descrito no ponto 6 de `privacidade.html`.
- Logótipo e fotografias: os do site do Reino Unido, nos caminhos listados no `README.md`. Usar a função de troca já existente (`<img hidden onload=...>` seguido do espaço reservado) para imagens novas, com `alt` descritivo.
- Entidade em Portugal: GADGETMÉTODO, LDA, NIPC 513978011, Rua das Cruzes, n.º 625, 4100-195 Porto. Email do site: info@tantielorraine.pt. Os formulários enviam só para info@tantielorraine.pt (decisão do utilizador, 17/10/2026; destinatários em `email-comum.php`). O de contactos (`contacto.php`, nas três línguas) envia também uma confirmação de receção a quem escreveu, na língua da página, que não repete o conteúdo do pedido.
- Formulário de opinião de degustação (`degustacao.html` e `degustacao.php`, pedidos do utilizador, 17/10/2026): versão curta da ficha em papel da marca (por compota: sabor principal, nota global, compraria, nível de picante e melhor utilização), com a fotografia do ingrediente principal e uma linha com os ingredientes junto de cada nome (nada de siglas sozinhas); só mostra a classificação das compotas marcadas como provadas. Pede local (preenchido por `?local=` nos códigos QR), nome, email e telefone (opcional). Envia a opinião para info@ (em português, gráfico, com as linhas para o Excel) e um agradecimento a quem respondeu, na língua escolhida, sem repetir texto escrito pela pessoa. Campos de texto com ditado por voz do navegador (Web Speech API; o site não recebe áudio; ponto 2 de `privacidade.html`). É página de serviço: fora do menu, do sitemap e do `llms.txt`, com `noindex` e sem JSON-LD (exceção à regra das páginas novas); ligação no fim de `produtos.html`. As chaves dos campos têm de coincidir entre a página e o PHP.

## Git

- Repositório privado `jorgemsscouto/tantielorraine.pt`, ramo `main`.
- Depois de cada alteração concluída: commit (mensagem curta em pt-PT) e `git push`.
- Cada push para `main` publica o site na Amen (GitHub Actions, `.github/workflows/publicar.yml`): só fazer push de trabalho pronto a ser visto. Credenciais só nos segredos do repositório (FTP_HOST, FTP_USER, FTP_PASS, SMTP_PASS), nunca no ficheiro do workflow.
- Nunca versionar credenciais (`.env`, com os dados FTP e do email; modelo em `.env.example`; `config-email.php`, que só existe no servidor) nem as cópias em `backups/`. Ficheiro sensível novo: acrescentar ao `.gitignore` antes do commit.
