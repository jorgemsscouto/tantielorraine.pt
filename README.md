# tantielorraine.pt

Versão portuguesa do site da Tantie Lorraine's (marca de molhos picantes e compotas de malagueta de Trindade e Tobago, feitos à mão em Surrey), baseada no site do Reino Unido, [tantielorraine.co.uk](https://www.tantielorraine.co.uk). Site estático em HTML e CSS, sem dependências nem passo de compilação, construído sobre o design system da marca.

O site é só de apresentação: não há venda nem preços. A página Produtos mostra a gama com o botão "Brevemente". Entidade responsável em Portugal: GADGETMÉTODO, LDA (NIPC 513978011), Rua das Cruzes, n.º 625, 4100-195 Porto.

## Versão WordPress (a publicar)

O site existe em duas formas com o mesmo conteúdo e o mesmo design:

- **Tema WordPress** em `wordpress/tantie-lorraine/`, para o alojamento WordPress da Amen. É a versão a publicar.
- **Site estático** na raiz do repositório, que serviu de base e fica como referência.

### Instalar no WordPress da Amen

1. Criar o ficheiro do tema: `wordpress/empacotar.sh` (gera `wordpress/dist/tantie-lorraine.zip`).
2. No painel do WordPress: Aparência > Temas > Adicionar novo tema > Carregar tema > escolher o zip > Instalar > Ativar.
3. Na ativação, o tema cria as páginas Início, História, Produtos, Eventos, Contactos e Privacidade, define a página inicial e os endereços (`/historia/`, `/produtos/`, ...), e fecha os comentários. Não apaga nada; os conteúdos de exemplo do WordPress ("Sample Page", "Hello world") podem ser apagados à mão.
4. Em Definições > Geral: idioma Português e endereços com `https://`.

Em alternativa ao passo 2, o tema pode ser enviado por FTP a partir do computador, com `scripts/publicar-wordpress.sh` (requer `lftp` e um `.env` na raiz, ignorado pelo Git, com `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `FTP_PORT` e, opcionalmente, `FTP_DIR`):

- `scripts/publicar-wordpress.sh testar`: só leitura; testa a ligação e mostra a pasta pública e os temas instalados.
- `scripts/publicar-wordpress.sh simular`: só leitura; mostra o que seria enviado.
- `scripts/publicar-wordpress.sh enviar`: envia o tema para `wp-content/themes/tantie-lorraine`, depois de escrever ENVIAR; nunca apaga ficheiros no servidor. A seguir, ativar em Aparência > Temas.

Não é preciso exportar nem importar a base de dados, nem substituir o `wp-config.php`: o WordPress pré-instalado da Amen já tem a sua base de dados, e o conteúdo é criado pelo tema na ativação.

### Como funciona

- `theme.json`: cores, tipos de letra (alojados no tema) e tamanhos do design system, disponíveis no editor.
- `patterns/`: conteúdo de cada página e do cabeçalho e rodapé, em blocos normais do WordPress. Cada página guarda apenas uma referência ao seu padrão; ao abrir a página no editor, o padrão passa a blocos editáveis e, depois de guardada, a página deixa de acompanhar as alterações ao tema.
- `assets/css/`: `tokens.css`, `components.css` e `site.css` (os mesmos do site estático) e `wordpress.css` (adaptação aos blocos do WordPress).
- `functions.php`: carregamento dos estilos, configuração inicial, língua `pt-PT`, descrição para motores de pesquisa a partir do resumo da página, ícone do site, sem emojis do WordPress (que carregam ficheiros de s.w.org) e sem comentários.
- Estilos de botão no editor: Compacto, Hero e Brevemente (inativo).

Testado em WordPress 6.5 (servido localmente com SQLite); a marcação dos blocos foi validada com a biblioteca de blocos atual do WordPress, sem avisos.

## Gama em Portugal

| Ref. | Produto | Fotografia |
|---|---|---|
| SBJ | Scotch Bonnet Jam (com selo Great Taste na tampa) | `assets/img/produtos/sbj.webp` |
| ACJ | Apple Chilli Jam | `assets/img/produtos/acj.webp` |
| RPCL | Red Pepper Chilli Lime Jam | `assets/img/produtos/rpcl.webp` |
| TCJ | Tomato Chilli Jam | `assets/img/produtos/tcj.webp` |

## Dados dos produtos

Peso, ingredientes, alergénios e informação nutricional (por 100 g) vêm das fichas técnicas de rótulo da marca (Project.Kitchen, 15 de outubro de 2026), traduzidos para português. Os ingredientes a negrito são os que a ficha assinala como fonte de sulfitos. A ficha da SBJ é a versão "copy using mash" (com pasta fermentada de Scotch Bonnet) e tem os kcal por confirmar.

## Imagens

Logótipo e fotografias são os do site do Reino Unido (inventário de 16 de outubro de 2026), recortados e convertidos para WebP. Cada imagem substitui automaticamente o espaço reservado; se um ficheiro faltar, aparece o espaço reservado, sem ícone de imagem partida. O favicon é o sol do logótipo oficial.

| Ficheiro | Onde aparece |
|---|---|
| `assets/img/logo.png` | Cabeçalho e rodapé (enquanto não existir, aparece o nome em texto) |
| `assets/img/hero.webp` | Fundo desfocado do topo da página inicial (só as filas de cima da pirâmide de frascos: RPCL, ACJ, TCJ) |
| `assets/img/produtos/<ref>.webp` | Cartões de produto (ver tabela acima) |
| `assets/img/inicio-tantie.webp` | Página inicial, bloco "Quem é a Tantie?" |
| `assets/img/sobre.webp` | Página História (retrato 4:5) |
| `assets/img/eventos.webp` | Página Eventos (pelau de uma sessão Limin with Tantie) |

Formato recomendado: WebP, até cerca de 300 KB; produtos quadrados.

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `index.html` | Início: hero, mensagem principal, produtos em destaque, apresentação, testemunhos |
| `produtos.html` | Uma secção por referência: fotografia, peso, descrição, ingredientes, alergénios e informação nutricional |
| `eventos.html` | Aulas de cozinha |
| `historia.html` | História da marca e da fundadora |
| `contactos.html`, `privacidade.html`, `404.html` | Contactos, informação legal, erro |
| `assets/css/tokens.css`, `components.css` | Design system da marca (cores, tipografia, componentes `.tl-*`) |
| `assets/css/fonts.css`, `assets/fonts/` | Anton, Poppins e Work Sans alojadas no próprio site (licença OFL) |
| `assets/css/site.css` | Composição das páginas, só com variáveis do design system |
| `assets/js/main.js` | Menu em ecrãs pequenos e ano no rodapé |
| `.claude/skills/tantie-lorraine-design-system/` | Design system original (regras, tokens, exemplos) |
| `.htaccess` | HTTPS obrigatório, domínio sem "www", página 404, cache e cabeçalhos de segurança (Apache) |
| `scripts/deploy.sh` | Publicação por FTPS no alojamento |

## Por fazer antes de publicar

- Textos marcados com `[A PREENCHER: ...]` (contorno tracejado no navegador): `grep -rn "A PREENCHER" --include=*.html .`
- Autorização escrita da Lorraine para usar a marca, o logótipo, as fotografias e os textos, incluindo o direito de imagem do retrato dela.

## Ver localmente

```bash
python3 -m http.server 8000
```

Abrir http://localhost:8000. O `.htaccess` só tem efeito no servidor Apache.

## Publicar

1. Copiar `.deploy.env.example` para `.deploy.env` e preencher com os dados FTP do alojamento (o ficheiro está no `.gitignore`).
2. Confirmar o que vai ser enviado: `scripts/deploy.sh --simular`
3. Publicar: `scripts/deploy.sh`

Requer `lftp` (`sudo apt install lftp` ou `brew install lftp`).

## Opções técnicas

- Fontes alojadas no site em vez do Google Fonts, sem mapas incorporados nem analytics: não há transferência de dados para terceiros e não é preciso aviso de cookies.
- As fontes DIN Neuzeit Grotesk, Avenir e DIN Next do site do Reino Unido são licenciadas pela Wix e não estão incluídas; o site usa as alternativas definidas no design system.
- Sem formulários: os contactos são feitos por email, telefone ou redes sociais.
