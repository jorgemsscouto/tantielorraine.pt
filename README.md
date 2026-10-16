# tantielorraine.pt

Versão portuguesa do site da Tantie Lorraine's (marca de molhos picantes e compotas de malagueta de Trindade e Tobago, feitos à mão em Surrey), baseada no site do Reino Unido, [tantielorraine.co.uk](https://www.tantielorraine.co.uk). Site estático em HTML e CSS, sem dependências nem passo de compilação, construído sobre o design system da marca.

A loja está em modo "Brevemente": os produtos aparecem com preços indicativos em euros e o botão de compra está desativado.

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `index.html` | Início: hero, mensagem principal, produtos em destaque, apresentação, testemunhos |
| `loja.html` | Gama por categoria, com preços indicativos e botão "Brevemente" |
| `mercados.html` | Mercados em Portugal (brevemente) e ligação aos do Reino Unido |
| `receitas.html` | Receitas do site do Reino Unido (métodos por traduzir) |
| `eventos.html` | Aulas de cozinha |
| `sobre.html` | História da marca e da fundadora |
| `contactos.html`, `entregas.html`, `privacidade.html`, `404.html` | Contactos, entregas e devoluções, informação legal, erro |
| `assets/css/tokens.css`, `components.css` | Design system da marca (cores, tipografia, componentes `.tl-*`) |
| `assets/css/fonts.css`, `assets/fonts/` | Anton, Poppins e Work Sans alojadas no próprio site (licença OFL) |
| `assets/css/site.css` | Composição das páginas, só com variáveis do design system |
| `assets/js/main.js` | Menu em ecrãs pequenos e ano no rodapé |
| `.claude/skills/tantie-lorraine-design-system/` | Design system original (regras, tokens, exemplos) |
| `.htaccess` | HTTPS obrigatório, domínio sem "www", página 404, cache e cabeçalhos de segurança (Apache) |
| `scripts/deploy.sh` | Publicação por FTPS no alojamento |

## Por fazer antes de publicar

- Textos marcados com `[A PREENCHER: ...]` (contorno tracejado no navegador): `grep -rn "A PREENCHER" --include=*.html .`
- Fotografias: produtos, hero e páginas interiores (procurar `TODO` nos `.html`).
- Logótipo oficial (procurar `TODO: logótipo oficial`).
- Validar os preços em euros (ver `loja.html` e `index.html`).

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
