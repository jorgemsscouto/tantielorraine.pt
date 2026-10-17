<?php
// tantielorraine.pt: recebe a opinião de degustação (/degustacao.html, /en/tasting.html, /es/degustacion.html) e
// envia-a por email para info@tantielorraine.pt, com o código comum de email-comum.php. As perguntas seguem a
// ficha em papel "Tantie Lorraine: Degustação de Chilli Jams"; as chaves têm de coincidir com as da página.
// Só conta a classificação das compotas que a pessoa disse ter provado; a nota global de cada uma é obrigatória.
// O email vem sempre em português, com uma linha final pronta a colar numa folha de cálculo (separada por ";").
//
// Resposta em JSON quando o pedido o aceita (envio por JavaScript); sem JavaScript, redireciona para a página da
// língua com #opiniao-enviada ou #opiniao-erro.

declare(strict_types=1);

require __DIR__ . '/email-comum.php';

const COMPOTAS = [
    'sbj' => [
        'nome' => 'Scotch Bonnet Jam (SBJ)',
        'criterios' => ['aroma' => 'Aroma', 'sabor' => 'Sabor do Scotch Bonnet', 'docura' => 'Doçura equilibrada', 'picante' => 'Intensidade do picante', 'persistencia' => 'Persistência agradável do calor', 'textura' => 'Textura', 'autenticidade' => 'Autenticidade caribenha', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'muito-picante' => 'Muito picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['queijo' => 'Queijo', 'carne' => 'Carne', 'sanduiche' => 'Sanduíche', 'pao' => 'Pão/torrada', 'caribenha' => 'Cozinha caribenha'],
    ],
    'acj' => [
        'nome' => 'Apple Chilli Jam (ACJ)',
        'criterios' => ['aroma' => 'Aroma', 'sabor' => 'Sabor frutado da maçã', 'docura' => 'Doçura equilibrada', 'picante' => 'Intensidade do picante', 'frescura' => 'Frescura e leveza', 'textura' => 'Textura', 'versatilidade' => 'Versatilidade gastronómica', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['muito-suave' => 'Muito suave', 'suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['queijo' => 'Queijo', 'bacon' => 'Bacon/sanduíche', 'porco' => 'Presunto/porco', 'pao' => 'Pão/torrada', 'glaze' => 'Glaze para carne'],
    ],
    'rpcl' => [
        'nome' => 'Red Pepper Chilli Lime Jam (RPCL)',
        'criterios' => ['aroma' => 'Aroma', 'sabor' => 'Sabor do pimento vermelho', 'lima' => 'Presença e equilíbrio da lima', 'docura' => 'Doçura equilibrada', 'acidez' => 'Acidez/frescura', 'picante' => 'Intensidade do picante', 'textura' => 'Textura', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['muito-suave' => 'Muito suave', 'suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['peixe' => 'Peixe/marisco', 'frango' => 'Frango', 'queijo-fresco' => 'Queijo fresco', 'saladas' => 'Saladas', 'tacos' => 'Tacos/wraps'],
    ],
    'tcj' => [
        'nome' => 'Tomato Chilli Jam (TCJ)',
        'criterios' => ['aroma' => 'Aroma', 'sabor' => 'Sabor do tomate', 'alho' => 'Presença de alho e gengibre', 'equilibrio' => 'Equilíbrio doce-salgado', 'picante' => 'Intensidade do picante', 'textura' => 'Textura', 'versatilidade' => 'Versatilidade gastronómica', 'global' => 'Nota global', 'compraria' => 'Compraria'],
        'niveis' => ['muito-suave' => 'Muito suave', 'suave' => 'Suave', 'equilibrado' => 'Equilibrado', 'picante' => 'Picante', 'demasiado' => 'Demasiado para mim'],
        'usos' => ['burger' => 'Burger', 'queijo' => 'Queijo', 'carne-grelhada' => 'Carne grelhada', 'sanduiche' => 'Sanduíche', 'ovos' => 'Ovos'],
    ],
];
const COMPARACAO = ['primeiro' => 'Qual compraria primeiro?', 'pouco-picante' => 'Qual recomendaria a alguém que não gosta muito de picante?', 'muito-picante' => 'Qual recomendaria a quem gosta de muito picante?', 'oferta' => 'Qual parece mais adequada para oferta/presente?'];
const PAGINAS = ['pt' => '/degustacao.html', 'en' => '/en/tasting.html', 'es' => '/es/degustacion.html'];
const NOMES_LINGUA = ['pt' => 'português', 'en' => 'inglês', 'es' => 'espanhol'];
// Máximo de opiniões por 24 horas, para o formulário não servir para inundar a caixa de correio
const LIMITE_OPINIOES = 300;

function lingua(): string
{
    $l = $_POST['idioma'] ?? 'pt';
    return (is_string($l) && isset(PAGINAS[$l])) ? $l : 'pt';
}

function responder(bool $ok, string $erro = '', array $campos = []): void
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($ok ? 200 : ['campos' => 422, 'limite' => 429][$erro] ?? 500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'erro' => $erro, 'campos' => $campos]);
    } else {
        header('Location: ' . PAGINAS[lingua()] . '#' . ($ok ? 'opiniao-enviada' : 'opiniao-erro'), true, 303);
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

$provou = escolhas('provou', array_keys(COMPOTAS));
if (!$provou) {
    responder(false, 'campos', ['provou']);
}
$invalidos = [];
foreach ($provou as $k) {
    if (nota($k . '_global') === '') {
        $invalidos[] = $k . '_global';
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
if (!dentro_do_limite($c, [['degustacao', LIMITE_OPINIOES]])) {
    error_log('degustacao.php: limite de opiniões atingido');
    responder(false, 'limite');
}

$varias = count($provou) > 1;
$preferida = $varias ? escolha('preferida', $provou) : $provou[0];
$porque = texto(campo('porque'));
$gostou = texto(campo('gostou'));
$melhoraria = texto(campo('melhoraria'));
$evento = linha(campo('evento'));
$evento = preg_match('/^[\p{L}\p{N} ._\/-]{1,80}$/u', $evento) ? $evento : '';
foreach ([$porque, $gostou, $melhoraria] as $livre) {
    if (caracteres($livre) > 2000) {
        responder(false, 'campos', ['texto']);
    }
}

$agora = new DateTime('now', new DateTimeZone('Europe/Lisbon'));
$nomes = function (array $ks): string {
    return implode(', ', array_map(function (string $k): string { return COMPOTAS[$k]['nome']; }, $ks));
};
$corpo = 'Opinião de degustação recebida no site ' . DOMINIO . ' em ' . $agora->format('d/m/Y H:i') . " (hora de Lisboa)\n"
    . 'Língua do formulário: ' . NOMES_LINGUA[lingua()] . "\n"
    . 'Degustação: ' . ($evento !== '' ? $evento : 'não indicada') . "\n\n"
    . 'Compotas provadas: ' . $nomes($provou) . "\n"
    . 'Preferida: ' . ($preferida !== '' ? COMPOTAS[$preferida]['nome'] : 'não indicada') . "\n"
    . 'Porquê: ' . ($porque !== '' ? $porque : '-') . "\n";

// Folha de cálculo: colunas fixas para todas as compotas (vazias nas que não foram provadas)
$cab = ['Data', 'Língua', 'Degustação', 'Provou', 'Preferida', 'Porquê'];
$val = [$agora->format('Y-m-d H:i'), lingua(), $evento, implode(',', $provou), $preferida, $porque];

foreach (COMPOTAS as $k => $def) {
    $provada = in_array($k, $provou, true);
    if ($provada) {
        $corpo .= "\n" . $def['nome'] . "\n";
    }
    foreach ($def['criterios'] as $ck => $rotulo) {
        $n = $provada ? nota($k . '_' . $ck) : '';
        if ($provada) {
            $corpo .= '  ' . $rotulo . ': ' . ($n !== '' ? $n : '-') . "\n";
        }
        $cab[] = strtoupper($k) . ' ' . $rotulo;
        $val[] = $n;
    }
    $nivel = $provada ? escolha($k . '_nivel', array_keys($def['niveis'])) : '';
    $usos = $provada ? escolhas($k . '_uso', array_keys($def['usos'])) : [];
    $outro = $provada ? linha(campo($k . '_uso_outro')) : '';
    $outro = caracteres($outro) > 100 ? '' : $outro;
    $usosTexto = array_map(function (string $u) use ($def): string { return $def['usos'][$u]; }, $usos);
    if ($outro !== '') {
        $usosTexto[] = 'Outra: ' . $outro;
    }
    if ($provada) {
        $corpo .= '  Nível de picante: ' . ($nivel !== '' ? $def['niveis'][$nivel] : '-') . "\n"
            . '  Melhor utilização: ' . ($usosTexto ? implode(', ', $usosTexto) : '-') . "\n";
    }
    array_push($cab, strtoupper($k) . ' Nível de picante', strtoupper($k) . ' Melhor utilização');
    array_push($val, $nivel !== '' ? $def['niveis'][$nivel] : '', implode(', ', $usosTexto));
}

if ($varias) {
    $corpo .= "\nComparação final\n";
}
foreach (COMPARACAO as $qk => $pergunta) {
    $r = $varias ? escolha('comp_' . $qk, $provou) : '';
    if ($varias) {
        $corpo .= '  ' . $pergunta . ' ' . ($r !== '' ? COMPOTAS[$r]['nome'] : '-') . "\n";
    }
    $cab[] = $pergunta;
    $val[] = $r !== '' ? strtoupper($r) : '';
}
$corpo .= "\nO que mais gostou na degustação?\n" . ($gostou !== '' ? $gostou : '-') . "\n"
    . "\nO que melhoraria em alguma das compotas?\n" . ($melhoraria !== '' ? $melhoraria : '-') . "\n";
array_push($cab, 'O que mais gostou', 'O que melhoraria');
array_push($val, $gostou, $melhoraria);

$corpo .= "\n--\nPara uma folha de cálculo (colunas separadas por \";\"):\n"
    . implode(';', array_map('celula', $cab)) . "\n" . implode(';', array_map('celula', $val)) . "\n";

try {
    enviar($c, [[DESTINATARIOS, mensagem(cabecalho(NOME_REMETENTE) . ' <' . REMETENTE . '>', DESTINATARIOS,
        'Opinião de degustação: ' . $nomes($provou), [], $corpo)]]);
} catch (FalhaEnvio $e) {
    responder(false, $e->getMessage());
}
responder(true);
