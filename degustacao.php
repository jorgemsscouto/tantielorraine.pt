<?php
// tantielorraine.pt: recebe a opinião de degustação (/degustacao.html, /en/tasting.html, /es/degustacion.html) e envia
// dois emails, com o código comum de email-comum.php:
// - a opinião completa para info@tantielorraine.pt, sempre em português, com os dados de quem respondeu e, no fim,
//   duas linhas separadas por ";" prontas a colar numa folha de cálculo (colunas sempre iguais);
// - um agradecimento a quem respondeu, na língua da página, com as notas globais. Só leva valores das listas fixas e o
//   nome (se parecer um nome), nunca texto escrito pela pessoa, para o formulário não servir para enviar mensagens a
//   terceiros.
// As perguntas são uma versão curta da ficha em papel "Tantie Lorraine: Degustação de Chilli Jams"; as chaves têm de
// coincidir com as da página. Só conta a classificação das compotas marcadas como provadas; a nota global de cada uma é
// obrigatória.
//
// Resposta em JSON quando o pedido o aceita (envio por JavaScript); sem JavaScript, redireciona para a página da
// língua com #opiniao-enviada ou #opiniao-erro.

declare(strict_types=1);

require __DIR__ . '/email-comum.php';

const COMPOTAS = [
    'sbj' => [
        'nome' => 'Scotch Bonnet Jam',
        'criterios' => ['sabor' => 'Sabor do Scotch Bonnet', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'muito-picante' => 'Muito picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['queijo' => 'Queijo', 'carne' => 'Carne', 'sanduiche' => 'Sanduíche', 'pao' => 'Pão/torrada', 'caribenha' => 'Cozinha caribenha'],
    ],
    'acj' => [
        'nome' => 'Apple Chilli Jam',
        'criterios' => ['sabor' => 'Sabor frutado da maçã', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['muito-suave' => 'Muito suave', 'suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['queijo' => 'Queijo', 'bacon' => 'Bacon/sanduíche', 'porco' => 'Presunto/porco', 'pao' => 'Pão/torrada', 'glaze' => 'Glaze para carne'],
    ],
    'rpcl' => [
        'nome' => 'Red Pepper Chilli Lime Jam',
        'criterios' => ['sabor' => 'Sabor do pimento vermelho', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['muito-suave' => 'Muito suave', 'suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['peixe' => 'Peixe/marisco', 'frango' => 'Frango', 'queijo-fresco' => 'Queijo fresco', 'saladas' => 'Saladas', 'tacos' => 'Tacos/wraps'],
    ],
    'tcj' => [
        'nome' => 'Tomato Chilli Jam',
        'criterios' => ['sabor' => 'Sabor do tomate', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['muito-suave' => 'Muito suave', 'suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['burger' => 'Burger', 'queijo' => 'Queijo', 'carne-grelhada' => 'Carne grelhada', 'sanduiche' => 'Sanduíche', 'ovos' => 'Ovos'],
    ],
];
const COMPARACAO = [
    'primeiro' => 'Qual compraria primeiro?',
    'pouco-picante' => 'Qual recomendaria a alguém que não gosta muito de picante?',
    'muito-picante' => 'Qual recomendaria a quem gosta de muito picante?',
    'oferta' => 'Qual parece mais adequada para oferta/presente?',
];
// Línguas do site: página do formulário, página de produtos (botão do agradecimento) e textos do agradecimento
const LINGUAS = [
    'pt' => ['pagina' => '/degustacao.html', 'produtos' => '/produtos.html', 'codigo' => 'pt-PT', 'nome' => 'português', 'i' => 0],
    'en' => ['pagina' => '/en/tasting.html', 'produtos' => '/en/products.html', 'codigo' => 'en-GB', 'nome' => 'inglês', 'i' => 1],
    'es' => ['pagina' => '/es/degustacion.html', 'produtos' => '/es/productos.html', 'codigo' => 'es-ES', 'nome' => 'espanhol', 'i' => 2],
];
const AGRADECIMENTO = [
    'pt' => [
        'assunto' => "Tantie Lorraine's: obrigado pela sua opinião", 'titulo' => 'Obrigado pela sua opinião', 'ola' => 'Olá',
        'corpo' => 'Recebemos a sua opinião sobre as nossas compotas de malagueta. Cada resposta ajuda-nos a fazer compotas ainda melhores.',
        'notas' => 'As suas notas', 'global' => 'Nota global', 'preferida' => 'A sua preferida', 'frasco' => 'Frasco de %s',
        'botao' => 'Conhecer a gama', 'despedida' => 'Com os melhores cumprimentos,', 'equipa' => "A equipa Tantie Lorraine's em Portugal",
        'selos' => 'Distinções da marca no Reino Unido',
        'rodape' => 'Recebeu este email porque enviou uma opinião de degustação no site tantielorraine.pt. Se não foi o caso, ignore esta mensagem.',
    ],
    'en' => [
        'assunto' => "Tantie Lorraine's: thank you for your feedback", 'titulo' => 'Thank you for your feedback', 'ola' => 'Hello',
        'corpo' => 'We have received your feedback on our chilli jams. Every answer helps us make even better jams.',
        'notas' => 'Your scores', 'global' => 'Overall score', 'preferida' => 'Your favourite', 'frasco' => 'Jar of %s',
        'botao' => 'Discover the range', 'despedida' => 'Kind regards,', 'equipa' => "The Tantie Lorraine's team in Portugal",
        'selos' => "The brand's awards in the UK",
        'rodape' => 'You received this email because you sent tasting feedback on the tantielorraine.pt website. If this was not you, please ignore this message.',
    ],
    'es' => [
        'assunto' => "Tantie Lorraine's: gracias por su opinión", 'titulo' => 'Gracias por su opinión', 'ola' => 'Hola',
        'corpo' => 'Hemos recibido su opinión sobre nuestras mermeladas de chile. Cada respuesta nos ayuda a hacer mermeladas aún mejores.',
        'notas' => 'Sus notas', 'global' => 'Nota global', 'preferida' => 'Su favorita', 'frasco' => 'Tarro de %s',
        'botao' => 'Descubrir la gama', 'despedida' => 'Un cordial saludo,', 'equipa' => "El equipo de Tantie Lorraine's en Portugal",
        'selos' => 'Distinciones de la marca en el Reino Unido',
        'rodape' => 'Ha recibido este correo porque envió una opinión de degustación en el sitio tantielorraine.pt. Si no ha sido usted, ignore este mensaje.',
    ],
];
// Limites por janela de 24 horas: opiniões com o mesmo email e opiniões no total
const LIMITE_EMAIL = 3;
const LIMITE_TOTAL = 300;

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
        header('Location: ' . LINGUAS[lingua()]['pagina'] . '#' . ($ok ? 'opiniao-enviada' : 'opiniao-erro'), true, 303);
    }
    exit;
}

// Valor de uma escolha: só aceita as chaves previstas
function escolha(string $nome, array $validas): string
{
    $v = campo($nome);
    return in_array($v, $validas, true) ? $v : '';
}

function escolhas(string $nome, array $validas): array
{
    $v = $_POST[$nome] ?? [];
    return is_array($v) ? array_values(array_intersect($validas, array_filter($v, 'is_string'))) : [];
}

function nota(string $nome): string
{
    return escolha($nome, ['1', '2', '3', '4', '5']);
}

// Valor para a linha da folha de cálculo: sem ";" nem mudanças de linha
function celula(string $t): string
{
    return trim(str_replace([';', "\n", "\t"], [',', ' ', ' '], $t));
}

// Nota de 1 a 5 em cinco círculos, cheios até à nota (como a escala da página)
function pontos(string $n, int $d): string
{
    $r = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="display:inline-table;vertical-align:middle;"><tr>';
    for ($k = 1; $k <= 5; $k++) {
        $cheio = $n !== '' && $k <= (int) $n;
        $r .= '<td style="width:' . $d . 'px;height:' . $d . 'px;padding:0;border:2px solid ' . ($n === '' ? '#adadad' : '#000000') . ';border-radius:' . $d . 'px;background:' . ($cheio ? '#000000' : '#ffffff') . ';font-size:0;line-height:0;">&nbsp;</td>'
            . ($k < 5 ? '<td style="width:4px;padding:0;font-size:0;line-height:0;">&nbsp;</td>' : '');
    }
    return $r . '</tr></table>';
}

function foto(string $k, int $d, string $alt): string
{
    return '<img src="' . SITE . '/assets/img/email/' . $k . '.jpg" width="' . $d . '" height="' . $d . '" alt="' . h($alt) . '" style="display:block;border:0;width:' . $d . 'px;height:' . $d . 'px;border-radius:12px;">';
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';
if ($metodo !== 'POST') {
    header('Location: /degustacao.html', true, 303);
    exit;
}

// Armadilhas para robôs (as mesmas do formulário de contactos)
$t = campo('t');
if (campo('website') !== '' || ($t !== '' && (int) $t < 3)) {
    responder(true);
}

$nome = linha(campo('nome'));
$email = linha(campo('email'));
$telefone = linha(campo('telefone'));
$local = linha(campo('local'));
$porque = texto(campo('porque'));
$gostou = texto(campo('gostou'));
$melhoraria = texto(campo('melhoraria'));
$provou = escolhas('provou', array_keys(COMPOTAS));

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
if (caracteres($local) < 1 || caracteres($local) > 80) {
    $invalidos[] = 'local';
}
if (!$provou) {
    $invalidos[] = 'provou';
}
foreach ($provou as $k) {
    if (nota($k . '_global') === '') {
        $invalidos[] = $k . '_global';
    }
}
foreach (['porque' => $porque, 'gostou' => $gostou, 'melhoraria' => $melhoraria] as $n => $livre) {
    if (caracteres($livre) > 2000) {
        $invalidos[] = $n;
    }
}
if ($invalidos) {
    responder(false, 'campos', $invalidos);
}

$c = config();
if ($c === null) {
    error_log('degustacao.php: config-email.php não encontrado (correr scripts/deploy.sh configurar-email)');
    responder(false, 'configuracao');
}
if (!dentro_do_limite($c, [['degustacao-email:' . strtolower($email), LIMITE_EMAIL], ['degustacao', LIMITE_TOTAL]])) {
    error_log('degustacao.php: limite de opiniões atingido');
    responder(false, 'limite');
}

// Respostas, já validadas, de cada compota provada
$varias = count($provou) > 1;
$preferida = $varias ? escolha('preferida', $provou) : $provou[0];
$respostas = [];
foreach ($provou as $k) {
    $def = COMPOTAS[$k];
    $notas = [];
    foreach (array_keys($def['criterios']) as $ck) {
        $notas[$ck] = nota($k . '_' . $ck);
    }
    $nivel = escolha($k . '_nivel', array_keys($def['niveis']));
    $usos = array_map(function (string $u) use ($def): string { return $def['usos'][$u]; }, escolhas($k . '_uso', array_keys($def['usos'])));
    $outro = linha(campo($k . '_uso_outro'));
    if ($outro !== '' && caracteres($outro) <= 100) {
        $usos[] = 'Outra: ' . $outro;
    }
    $respostas[$k] = ['notas' => $notas, 'nivel' => $nivel !== '' ? $def['niveis'][$nivel] : '', 'usos' => $usos];
}
$comparacao = [];
foreach (COMPARACAO as $qk => $pergunta) {
    $comparacao[$qk] = $varias ? escolha('comp_' . $qk, $provou) : '';
}
$agora = new DateTime('now', new DateTimeZone('Europe/Lisbon'));
$nomeCompota = function (string $k): string { return COMPOTAS[$k]['nome'] . ' (' . strtoupper($k) . ')'; };
$lista = implode(', ', array_map($nomeCompota, $provou));

// Linhas para a folha de cálculo: colunas fixas para todas as compotas (vazias nas que não foram provadas)
$cab = ['Data', 'Língua', 'Local', 'Nome', 'Email', 'Telefone', 'Provou', 'Preferida', 'Porquê'];
$val = [$agora->format('Y-m-d H:i'), lingua(), $local, $nome, $email, $telefone, implode(',', $provou), $preferida, $porque];
foreach (COMPOTAS as $k => $def) {
    $r = $respostas[$k] ?? null;
    foreach ($def['criterios'] as $ck => $rotulo) {
        $cab[] = strtoupper($k) . ' ' . $rotulo;
        $val[] = $r ? $r['notas'][$ck] : '';
    }
    array_push($cab, strtoupper($k) . ' Nível de picante', strtoupper($k) . ' Melhor utilização');
    array_push($val, $r ? $r['nivel'] : '', $r ? implode(', ', $r['usos']) : '');
}
foreach (COMPARACAO as $qk => $pergunta) {
    $cab[] = $pergunta;
    $val[] = strtoupper($comparacao[$qk]);
}
array_push($cab, 'O que mais gostou', 'O que melhoraria');
array_push($val, $gostou, $melhoraria);
$excel = implode(';', array_map('celula', $cab)) . "\n" . implode(';', array_map('celula', $val));

// Email para a equipa: texto
$quando = $agora->format('d/m/Y H:i');
$texto = 'Nova opinião de degustação recebida no site ' . DOMINIO . ' em ' . $quando . " (hora de Lisboa)\n\n"
    . 'Nome: ' . $nome . "\nEmail: " . $email . "\nTelefone: " . ($telefone !== '' ? $telefone : 'não indicado') . "\n"
    . 'Local da degustação: ' . $local . "\nLíngua do formulário: " . LINGUAS[lingua()]['nome'] . "\n\n"
    . 'Compotas provadas: ' . $lista . "\n"
    . 'Preferida: ' . ($preferida !== '' ? $nomeCompota($preferida) : 'não indicada') . "\n"
    . 'Porquê: ' . ($porque !== '' ? $porque : '-') . "\n";
foreach ($respostas as $k => $r) {
    $texto .= "\n" . $nomeCompota($k) . "\n";
    foreach (COMPOTAS[$k]['criterios'] as $ck => $rotulo) {
        $texto .= '  ' . $rotulo . ': ' . ($r['notas'][$ck] !== '' ? $r['notas'][$ck] . '/5' : '-') . "\n";
    }
    $texto .= '  Nível de picante: ' . ($r['nivel'] !== '' ? $r['nivel'] : '-') . "\n"
        . '  Melhor utilização: ' . ($r['usos'] ? implode(', ', $r['usos']) : '-') . "\n";
}
if ($varias) {
    $texto .= "\nComparação final\n";
    foreach (COMPARACAO as $qk => $pergunta) {
        $texto .= '  ' . $pergunta . ' ' . ($comparacao[$qk] !== '' ? $nomeCompota($comparacao[$qk]) : '-') . "\n";
    }
}
$texto .= "\nO que mais gostou na degustação?\n" . ($gostou !== '' ? $gostou : '-') . "\n"
    . "\nO que melhoraria em alguma das compotas?\n" . ($melhoraria !== '' ? $melhoraria : '-') . "\n"
    . "\n--\nPara colar no Excel (colunas separadas por \";\"):\n" . $excel . "\n";

// Email para a equipa: HTML
$rotulo = function (string $t): string {
    return '<p style="margin:0 0 2px;' . ESTILO_TITULO . 'font-size:13px;line-height:18px;">' . h($t) . '</p>';
};
$livre = function (string $t): string {
    return $t !== '' ? nl2br(h($t)) : '<span style="color:#5e5362;">-</span>';
};
$dados = '';
foreach ([['Nome', h($nome)], ['Email', '<a href="mailto:' . h($email) . '" style="color:#000000;">' . h($email) . '</a>'],
          ['Telefone', $telefone !== '' ? '<a href="tel:' . h(preg_replace('/[^0-9+]/', '', $telefone)) . '" style="color:#000000;">' . h($telefone) . '</a>' : '<span style="color:#5e5362;">não indicado</span>'],
          ['Local da degustação', h($local)]] as [$r, $v]) {
    $dados .= '<tr><td style="padding:6px 0;width:42%;vertical-align:top;' . ESTILO_PEQUENO . 'color:#5e5362;">' . $r . '</td><td style="padding:6px 0;vertical-align:top;' . ESTILO_CORPO . '">' . $v . '</td></tr>';
}
$html = '<p style="margin:0 0 4px;' . ESTILO_PEQUENO . 'color:#5e5362;">' . h($quando) . ' · formulário em ' . h(LINGUAS[lingua()]['nome']) . '</p>'
    . '<h1 style="margin:0 0 20px;' . ESTILO_TITULO . 'font-size:32px;line-height:38px;">Nova opinião de degustação</h1>'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;margin:0 0 24px;"><tr><td style="padding:16px 24px;">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $dados . '</table></td></tr></table>'
    . $rotulo('Compotas provadas') . '<p style="margin:0 0 12px;">' . h($lista) . '</p>'
    . ($varias ? $rotulo('Preferida') . '<p style="margin:0 0 12px;">' . ($preferida !== '' ? '<strong>' . h($nomeCompota($preferida)) . '</strong>' : '-') . '</p>'
        . $rotulo('Porquê') . '<p style="margin:0 0 12px;">' . $livre($porque) . '</p>' : '');
foreach ($respostas as $k => $r) {
    $linhas = '';
    foreach (COMPOTAS[$k]['criterios'] as $ck => $rot) {
        $n = $r['notas'][$ck];
        $linhas .= '<tr><td style="padding:5px 0;' . ESTILO_PEQUENO . '">' . h($rot) . '</td>'
            . '<td align="right" style="padding:5px 0;white-space:nowrap;">' . pontos($n, 12) . '<span style="display:inline-block;width:36px;text-align:right;' . ESTILO_PEQUENO . 'font-weight:700;">' . ($n !== '' ? $n . '/5' : '-') . '</span></td></tr>';
    }
    $linhas .= '<tr><td style="padding:5px 0;' . ESTILO_PEQUENO . '">Nível de picante</td><td align="right" style="padding:5px 0;' . ESTILO_PEQUENO . 'font-weight:700;">' . ($r['nivel'] !== '' ? h($r['nivel']) : '-') . '</td></tr>'
        . '<tr><td style="padding:5px 0;vertical-align:top;' . ESTILO_PEQUENO . '">Melhor utilização</td><td align="right" style="padding:5px 0;' . ESTILO_PEQUENO . 'font-weight:700;">' . ($r['usos'] ? h(implode(', ', $r['usos'])) : '-') . '</td></tr>';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;border-top:2px solid #000000;"><tr>'
        . '<td width="80" style="width:80px;padding:16px 16px 8px 0;vertical-align:top;">' . foto($k, 64, 'Frasco de ' . COMPOTAS[$k]['nome']) . '</td>'
        . '<td style="padding:16px 0 8px;vertical-align:top;"><p style="margin:0 0 6px;' . ESTILO_TITULO . 'font-size:20px;line-height:26px;">' . h($nomeCompota($k)) . '</p>'
        . '<p style="margin:0;">' . pontos($r['notas']['global'], 16) . ' <span style="' . ESTILO_TITULO . 'font-size:20px;line-height:26px;vertical-align:middle;">&nbsp;' . h($r['notas']['global']) . '/5</span></p></td></tr>'
        . '<tr><td colspan="2"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $linhas . '</table></td></tr></table>';
}
if ($varias) {
    $linhas = '';
    foreach (COMPARACAO as $qk => $pergunta) {
        $linhas .= '<tr><td style="padding:5px 0;' . ESTILO_PEQUENO . '">' . h($pergunta) . '</td><td align="right" style="padding:5px 0 5px 12px;white-space:nowrap;' . ESTILO_PEQUENO . 'font-weight:700;">' . ($comparacao[$qk] !== '' ? strtoupper($comparacao[$qk]) : '-') . '</td></tr>';
    }
    $html .= '<p style="margin:24px 0 4px;padding-top:16px;border-top:2px solid #000000;' . ESTILO_TITULO . 'font-size:20px;line-height:26px;">Comparação final</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $linhas . '</table>';
}
$html .= '<p style="margin:24px 0 4px;padding-top:16px;border-top:2px solid #000000;' . ESTILO_TITULO . 'font-size:20px;line-height:26px;">Comentários</p>'
    . $rotulo('O que mais gostou na degustação') . '<p style="margin:0 0 12px;">' . $livre($gostou) . '</p>'
    . $rotulo('O que melhoraria em alguma das compotas') . '<p style="margin:0 0 12px;">' . $livre($melhoraria) . '</p>'
    . '<p style="margin:24px 0 4px;padding-top:16px;border-top:2px solid #000000;' . ESTILO_TITULO . 'font-size:20px;line-height:26px;">Para colar no Excel</p>'
    . '<p style="margin:0 0 8px;' . ESTILO_PEQUENO . '">Duas linhas, com as colunas separadas por ponto e vírgula: os títulos e as respostas. No Excel, colar na coluna A e usar Dados &gt; Texto para Colunas, com o separador ponto e vírgula. As colunas são sempre as mesmas, por isso as opiniões podem ser juntadas numa só folha.</p>'
    . '<pre style="margin:0;padding:12px;background:#f0f0f0;font-family:Menlo,Consolas,\'Courier New\',monospace;font-size:12px;line-height:18px;white-space:pre-wrap;word-break:break-all;color:#000000;">' . h($excel) . '</pre>';
$htmlEquipa = email_html('pt-PT', 0, 'Nova opinião de degustação', $html, '',
    'Enviado pelo formulário de opinião de degustação do site ' . DOMINIO . '. Para responder a quem deu a opinião, use Responder.', true);

// Agradecimento para quem respondeu, na língua da página
$a = AGRADECIMENTO[lingua()];
$saudacao = preg_match("/^\\p{L}[\\p{L}\\p{M} .'’-]{1,59}$/u", $nome) ? $a['ola'] . ', ' . $nome . ',' : $a['ola'] . ',';
$produtos = SITE . LINGUAS[lingua()]['produtos'];
$agTexto = $saudacao . "\n\n" . $a['corpo'] . "\n\n" . $a['notas'] . ":\n";
$agNotas = '';
foreach ($respostas as $k => $r) {
    $agTexto .= '- ' . COMPOTAS[$k]['nome'] . ': ' . $r['notas']['global'] . "/5\n";
    $agNotas .= '<tr><td width="88" style="width:88px;padding:12px 16px 12px 0;vertical-align:middle;">' . foto($k, 72, sprintf($a['frasco'], COMPOTAS[$k]['nome'])) . '</td>'
        . '<td style="padding:12px 0;vertical-align:middle;"><p style="margin:0 0 4px;' . ESTILO_TITULO . 'font-size:18px;line-height:24px;">' . h(COMPOTAS[$k]['nome']) . '</p>'
        . '<p style="margin:0;' . ESTILO_PEQUENO . '">' . h($a['global']) . '</p>'
        . '<p style="margin:4px 0 0;">' . pontos($r['notas']['global'], 16) . ' <span style="' . ESTILO_TITULO . 'font-size:18px;line-height:24px;vertical-align:middle;">&nbsp;' . h($r['notas']['global']) . '/5</span></p></td></tr>';
}
$fav = ($varias && $preferida !== '') ? COMPOTAS[$preferida]['nome'] : '';
if ($fav !== '') {
    $agTexto .= "\n" . $a['preferida'] . ': ' . $fav . "\n";
}
$agTexto .= "\n" . $a['botao'] . ': ' . $produtos . "\n\n" . $a['despedida'] . "\n" . $a['equipa'] . "\n"
    . REMETENTE . ' | ' . SITE . "\n\n" . $a['rodape'] . "\nGADGETMÉTODO, LDA · Rua das Cruzes, n.º 625, 4100-195 Porto\n";
$agConteudo = '<h1 style="margin:0 0 20px;' . ESTILO_TITULO . 'font-size:32px;line-height:38px;">' . h($a['titulo']) . '</h1>'
    . '<p style="margin:0 0 12px;">' . h($saudacao) . '</p>'
    . '<p style="margin:0 0 24px;">' . h($a['corpo']) . '</p>'
    . '<p style="margin:0;padding-top:16px;border-top:2px solid #000000;' . ESTILO_TITULO . 'font-size:20px;line-height:26px;">' . h($a['notas']) . '</p>'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">' . $agNotas . '</table>'
    . ($fav !== '' ? '<p style="margin:0 0 24px;padding:12px 16px;background:#f0f0f0;">' . h($a['preferida']) . ': <strong>' . h($fav) . '</strong></p>' : '')
    . '<p style="margin:8px 0 28px;"><a href="' . $produtos . '" target="_blank" style="display:inline-block;padding:14px 32px;border:2px solid #000000;border-radius:999px;' . ESTILO_TITULO . 'font-size:16px;line-height:20px;text-decoration:none;">' . h($a['botao']) . '</a></p>'
    . '<p style="margin:0;">' . h($a['despedida']) . '<br>' . h($a['equipa']) . '</p>';
$agHtml = email_html(LINGUAS[lingua()]['codigo'], LINGUAS[lingua()]['i'], $a['titulo'], $agConteudo, $a['selos'], $a['rodape'], true);

try {
    enviar($c, [
        [DESTINATARIOS, mensagem(cabecalho(NOME_REMETENTE) . ' <' . REMETENTE . '>', DESTINATARIOS, 'Opinião de degustação: ' . $nome . ', ' . $local,
            ['Reply-To: ' . cabecalho($nome) . ' <' . $email . '>'], $texto, $htmlEquipa)],
        [[$email], mensagem(cabecalho(NOME_MARCA) . ' <' . REMETENTE . '>', [$email], $a['assunto'],
            ['Auto-Submitted: auto-replied', 'X-Auto-Response-Suppress: All'], $agTexto, $agHtml)],
    ]);
} catch (FalhaEnvio $e) {
    responder(false, $e->getMessage());
}
responder(true);
