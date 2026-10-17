#!/usr/bin/env python3
"""Gera as versões em inglês (/en/) e espanhol (/es/) a partir das páginas em português, que são a fonte.

Para cada página portuguesa: substitui os fragmentos de texto pelas traduções da tabela abaixo, traduz os dados
estruturados (JSON-LD), troca as ligações internas pelas da língua, e acrescenta a todas as páginas (também às
portuguesas) as ligações hreflang e o seletor de língua. Escreve ainda o sitemap.xml com as três línguas.

Uso: python3 scripts/idiomas.py   (correr depois de qualquer alteração a uma página em português)
Falha, sem escrever nada, se um fragmento da tabela já não existir na página portuguesa ou se sobrar texto em
português numa página traduzida: nesse caso, atualizar a tabela.
"""
import json
import re
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
SITE = 'https://tantielorraine.pt'
GUILD = 'https://gff.co.uk/directory/producer/?id=25813'
DATA_SITEMAP = '2026-10-17'

# Língua: (código, og:locale, rótulo do seletor, nome da língua, rótulo do grupo)
LINGUAS = {
    'pt': ('pt-PT', 'pt_PT', 'PT', 'Português', 'Idioma'),
    'en': ('en-GB', 'en_GB', 'EN', 'English', 'Language'),
    'es': ('es-ES', 'es_ES', 'ES', 'Español', 'Idioma'),
}
# Página portuguesa: caminho público em cada língua
PAGINAS = {
    'index.html': {'pt': '/', 'en': '/en/', 'es': '/es/'},
    'historia.html': {'pt': '/historia.html', 'en': '/en/story.html', 'es': '/es/historia.html'},
    'produtos.html': {'pt': '/produtos.html', 'en': '/en/products.html', 'es': '/es/productos.html'},
    'publicacoes.html': {'pt': '/publicacoes.html', 'en': '/en/posts.html', 'es': '/es/publicaciones.html'},
    'contactos.html': {'pt': '/contactos.html', 'en': '/en/contact.html', 'es': '/es/contacto.html'},
    'privacidade.html': {'pt': '/privacidade.html', 'en': '/en/privacy.html', 'es': '/es/privacidad.html'},
    'degustacao.html': {'pt': '/degustacao.html', 'en': '/en/tasting.html', 'es': '/es/degustacion.html'},
}
# Páginas fora do sitemap: o formulário de degustação é para quem provou (ligação em Produtos ou código QR) e tem noindex
SEM_SITEMAP = {'degustacao.html'}

def alt_selo(pt, en, es):
    sufixo = ('prémios da marca no diretório da Guild of Fine Food',
              "the brand's awards in the Guild of Fine Food directory",
              'premios de la marca en el directorio de la Guild of Fine Food')
    return (f'{pt}: {sufixo[0]}', f'{en}: {sufixo[1]}', f'{es}: {sufixo[2]}')

# Fragmentos comuns a várias páginas (cabeçalho, rodapé, cartões); podem não existir em todas
COMUM = [
    ('>Saltar para o conteúdo</a>', '>Skip to content</a>', '>Saltar al contenido</a>'),
    ('aria-label="Tantie Lorraine\'s, início"', 'aria-label="Tantie Lorraine\'s, home"', 'aria-label="Tantie Lorraine\'s, inicio"'),
    alt_selo('Great Taste 2026, uma estrela', 'Great Taste 2026, one star', 'Great Taste 2026, una estrella'),
    alt_selo('Great Taste Producer', 'Great Taste Producer', 'Great Taste Producer'),
    alt_selo('Great Taste 2019, uma estrela', 'Great Taste 2019, one star', 'Great Taste 2019, una estrella'),
    alt_selo('Great Taste 2021, uma estrela', 'Great Taste 2021, one star', 'Great Taste 2021, una estrella'),
    alt_selo('Great Taste 2024, duas estrelas', 'Great Taste 2024, two stars', 'Great Taste 2024, dos estrellas'),
    ('aria-controls="nav-principal">Menu</button>', 'aria-controls="nav-principal">Menu</button>', 'aria-controls="nav-principal">Menú</button>'),
    ('aria-label="Principal"', 'aria-label="Main"', 'aria-label="Principal"'),
    ('>História</a>', '>Our story</a>', '>Historia</a>'),
    ('>Produtos</a>', '>Products</a>', '>Productos</a>'),
    ('>Publicações</a>', '>Posts</a>', '>Publicaciones</a>'),
    ('>Contactos</a>', '>Contact</a>', '>Contacto</a>'),
    ('>Privacidade</a>', '>Privacy</a>', '>Privacidad</a>'),
    ('>Livro de Reclamações</a>', '>Complaints Book</a>', '>Libro de Reclamaciones</a>'),
    ('>Distinções da marca no Reino Unido</p>', ">The brand's awards in the UK</p>", '>Distinciones de la marca en el Reino Unido</p>'),
    (' TODOS OS DIREITOS RESERVADOS</div>', ' ALL RIGHTS RESERVED</div>', ' TODOS LOS DERECHOS RESERVADOS</div>'),
    ('content="Logótipo da Tantie Lorraine\'s e frasco de Scotch Bonnet Jam"', 'content="Tantie Lorraine\'s logo and a jar of Scotch Bonnet Jam"',
     'content="Logotipo de Tantie Lorraine\'s y tarro de Scotch Bonnet Jam"'),
    ('<span>Fotografia do produto</span>', '<span>Product photo</span>', '<span>Foto del producto</span>'),
    ('<span>Fotografia</span>', '<span>Photo</span>', '<span>Foto</span>'),
    ('disabled>Brevemente</button>', 'disabled>Coming soon</button>', 'disabled>Próximamente</button>'),
    ('alt="Frasco de ', 'alt="Jar of ', 'alt="Tarro de '),
    ('alt="Ingrediente principal: malagueta Scotch Bonnet"', 'alt="Main ingredient: Scotch Bonnet chillies"', 'alt="Ingrediente principal: chile Scotch Bonnet"'),
    ('alt="Ingrediente principal: maçã"', 'alt="Main ingredient: apple"', 'alt="Ingrediente principal: manzana"'),
    ('alt="Ingrediente principal: lima"', 'alt="Main ingredient: lime"', 'alt="Ingrediente principal: lima"'),
    ('alt="Ingrediente principal: tomate cereja"', 'alt="Main ingredient: cherry tomatoes"', 'alt="Ingrediente principal: tomate cherry"'),
    ('alt="Lorraine, fundadora da Tantie Lorraine\'s"', 'alt="Lorraine, founder of Tantie Lorraine\'s"', 'alt="Lorraine, fundadora de Tantie Lorraine\'s"'),
]

# Fragmentos de cada página; têm todos de existir na página portuguesa
PAGINA = {
'index.html': [
    ("Tantie Lorraine's | Sabores das Caraíbas", "Tantie Lorraine's | Caribbean flavours", "Tantie Lorraine's | Sabores del Caribe"),
    ('Compotas de malagueta com sabores de Trinidad e Tobago, feitas à mão em pequenos lotes. Brevemente em Portugal e Espanha.',
     'Chilli jams with the flavours of Trinidad and Tobago, handmade in small batches. Coming soon to Portugal and Spain.',
     'Mermeladas de chile con sabores de Trinidad y Tobago, hechas a mano en pequeños lotes. Próximamente en Portugal y España.'),
    ('Compotas de malagueta com sabores de Trinidad e Tobago. Brevemente em Portugal e Espanha.',
     'Chilli jams with the flavours of Trinidad and Tobago. Coming soon to Portugal and Spain.',
     'Mermeladas de chile con sabores de Trinidad y Tobago. Próximamente en Portugal y España.'),
    ('>Ver produtos</a>', '>See products</a>', '>Ver productos</a>'),
    ('Sabores das Caraíbas, feitos à mão em Surrey, Londres', 'Caribbean flavours, handmade in Surrey, London', 'Sabores del Caribe, hechos a mano en Surrey, Londres'),
    ('Compotas de malagueta feitas em pequenos lotes, com ingredientes frescos e naturais. Sem conservantes nem aditivos artificiais.',
     'Chilli jams made in small batches with fresh, natural ingredients. No artificial preservatives or additives.',
     'Mermeladas de chile elaboradas en pequeños lotes, con ingredientes frescos y naturales. Sin conservantes ni aditivos artificiales.'),
    ('>A gama</h2>', '>The range</h2>', '>La gama</h2>'),
    ('>Conhecer a gama</a>', '>Discover the range</a>', '>Descubrir la gama</a>'),
    ('>Quem é a Tantie?</h2>', '>Who is Tantie?</h2>', '>¿Quién es Tantie?</h2>'),
    ('Lorraine nasceu e cresceu em Trinidad e Tobago, criada por tias e avós. Em Surrey, Londres, onde vive, transformou as receitas de casa numa marca artesanal premiada.',
     'Lorraine was born and raised in Trinidad and Tobago, brought up by aunts and grandmothers. In Surrey, London, where she lives, she turned her family recipes into an award-winning artisan brand.',
     'Lorraine nació y creció en Trinidad y Tobago, criada por tías y abuelas. En Surrey, Londres, donde vive, convirtió las recetas de casa en una marca artesanal premiada.'),
    ('>Conhecer a história</a>', '>Read our story</a>', '>Conocer la historia</a>'),
],
'historia.html': [
    ("História | Tantie Lorraine's", "Our story | Tantie Lorraine's", "Historia | Tantie Lorraine's"),
    ("A história de Lorraine e da Tantie Lorraine's, de Trinidad e Tobago para Surrey, Londres.",
     "The story of Lorraine and Tantie Lorraine's, from Trinidad and Tobago to Surrey, London.",
     "La historia de Lorraine y de Tantie Lorraine's, de Trinidad y Tobago a Surrey, Londres."),
    ('>De Trinidad para Surrey, Londres</h1>', '>From Trinidad to Surrey, London</h1>', '>De Trinidad a Surrey, Londres</h1>'),
    ('Em Trinidad e Tobago, "tantie" é a tia. Lorraine nasceu e cresceu no campo, criada por tias e avós, e foi aí que aprendeu a dar valor aos ingredientes frescos e naturais.',
     'In Trinidad and Tobago, "tantie" means auntie. Lorraine was born and raised in the countryside, brought up by aunts and grandmothers, and that is where she learnt to value fresh, natural ingredients.',
     'En Trinidad y Tobago, "tantie" es la tía. Lorraine nació y creció en el campo, criada por tías y abuelas, y allí aprendió a valorar los ingredientes frescos y naturales.'),
    ('A marca nasceu de uma falta. A geleia de malagueta trazida de Trinidad acabou e não havia onde a comprar no Reino Unido. Lorraine decidiu fazê-la ela própria. O primeiro lote esgotou num mercado de produtores local; o resto é história.',
     "The brand was born out of a shortage. The chilli jelly brought over from Trinidad ran out and there was nowhere to buy it in the UK, so Lorraine decided to make it herself. The first batch sold out at a local farmers' market; the rest is history.",
     'La marca nació de una carencia. La jalea de chile traída de Trinidad se acabó y no había dónde comprarla en el Reino Unido. Lorraine decidió hacerla ella misma. El primer lote se agotó en un mercado de productores local; el resto es historia.'),
    ('>Feito à mão, em pequenos lotes</h2>', '>Handmade in small batches</h2>', '>Hecho a mano, en pequeños lotes</h2>'),
    ('Durante cerca de dez anos, as receitas foram afinadas na cozinha de casa, em Surrey, Londres, aos serões e fins de semana. Em Portugal e Espanha, a gama começa por quatro compotas de malagueta.',
     'For around ten years, the recipes were perfected in her home kitchen in Surrey, London, in the evenings and at weekends. In Portugal and Spain, the range starts with four chilli jams.',
     'Durante unos diez años, las recetas se perfeccionaron en la cocina de casa, en Surrey, Londres, por las noches y los fines de semana. En Portugal y España, la gama empieza con cuatro mermeladas de chile.'),
    ('Tudo é feito à mão em pequenos lotes, com ingredientes frescos e naturais e sem conservantes nem aditivos artificiais.',
     'Everything is handmade in small batches, with fresh, natural ingredients and no artificial preservatives or additives.',
     'Todo se hace a mano en pequeños lotes, con ingredientes frescos y naturales y sin conservantes ni aditivos artificiales.'),
    ('A Scotch Bonnet Jam recebeu duas estrelas nos Great Taste Awards de 2024, a classificação mais alta da gama até hoje.',
     'Scotch Bonnet Jam received two stars at the 2024 Great Taste Awards, the highest rating in the range to date.',
     'La Scotch Bonnet Jam recibió dos estrellas en los Great Taste Awards de 2024, la calificación más alta de la gama hasta hoy.'),
    ('>A missão</h3>', '>Our mission</h3>', '>La misión</h3>'),
    ('Partilhar a herança culinária das Caraíbas e criar comunidade à volta da comida.',
     'To share the culinary heritage of the Caribbean and build community around food.',
     'Compartir la herencia culinaria del Caribe y crear comunidad en torno a la comida.'),
    ('>Em Portugal e Espanha</h2>',
     '>In Portugal and Spain</h2>',
     '>En Portugal y España</h2>'),
    ("Em Portugal e Espanha, a Tantie Lorraine's é apresentada pela Gadgetmétodo, Lda., com sede no Porto. Começamos por quatro compotas de malagueta, entre elas a premiada Scotch Bonnet Jam. A venda abre brevemente.",
     "In Portugal and Spain, Tantie Lorraine's is represented by Gadgetmétodo, Lda., based in Porto. We are starting with four chilli jams, including the award-winning Scotch Bonnet Jam. Sales open soon.",
     "En Portugal y España, Tantie Lorraine's está representada por Gadgetmétodo, Lda., con sede en Oporto. Empezamos con cuatro mermeladas de chile, entre ellas la premiada Scotch Bonnet Jam. La venta abre próximamente."),
],
'produtos.html': [
    ("Produtos | Tantie Lorraine's", "Products | Tantie Lorraine's", "Productos | Tantie Lorraine's"),
    ("Compotas de malagueta da Tantie Lorraine's. Brevemente em Portugal e Espanha.",
     "Tantie Lorraine's chilli jams. Coming soon to Portugal and Spain.",
     "Mermeladas de chile de Tantie Lorraine's. Próximamente en Portugal y España."),
    ('>Produtos</h1>', '>Products</h1>', '>Productos</h1>'),
    ('<span class="tl-text-subhead">Brevemente</span>', '<span class="tl-text-subhead">Coming soon</span>', '<span class="tl-text-subhead">Próximamente</span>'),
    ('A Tantie Lorraine\'s chega a Portugal e Espanha com quatro compotas de malagueta e a venda online abre em breve. Quer ser avisado? <a href="/contactos.html#pedido">Fale connosco</a>.',
     'Tantie Lorraine\'s is coming to Portugal and Spain with four chilli jams, and online sales open soon. Want to be notified? <a href="/contactos.html#pedido">Get in touch</a>.',
     'Tantie Lorraine\'s llega a Portugal y España con cuatro mermeladas de chile y la venta online abre pronto. ¿Quiere que le avisemos? <a href="/contactos.html#pedido">Contacte con nosotros</a>.'),
    ('Feito à mão em pequenos lotes · Sem conservantes artificiais · Surrey, Londres', 'Handmade in small batches · No artificial preservatives · Surrey, London',
     'Hecho a mano en pequeños lotes · Sin conservantes artificiales · Surrey, Londres'),
    (' · Peso líquido ', ' · Net weight ', ' · Peso neto '),
    ('A compota de referência da casa e a mais premiada da gama: duas estrelas nos Great Taste Awards de 2024. Feita com pimento vermelho e pasta fermentada de malagueta Scotch Bonnet (100&nbsp;000 a 350&nbsp;000 unidades de calor Scoville, SHU).',
     'The house signature jam and the most awarded in the range: two stars at the 2024 Great Taste Awards. Made with red pepper and fermented Scotch Bonnet chilli mash (100,000 to 350,000 Scoville heat units, SHU).',
     'La mermelada emblemática de la casa y la más premiada de la gama: dos estrellas en los Great Taste Awards de 2024. Elaborada con pimiento rojo y pasta fermentada de chile Scotch Bonnet (100&nbsp;000 a 350&nbsp;000 unidades de picor Scoville, SHU).'),
    ('>Ingredientes</h3>', '>Ingredients</h3>', '>Ingredientes</h3>'),
    ('Açúcar branco, pimento vermelho, <strong>vinagre</strong>, pasta fermentada de malagueta Scotch Bonnet (4,92 %), água destilada, <strong>pectina</strong>.',
     'White sugar, red pepper, <strong>vinegar</strong>, Scotch Bonnet fermented chilli mash (4.92%), distilled water, <strong>pectin</strong>.',
     'Azúcar blanco, pimiento rojo, <strong>vinagre</strong>, pasta fermentada de chile Scotch Bonnet (4,92 %), agua destilada, <strong>pectina</strong>.'),
    ('<strong>Alergénios:</strong> contém dióxido de enxofre e sulfitos.', '<strong>Allergens:</strong> contains sulphur dioxide and sulphites.',
     '<strong>Alérgenos:</strong> contiene dióxido de azufre y sulfitos.'),
    ('Informação nutricional (por 100 g)', 'Nutrition information (per 100 g)', 'Información nutricional (por 100 g)'),
    ('<th scope="row">Energia</th>', '<th scope="row">Energy</th>', '<th scope="row">Valor energético</th>'),
    ('<th scope="row">Lípidos</th>', '<th scope="row">Fat</th>', '<th scope="row">Grasas</th>'),
    ('<th scope="row">dos quais saturados</th>', '<th scope="row">of which saturates</th>', '<th scope="row">de las cuales saturadas</th>'),
    ('<th scope="row">Hidratos de carbono</th>', '<th scope="row">Carbohydrate</th>', '<th scope="row">Hidratos de carbono</th>'),
    ('<th scope="row">dos quais açúcares</th>', '<th scope="row">of which sugars</th>', '<th scope="row">de los cuales azúcares</th>'),
    ('<th scope="row">Fibra</th>', '<th scope="row">Fibre</th>', '<th scope="row">Fibra alimentaria</th>'),
    ('<th scope="row">Proteínas</th>', '<th scope="row">Protein</th>', '<th scope="row">Proteínas</th>'),
    ('<th scope="row">Sal</th>', '<th scope="row">Salt</th>', '<th scope="row">Sal</th>'),
    ('Maçã inglesa e malagueta: doce, frutada e com um calor suave. Para quem quer o sabor da malagueta sem muito picante.',
     'English apples and chilli: sweet, fruity and gently warm. For those who want the flavour of chilli without too much heat.',
     'Manzana inglesa y chile: dulce, afrutada y con un picor suave. Para quien quiere el sabor del chile sin demasiado picante.'),
    ('Açúcar branco, maçã (polpa e casca), <strong>vinagre</strong>, sumo de maçã concentrado sem açúcar, malagueta fresca, <strong>pectina</strong>, malagueta em flocos.',
     'White sugar, apples (flesh and skin), <strong>vinegar</strong>, unsweetened apple juice concentrate, fresh chillies, <strong>pectin</strong>, chilli flakes.',
     'Azúcar blanco, manzana (pulpa y piel), <strong>vinagre</strong>, concentrado de zumo de manzana sin azúcar, chile fresco, <strong>pectina</strong>, chile en copos.'),
    ('Pimento vermelho, malagueta e lima, de picante médio. A compota com que tudo começou.',
     'Red pepper, chilli and lime, with medium heat. The jam where it all began.',
     'Pimiento rojo, chile y lima, de picor medio. La mermelada con la que empezó todo.'),
    ('Açúcar branco, pimento vermelho, <strong>vinagre</strong>, <strong>sumo de lima fresco</strong>, malagueta fresca, <strong>pectina</strong>, malagueta em flocos.',
     'White sugar, red pepper, <strong>vinegar</strong>, <strong>fresh lime juice</strong>, fresh chillies, <strong>pectin</strong>, chilli flakes.',
     'Azúcar blanco, pimiento rojo, <strong>vinagre</strong>, <strong>zumo de lima fresco</strong>, chile fresco, <strong>pectina</strong>, chile en copos.'),
    ('Com pedaços e picante médio: o alho, o gengibre e o tomate cereja equilibram a malagueta. Feita para tábuas de queijos e enchidos.',
     'Chunky, with medium heat: garlic, ginger and cherry tomatoes balance the chilli. Made for cheese and charcuterie boards.',
     'Con trozos y picor medio: el ajo, el jengibre y el tomate cherry equilibran el chile. Pensada para tablas de quesos y embutidos.'),
    ('Açúcar branco, tomate cereja, pimento vermelho, <strong>vinagre</strong>, malagueta fresca, alho, gengibre fresco.',
     'White sugar, cherry tomatoes, red pepper, <strong>vinegar</strong>, fresh chillies, garlic, fresh ginger.',
     'Azúcar blanco, tomate cherry, pimiento rojo, <strong>vinagre</strong>, chile fresco, ajo, jengibre fresco.'),
    ('>Perguntas frequentes</h2>', '>Frequently asked questions</h2>', '>Preguntas frecuentes</h2>'),
    ("Onde posso comprar as compotas Tantie Lorraine's em Portugal e Espanha?",
     "Where can I buy Tantie Lorraine's jams in Portugal and Spain?",
     "¿Dónde puedo comprar las mermeladas de Tantie Lorraine's en Portugal y España?"),
    ('A venda em Portugal e Espanha abre brevemente. Para ser avisado, envie um <a href="/contactos.html#pedido">pedido de informação</a>.',
     'Sales in Portugal and Spain open soon. To be notified, send us an <a href="/contactos.html#pedido">information request</a>.',
     'La venta en Portugal y España abre próximamente. Para que le avisemos, envíenos una <a href="/contactos.html#pedido">solicitud de información</a>.'),
    ('Que compotas existem em Portugal e Espanha?',
     'Which jams are available in Portugal and Spain?',
     '¿Qué mermeladas hay en Portugal y España?'),
    ('Quatro compotas de malagueta: Scotch Bonnet Jam (SBJ), Apple Chilli Jam (ACJ), Red Pepper Chilli Lime Jam (RPCL) e Tomato Chilli Jam (TCJ).',
     'Four chilli jams: Scotch Bonnet Jam (SBJ), Apple Chilli Jam (ACJ), Red Pepper Chilli Lime Jam (RPCL) and Tomato Chilli Jam (TCJ).',
     'Cuatro mermeladas de chile: Scotch Bonnet Jam (SBJ), Apple Chilli Jam (ACJ), Red Pepper Chilli Lime Jam (RPCL) y Tomato Chilli Jam (TCJ).'),
    ('Qual é a compota menos picante?', 'Which is the mildest jam?', '¿Cuál es la mermelada menos picante?'),
    ('A Apple Chilli Jam: doce, frutada e com um calor suave, para quem quer o sabor da malagueta sem muito picante. A Red Pepper Chilli Lime Jam e a Tomato Chilli Jam têm picante médio. A Scotch Bonnet Jam é feita com pasta fermentada de malagueta Scotch Bonnet (100&nbsp;000 a 350&nbsp;000 unidades de calor Scoville).',
     'Apple Chilli Jam: sweet, fruity and gently warm, for those who want the flavour of chilli without too much heat. Red Pepper Chilli Lime Jam and Tomato Chilli Jam have medium heat. Scotch Bonnet Jam is made with fermented Scotch Bonnet chilli mash (100,000 to 350,000 Scoville heat units).',
     'La Apple Chilli Jam: dulce, afrutada y con un picor suave, para quien quiere el sabor del chile sin demasiado picante. La Red Pepper Chilli Lime Jam y la Tomato Chilli Jam tienen un picor medio. La Scotch Bonnet Jam se elabora con pasta fermentada de chile Scotch Bonnet (100&nbsp;000 a 350&nbsp;000 unidades de picor Scoville).'),
    ('Com que se comem estas compotas?', 'What do you eat these jams with?', '¿Con qué se comen estas mermeladas?'),
    ('A Tomato Chilli Jam foi feita para tábuas de queijos e enchidos. A marca sugere também sanduíches, por exemplo ovo com maionese, rúcula e Scotch Bonnet Jam, ou pesto, queijo, rúcula e Tomato Chilli Jam. Há mais ideias em <a href="/publicacoes.html">Publicações</a>.',
     'Tomato Chilli Jam was made for cheese and charcuterie boards. The brand also suggests sandwiches, such as egg mayonnaise, rocket and Scotch Bonnet Jam, or pesto, cheese, rocket and Tomato Chilli Jam. There are more ideas in <a href="/publicacoes.html">Posts</a>.',
     'La Tomato Chilli Jam se pensó para tablas de quesos y embutidos. La marca sugiere también sándwiches, por ejemplo huevo con mayonesa, rúcula y Scotch Bonnet Jam, o pesto, queso, rúcula y Tomato Chilli Jam. Hay más ideas en <a href="/publicacoes.html">Publicaciones</a>.'),
    ('Onde são feitas?', 'Where are they made?', '¿Dónde se elaboran?'),
    ('À mão, em pequenos lotes, em Surrey, Londres, com receitas de Trinidad e Tobago, a terra da fundadora, Lorraine.',
     'By hand, in small batches, in Surrey, London, with recipes from Trinidad and Tobago, the homeland of the founder, Lorraine.',
     'A mano, en pequeños lotes, en Surrey, Londres, con recetas de Trinidad y Tobago, la tierra de la fundadora, Lorraine.'),
    ('As compotas têm prémios?', 'Have the jams won awards?', '¿Las mermeladas tienen premios?'),
    ('A Scotch Bonnet Jam recebeu duas estrelas nos Great Taste Awards de 2024, atribuídos pela Guild of Fine Food. A marca tem também distinções Great Taste de 2019, 2021 e 2026.',
     'Scotch Bonnet Jam received two stars at the 2024 Great Taste Awards, run by the Guild of Fine Food. The brand also holds Great Taste awards from 2019, 2021 and 2026.',
     'La Scotch Bonnet Jam recibió dos estrellas en los Great Taste Awards de 2024, concedidos por la Guild of Fine Food. La marca tiene también distinciones Great Taste de 2019, 2021 y 2026.'),
    ('As compotas contêm alergénios?', 'Do the jams contain allergens?', '¿Las mermeladas contienen alérgenos?'),
    ('Sim: as quatro contêm dióxido de enxofre e sulfitos. Os ingredientes e a informação nutricional de cada compota estão nesta página.',
     'Yes: all four contain sulphur dioxide and sulphites. The ingredients and nutrition information for each jam are on this page.',
     'Sí: las cuatro contienen dióxido de azufre y sulfitos. Los ingredientes y la información nutricional de cada mermelada están en esta página.'),
    ('Quem apresenta a marca em Portugal e Espanha?',
     'Who represents the brand in Portugal and Spain?',
     '¿Quién representa la marca en Portugal y España?'),
    ('A GADGETMÉTODO, LDA, com sede no Porto. Contacto: ', 'GADGETMÉTODO, LDA, based in Porto. Contact: ', 'GADGETMÉTODO, LDA, con sede en Oporto. Contacto: '),
    ('Provou as nossas compotas, numa degustação ou em casa? <a href="/degustacao.html">Dê-nos a sua opinião</a>.',
     'Tasted our jams, at a tasting or at home? <a href="/degustacao.html">Tell us what you think</a>.',
     '¿Ha probado nuestras mermeladas, en una degustación o en casa? <a href="/degustacao.html">Denos su opinión</a>.'),
],
'publicacoes.html': [
    ("Publicações | Tantie Lorraine's", "Posts | Tantie Lorraine's", "Publicaciones | Tantie Lorraine's"),
    ("Sugestões de utilização, receitas e novidades da Tantie Lorraine's, publicadas no Instagram da marca.",
     "Serving suggestions, recipes and news from Tantie Lorraine's, published on the brand's Instagram.",
     "Sugerencias de uso, recetas y novedades de Tantie Lorraine's, publicadas en el Instagram de la marca."),
    ('>Publicações</h1>', '>Posts</h1>', '>Publicaciones</h1>'),
    ("Sugestões, receitas e novidades da Tantie Lorraine's, publicadas no Instagram da marca.",
     "Ideas, recipes and news from Tantie Lorraine's, published on the brand's Instagram.",
     "Sugerencias, recetas y novedades de Tantie Lorraine's, publicadas en el Instagram de la marca."),
    ('Algumas publicações referem produtos e eventos da marca no Reino Unido que ainda não estão disponíveis em Portugal e Espanha, onde a gama começa pelas quatro compotas de malagueta apresentadas em ',
     "Some posts mention the brand's products and events in the UK that are not yet available in Portugal and Spain, where the range starts with the four chilli jams shown in ",
     'Algunas publicaciones mencionan productos y eventos de la marca en el Reino Unido que todavía no están disponibles en Portugal y España, donde la gama empieza con las cuatro mermeladas de chile presentadas en '),
    ('>Ver no Instagram</a>', '>View on Instagram</a>', '>Ver en Instagram</a>'),
    ('Cinco formas de usar os nossos molhos picantes no dia a dia', 'Five ways to use our hot sauces every day', 'Cinco formas de usar nuestras salsas picantes en el día a día'),
    ('Perguntam-nos muitas vezes com que pratos combinam os molhos Tantie Lorraine. A resposta é simples: com quase tudo. Juntámos cinco sugestões práticas para dar mais sabor às refeições de todos os dias, de ovos a sanduíches, carnes grelhadas e petiscos. Qual é a sua preferida?',
     'We are often asked which dishes go with Tantie Lorraine sauces. The answer is simple: almost everything. We have put together five practical ideas to add flavour to everyday meals, from eggs to sandwiches, grilled meats and snacks. Which is your favourite?',
     'A menudo nos preguntan con qué platos combinan las salsas Tantie Lorraine. La respuesta es sencilla: con casi todo. Hemos reunido cinco sugerencias prácticas para dar más sabor a las comidas de cada día, de huevos a sándwiches, carnes a la parrilla y aperitivos. ¿Cuál es su favorita?'),
    ('Lime Pepper Relish com queijo: uma combinação inesperada', 'Lime Pepper Relish with cheese: an unexpected pairing', 'Lime Pepper Relish con queso: una combinación inesperada'),
    ('Foi um cliente que nos contou que comia o Lime Pepper Relish com queijo. Ao início estranhámos. Depois lembrámo-nos do “cheese paste” tradicional de Trinidad e Tobago, experimentámos e rendemo-nos. A acidez da lima e o picante equilibram a cremosidade do queijo. Habitualmente servimo-lo com peixe, mas esta passou a ser uma das nossas sugestões favoritas.',
     'It was a customer who told us they ate Lime Pepper Relish with cheese. At first we were surprised. Then we remembered the traditional “cheese paste” of Trinidad and Tobago, tried it and were won over. The acidity of the lime and the heat balance the creaminess of the cheese. We usually serve it with fish, but this has become one of our favourite suggestions.',
     'Fue un cliente quien nos contó que comía el Lime Pepper Relish con queso. Al principio nos extrañó. Después nos acordamos del “cheese paste” tradicional de Trinidad y Tobago, lo probamos y nos rendimos. La acidez de la lima y el picante equilibran la cremosidad del queso. Normalmente lo servimos con pescado, pero esta se ha convertido en una de nuestras sugerencias favoritas.'),
    ('Para quem procura mais picante: geleias Scorpion e Ghost', 'For those who want more heat: Scorpion and Ghost jellies', 'Para quien busca más picante: jaleas Scorpion y Ghost'),
    ('Se a geleia de Scotch Bonnet já não chega, as geleias de malagueta Scorpion e Ghost são as mais procuradas pelos verdadeiros apreciadores de picante. Usam-se como qualquer doce de malagueta: com queijos, carnes, marinadas ou sanduíches, para um toque de intensidade extra.',
     'If the Scotch Bonnet jelly is no longer enough, the Scorpion and Ghost chilli jellies are the favourites of true heat lovers. Use them like any chilli jam: with cheese, meat, marinades or sandwiches, for an extra kick.',
     'Si la jalea de Scotch Bonnet ya no basta, las jaleas de chile Scorpion y Ghost son las más buscadas por los verdaderos amantes del picante. Se usan como cualquier mermelada de chile: con quesos, carnes, adobos o sándwiches, para un toque de intensidad extra.'),
    ('Receita: hambúrguer de frango inspirado no “shark and bake”', 'Recipe: chicken burger inspired by “shark and bake”', 'Receta: hamburguesa de pollo inspirada en el “shark and bake”'),
    ('Para celebrar o Notting Hill Carnival, recriámos um clássico da comida de rua de Trinidad, o “shark and bake”, em versão hambúrguer de frango. Leva chutney de pepino, molho de tamarindo, Mayaro Pepper Sauce e Red Pepper Chilli Lime. Também fica excelente com peixe ou com qualquer uma das nossas geleias de malagueta. Para mais picante, experimente a de Scotch Bonnet.',
     'To celebrate Notting Hill Carnival, we recreated a Trinidadian street food classic, “shark and bake”, as a chicken burger. It comes with cucumber chutney, tamarind sauce, Mayaro Pepper Sauce and Red Pepper Chilli Lime. It is also excellent with fish or with any of our chilli jellies. For more heat, try the Scotch Bonnet.',
     'Para celebrar el Notting Hill Carnival, recreamos un clásico de la comida callejera de Trinidad, el “shark and bake”, en versión hamburguesa de pollo. Lleva chutney de pepino, salsa de tamarindo, Mayaro Pepper Sauce y Red Pepper Chilli Lime. También queda excelente con pescado o con cualquiera de nuestras jaleas de chile. Para más picante, pruebe la de Scotch Bonnet.'),
    ('Mayaro Pepper Sauce distinguido nos Great Taste Awards', 'Mayaro Pepper Sauce recognised at the Great Taste Awards', 'Mayaro Pepper Sauce, distinguida en los Great Taste Awards'),
    ('O Mayaro Pepper Sauce foi lançado no ano passado e rapidamente se tornou um dos favoritos dos nossos clientes. Agora foi também distinguido nos Great Taste Awards, um dos prémios mais reconhecidos da indústria alimentar britânica. Feito com manga, coentros e malagueta Scotch Bonnet, é um molho verde fresco e vibrante, adequado a vegans e sem conservantes artificiais. O nome homenageia Mayaro, a aldeia de Trinidad onde cresceu a fundadora.',
     "Mayaro Pepper Sauce was launched last year and quickly became one of our customers' favourites. Now it has also been recognised at the Great Taste Awards, one of the most respected awards in the British food industry. Made with mango, coriander and Scotch Bonnet chilli, it is a fresh, vibrant green sauce, suitable for vegans and free from artificial preservatives. Its name honours Mayaro, the village in Trinidad where the founder grew up.",
     'La Mayaro Pepper Sauce se lanzó el año pasado y rápidamente se convirtió en una de las favoritas de nuestros clientes. Ahora también ha sido distinguida en los Great Taste Awards, uno de los premios más reconocidos de la industria alimentaria británica. Elaborada con mango, cilantro y chile Scotch Bonnet, es una salsa verde fresca y vibrante, apta para veganos y sin conservantes artificiales. Su nombre rinde homenaje a Mayaro, el pueblo de Trinidad donde creció la fundadora.'),
    ('Semana Nacional da Sanduíche: duas sugestões com as nossas geleias', 'National Sandwich Week: two ideas with our jellies', 'Semana Nacional del Sándwich: dos sugerencias con nuestras jaleas'),
    ('Poucas combinações funcionam tão bem como uma boa sanduíche e uma geleia de malagueta. As nossas sugestões são ovo com maionese, rúcula e geleia de Scotch Bonnet, ou pesto, queijo, rúcula e Tomato Chilli. Qualquer uma das nossas geleias pode ser usada nestas receitas.',
     'Few combinations work as well as a good sandwich and a chilli jelly. Our suggestions are egg mayonnaise, rocket and Scotch Bonnet jelly, or pesto, cheese, rocket and Tomato Chilli. Any of our jellies can be used in these recipes.',
     'Pocas combinaciones funcionan tan bien como un buen sándwich y una jalea de chile. Nuestras sugerencias son huevo con mayonesa, rúcula y jalea de Scotch Bonnet, o pesto, queso, rúcula y Tomato Chilli. Cualquiera de nuestras jaleas puede usarse en estas recetas.'),
    ('Tantie Lorraine nas feiras e mercados de Londres', "Tantie Lorraine at London's fairs and markets", 'Tantie Lorraine en las ferias y mercados de Londres'),
    ('Este ano voltámos às feiras e mercados de Londres, com presenças no Hot Sauce Festival e na Pop Up Africa, no Spitalfields Market. O Mayaro Pepper Sauce esgotou no Hot Sauce Festival e voltou entretanto a estar disponível na loja online.',
     "This year we returned to London's fairs and markets, with stalls at the Hot Sauce Festival and at Pop Up Africa in Spitalfields Market. Mayaro Pepper Sauce sold out at the Hot Sauce Festival and is now back in stock in the online shop.",
     'Este año volvimos a las ferias y mercados de Londres, con presencia en el Hot Sauce Festival y en Pop Up Africa, en el Spitalfields Market. La Mayaro Pepper Sauce se agotó en el Hot Sauce Festival y ya vuelve a estar disponible en la tienda online.'),
],
'contactos.html': [
    ("Contactos | Tantie Lorraine's", "Contact | Tantie Lorraine's", "Contacto | Tantie Lorraine's"),
    ("Contactos da Tantie Lorraine's em Portugal e Espanha.",
     "Contact Tantie Lorraine's in Portugal and Spain.",
     "Contacto de Tantie Lorraine's en Portugal y España."),
    ('>Contactos</h1>', '>Contact</h1>', '>Contacto</h1>'),
    ('Perguntas sobre a marca, os produtos ou parcerias: diga-nos o que precisa.', 'Questions about the brand, the products or partnerships: tell us what you need.',
     'Preguntas sobre la marca, los productos o colaboraciones: díganos qué necesita.'),
    ('>Pedido de informação</h2>', '>Information request</h2>', '>Solicitud de información</h2>'),
    ('Obrigado pelo seu pedido. Recebemos a mensagem e respondemos por email com a maior brevidade possível.',
     'Thank you for your request. We have received your message and will reply by email as soon as possible.',
     'Gracias por su solicitud. Hemos recibido el mensaje y le responderemos por correo electrónico lo antes posible.'),
    ('Não foi possível enviar o pedido. Tente de novo dentro de alguns minutos ou escreva para ',
     'Your request could not be sent. Please try again in a few minutes or write to ',
     'No ha sido posible enviar la solicitud. Inténtelo de nuevo dentro de unos minutos o escriba a '),
    ('for="pedido-nome">Nome</label>', 'for="pedido-nome">Name</label>', 'for="pedido-nome">Nombre</label>'),
    ('for="pedido-email">Email</label>', 'for="pedido-email">Email</label>', 'for="pedido-email">Correo electrónico</label>'),
    ('Telefone <span class="tl-text-ui-sm">(opcional)</span>', 'Phone <span class="tl-text-ui-sm">(optional)</span>', 'Teléfono <span class="tl-text-ui-sm">(opcional)</span>'),
    ('title="Só algarismos, espaços e os sinais + ( ) . / -"', 'title="Digits, spaces and the characters + ( ) . / - only"', 'title="Solo dígitos, espacios y los signos + ( ) . / -"'),
    ('for="pedido-mensagem">Mensagem</label>', 'for="pedido-mensagem">Message</label>', 'for="pedido-mensagem">Mensaje</label>'),
    ('>Não preencher este campo</label>', '>Leave this field empty</label>', '>No rellenar este campo</label>'),
    ('Usamos estes dados só para responder ao seu pedido. Saiba mais na <a href="/privacidade.html">política de privacidade</a>.',
     'We only use this information to reply to your request. Find out more in our <a href="/privacidade.html">privacy policy</a>.',
     'Solo usamos estos datos para responder a su solicitud. Más información en la <a href="/privacidade.html">política de privacidad</a>.'),
    ('data-enviando="A enviar..."', 'data-enviando="Sending..."', 'data-enviando="Enviando..."'),
    ('>Enviar pedido</button>', '>Send request</button>', '>Enviar solicitud</button>'),
    ('<span class="tl-text-label-strong">Email</span>', '<span class="tl-text-label-strong">Email</span>', '<span class="tl-text-label-strong">Correo electrónico</span>'),
],
'privacidade.html': [
    ("Privacidade e informação legal | Tantie Lorraine's", "Privacy and legal information | Tantie Lorraine's", "Privacidad e información legal | Tantie Lorraine's"),
    ('Política de privacidade e informação legal do site tantielorraine.pt.', 'Privacy policy and legal information for the tantielorraine.pt website.',
     'Política de privacidad e información legal del sitio tantielorraine.pt.'),
    ('>Privacidade e informação legal</h1>', '>Privacy and legal information</h1>', '>Privacidad e información legal</h1>'),
    ('Última atualização: 17 de outubro de 2026', 'Last updated: 17 October 2026', 'Última actualización: 17 de octubre de 2026'),
    ('>1. Responsável pelo tratamento</h2>', '>1. Data controller</h2>', '>1. Responsable del tratamiento</h2>'),
    ('O responsável pelo tratamento dos dados pessoais recolhidos através deste site é a GADGETMÉTODO, LDA, sociedade por quotas com o Número de Identificação de Pessoa Coletiva (NIPC) 513978011 e sede na Rua das Cruzes, n.º 625, 4100-195 Porto, Portugal. Contacto para questões de privacidade: ',
     'The controller of the personal data collected through this website is GADGETMÉTODO, LDA, a Portuguese private limited company (sociedade por quotas) with Legal Person Identification Number (NIPC) 513978011 and registered office at Rua das Cruzes, n.º 625, 4100-195 Porto, Portugal. Contact for privacy matters: ',
     'El responsable del tratamiento de los datos personales recogidos a través de este sitio es GADGETMÉTODO, LDA, sociedad de responsabilidad limitada portuguesa (sociedade por quotas) con Número de Identificación de Persona Jurídica (NIPC) 513978011 y domicilio social en Rua das Cruzes, n.º 625, 4100-195 Porto, Portugal. Contacto para cuestiones de privacidad: '),
    ('>2. Que dados tratamos</h2>', '>2. What data we process</h2>', '>2. Qué datos tratamos</h2>'),
    ('Este site não pede registo. Só usa cookies de medição de visitas (Google Analytics) se o autorizar no aviso de cookies; ver o ponto 6. As fontes tipográficas e as imagens são servidas pelo próprio site. Só tratamos dados pessoais quando nos envia um pedido de informação pelo formulário da página de contactos (nome, email, telefone, se o indicar, e a mensagem), quando nos envia uma opinião pelo formulário de degustação (ver abaixo) ou quando nos contacta por email, telefone ou redes sociais (nome, contacto e conteúdo da mensagem).',
     'This website does not require registration. It only uses visitor measurement cookies (Google Analytics) if you allow them in the cookie notice; see section 6. Fonts and images are served by the website itself. We only process personal data when you send us an information request through the form on the contact page (name, email, phone number if provided, and your message), when you send us feedback through the tasting form (see below) or when you contact us by email, phone or social media (name, contact details and content of the message).',
     'Este sitio no pide registro. Solo usa cookies de medición de visitas (Google Analytics) si lo autoriza en el aviso de cookies; véase el apartado 6. Las fuentes tipográficas y las imágenes se sirven desde el propio sitio. Solo tratamos datos personales cuando nos envía una solicitud de información mediante el formulario de la página de contacto (nombre, correo electrónico, teléfono, si lo indica, y el mensaje), cuando nos envía una opinión mediante el formulario de degustación (véase más abajo) o cuando nos contacta por correo electrónico, teléfono o redes sociales (nombre, contacto y contenido del mensaje).'),
    ('Ao enviar o formulário, recebe no email indicado uma confirmação automática de receção. O pedido não fica guardado no site: é enviado por email para as caixas de correio da empresa, onde é conservado nos termos do ponto 4. Para evitar abusos, o servidor guarda durante 24 horas um resumo cifrado do email indicado, que não permite reconstituir o endereço e serve apenas para limitar o número de pedidos.',
     "When you submit the form, you receive an automatic acknowledgement at the email address provided. The request is not stored on the website: it is sent by email to the company's mailboxes, where it is kept as described in section 4. To prevent abuse, the server keeps, for 24 hours, an encrypted digest of the email address provided, which cannot be used to reconstruct the address and is used only to limit the number of requests.",
     'Al enviar el formulario, recibe en el correo indicado una confirmación automática de recepción. La solicitud no se guarda en el sitio: se envía por correo electrónico a los buzones de la empresa, donde se conserva en los términos del apartado 4. Para evitar abusos, el servidor guarda durante 24 horas un resumen cifrado del correo indicado, que no permite reconstruir la dirección y solo sirve para limitar el número de solicitudes.'),
    ('Por razões de segurança, o servidor que aloja o site regista automaticamente dados técnicos de cada acesso (endereço IP, data e hora, página pedida e navegador).',
     'For security reasons, the server hosting the website automatically logs technical data for each visit (IP address, date and time, page requested and browser).',
     'Por razones de seguridad, el servidor que aloja el sitio registra automáticamente datos técnicos de cada acceso (dirección IP, fecha y hora, página solicitada y navegador).'),
    ('>3. Finalidades e fundamento</h2>', '>3. Purposes and legal basis</h2>', '>3. Finalidades y base jurídica</h2>'),
    ('Usamos os dados de contacto apenas para responder ao seu pedido, com base no nosso interesse legítimo em responder a quem nos contacta (artigo 6.º, n.º 1, alínea f), do Regulamento Geral sobre a Proteção de Dados (RGPD)) ou, quando o pedido disser respeito a um eventual contrato, em diligências pré-contratuais a seu pedido (alínea b) do mesmo artigo). Os dados não são usados para envio de publicidade. Com o mesmo fundamento, tratamos os registos técnicos do servidor para garantir a segurança e o funcionamento do site.',
     "We use contact data only to reply to your request, on the basis of our legitimate interest in replying to those who contact us (Article 6(1)(f) of the General Data Protection Regulation (GDPR)) or, where the request relates to a possible contract, to take steps at your request prior to entering into a contract (Article 6(1)(b)). The data is not used to send advertising. On the same basis, we process the server's technical logs to keep the website secure and working.",
     'Usamos los datos de contacto solo para responder a su solicitud, sobre la base de nuestro interés legítimo en responder a quien nos contacta (artículo 6, apartado 1, letra f), del Reglamento General de Protección de Datos (RGPD)) o, cuando la solicitud se refiera a un posible contrato, para aplicar medidas precontractuales a petición suya (letra b) del mismo artículo). Los datos no se usan para enviar publicidad. Con la misma base, tratamos los registros técnicos del servidor para garantizar la seguridad y el funcionamiento del sitio.'),
    ('>4. Conservação</h2>', '>4. Retention</h2>', '>4. Conservación</h2>'),
    ('Os dados de contacto são conservados durante 12 meses após o último contacto, salvo se for necessário conservá-los por mais tempo para cumprir uma obrigação legal ou para a defesa de direitos. Os registos do servidor são conservados pelo prazo definido pelo prestador de alojamento.',
     'Contact data is kept for 12 months after the last contact, unless it must be kept longer to comply with a legal obligation or to establish, exercise or defend legal claims. Server logs are kept for the period set by the hosting provider.',
     'Los datos de contacto se conservan durante 12 meses tras el último contacto, salvo que sea necesario conservarlos más tiempo para cumplir una obligación legal o para la defensa de derechos. Los registros del servidor se conservan durante el plazo fijado por el proveedor de alojamiento.'),
    ('>5. Quem tem acesso</h2>', '>5. Who has access</h2>', '>5. Quién tiene acceso</h2>'),
    ('Os dados não são vendidos nem cedidos a terceiros para fins comerciais. Têm acesso a eles, por nossa conta, o prestador de alojamento do site (registos técnicos e envio dos formulários), os prestadores dos serviços de email (mensagens recebidas) e, se autorizar a medição de visitas, a Google (ponto 6). O serviço de email pode tratar dados fora do Espaço Económico Europeu, com as garantias previstas no RGPD, como decisões de adequação ou cláusulas contratuais-tipo.',
     'The data is not sold or passed on to third parties for commercial purposes. On our behalf, access is given to the website hosting provider (technical logs and sending of form submissions), the email service providers (messages received) and, if you allow visitor measurement, Google (section 6). The email service may process data outside the European Economic Area, with the safeguards provided for in the GDPR, such as adequacy decisions or standard contractual clauses.',
     'Los datos no se venden ni se ceden a terceros con fines comerciales. Tienen acceso a ellos, por cuenta nuestra, el proveedor de alojamiento del sitio (registros técnicos y envío de los formularios), los proveedores de los servicios de correo electrónico (mensajes recibidos) y, si autoriza la medición de visitas, Google (apartado 6). El servicio de correo electrónico puede tratar datos fuera del Espacio Económico Europeo, con las garantías previstas en el RGPD, como decisiones de adecuación o cláusulas contractuales tipo.'),
    ('>6. Cookies e medição de visitas</h2>', '>6. Cookies and visitor measurement</h2>', '>6. Cookies y medición de visitas</h2>'),
    ('Só se aceitar no aviso de cookies, o site usa o Google Analytics 4, da Google Ireland Limited, para medir as visitas: páginas vistas, origem da visita (por exemplo, motores de busca, assistentes de inteligência artificial ou redes sociais), tipo de dispositivo e localização aproximada. São usados os cookies _ga e _ga_PMGEGTHM1W, que duram até 2 anos. Sem a sua autorização, o site não carrega nada da Google.',
     'Only if you accept in the cookie notice does the website use Google Analytics 4, provided by Google Ireland Limited, to measure visits: pages viewed, where the visit came from (for example, search engines, artificial intelligence assistants or social media), device type and approximate location. The cookies used are _ga and _ga_PMGEGTHM1W, which last up to 2 years. Without your permission, the website loads nothing from Google.',
     'Solo si lo acepta en el aviso de cookies, el sitio usa Google Analytics 4, de Google Ireland Limited, para medir las visitas: páginas vistas, origen de la visita (por ejemplo, buscadores, asistentes de inteligencia artificial o redes sociales), tipo de dispositivo y ubicación aproximada. Se usan las cookies _ga y _ga_PMGEGTHM1W, que duran hasta 2 años. Sin su autorización, el sitio no carga nada de Google.'),
    ('O fundamento é o seu consentimento (artigo 6.º, n.º 1, alínea a), do RGPD, e artigo 5.º da Lei n.º 41/2004, de 18 de agosto). Pode retirá-lo a qualquer momento em "Cookies", no rodapé de qualquer página; os cookies de medição são então apagados. Os dados de medição são conservados durante o prazo definido na conta do Google Analytics, de 2 meses. A Google pode tratar dados nos Estados Unidos da América, ao abrigo do Quadro de Privacidade de Dados UE-EUA (Data Privacy Framework), em que está certificada.',
     'The legal basis is your consent (Article 6(1)(a) of the GDPR and Article 5 of Portuguese Law No. 41/2004 of 18 August). You can withdraw it at any time via "Cookies" in the footer of any page; the measurement cookies are then deleted. Measurement data is kept for the period set in the Google Analytics account, 2 months. Google may process data in the United States of America under the EU-US Data Privacy Framework, under which it is certified.',
     'La base jurídica es su consentimiento (artículo 6, apartado 1, letra a), del RGPD, y artículo 5 de la Ley portuguesa n.º 41/2004, de 18 de agosto). Puede retirarlo en cualquier momento en "Cookies", en el pie de cualquier página; las cookies de medición se borran entonces. Los datos de medición se conservan durante el plazo fijado en la cuenta de Google Analytics, de 2 meses. Google puede tratar datos en los Estados Unidos de América, al amparo del Marco de Privacidad de Datos UE-EE. UU. (Data Privacy Framework), en el que está certificada.'),
    ('A sua escolha fica guardada no próprio navegador, para não voltar a ser perguntada; esse registo é necessário ao funcionamento do aviso e não exige consentimento.',
     'Your choice is stored in your own browser so that you are not asked again; this record is necessary for the notice to work and does not require consent.',
     'Su elección se guarda en el propio navegador, para no volver a preguntársela; ese registro es necesario para el funcionamiento del aviso y no requiere consentimiento.'),
    ('>7. Os seus direitos</h2>', '>7. Your rights</h2>', '>7. Sus derechos</h2>'),
    ('Pode pedir o acesso, a retificação, o apagamento, a limitação do tratamento e a portabilidade dos seus dados, bem como opor-se ao tratamento, através do contacto indicado no ponto 1. Tem ainda o direito de apresentar reclamação à Comissão Nacional de Proteção de Dados (CNPD), em ',
     'You can request access to, rectification, erasure, restriction of processing and portability of your data, and object to processing, using the contact details in section 1. You also have the right to lodge a complaint with the Portuguese data protection authority, the Comissão Nacional de Proteção de Dados (CNPD), at ',
     'Puede solicitar el acceso, la rectificación, la supresión, la limitación del tratamiento y la portabilidad de sus datos, así como oponerse al tratamiento, a través del contacto indicado en el apartado 1. Tiene además derecho a presentar una reclamación ante la autoridad portuguesa de protección de datos, la Comissão Nacional de Proteção de Dados (CNPD), en '),
    ('>8. Ligações externas</h2>', '>8. External links</h2>', '>8. Enlaces externos</h2>'),
    ('O site contém ligações para o Instagram da marca e para o diretório de produtores da Guild of Fine Food, entidade que atribui os prémios Great Taste. Ao segui-las, passa a aplicar-se a política de privacidade desses serviços.',
     "The website contains links to the brand's Instagram and to the Guild of Fine Food producer directory, the organisation that runs the Great Taste awards. When you follow them, those services' privacy policies apply.",
     'El sitio contiene enlaces al Instagram de la marca y al directorio de productores de la Guild of Fine Food, entidad que concede los premios Great Taste. Al seguirlos, se aplica la política de privacidad de esos servicios.'),
    ('>9. Livro de Reclamações</h2>', '>9. Complaints Book (Livro de Reclamações)</h2>', '>9. Libro de Reclamaciones (Livro de Reclamações)</h2>'),
    ('Pode apresentar reclamação no <a href="https://www.livroreclamacoes.pt" rel="noopener" target="_blank">Livro de Reclamações Eletrónico</a>.',
     'You can file a complaint in the <a href="https://www.livroreclamacoes.pt" rel="noopener" target="_blank">Electronic Complaints Book (Livro de Reclamações Eletrónico)</a>.',
     'Puede presentar una reclamación en el <a href="https://www.livroreclamacoes.pt" rel="noopener" target="_blank">Libro de Reclamaciones Electrónico (Livro de Reclamações Eletrónico)</a>.'),
    ('No formulário de opinião de degustação pedimos o nome, o email, o telefone (opcional) e o local da degustação, além das respostas sobre as compotas. Estes dados são enviados por email para a caixa de correio da empresa e conservados como os dados de contacto (ponto 4); no email indicado, recebe um agradecimento com as suas notas globais. Para evitar abusos, o servidor guarda durante 24 horas um resumo cifrado do email indicado e o número total de opiniões recebidas, que servem apenas para limitar os envios.',
     "The tasting feedback form asks for your name, email, phone number (optional) and where the tasting took place, as well as your answers about the jams. This information is sent by email to the company's mailbox and kept like contact data (section 4); you receive a thank-you message with your overall scores at the email address provided. To prevent abuse, the server keeps, for 24 hours, an encrypted digest of the email address provided and the total number of responses received, which are used only to limit submissions.",
     'El formulario de opinión de degustación pide el nombre, el correo electrónico, el teléfono (opcional) y el lugar de la degustación, además de las respuestas sobre las mermeladas. Estos datos se envían por correo electrónico al buzón de la empresa y se conservan como los datos de contacto (apartado 4); en el correo indicado, recibe un agradecimiento con sus notas globales. Para evitar abusos, el servidor guarda durante 24 horas un resumen cifrado del correo indicado y el número total de opiniones recibidas, que solo sirven para limitar los envíos.'),
    ('Também com base no interesse legítimo, usamos as opiniões das degustações e os dados de quem as envia só para avaliar e melhorar os produtos e, se necessário, para entrar em contacto sobre a opinião deixada; não são usados para envio de publicidade.',
     'Also on the basis of legitimate interest, we use tasting feedback and the details of those who send it only to assess and improve the products and, where necessary, to contact them about their feedback; they are not used to send advertising.',
     'También sobre la base del interés legítimo, usamos las opiniones de las degustaciones y los datos de quien las envía solo para evaluar y mejorar los productos y, si es necesario, para contactar con esa persona sobre su opinión; no se usan para enviar publicidad.'),
    ('Nos campos de texto do formulário de degustação, pode ditar a resposta com o botão do microfone. A voz é transcrita pelo serviço de reconhecimento de voz do seu navegador (por exemplo, o da Google no Chrome, o da Apple no Safari ou o da Microsoft no Edge), que pode enviar o áudio para os servidores desse fornecedor, nos termos da respetiva política de privacidade. O navegador pede autorização para usar o microfone. O site não recebe nem guarda o áudio: recebe só o texto que estiver no campo quando enviar o formulário, que pode rever e corrigir antes.',
     "In the text fields of the tasting form, you can dictate your answer using the microphone button. Your voice is transcribed by your browser's speech recognition service (for example, Google's in Chrome, Apple's in Safari or Microsoft's in Edge), which may send the audio to that provider's servers under its own privacy policy. Your browser asks for permission to use the microphone. The website does not receive or store the audio: it only receives the text in the field when you submit the form, which you can review and correct beforehand.",
     'En los campos de texto del formulario de degustación, puede dictar la respuesta con el botón del micrófono. La voz la transcribe el servicio de reconocimiento de voz de su navegador (por ejemplo, el de Google en Chrome, el de Apple en Safari o el de Microsoft en Edge), que puede enviar el audio a los servidores de ese proveedor, en los términos de su propia política de privacidad. El navegador pide autorización para usar el micrófono. El sitio no recibe ni guarda el audio: solo recibe el texto que haya en el campo al enviar el formulario, que puede revisar y corregir antes.'),
],
'degustacao.html': [
    ("Opinião de degustação | Tantie Lorraine's",
     "Tasting feedback | Tantie Lorraine's",
     "Opinión de degustación | Tantie Lorraine's"),
    ("Formulário de opinião das degustações das compotas de malagueta Tantie Lorraine's.",
     "Feedback form for Tantie Lorraine's chilli jam tastings.",
     "Formulario de opinión de las degustaciones de mermeladas de chile de Tantie Lorraine's."),
    ('>Opinião de degustação</h1>',
     '>Tasting feedback</h1>',
     '>Opinión de degustación</h1>'),
    ('A sua opinião é muito importante. Classifique cada compota de 1 a 5.',
     'Your opinion matters. Rate each jam from 1 to 5.',
     'Su opinión es muy importante. Valore cada mermelada del 1 al 5.'),
    ('1 = Muito fraco · 2 = Fraco · 3 = Razoável · 4 = Muito bom · 5 = Excelente',
     '1 = Very poor · 2 = Poor · 3 = Fair · 4 = Very good · 5 = Excellent',
     '1 = Muy flojo · 2 = Flojo · 3 = Aceptable · 4 = Muy bueno · 5 = Excelente'),
    ('Obrigado por participar na degustação Tantie Lorraine. A sua opinião foi enviada.',
     'Thank you for taking part in the Tantie Lorraine tasting. Your feedback has been sent.',
     'Gracias por participar en la degustación Tantie Lorraine. Su opinión se ha enviado.'),
    ('Não foi possível enviar a opinião. Confirme o local, as compotas que provou e a nota global de cada uma, o nome e o email, e tente de novo dentro de alguns minutos.',
     'Your feedback could not be sent. Please check the location, the jams you tasted and the overall score for each, your name and your email, and try again in a few minutes.',
     'No ha sido posible enviar la opinión. Compruebe el lugar, las mermeladas que ha probado y la nota global de cada una, el nombre y el correo electrónico, e inténtelo de nuevo dentro de unos minutos.'),
    ('>Antes de começar</h2>',
     '>Before you start</h2>',
     '>Antes de empezar</h2>'),
    ('>Quais compotas provou? (marque todas)</legend>',
     '>Which jams did you taste? (tick all that apply)</legend>',
     '>¿Qué mermeladas ha probado? (marque todas)</legend>'),
    ('data-obrigatorio="Escolha pelo menos uma compota."',
     'data-obrigatorio="Choose at least one jam."',
     'data-obrigatorio="Elija al menos una mermelada."'),
    ('>Qual gostou mais?</legend>',
     '>Which did you like most?</legend>',
     '>¿Cuál le gustó más?</legend>'),
    ('>Porquê, em poucas palavras?</label>',
     '>Why, in a few words?</label>',
     '>¿Por qué, en pocas palabras?</label>'),
    ('>Nível de picante</p>',
     '>Heat level</p>',
     '>Nivel de picante</p>'),
    ('>Melhor utilização</p>',
     '>Best use</p>',
     '>Mejor uso</p>'),
    ('<span class="tl-text-ui">Outra:</span>',
     '<span class="tl-text-ui">Other:</span>',
     '<span class="tl-text-ui">Otro:</span>'),
    ('>Comparação final</legend>',
     '>Final comparison</legend>',
     '>Comparación final</legend>'),
    ('>O que mais gostou na degustação?</label>',
     '>What did you like most about the tasting?</label>',
     '>¿Qué es lo que más le gustó de la degustación?</label>'),
    ('>O que melhoraria em alguma das compotas?</label>',
     '>What would you improve in any of the jams?</label>',
     '>¿Qué mejoraría en alguna de las mermeladas?</label>'),
    ('>Não preencher este campo</label>',
     '>Leave this field empty</label>',
     '>No rellenar este campo</label>'),
    ('data-enviando="A enviar..."',
     'data-enviando="Sending..."',
     'data-enviando="Enviando..."'),
    ('>Enviar opinião</button>',
     '>Send feedback</button>',
     '>Enviar opinión</button>'),
    ('>Sabor do Scotch Bonnet</span><div class="escala"',
     '>Scotch Bonnet flavour</span><div class="escala"',
     '>Sabor del Scotch Bonnet</span><div class="escala"'),
    ('>Nota global</span><div class="escala"',
     '>Overall score</span><div class="escala"',
     '>Nota global</span><div class="escala"'),
    ('>Compraria</span><div class="escala"',
     '>Would buy</span><div class="escala"',
     '>Lo compraría</span><div class="escala"'),
    ('>Sabor frutado da maçã</span><div class="escala"',
     '>Fruity apple flavour</span><div class="escala"',
     '>Sabor afrutado de la manzana</span><div class="escala"'),
    ('>Sabor do pimento vermelho</span><div class="escala"',
     '>Red pepper flavour</span><div class="escala"',
     '>Sabor del pimiento rojo</span><div class="escala"'),
    ('>Sabor do tomate</span><div class="escala"',
     '>Tomato flavour</span><div class="escala"',
     '>Sabor del tomate</span><div class="escala"'),
    ('<span class="opcao__texto">Muito suave</span>',
     '<span class="opcao__texto">Very mild</span>',
     '<span class="opcao__texto">Muy suave</span>'),
    ('<span class="opcao__texto">Suave</span>',
     '<span class="opcao__texto">Mild</span>',
     '<span class="opcao__texto">Suave</span>'),
    ('<span class="opcao__texto">Equilibrado</span>',
     '<span class="opcao__texto">Balanced</span>',
     '<span class="opcao__texto">Equilibrado</span>'),
    ('<span class="opcao__texto">Picante</span>',
     '<span class="opcao__texto">Hot</span>',
     '<span class="opcao__texto">Picante</span>'),
    ('<span class="opcao__texto">Muito picante</span>',
     '<span class="opcao__texto">Very hot</span>',
     '<span class="opcao__texto">Muy picante</span>'),
    ('<span class="opcao__texto">Demasiado para mim</span>',
     '<span class="opcao__texto">Too hot for me</span>',
     '<span class="opcao__texto">Demasiado para mí</span>'),
    ('<span class="opcao__texto">Queijo</span>',
     '<span class="opcao__texto">Cheese</span>',
     '<span class="opcao__texto">Queso</span>'),
    ('<span class="opcao__texto">Carne</span>',
     '<span class="opcao__texto">Meat</span>',
     '<span class="opcao__texto">Carne</span>'),
    ('<span class="opcao__texto">Sanduíche</span>',
     '<span class="opcao__texto">Sandwiches</span>',
     '<span class="opcao__texto">Sándwich</span>'),
    ('<span class="opcao__texto">Pão/torrada</span>',
     '<span class="opcao__texto">Bread/toast</span>',
     '<span class="opcao__texto">Pan/tostada</span>'),
    ('<span class="opcao__texto">Cozinha caribenha</span>',
     '<span class="opcao__texto">Caribbean cooking</span>',
     '<span class="opcao__texto">Cocina caribeña</span>'),
    ('<span class="opcao__texto">Bacon/sanduíche</span>',
     '<span class="opcao__texto">Bacon/sandwiches</span>',
     '<span class="opcao__texto">Bacon/sándwich</span>'),
    ('<span class="opcao__texto">Presunto/porco</span>',
     '<span class="opcao__texto">Ham/pork</span>',
     '<span class="opcao__texto">Jamón/cerdo</span>'),
    ('<span class="opcao__texto">Glaze para carne</span>',
     '<span class="opcao__texto">Glaze for meat</span>',
     '<span class="opcao__texto">Glaseado para carne</span>'),
    ('<span class="opcao__texto">Peixe/marisco</span>',
     '<span class="opcao__texto">Fish/seafood</span>',
     '<span class="opcao__texto">Pescado/marisco</span>'),
    ('<span class="opcao__texto">Frango</span>',
     '<span class="opcao__texto">Chicken</span>',
     '<span class="opcao__texto">Pollo</span>'),
    ('<span class="opcao__texto">Queijo fresco</span>',
     '<span class="opcao__texto">Fresh cheese</span>',
     '<span class="opcao__texto">Queso fresco</span>'),
    ('<span class="opcao__texto">Saladas</span>',
     '<span class="opcao__texto">Salads</span>',
     '<span class="opcao__texto">Ensaladas</span>'),
    ('<span class="opcao__texto">Tacos/wraps</span>',
     '<span class="opcao__texto">Tacos/wraps</span>',
     '<span class="opcao__texto">Tacos/wraps</span>'),
    ('<span class="opcao__texto">Burger</span>',
     '<span class="opcao__texto">Burgers</span>',
     '<span class="opcao__texto">Hamburguesa</span>'),
    ('<span class="opcao__texto">Carne grelhada</span>',
     '<span class="opcao__texto">Grilled meat</span>',
     '<span class="opcao__texto">Carne a la parrilla</span>'),
    ('<span class="opcao__texto">Ovos</span>',
     '<span class="opcao__texto">Eggs</span>',
     '<span class="opcao__texto">Huevos</span>'),
    ('>Qual compraria primeiro?</span><div class="opcoes">',
     '>Which would you buy first?</span><div class="opcoes">',
     '>¿Cuál compraría primero?</span><div class="opcoes">'),
    ('>Qual recomendaria a alguém que não gosta muito de picante?</span><div class="opcoes">',
     '>Which would you recommend to someone who does not like much heat?</span><div class="opcoes">',
     '>¿Cuál recomendaría a alguien a quien no le gusta mucho el picante?</span><div class="opcoes">'),
    ('>Qual recomendaria a quem gosta de muito picante?</span><div class="opcoes">',
     '>Which would you recommend to someone who loves heat?</span><div class="opcoes">',
     '>¿Cuál recomendaría a quien le gusta mucho el picante?</span><div class="opcoes">'),
    ('>Qual parece mais adequada para oferta/presente?</span><div class="opcoes">',
     '>Which seems best as a gift?</span><div class="opcoes">',
     '>¿Cuál le parece más adecuada para regalar?</span><div class="opcoes">'),
    ('Usamos estes dados só para avaliar e melhorar as compotas e, se necessário, para entrar em contacto consigo sobre a sua opinião. Saiba mais na <a href="/privacidade.html">política de privacidade</a>.',
     'We only use this information to assess and improve the jams and, if needed, to contact you about your feedback. Find out more in our <a href="/privacidade.html">privacy policy</a>.',
     'Solo usamos estos datos para evaluar y mejorar las mermeladas y, si es necesario, para contactar con usted sobre su opinión. Más información en la <a href="/privacidade.html">política de privacidad</a>.'),
    ('Marque acima as compotas que provou para ver as perguntas de cada uma.',
     'Tick the jams you tasted above to see the questions for each one.',
     'Marque arriba las mermeladas que ha probado para ver las preguntas de cada una.'),
    ('title="Só algarismos, espaços e os sinais + ( ) . / -"',
     'title="Digits, spaces and the characters + ( ) . / - only"',
     'title="Solo dígitos, espacios y los signos + ( ) . / -"'),
    ('Telefone <span class="tl-text-ui-sm">(opcional)</span>',
     'Phone <span class="tl-text-ui-sm">(optional)</span>',
     'Teléfono <span class="tl-text-ui-sm">(opcional)</span>'),
    ('for="degustacao-email">Email</label>',
     'for="degustacao-email">Email</label>',
     'for="degustacao-email">Correo electrónico</label>'),
    ('for="degustacao-nome">Nome</label>',
     'for="degustacao-nome">Name</label>',
     'for="degustacao-nome">Nombre</label>'),
    ('>Os seus dados</h2>',
     '>Your details</h2>',
     '>Sus datos</h2>'),
    ('Por exemplo, o nome do evento ou da loja, ou "Em casa".',
     'For example, the name of the event or shop, or "At home".',
     'Por ejemplo, el nombre del evento o de la tienda, o "En casa".'),
    ('for="degustacao-local">Local da degustação</label>',
     'for="degustacao-local">Tasting location</label>',
     'for="degustacao-local">Lugar de la degustación</label>'),
    ('>Tomate cereja e malagueta</span>',
     '>Cherry tomato and chilli</span>',
     '>Tomate cherry y chile</span>'),
    ('>Pimento vermelho, malagueta e lima</span>',
     '>Red pepper, chilli and lime</span>',
     '>Pimiento rojo, chile y lima</span>'),
    ('>Maçã e malagueta</span>',
     '>Apple and chilli</span>',
     '>Manzana y chile</span>'),
    ('>Malagueta Scotch Bonnet</span>',
     '>Scotch Bonnet chilli</span>',
     '>Chile Scotch Bonnet</span>'),
],
}

# Textos que só existem nos dados estruturados (valor completo); os restantes usam os fragmentos acima
JSON = [
    ('Compota de malagueta', 'Chilli jam', 'Mermelada de chile'),
    ('Reino Unido', 'United Kingdom', 'Reino Unido'),
    ('Great Taste Awards 2024, duas estrelas (Guild of Fine Food)', 'Great Taste Awards 2024, two stars (Guild of Fine Food)',
     'Great Taste Awards 2024, dos estrellas (Guild of Fine Food)'),
    ('A compota de referência da casa e a mais premiada da gama: duas estrelas nos Great Taste Awards de 2024. Feita com pimento vermelho e pasta fermentada de malagueta Scotch Bonnet (100 000 a 350 000 unidades de calor Scoville, SHU).',
     'The house signature jam and the most awarded in the range: two stars at the 2024 Great Taste Awards. Made with red pepper and fermented Scotch Bonnet chilli mash (100,000 to 350,000 Scoville heat units, SHU).',
     'La mermelada emblemática de la casa y la más premiada de la gama: dos estrellas en los Great Taste Awards de 2024. Elaborada con pimiento rojo y pasta fermentada de chile Scotch Bonnet (100 000 a 350 000 unidades de picor Scoville, SHU).'),
    ('A venda em Portugal e Espanha abre brevemente. Para ser avisado, envie um pedido de informação na página de contactos.',
     'Sales in Portugal and Spain open soon. To be notified, send an information request on the contact page.',
     'La venta en Portugal y España abre próximamente. Para que le avisemos, envíe una solicitud de información en la página de contacto.'),
    ('A Apple Chilli Jam: doce, frutada e com um calor suave, para quem quer o sabor da malagueta sem muito picante. A Red Pepper Chilli Lime Jam e a Tomato Chilli Jam têm picante médio. A Scotch Bonnet Jam é feita com pasta fermentada de malagueta Scotch Bonnet (100 000 a 350 000 unidades de calor Scoville).',
     'Apple Chilli Jam: sweet, fruity and gently warm, for those who want the flavour of chilli without too much heat. Red Pepper Chilli Lime Jam and Tomato Chilli Jam have medium heat. Scotch Bonnet Jam is made with fermented Scotch Bonnet chilli mash (100,000 to 350,000 Scoville heat units).',
     'La Apple Chilli Jam: dulce, afrutada y con un picor suave, para quien quiere el sabor del chile sin demasiado picante. La Red Pepper Chilli Lime Jam y la Tomato Chilli Jam tienen un picor medio. La Scotch Bonnet Jam se elabora con pasta fermentada de chile Scotch Bonnet (100 000 a 350 000 unidades de picor Scoville).'),
    ('A Tomato Chilli Jam foi feita para tábuas de queijos e enchidos. A marca sugere também sanduíches, por exemplo ovo com maionese, rúcula e Scotch Bonnet Jam, ou pesto, queijo, rúcula e Tomato Chilli Jam. Há mais ideias nas publicações.',
     'Tomato Chilli Jam was made for cheese and charcuterie boards. The brand also suggests sandwiches, such as egg mayonnaise, rocket and Scotch Bonnet Jam, or pesto, cheese, rocket and Tomato Chilli Jam. There are more ideas in our posts.',
     'La Tomato Chilli Jam se pensó para tablas de quesos y embutidos. La marca sugiere también sándwiches, por ejemplo huevo con mayonesa, rúcula y Scotch Bonnet Jam, o pesto, queso, rúcula y Tomato Chilli Jam. Hay más ideas en las publicaciones.'),
    ("Tantie Lorraine's Portugal e Espanha", "Tantie Lorraine's Portugal and Spain", "Tantie Lorraine's Portugal y España"),
    ('Espanha', 'Spain', 'España'),
]

# Nomes próprios em português que podem ficar nas páginas traduzidas (não contam como texto por traduzir)
NOMES_PT = ['GADGETMÉTODO', 'Gadgetmétodo', 'Rua das Cruzes', 'n.º', 'Comissão Nacional de Proteção de Dados', 'Livro de Reclamações Eletrónico',
            'Livro de Reclamações', 'sociedade por quotas', 'Tantie Lorraine', 'Porto']
SINAIS_PT = re.compile(r'[ãõç]|\b(não|são|com|das|às|uma|pelo|pela|também|você|está|é|ção|ões|muito|nosso|nossa|espanha)\b', re.I)
SINAIS_PT_ES = re.compile(r'[ãõç]|\b(não|são|com|das|às|uma|pelo|pela|também|você|é|muito|nosso|nossa|espanha)\b|ção|ões', re.I)
# Entidades partilhadas pelas três línguas: os identificadores não mudam
ENTIDADES = {SITE + '/#organizacao', SITE + '/#marca', SITE + '/#site'}


def caminho_publico(ficheiro, lingua):
    return PAGINAS[ficheiro][lingua]


def ficheiro_destino(ficheiro, lingua):
    p = PAGINAS[ficheiro][lingua].lstrip('/')
    return RAIZ / (p + 'index.html' if p.endswith('/') or p == '' else p)


def bloco_idiomas(ficheiro, lingua):
    linhas = ['  <!-- idiomas: gerado por scripts/idiomas.py -->']
    for l, (codigo, _, _, _, _) in LINGUAS.items():
        linhas.append(f'  <link rel="alternate" hreflang="{codigo}" href="{SITE}{caminho_publico(ficheiro, l)}">')
    linhas.append(f'  <link rel="alternate" hreflang="x-default" href="{SITE}{caminho_publico(ficheiro, "pt")}">')
    for l, (_, locale, _, _, _) in LINGUAS.items():
        if l != lingua:
            linhas.append(f'  <meta property="og:locale:alternate" content="{locale}">')
    linhas.append('  <!-- /idiomas -->')
    return '\n'.join(linhas) + '\n'


def seletor(destinos, lingua):
    rotulo = LINGUAS[lingua][4]
    partes = []
    for l, (codigo, _, curto, nome, _) in LINGUAS.items():
        atual = ' aria-current="true"' if l == lingua else ''
        partes.append(f'<a href="{destinos[l]}" hreflang="{codigo}" lang="{codigo}" title="{nome}"{atual}>{curto}</a>')
    return f'<span class="idiomas" role="group" aria-label="{rotulo}">' + ''.join(partes) + '</span>'


def aplicar_seletor(s, destinos, lingua):
    s = re.sub(r'<span class="idiomas"[^>]*>.*?</span>', '', s)
    s, n = re.subn(r'(<nav class="tl-nav"[^>]*>.*?)(</nav>)', lambda m: m.group(1) + seletor(destinos, lingua) + m.group(2), s, count=1, flags=re.S)
    assert n == 1, 'menu não encontrado'
    return s


def aplicar_bloco(s, bloco):
    s = re.sub(r'  <!-- idiomas: gerado por scripts/idiomas.py -->\n.*?  <!-- /idiomas -->\n', '', s, flags=re.S)
    s, n = re.subn(r'(  <link rel="canonical" href="[^"]*">\n)', lambda m: m.group(1) + bloco, s, count=1)
    assert n == 1, 'canonical não encontrado'
    return s


def traduzir_texto(texto, pares, idx):
    """Substitui os fragmentos (do mais longo para o mais curto) por marcadores e depois pelas traduções."""
    usados = set()
    for i, par in sorted(enumerate(pares), key=lambda x: -len(x[1][0])):
        if par[0] in texto:
            texto = texto.replace(par[0], f'\x00{i}\x00')
            usados.add(i)
    texto = re.sub(r'\x00(\d+)\x00', lambda m: pares[int(m.group(1))][idx], texto)
    return texto, usados


def mapear_urls(s, lingua):
    """Ligações internas e endereços absolutos das páginas portuguesas passam para as da língua."""
    for ficheiro, caminhos in PAGINAS.items():
        pt, novo = caminhos['pt'], caminhos[lingua]
        if pt == '/':
            s = re.sub(r'href="/(#[^"]*)?"', lambda m: f'href="{novo}{m.group(1) or ""}"', s)
            s = re.sub(re.escape(f'"{SITE}/') + r'(#[^"]*)?"', lambda m: f'"{SITE}{novo}{m.group(1) or ""}"', s)
        else:
            s = re.sub(r'href="' + re.escape(pt) + r'(#[^"]*)?"', lambda m: f'href="{novo}{m.group(1) or ""}"', s)
            s = s.replace(f'{SITE}{pt}', f'{SITE}{novo}')
    return s


def traduzir_json(no, lingua, pares, problemas, mapear=True):
    idx = 1 if lingua == 'en' else 2
    if isinstance(no, dict):
        tipo = no.get('@type')
        mapear_aqui = mapear and tipo not in ('Organization', 'Brand', 'WebSite')
        for k, v in list(no.items()):
            if isinstance(v, str):
                if k in ('url', '@id', 'primaryImageOfPage', 'image', 'logo', 'sameAs'):
                    if mapear_aqui and k in ('url', '@id') and v not in ENTIDADES:
                        no[k] = mapear_urls(f'"{v}"', lingua)[1:-1]
                elif k == 'inLanguage':
                    no[k] = LINGUAS[lingua][0]
                elif k not in ('@type', '@context', 'email', 'taxID', 'postalCode', 'addressCountry', 'unitCode', 'sku', 'legalName',
                               'streetAddress', 'addressLocality', 'contactType', 'availableLanguage'):
                    exato = {p[0]: p[idx] for p in JSON}
                    if v in exato:
                        no[k] = exato[v]
                    else:
                        no[k], _ = traduzir_texto(v, pares, idx)
                    resto = no[k]
                    for nome in NOMES_PT:
                        resto = resto.replace(nome, '')
                    if (SINAIS_PT if lingua == 'en' else SINAIS_PT_ES).search(resto):
                        problemas.append(f'JSON-LD por traduzir ({k}): {no[k][:90]}')
            else:
                no[k] = traduzir_json(v, lingua, pares, problemas, mapear_aqui)
        if tipo == 'WebSite':
            no['inLanguage'] = [c for c, *_ in LINGUAS.values()]
    elif isinstance(no, list):
        return [traduzir_json(x, lingua, pares, problemas, mapear) for x in no]
    return no


def ajustar_json_pt(s):
    """Na página portuguesa, só se acertam o site trilingue e as línguas de contacto."""
    def troca(m):
        dados = json.loads(m.group(1))
        def andar(no):
            if isinstance(no, dict):
                if no.get('@type') == 'WebSite':
                    no['inLanguage'] = [c for c, *_ in LINGUAS.values()]
                if no.get('@type') == 'ContactPoint':
                    no['availableLanguage'] = ['Portuguese', 'English', 'Spanish']
                for v in no.values():
                    andar(v)
            elif isinstance(no, list):
                for v in no:
                    andar(v)
        andar(dados)
        return '<script type="application/ld+json">\n' + json.dumps(dados, ensure_ascii=False, indent=1) + '\n</script>'
    return re.sub(r'<script type="application/ld\+json">\n(.*?)\n</script>', troca, s, flags=re.S)


def texto_visivel(s):
    s = re.sub(r'<script.*?</script>|<style.*?</style>', ' ', s, flags=re.S)
    partes = re.findall(r'(?:alt|title|aria-label|content|data-enviando)="([^"]*)"', s)
    s = re.sub(r'<[^>]+>', ' ', s)
    return ' '.join([s] + partes)


def gerar():
    saidas = {}
    problemas = []
    for ficheiro in PAGINAS:
        origem = (RAIZ / ficheiro).read_text(encoding='utf-8')
        destinos = {l: c for l, c in PAGINAS[ficheiro].items()}
        # Página portuguesa: seletor, hreflang e dados estruturados do site trilingue
        pt = aplicar_seletor(aplicar_bloco(origem, bloco_idiomas(ficheiro, 'pt')), destinos, 'pt')
        pt = ajustar_json_pt(pt)
        saidas[RAIZ / ficheiro] = pt
        for lingua, idx in (('en', 1), ('es', 2)):
            pares = COMUM + PAGINA[ficheiro]
            corpo = re.sub(r'<script type="application/ld\+json">\n.*?\n</script>', '\x01JSONLD\x01', pt, count=1, flags=re.S)
            corpo, usados = traduzir_texto(corpo, pares, idx)
            for i in range(len(COMUM), len(pares)):
                if i not in usados:
                    problemas.append(f'{ficheiro}: fragmento já não existe na página portuguesa: {pares[i][0][:80]}')
            # Ligações para as páginas da língua; o seletor e o hreflang são refeitos depois, com os caminhos certos
            corpo = mapear_urls(corpo, lingua)
            corpo = aplicar_seletor(corpo, destinos, lingua)
            corpo = aplicar_bloco(corpo, bloco_idiomas(ficheiro, lingua))
            corpo = corpo.replace('<html lang="pt-PT">', f'<html lang="{LINGUAS[lingua][0]}">')
            corpo = corpo.replace('<meta property="og:locale" content="pt_PT">', f'<meta property="og:locale" content="{LINGUAS[lingua][1]}">')
            corpo = corpo.replace('<input type="hidden" name="idioma" value="pt">', f'<input type="hidden" name="idioma" value="{lingua}">')
            if lingua == 'en':
                # Decimais com ponto em inglês, só nas células das tabelas nutricionais (os outros números vêm traduzidos)
                corpo = re.sub(r'<td>[^<]*</td>', lambda mm: re.sub(r'(?<=\d),(?=\d)', '.', mm.group(0)), corpo)
            # JSON-LD traduzido, já com os endereços da língua (os das entidades partilhadas ficam iguais)
            m = re.search(r'<script type="application/ld\+json">\n(.*?)\n</script>', pt, re.S)
            if m:
                dados = traduzir_json(json.loads(m.group(1)), lingua, pares, problemas)
                corpo = corpo.replace('\x01JSONLD\x01', '<script type="application/ld+json">\n' + json.dumps(dados, ensure_ascii=False, indent=1) + '\n</script>')
            visivel = texto_visivel(corpo)
            for nome in NOMES_PT:
                visivel = visivel.replace(nome, '')
            for achado in set((SINAIS_PT if lingua == 'en' else SINAIS_PT_ES).findall(visivel)):
                problemas.append(f'{ficheiro} ({lingua}): texto em português por traduzir, sinal "{achado}"')
            saidas[ficheiro_destino(ficheiro, lingua)] = corpo
    # 404: só o seletor, com as páginas iniciais
    p404 = (RAIZ / '404.html').read_text(encoding='utf-8')
    saidas[RAIZ / '404.html'] = aplicar_seletor(p404, {'pt': '/', 'en': '/en/', 'es': '/es/'}, 'pt')
    if problemas:
        print('\n'.join(sorted(set(problemas))), file=sys.stderr)
        sys.exit('Nada foi escrito: atualizar a tabela de traduções em scripts/idiomas.py.')
    for caminho, conteudo in saidas.items():
        caminho.parent.mkdir(parents=True, exist_ok=True)
        caminho.write_text(conteudo, encoding='utf-8')
    escrever_sitemap()
    print(f'{len(saidas)} páginas escritas (português, inglês e espanhol) e sitemap.xml atualizado.')


def escrever_sitemap():
    linhas = ['<?xml version="1.0" encoding="UTF-8"?>',
              '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">']
    for ficheiro in PAGINAS:
        if ficheiro in SEM_SITEMAP:
            continue
        for lingua in LINGUAS:
            linhas.append(f'  <url>\n    <loc>{SITE}{caminho_publico(ficheiro, lingua)}</loc>\n    <lastmod>{DATA_SITEMAP}</lastmod>')
            for l, (codigo, *_ ) in LINGUAS.items():
                linhas.append(f'    <xhtml:link rel="alternate" hreflang="{codigo}" href="{SITE}{caminho_publico(ficheiro, l)}"/>')
            linhas.append('  </url>')
    linhas.append('</urlset>')
    (RAIZ / 'sitemap.xml').write_text('\n'.join(linhas) + '\n', encoding='utf-8')


if __name__ == '__main__':
    gerar()
