# Materiais

Ficheiros de apoio que não fazem parte do site: `scripts/deploy.sh` não envia esta pasta para o servidor.

| Ficheiro | O que é |
|---|---|
| `qr-degustacao.png`, `qr-degustacao.svg` | Código QR genérico para https://tantielorraine.pt/degustacao.html (sem local pré-preenchido). Correção de erros Q; PNG com 1968 px de lado, SVG vetorial para gráfica. Impressão: pelo menos 3 x 3 cm numa mesa, 10 a 20 cm num cartaz |
| `folheto-amostras.pdf` | Folheto para os sacos das amostras: quatro A6 (105 x 148,5 mm) numa folha A4, para cortar ao meio nos dois sentidos pelas linhas tracejadas. Código QR do inquérito de degustação com o local "Amostra em casa" já preenchido, para as respostas destas amostras se distinguirem das outras. Imprimir a 100 % (tamanho real), com os gráficos de fundo; papel de 160 a 250 g/m² fica mais elegante |
| `folheto-amostras.html` | Fonte do folheto. Para alterar, editar e gerar de novo o PDF no Chrome (Imprimir > Guardar como PDF, A4, margens Nenhuma, Gráficos de fundo) |
| `qr-amostras.svg`, `qr-amostras.png` | Código QR do folheto: https://tantielorraine.pt/degustacao.html?local=Amostra%20em%20casa (correção de erros Q) |
| `fontes/` | Work Sans Light (licença OFL), só para o texto corrido do folheto impresso |
| `ficha-degustacao-chilli-jams.pdf` | Ficha em papel "Tantie Lorraine: Degustação de Chilli Jams", fornecida pelo utilizador, que deu origem ao formulário de degustação (o formulário é uma versão curta) |

Código QR de uma degustação com o local já preenchido: usar o endereço `https://tantielorraine.pt/degustacao.html?local=<nome do local>` (até 80 caracteres). Para gerar, por exemplo com a biblioteca Python `segno`:

```
python3 -m venv /tmp/qr && /tmp/qr/bin/pip install -q segno
/tmp/qr/bin/python -c "import segno; segno.make('https://tantielorraine.pt/degustacao.html?local=Mercado do Bolhão', error='q').save('qr-bolhao.png', scale=48, border=4)"
```
