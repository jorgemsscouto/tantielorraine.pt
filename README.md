# tantielorraine.pt

Site institucional estático (HTML e CSS, sem dependências nem passo de compilação) do domínio tantielorraine.pt, registado na Amen.pt.

## Estrutura

| Caminho | Conteúdo |
|---|---|
| `index.html` | Início |
| `sobre.html` | História, missão e valores |
| `servicos.html` | Serviços ou produtos |
| `galeria.html` | Galeria de imagens |
| `contactos.html` | Contactos, morada, horário, mapa (ligação, sem incorporação) |
| `privacidade.html` | Política de privacidade, Livro de Reclamações e resolução alternativa de litígios |
| `404.html` | Página de erro |
| `assets/css/style.css` | Estilos; cores e tipos de letra nas variáveis de `:root` |
| `assets/js/main.js` | Menu em ecrãs pequenos e ano no rodapé |
| `assets/img/` | Imagens otimizadas e favicon |
| `.htaccess` | HTTPS obrigatório, domínio sem "www", página 404, cache e cabeçalhos de segurança (Apache) |
| `robots.txt`, `sitemap.xml` | Indexação nos motores de pesquisa |
| `scripts/deploy.sh` | Publicação por FTPS no alojamento |

## Conteúdo por preencher

Os textos provisórios estão marcados com `[A PREENCHER: ...]` e realçados a amarelo no navegador. Para listar o que falta:

```bash
grep -rn "A PREENCHER" --include=*.html .
```

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

- Sem tipos de letra externos (Google Fonts) nem mapas incorporados: evita transferências de dados para terceiros e dispensa aviso de cookies.
- Sem formulários: os contactos são feitos por email, telefone ou redes sociais. Um formulário exige tratamento no servidor (PHP) e atualização da política de privacidade.
