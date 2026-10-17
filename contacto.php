<?php
// tantielorraine.pt: recebe o pedido de informação das páginas de contactos (três línguas) e envia-o por email
// através do servidor de envio (SMTP) da Amen, com o código comum de email-comum.php.
//
// Cada pedido gera dois emails na mesma ligação: o aviso para a equipa e uma confirmação de receção para quem
// escreveu (HTML com logótipo e selos, mais versão em texto). A confirmação não repete nada do que foi escrito,
// exceto o nome quando parece um nome, para o formulário não servir para enviar conteúdo alheio em nosso nome.
//
// Resposta em JSON quando o pedido o aceita (envio por JavaScript); sem JavaScript, redireciona para
// /contactos.html#pedido-enviado ou #pedido-erro. GET ?verificar indica só se a configuração foi encontrada.

declare(strict_types=1);

require __DIR__ . '/email-comum.php';

// Selos do rodapé da confirmação, pela ordem do site; só entram os que existem no servidor
const SELOS = [
    ['food-hygiene-rating-5.png', ['Food Hygiene Rating 5, Very Good', 'Food Hygiene Rating 5, Very Good', 'Food Hygiene Rating 5, Very Good'], false],
    ['great-taste-producer.png', ['Great Taste Producer', 'Great Taste Producer', 'Great Taste Producer'], true],
    ['great-taste-2019-1-estrela.png', ['Great Taste 2019, uma estrela', 'Great Taste 2019, one star', 'Great Taste 2019, una estrella'], true],
    ['great-taste-2021-1-estrela.png', ['Great Taste 2021, uma estrela', 'Great Taste 2021, one star', 'Great Taste 2021, una estrella'], true],
    ['great-taste-2024-2-estrelas.png', ['Great Taste 2024, duas estrelas', 'Great Taste 2024, two stars', 'Great Taste 2024, dos estrellas'], true],
    ['great-taste-2026.png', ['Great Taste 2026, uma estrela', 'Great Taste 2026, one star', 'Great Taste 2026, una estrella'], true],
];
// Línguas do site (formulário em /contactos.html, /en/contact.html e /es/contacto.html): página de volta e textos da confirmação
const LINGUAS = [
    'pt' => ['pagina' => '/contactos.html', 'codigo' => 'pt-PT', 'nome' => 'português', 'i' => 0],
    'en' => ['pagina' => '/en/contact.html', 'codigo' => 'en-GB', 'nome' => 'inglês', 'i' => 1],
    'es' => ['pagina' => '/es/contacto.html', 'codigo' => 'es-ES', 'nome' => 'espanhol', 'i' => 2],
];
const CONFIRMACAO = [
    'pt' => [
        'assunto' => "Tantie Lorraine's: recebemos o seu pedido", 'titulo' => 'Recebemos o seu pedido',
        'ola' => 'Olá', 'cabecalho' => 'Obrigado pelo seu contacto',
        'corpo' => 'Recebemos o seu pedido de informação e responderemos com a maior brevidade possível.',
        'corpo_texto' => 'Obrigado pelo seu contacto. Recebemos o seu pedido de informação e responderemos com a maior brevidade possível.',
        'despedida' => 'Com os melhores cumprimentos,', 'equipa' => "A equipa Tantie Lorraine's em Portugal",
        'premios' => 'Prémios Great Taste da marca', 'selos' => 'Distinções da marca no Reino Unido',
        'rodape' => 'Recebeu este email porque enviou um pedido de informação no site tantielorraine.pt. Se não foi o caso, ignore esta mensagem.',
    ],
    'en' => [
        'assunto' => "Tantie Lorraine's: we have received your request", 'titulo' => 'We have received your request',
        'ola' => 'Hello', 'cabecalho' => 'Thank you for getting in touch',
        'corpo' => 'We have received your information request and will reply as soon as possible.',
        'corpo_texto' => 'Thank you for getting in touch. We have received your information request and will reply as soon as possible.',
        'despedida' => 'Kind regards,', 'equipa' => "The Tantie Lorraine's team in Portugal",
        'premios' => "The brand's Great Taste awards", 'selos' => "The brand's awards in the UK",
        'rodape' => 'You received this email because you sent an information request on the tantielorraine.pt website. If this was not you, please ignore this message.',
    ],
    'es' => [
        'assunto' => "Tantie Lorraine's: hemos recibido su solicitud", 'titulo' => 'Hemos recibido su solicitud',
        'ola' => 'Hola', 'cabecalho' => 'Gracias por contactar con nosotros',
        'corpo' => 'Hemos recibido su solicitud de información y le responderemos lo antes posible.',
        'corpo_texto' => 'Gracias por contactar con nosotros. Hemos recibido su solicitud de información y le responderemos lo antes posible.',
        'despedida' => 'Un cordial saludo,', 'equipa' => "El equipo de Tantie Lorraine's en Portugal",
        'premios' => 'Premios Great Taste de la marca', 'selos' => 'Distinciones de la marca en el Reino Unido',
        'rodape' => 'Ha recibido este correo porque envió una solicitud de información en el sitio tantielorraine.pt. Si no ha sido usted, ignore este mensaje.',
    ],
];
// Limites por janela de 24 horas: pedidos para o mesmo email e pedidos no total
const LIMITE_EMAIL = 3;
const LIMITE_TOTAL = 100;

// Língua do formulário (campo escondido "idioma"); por omissão, português
function lingua(): string
{
    $l = $_POST['idioma'] ?? 'pt';
    return (is_string($l) && isset(LINGUAS[$l])) ? $l : 'pt';
}

function responder(bool $ok, string $erro = '', array $campos = []): void
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($ok ? 200 : ['campos' => 422, 'limite' => 429][$erro] ?? 500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'erro' => $erro, 'campos' => $campos]);
    } else {
        header('Location: ' . LINGUAS[lingua()]['pagina'] . '#' . ($ok ? 'pedido-enviado' : 'pedido-erro'), true, 303);
    }
    exit;
}

// Confirmação para quem escreveu: [texto, HTML]. Cores dos tokens do design system em hexadecimal, porque os
// programas de email não suportam variáveis CSS; tipos de letra com as alternativas das pilhas do sistema (a Anton
// vem do site nos programas que aceitam tipos de letra externos). Selos em linha, para mudarem de linha em ecrãs estreitos.
function confirmacao(string $nome, string $lingua): array
{
    $c = CONFIRMACAO[$lingua];
    $saudacao = preg_match("/^\\p{L}[\\p{L}\\p{M} .'’-]{1,59}$/u", $nome) ? $c['ola'] . ", $nome," : $c['ola'] . ',';
    $texto = "$saudacao\n\n" . $c['corpo_texto'] . "\n\n"
        . $c['despedida'] . "\n" . $c['equipa'] . "\n"
        . REMETENTE . ' | ' . SITE . "\n\n"
        . $c['premios'] . ': ' . GUILD . "\n\n"
        . $c['rodape'] . "\n"
        . "GADGETMÉTODO, LDA · Rua das Cruzes, n.º 625, 4100-195 Porto\n";

    $h = function (string $t): string {
        return htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    };
    $selos = '';
    foreach (SELOS as [$ficheiro, $alts, $ligacao]) {
        $alt = $alts[LINGUAS[$lingua]['i']];
        $dimensoes = @getimagesize(__DIR__ . '/assets/img/selos/' . $ficheiro);
        if (!$dimensoes) {
            continue;
        }
        $largura = (int) round($dimensoes[0] * 64 / max(1, $dimensoes[1]));
        $img = '<img src="' . SITE . '/assets/img/selos/' . $ficheiro . '" width="' . $largura . '" height="64" alt="' . $h($alt) . '" style="display:inline-block;vertical-align:middle;border:0;height:64px;width:' . $largura . 'px;margin:0 12px 12px 0;">';
        $selos .= $ligacao ? '<a href="' . GUILD . '" target="_blank">' . $img . '</a>' : $img;
    }
    $blocoSelos = $selos === '' ? '' : '<p style="margin:24px 0 12px;font-family:Anton,\'Arial Narrow\',Impact,sans-serif;font-size:16px;line-height:21px;text-transform:uppercase;color:#000000;">' . $h($c['selos']) . '</p>'
        . '<p style="margin:0;font-size:0;line-height:0;">' . $selos . '</p>';
    $corpo = 'font-family:\'Avenir Next\',Avenir,\'Helvetica Neue\',Arial,sans-serif;font-size:16px;line-height:24px;color:#000000;';
    $pequeno = 'font-family:\'Helvetica Neue\',Arial,sans-serif;font-size:14px;line-height:20px;color:#000000;';
    $html = '<!doctype html><html lang="' . LINGUAS[$lingua]['codigo'] . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="color-scheme" content="light"><title>' . $h($c['titulo']) . '</title>'
        . '<style>@font-face{font-family:Anton;src:url(' . SITE . '/assets/fonts/anton-latin-400-normal.woff2) format("woff2");}</style></head>'
        . '<body style="margin:0;padding:0;background:#f0f0f0;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;"><tr><td align="center" style="padding:32px 12px;">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;">'
        . '<tr><td style="padding:32px;' . $corpo . '">'
        . '<h1 style="margin:0 0 20px;font-family:Anton,\'Arial Narrow\',Impact,sans-serif;font-weight:400;font-size:32px;line-height:38px;text-transform:uppercase;color:#000000;">' . $h($c['cabecalho']) . '</h1>'
        . '<p style="margin:0 0 12px;">' . $h($saudacao) . '</p>'
        . '<p style="margin:0 0 12px;">' . $h($c['corpo']) . '</p>'
        . '<p style="margin:0;">' . $h($c['despedida']) . '<br>' . $h($c['equipa']) . '</p>'
        . '</td></tr>'
        . '<tr><td style="background:#ff2524;padding:32px;' . $pequeno . '">'
        . '<a href="' . SITE . '" target="_blank"><img src="' . SITE . '/assets/img/logo.png" width="140" height="80" alt="' . $h(NOME_MARCA) . '" style="display:block;border:0;width:140px;height:80px;"></a>'
        . $blocoSelos
        . '<p style="margin:12px 0 0;' . $pequeno . '"><a href="mailto:' . REMETENTE . '" style="color:#000000;">' . REMETENTE . '</a> · <a href="' . SITE . '" style="color:#000000;">' . DOMINIO . '</a></p>'
        . '<p style="margin:12px 0 0;' . $pequeno . '">' . $h($c['rodape']) . '<br>GADGETMÉTODO, LDA · Rua das Cruzes, n.º 625, 4100-195 Porto</p>'
        . '</td></tr></table></td></tr></table></body></html>';
    return [$texto, $html];
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';
if ($metodo === 'GET' && isset($_GET['verificar'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['configurado' => config() !== null]);
    exit;
}
if ($metodo !== 'POST') {
    header('Location: /contactos.html#pedido', true, 303);
    exit;
}

// Armadilhas para robôs: campo escondido preenchido, ou envio menos de 3 segundos depois de abrir a página
// (medido pelo JavaScript; sem JavaScript o campo vem vazio). Responde como se tivesse enviado, sem dar pistas.
$t = campo('t');
if (campo('website') !== '' || ($t !== '' && (int) $t < 3)) {
    responder(true);
}

$nome = linha(campo('nome'));
$email = linha(campo('email'));
$telefone = linha(campo('telefone'));
$mensagem = texto(campo('mensagem'));

$invalidos = [];
if (caracteres($nome) < 2 || caracteres($nome) > 100) {
    $invalidos[] = 'nome';
}
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $invalidos[] = 'email';
}
if ($telefone !== '' && !preg_match('/^[0-9 +().\/-]{6,30}$/', $telefone)) {
    $invalidos[] = 'telefone';
}
if (caracteres($mensagem) < 10 || caracteres($mensagem) > 5000) {
    $invalidos[] = 'mensagem';
}
if ($invalidos) {
    responder(false, 'campos', $invalidos);
}

$c = config();
if ($c === null) {
    error_log('contacto.php: config-email.php não encontrado (correr scripts/deploy.sh configurar-email)');
    responder(false, 'configuracao');
}

if (!dentro_do_limite($c, [['email:' . strtolower($email), LIMITE_EMAIL], ['total', LIMITE_TOTAL]])) {
    error_log('contacto.php: limite de pedidos atingido');
    responder(false, 'limite');
}

$agora = new DateTime('now', new DateTimeZone('Europe/Lisbon'));
$aviso = "Pedido de informação recebido no site " . DOMINIO . "\n\n"
    . "Nome: $nome\n"
    . "Email: $email\n"
    . 'Telefone: ' . ($telefone !== '' ? $telefone : 'não indicado') . "\n"
    . 'Língua do site: ' . LINGUAS[lingua()]['nome'] . "\n\n"
    . "Mensagem:\n$mensagem\n\n"
    . "--\nEnviado em " . $agora->format('d/m/Y H:i') . " (hora de Lisboa). "
    . "Para responder, use Responder: a resposta segue para o email indicado no pedido. "
    . "Quem escreveu recebe uma confirmação automática de receção.\n";
[$confirmacaoTexto, $confirmacaoHtml] = confirmacao($nome, lingua());

try {
    enviar($c, [
        [DESTINATARIOS, mensagem(cabecalho(NOME_REMETENTE) . ' <' . REMETENTE . '>', DESTINATARIOS, 'Pedido de informação: ' . $nome,
            ['Reply-To: ' . cabecalho($nome) . ' <' . $email . '>'], $aviso)],
        [[$email], mensagem(cabecalho(NOME_MARCA) . ' <' . REMETENTE . '>', [$email], CONFIRMACAO[lingua()]['assunto'],
            ['Auto-Submitted: auto-replied', 'X-Auto-Response-Suppress: All'], $confirmacaoTexto, $confirmacaoHtml)],
    ]);
} catch (FalhaEnvio $e) {
    responder(false, $e->getMessage());
}
responder(true);
