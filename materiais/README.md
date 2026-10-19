# Materiais

Ficheiros de apoio que não fazem parte do site: `scripts/deploy.sh` não envia esta pasta para o servidor.

| Ficheiro | O que é |
|---|---|
| `qr-degustacao.png`, `qr-degustacao.svg` | Código QR genérico para https://tantielorraine.pt/degustacao.html (sem local pré-preenchido). Correção de erros Q; PNG com 1968 px de lado, SVG vetorial para gráfica. Impressão: pelo menos 3 x 3 cm numa mesa, 10 a 20 cm num cartaz |
| `ficha-degustacao-chilli-jams.pdf` | Ficha em papel "Tantie Lorraine: Degustação de Chilli Jams", fornecida pelo utilizador, que deu origem ao formulário de degustação (o formulário é uma versão curta) |

Código QR de uma degustação com o local já preenchido: usar o endereço `https://tantielorraine.pt/degustacao.html?local=<nome do local>` (até 80 caracteres). Para gerar, por exemplo com a biblioteca Python `segno`:

```
python3 -m venv /tmp/qr && /tmp/qr/bin/pip install -q segno
/tmp/qr/bin/python -c "import segno; segno.make('https://tantielorraine.pt/degustacao.html?local=Mercado do Bolhão', error='q').save('qr-bolhao.png', scale=48, border=4)"
```
