# tantielorraine.pt

Versão portuguesa do site da Tantie Lorraine's (tantielorraine.co.uk). Site estático em HTML, CSS e JavaScript simples, sem frameworks nem passo de compilação. Ver `README.md` para estrutura, pendentes e publicação.

## Design system

Usar sempre a skill `tantie-lorraine-design-system` (em `.claude/skills/`). Em resumo: só variáveis de `tokens.css`, tipografia pelas classes `.tl-text-*`, componentes `.tl-*` de `components.css`, vermelho só em blocos inteiros, sem logótipo desenhado e sem selos Great Taste ou Food Hygiene Rating.

Adaptações deste site ao sistema (manter):
- Texto em português de Portugal (o sistema prevê inglês britânico para o site do Reino Unido); preços em euros no formato `7,00 €`.
- Fontes servidas localmente (`assets/css/fonts.css`), sem o `@import` do Google Fonts.
- Títulos em Anton com entrelinha 1,2 (em vez de 1), para os acentos das maiúsculas não tocarem na linha de cima.
- Títulos grandes reduzem em ecrãs estreitos com `clamp()`, com o valor do token como máximo.
- `assets/css/tokens.css` e `components.css` são cópias do sistema; se o sistema mudar, atualizar a partir da skill.

## Regras

- Português de Portugal em todo o conteúdo e nas mensagens de commit. Sem travessão longo.
- Nomes de produto em inglês, como no Reino Unido (nomes de marca); descrições em português.
- Não inventar testemunhos, prémios, características de produto ou receitas: usar só o que está confirmado no site do Reino Unido ou foi fornecido pela marca, e marcar o resto com `<span class="a-preencher">[A PREENCHER: ...]</span>`.
- Os preços são indicativos (conversão de GBP) até a marca os validar; manter o aviso na loja enquanto assim for.
- Cabeçalho e rodapé repetidos em todas as páginas `.html`: qualquer alteração aplica-se a todas. Página nova: acrescentar ao menu, ao rodapé se aplicável e ao `sitemap.xml`.
- Ligações internas com caminho absoluto (`/loja.html`, `/assets/...`).
- Não acrescentar recursos de terceiros (fontes externas, mapas incorporados, analytics, píxeis) sem atualizar `privacidade.html` e avaliar a necessidade de aviso de cookies.
- Imagens otimizadas (WebP ou JPEG, até cerca de 300 KB) em `assets/img/`, sempre com `alt` descritivo.

## Git

- Repositório privado `jorgemsscouto/tantielorraine.pt`, ramo `main`.
- Depois de cada alteração concluída: commit (mensagem curta em pt-PT) e `git push`.
- Nunca versionar credenciais (`.deploy.env`). Ficheiro sensível novo: acrescentar ao `.gitignore` antes do commit.
