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
- Gama em Portugal: só quatro referências, SBJ (Scotch Bonnet Jam), ACJ (Apple Chilli Jam), RPCL (Red Pepper Chilli Lime Jam) e TCJ (Tomato Chilli Jam). Não mencionar outros produtos da marca (molhos, geleias, relishes, temperos, pastelaria, bebidas, conjuntos) em nenhuma página.
- A referência aparece no cartão (`Ref. SBJ`, em `plum-grey`) e no atributo `data-ref` do `<article>`.
- Dados de produto (peso, ingredientes, alergénios, nutrição) só a partir das fichas técnicas de rótulo da marca; não arredondar nem recalcular valores. Exceção decidida pelo utilizador: energia da SBJ, 1210 kJ / 289 kcal (a ficha diz 264 kcal; ver README).
- Selos (decisão do utilizador, 17/10/2026, contra a regra geral do design system): Great Taste Producer ao centro do cabeçalho; no rodapé, sob "Distinções da marca no Reino Unido", Food Hygiene Rating 5 e Great Taste Producer, 2019, 2021, 2024 e 2026. Todos os selos Great Taste, no site e no email de confirmação, ligam a https://gff.co.uk/directory/producer/?id=25813 (o Food Hygiene Rating não tem ligação). Só ficheiros originais em `assets/img/selos/`, sem alterações. Não acrescentar outros selos sem pedido.
- Nomes de produto em inglês, como no Reino Unido (nomes de marca); descrições em português.
- Não inventar testemunhos, prémios, características de produto ou receitas: usar só o que está confirmado no site do Reino Unido ou foi fornecido pela marca, e marcar o resto com `<span class="a-preencher">[A PREENCHER: ...]</span>`.
- Site só de apresentação: sem preços, sem carrinho e sem página de entregas. Produtos com botão "Brevemente" desativado.
- Cabeçalho e rodapé repetidos em todas as páginas `.html`: qualquer alteração aplica-se a todas. Página nova: acrescentar ao menu, ao rodapé se aplicável e ao `sitemap.xml`.
- Ligações internas com caminho absoluto (`/produtos.html`, `/assets/...`).
- Sem ligações para o site do Reino Unido (tantielorraine.co.uk) nem indicação de versão inglesa (hreflang).
- Menu, por esta ordem: História, Produtos, Eventos, Contactos. Sem páginas de Mercados nem de Receitas. Rodapé: História, Produtos, Contactos, Privacidade, Livro de Reclamações.
- Não acrescentar recursos de terceiros (fontes externas, mapas incorporados, analytics, píxeis) sem atualizar `privacidade.html` e avaliar a necessidade de aviso de cookies.
- Logótipo e fotografias: os do site do Reino Unido, nos caminhos listados no `README.md`. Usar a função de troca já existente (`<img hidden onload=...>` seguido do espaço reservado) para imagens novas, com `alt` descritivo.
- Entidade em Portugal: GADGETMÉTODO, LDA, NIPC 513978011, Rua das Cruzes, n.º 625, 4100-195 Porto. Email do site: info@tantielorraine.pt. O formulário de contactos (`contacto.php`) envia os pedidos para info@tantielorraine.pt e gadgetmetodo@gmail.com e uma confirmação de receção a quem escreveu, que não repete o conteúdo do pedido.

## Git

- Repositório privado `jorgemsscouto/tantielorraine.pt`, ramo `main`.
- Depois de cada alteração concluída: commit (mensagem curta em pt-PT) e `git push`.
- Nunca versionar credenciais (`.env`, com os dados FTP e do email; modelo em `.env.example`; `config-email.php`, que só existe no servidor) nem as cópias em `backups/`. Ficheiro sensível novo: acrescentar ao `.gitignore` antes do commit.
