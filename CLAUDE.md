# tantielorraine.pt

Site institucional estático do domínio tantielorraine.pt (registado na Amen.pt). HTML, CSS e JavaScript simples, sem frameworks nem passo de compilação. Ver `README.md` para estrutura e publicação.

## Regras

- Português de Portugal em todo o conteúdo e nas mensagens de commit. Sem travessão longo.
- O cabeçalho (`<header>`) e o rodapé (`<footer>`) estão repetidos em todas as páginas `.html`: qualquer alteração a um deles aplica-se a todas.
- Página nova: acrescentar ao menu de todas as páginas e ao `sitemap.xml`.
- Ligações internas com caminho absoluto (`/sobre.html`, `/assets/...`), para funcionarem também na página 404.
- Não acrescentar recursos de terceiros (tipos de letra, mapas incorporados, analytics, píxeis) sem atualizar `privacidade.html` e avaliar a necessidade de aviso de cookies.
- Texto provisório marcado com `<span class="a-preencher">[A PREENCHER: ...]</span>`; ao colocar o texto final, remover o `span`.
- Imagens: versões otimizadas (WebP ou JPEG, até cerca de 300 KB) em `assets/img/`, sempre com `alt` descritivo.

## Git

- Repositório privado `jorgemsscouto/tantielorraine.pt`, ramo `main`.
- Depois de cada alteração concluída: commit (mensagem curta em pt-PT) e `git push`.
- Nunca versionar credenciais (`.deploy.env`). Ficheiro sensível novo: acrescentar ao `.gitignore` antes do commit.
