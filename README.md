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
| `.htaccess` | Regras para Apache (HTTPS, domínio sem "www", página 404, cache, cabeçalhos de segurança). Sem efeito no alojamento da Amen (ver abaixo); mantido para o caso de mudança de servidor |
| `scripts/deploy.sh` | Publicação por FTP/FTPS com o curl: testar, backup, enviar, retirar o WordPress |

## Por fazer antes de publicar

- Textos marcados com `[A PREENCHER: ...]` (contorno tracejado no navegador): `grep -rn "A PREENCHER" --include=*.html .`
- Autorização escrita da Lorraine para usar a marca, o logótipo, as fotografias e os textos, incluindo o direito de imagem do retrato dela.

## Ver localmente

```bash
python3 -m http.server 8000
```

Abrir http://localhost:8000.

## Publicar (alojamento da Amen)

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

### Comportamento do servidor da Amen (confirmado em 17/10/2026)

- Os ficheiros são servidos diretamente pelo nginx, que não lê o `.htaccess`: não se aplicam o `DirectoryIndex`, os redirecionamentos, a página de erro, a cache nem os cabeçalhos de segurança aí definidos. Alterações destas pedem-se à Amen ou fazem-se no painel.
- `http://` redireciona para `https://` (301), por configuração do servidor.
- `www.tantielorraine.pt` responde com o próprio site (200), sem redirecionar para `tantielorraine.pt`. Todas as páginas têm `<link rel="canonical">` para o domínio sem "www", o que evita conteúdo duplicado nos motores de busca. Pendente: pedir à Amen um redirecionamento 301 de "www" para o domínio sem "www".
- Endereços inexistentes devolvem o código 404; falta confirmar se mostram a página `404.html` do site ou a página genérica do servidor.

## Opções técnicas

- Fontes alojadas no site em vez do Google Fonts, sem mapas incorporados nem analytics: não há transferência de dados para terceiros e não é preciso aviso de cookies.
- As fontes DIN Neuzeit Grotesk, Avenir e DIN Next do site do Reino Unido são licenciadas pela Wix e não estão incluídas; o site usa as alternativas definidas no design system.
- Sem formulários: os contactos são feitos por email, telefone ou redes sociais.
