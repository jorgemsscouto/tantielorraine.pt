---
name: tantie-lorraine-design-system
description: Design system da marca Tantie Lorraine (tantielorraine.co.uk). Usar sempre que se construa ou altere interface, página, e-mail, landing page ou material digital para a Tantie Lorraine ou para a sua representação ibérica.
---

# Tantie Lorraine Design System

## Ficheiros

- `tokens.css`: variáveis CSS (cores, espaçamento, raios, sombra, famílias) e classes de texto `.tl-text-<estilo>`. Importar primeiro.
- `components.css`: classes dos componentes (`.tl-btn`, `.tl-header`, `.tl-card`, `.tl-qty`, `.tl-crumbs`, `.tl-footer`). Importar depois de `tokens.css`.
- `tokens.json`: os mesmos valores em JSON, com nota de uso por token. Fonte de verdade; para Tailwind ou outro framework, mapear a partir daqui.
- `examples.html`: marcação de referência de cada componente. Copiar a estrutura em vez de a reinventar.

## Regras obrigatórias

1. Usar sempre as variáveis (`var(--chilli-red)`, `var(--space-gutter)`); nunca escrever hex, px de espaçamento ou raios soltos no código.
2. Usar as classes `.tl-text-*` para tipografia; não criar tamanhos fora da escala.
3. Tema único (claro). Não inventar modo escuro sem pedido explícito.
4. Não desenhar nem aproximar o logótipo. Até haver ficheiro oficial, usar o nome em `.tl-wordmark` e deixar comentário `<!-- TODO: logótipo oficial -->`.
5. Não usar os selos Great Taste nem Food Hygiene Rating: são marcas de terceiros.
6. Texto do site em inglês britânico (en-GB); preços em libras com duas casas (£6.00), salvo indicação para o mercado ibérico (EUR, PT/ES).

## A marca

Marca artesanal de sabores caribenhos e trinitários feita em Surrey. O site é um cartaz de mercado: vermelho chilli a todo o comprimento, letra condensada gigante em maiúsculas, fotografia de frascos sobre fundo verde escuro e muito branco à volta.

## Princípios

- Um só vermelho, usado em blocos inteiros. `chilli-red` ocupa cabeçalho, rodapé e faixas completas; nunca aparece em gradiente, em tons claros nem como fundo de cartão.
- Contraste de cartaz. Fora do vermelho, o sistema é `ink` sobre `paper`. Não há cinzentos intermédios no texto corrente.
- A tipografia faz o trabalho visual. Títulos em Anton, maiúsculas, entrelinha 1. Cada página tem um título dominante.
- Fotografia de produto real, com cantos `radius-media` (20px). Blocos de cor, botões de texto e cabeçalho ficam retos (`radius-none`) ou em pílula (`radius-pill`), sem meio-termo.

## Conteúdo e tom

- Inglês britânico, informal e caloroso, com expressões trinitárias: "Chilli Tings", "Word on the street is", "Caribbean flavours from sunny Surrey".
- Descrições de produto em três parágrafos curtos: o que é e como sabe ("Medium heat, big flavour, endlessly versatile."), onde usar ("brilliant as a glaze for sausages or roasted vegetables"), e o argumento de prova ("No artificial preservatives.").
- Linha de atributos separada por pontos médios: "Gluten-free · Vegan · Vegetarian · Handmade in Surrey, UK".
- Maiúsculas em títulos, navegação e botão principal (ADD TO CART, SHOP NOW). Title Case em nomes de produto e no botão compacto (Add to Cart).
- Sem emojis. Preços em libras com duas casas: £6.00.

## Cor

- Fundo de página: `paper`. Secção de destaque de produtos: `mist`.
- Cabeçalho, rodapé e faixa de testemunhos: `chilli-red` com texto e ícones em `ink`. Nunca texto `paper` pequeno sobre `chilli-red` (3,79:1).
- `chilli-red` como cor de texto só a partir de 24px (H1 de produto, `product-title`) e no item ativo do breadcrumb (16px, abaixo de 4,5:1 no original; em materiais novos usar `ink` sublinhado).
- `plum-grey` só para SKU e metadados. `muted` só para estados inativos, nunca para texto a ler.
- `platform-blue` é resíduo do widget de avaliações do Wix; não faz parte da marca.

## Tipografia

- `display` (Anton, Google Fonts) para tudo o que é título, botão principal e copyright. Estilos: `hero-mega`, `statement`, `section-title`, `product-title`, `subhead`, `button-label`, `label-strong`, `legal`.
- `condensed` (DIN Neuzeit Grotesk Bold Condensed) para navegação, links de rodapé, preço e categorias: `nav`, `price`, `category`. É fonte comercial servida pelo Wix; fora do site, cai para Anton.
- `body` (Avenir 35 Light) para descrições: `body`, 16/24.
- `ui` (DIN Next Light) para breadcrumb, rótulos, SKU e estados vazios: `ui-lg`, `ui`, `ui-sm`.
- `product` (Poppins SemiBold) só no nome do produto dentro do cartão: `card-title`.
- `quote` (Work Sans SemiBold) só na faixa de testemunhos: `quote-title`, `quote-name`.
- Avenir, DIN Next e DIN Neuzeit Grotesk são licenciadas via Wix e não estão incluídas neste sistema. Em materiais fora do site, usar Anton para títulos e as alternativas das pilhas de fallback.

## Espaçamento, raios e sombra

- Margem lateral `space-gutter` (32px); colunas da página de produto separadas por `space-column-gap` (40px); cartões por `space-card-gap` (20px).
- Botão principal: `space-btn-y` × `space-btn-x`, contorno `border-button` em `ink`, `radius-pill`. Botão compacto: `space-btn-compact-y` × `space-btn-compact-x`, fundo `ink`, `radius-button-compact`.
- `shadow-hard` (deslocada, sem desfoque) só no SHOP NOW do hero. O resto do site não tem sombras.
- Cabeçalho fixo com `space-header` (92px) de altura.

## Imagem

- Frascos e garrafas fotografados de perto sobre verde escuro, com malaguetas vermelhas, amarelas e verdes desfocadas à frente e atrás.
- Receitas em grande plano, luz natural, ocupando metade da largura ao lado do texto.
- Hero: fotografia desfocada a toda a largura com o título `hero-mega` em `paper` por cima.

## Logótipo e selos

- Logótipo: sol de traço fino sobre a palavra TANTIE LORRAINE'S™ em `ink`, alinhado à esquerda no cabeçalho e no rodapé. O ficheiro não foi copiado para este sistema; obter o original junto da marca antes de o usar. Nos exemplos, o nome surge em texto simples.
- O selo Great Taste ao centro do cabeçalho e os selos do rodapé (Great Taste, Food Hygiene Rating 5) são marcas de terceiros e não fazem parte deste sistema.

## Iconografia

- Ícones de linha fina em `ink`: chevrons de navegação, saco de compras com contador, mais e menos no seletor de quantidade.
- Redes sociais (Facebook, Instagram, TikTok, WhatsApp, Pinterest, X) como ícones preenchidos em `ink`. Usar os ícones oficiais de cada rede, sem os redesenhar.

## Componentes

### Button

Três botões, todos em pílula: principal com contorno, compacto preenchido e hero com sombra dura.

- Principal (`tl-btn tl-btn--primary`): ação de compra na página de produto. Ocupa a largura da coluna de informação (cerca de 400px), 58px de altura, contorno `border-button` em `ink`, etiqueta `button-label` em maiúsculas. Um por página.
- Compacto (`tl-btn tl-btn--compact`): Add to Cart dentro de cartões e carrosséis. Fundo `ink`, texto `paper` em `condensed` 18px, Title Case, `radius-button-compact`.
- Hero (`tl-btn tl-btn--hero`): SHOP NOW na homepage. Contorno `ink`, `shadow-hard`, Anton 15px. Só no hero.

O consumidor fornece o texto e o elemento (`button` para ações, `a` para navegação). Não usar `chilli-red` como fundo de botão: o vermelho é do cabeçalho e do rodapé.

### SiteHeader

Cabeçalho fixo em `chilli-red`, 92px (`space-header`), com logótipo à esquerda, navegação `nav` à direita e saco de compras.

- No site, o selo Great Taste fica centrado e sobreposto ao limite inferior da barra. É marca de terceiros: só usar com autorização e com o ficheiro original.
- O logótipo real (sol de traço fino sobre TANTIE LORRAINE'S™) não está neste sistema; a pré-visualização usa o nome em Anton como substituto.
- Navegação em maiúsculas, `ink`, sem sublinhado em repouso. Em ecrãs estreitos o site corta a lista com uma seta; preferir um menu.
- O consumidor fornece os links e o número de artigos do carrinho.

### ProductCard

Cartão de produto para carrosséis e grelhas: fotografia quadrada com `radius-media`, nome em `card-title`, preço em `ui` e botão compacto.

- Sem contorno, fundo ou sombra no cartão; a fotografia é o objeto.
- Tudo centrado. Cartões separados por `space-card-gap` (20px).
- O consumidor fornece a fotografia (frasco sobre fundo verde escuro, como no site), o nome em Title Case e o preço em libras com duas casas.

### QuantityStepper

Dois botões circulares de 40px com contorno `mist` (`border-stepper`) e o valor ao centro, sob o rótulo `ui-sm`.

- Fica entre a linha de atributos e o botão principal na página de produto.
- O contorno `mist` é muito fraco sobre `paper` (abaixo de 3:1), tal como no site; os sinais em `ink` garantem a leitura. Em materiais novos, considerar contorno `muted`.
- O consumidor fornece o valor, o mínimo e o máximo.

### Breadcrumb

Trilho em `ui` 16px com separador e página atual em `chilli-red`, e navegação Previous / Next em `muted` separada por um traço vermelho.

- Página atual em `chilli-red` a 16px fica em 3,79:1 sobre `paper`, abaixo do mínimo; é o original. Em materiais novos, manter `ink` e sinalizar a página atual com sublinhado.
- O consumidor fornece os níveis do trilho e os links anterior e seguinte.

### SiteFooter

Rodapé em `chilli-red` com links `nav` sublinhados em duas colunas, ícones sociais em `ink`, logótipo e linha `legal` alinhada à direita.

- No site, a zona central mostra selos de terceiros (Great Taste, Food Hygiene Rating); não fazem parte da marca.
- Os ícones sociais (Facebook, Instagram, TikTok) usam os ícones oficiais preenchidos em `ink`.
- O consumidor fornece os links, o logótipo original e o ano.

