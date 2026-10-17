<?php
// tantielorraine.pt: recebe o pedido de informação de /contactos.html e envia-o por email através do servidor
// de envio (SMTP) da Amen, autenticado com a conta do site. As credenciais ficam em config-email.php, fora da
// pasta pública (ou ao lado deste ficheiro, se o alojamento não deixar ler fora dela); esse ficheiro é criado
// no servidor por scripts/deploy.sh configurar-email e nunca é versionado.
//
// Resposta em JSON quando o pedido o aceita (envio por JavaScript); sem JavaScript, redireciona para
// /contactos.html#pedido-enviado ou #pedido-erro. GET ?verificar indica só se a configuração foi encontrada.

declare(strict_types=1);

const REMETENTE = 'info@tantielorraine.pt';
const NOME_REMETENTE = "Site Tantie Lorraine's";
const DESTINATARIOS = ['info@tantielorraine.pt', 'gadgetmetodo@gmail.com'];
const DOMINIO = 'tantielorraine.pt';

final class FalhaEnvio extends Exception {}

function config(): ?array
{
    foreach ([dirname(__DIR__) . '/config-email.php', __DIR__ . '/config-email.php'] as $ficheiro) {
        if (@is_file($ficheiro)) {
            $c = include $ficheiro;
            if (is_array($c) && !empty($c['utilizador']) && !empty($c['palavra_passe'])) {
                return $c;
            }
        }
    }
    return null;
}

function responder(bool $ok, string $erro = '', array $campos = []): void
{
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code($ok ? 200 : ($erro === 'campos' ? 422 : 500));
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'erro' => $erro, 'campos' => $campos]);
    } else {
        header('Location: /contactos.html#' . ($ok ? 'pedido-enviado' : 'pedido-erro'), true, 303);
    }
    exit;
}

// Texto recebido: UTF-8 válido, sem espaços nas pontas; numa linha (cabeçalhos) ou com mudanças de linha (mensagem)
function campo(string $nome): string
{
    $v = $_POST[$nome] ?? '';
    return (is_string($v) && preg_match('//u', $v)) ? trim($v) : '';
}
function linha(string $t): string { return trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $t)); }
function texto(string $t): string { return preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', str_replace(["\r\n", "\r"], "\n", $t)); }
function caracteres(string $t): int { return (int) preg_match_all('/./su', $t); }

// Cabeçalho com acentos (RFC 2047), em blocos de até 45 bytes sem partir caracteres
function cabecalho(string $t): string
{
    $partes = [];
    $atual = '';
    foreach (preg_split('//u', $t, -1, PREG_SPLIT_NO_EMPTY) as $c) {
        if (strlen($atual . $c) > 45) {
            $partes[] = $atual;
            $atual = '';
        }
        $atual .= $c;
    }
    $partes[] = $atual;
    return implode("\r\n ", array_map(function (string $p): string {
        return '=?UTF-8?B?' . base64_encode($p) . '?=';
    }, $partes));
}

// Cliente SMTP mínimo: STARTTLS nas portas 25, 587 e 2525, TLS direto nas restantes (465); autenticação PLAIN ou LOGIN
function smtp_resposta($s): array
{
    $texto = '';
    while (($l = fgets($s, 1024)) !== false) {
        $texto .= $l;
        if (strlen($l) < 4 || $l[3] !== '-') {
            break;
        }
    }
    return [(int) substr($texto, 0, 3), $texto];
}

function smtp($s, ?string $comando, array $esperado, string $fase): string
{
    if ($comando !== null) {
        fwrite($s, $comando . "\r\n");
    }
    [$codigo, $texto] = smtp_resposta($s);
    if (!in_array($codigo, $esperado, true)) {
        error_log('contacto.php: falha na fase ' . $fase . ': ' . trim($texto ?: 'sem resposta'));
        throw new FalhaEnvio($fase);
    }
    return $texto;
}

function enviar(array $c, string $mensagem): void
{
    $host = (string) ($c['host'] ?? 'smtp-pt.securemail.pro');
    $porta = (int) ($c['porta'] ?? 465);
    $tls_direto = !in_array($porta, [25, 587, 2525], true);
    $tls = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);
    $contexto = stream_context_create(['ssl' => ['peer_name' => $host, 'crypto_method' => $tls]]);
    $s = @stream_socket_client(($tls_direto ? 'tls://' : 'tcp://') . $host . ':' . $porta, $n, $erro, 15, STREAM_CLIENT_CONNECT, $contexto);
    if (!$s) {
        error_log('contacto.php: sem ligação a ' . $host . ':' . $porta . ': ' . $erro);
        throw new FalhaEnvio('ligacao');
    }
    stream_set_timeout($s, 20);
    smtp($s, null, [220], 'ligacao');
    $ehlo = smtp($s, 'EHLO ' . DOMINIO, [250], 'ligacao');
    if (!$tls_direto) {
        smtp($s, 'STARTTLS', [220], 'ligacao');
        if (!@stream_socket_enable_crypto($s, true, $tls)) {
            error_log('contacto.php: falha no STARTTLS com ' . $host);
            throw new FalhaEnvio('ligacao');
        }
        $ehlo = smtp($s, 'EHLO ' . DOMINIO, [250], 'ligacao');
    }
    if (preg_match('/^250[ -]AUTH[ =].*\bPLAIN\b/mi', $ehlo)) {
        smtp($s, 'AUTH PLAIN ' . base64_encode("\0" . $c['utilizador'] . "\0" . $c['palavra_passe']), [235], 'autenticacao');
    } else {
        smtp($s, 'AUTH LOGIN', [334], 'autenticacao');
        smtp($s, base64_encode((string) $c['utilizador']), [334], 'autenticacao');
        smtp($s, base64_encode((string) $c['palavra_passe']), [235], 'autenticacao');
    }
    smtp($s, 'MAIL FROM:<' . REMETENTE . '>', [250], 'envio');
    foreach (DESTINATARIOS as $d) {
        smtp($s, 'RCPT TO:<' . $d . '>', [250, 251], 'envio');
    }
    smtp($s, 'DATA', [354], 'envio');
    smtp($s, preg_replace('/^\./m', '..', $mensagem) . "\r\n.", [250], 'envio');
    fwrite($s, "QUIT\r\n");
    fclose($s);
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

$agora = new DateTime('now', new DateTimeZone('Europe/Lisbon'));
$corpo = "Pedido de informação recebido no site " . DOMINIO . "\n\n"
    . "Nome: $nome\n"
    . "Email: $email\n"
    . 'Telefone: ' . ($telefone !== '' ? $telefone : 'não indicado') . "\n\n"
    . "Mensagem:\n$mensagem\n\n"
    . "--\nEnviado em " . $agora->format('d/m/Y H:i') . " (hora de Lisboa). "
    . "Para responder, use Responder: a resposta segue para o email indicado no pedido.\n";

$cabecalhos = [
    'Date: ' . $agora->format(DATE_RFC2822),
    'From: ' . cabecalho(NOME_REMETENTE) . ' <' . REMETENTE . '>',
    'To: ' . implode(', ', DESTINATARIOS),
    'Reply-To: ' . cabecalho($nome) . ' <' . $email . '>',
    'Subject: ' . cabecalho('Pedido de informação: ' . $nome),
    'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . DOMINIO . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: base64',
];

try {
    enviar($c, implode("\r\n", $cabecalhos) . "\r\n\r\n" . rtrim(chunk_split(base64_encode(str_replace("\n", "\r\n", $corpo)))));
} catch (FalhaEnvio $e) {
    responder(false, $e->getMessage());
}
responder(true);
