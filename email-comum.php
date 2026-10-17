<?php
// tantielorraine.pt: código comum aos formulários do site (contacto.php e degustacao.php). Lê a configuração de
// envio (config-email.php, criada no servidor por scripts/deploy.sh configurar-email e nunca versionada), valida
// texto recebido e envia emails pelo servidor de envio (SMTP) da Amen. Não faz nada se for aberto diretamente.

declare(strict_types=1);

const REMETENTE = 'info@tantielorraine.pt';
const NOME_REMETENTE = "Site Tantie Lorraine's";
const NOME_MARCA = "Tantie Lorraine's";
// Caixas que recebem os pedidos e as opiniões dos formulários
const DESTINATARIOS = ['info@tantielorraine.pt'];
const DOMINIO = 'tantielorraine.pt';
const SITE = 'https://tantielorraine.pt';
const GUILD = 'https://gff.co.uk/directory/producer/?id=25813';

// Selos do rodapé dos emails, pela ordem do site, com o texto alternativo em português, inglês e espanhol; só entram
// os que existem no servidor. Os Great Taste ligam ao diretório da Guild of Fine Food.
const SELOS = [
    ['food-hygiene-rating-5.png', ['Food Hygiene Rating 5, Very Good', 'Food Hygiene Rating 5, Very Good', 'Food Hygiene Rating 5, Very Good'], false],
    ['great-taste-producer.png', ['Great Taste Producer', 'Great Taste Producer', 'Great Taste Producer'], true],
    ['great-taste-2019-1-estrela.png', ['Great Taste 2019, uma estrela', 'Great Taste 2019, one star', 'Great Taste 2019, una estrella'], true],
    ['great-taste-2021-1-estrela.png', ['Great Taste 2021, uma estrela', 'Great Taste 2021, one star', 'Great Taste 2021, una estrella'], true],
    ['great-taste-2024-2-estrelas.png', ['Great Taste 2024, duas estrelas', 'Great Taste 2024, two stars', 'Great Taste 2024, dos estrellas'], true],
    ['great-taste-2026.png', ['Great Taste 2026, uma estrela', 'Great Taste 2026, one star', 'Great Taste 2026, una estrella'], true],
];

// Estilos dos emails: cores dos tokens do design system em hexadecimal, porque os programas de email não suportam
// variáveis CSS; tipos de letra com as alternativas das pilhas do sistema (a Anton vem do site nos programas que
// aceitam tipos de letra externos).
const ESTILO_TITULO = "font-family:Anton,'Arial Narrow',Impact,sans-serif;font-weight:400;text-transform:uppercase;color:#000000;";
const ESTILO_CORPO = "font-family:'Avenir Next',Avenir,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:24px;color:#000000;";
const ESTILO_PEQUENO = "font-family:'Helvetica Neue',Arial,sans-serif;font-size:14px;line-height:20px;color:#000000;";

final class FalhaEnvio extends Exception {}

function h(string $t): string
{
    return htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
}

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
        error_log('formulário: falha na fase ' . $fase . ': ' . trim($texto ?: 'sem resposta'));
        throw new FalhaEnvio($fase);
    }
    return $texto;
}

// Envia as mensagens [destinatários, dados] por ordem na mesma ligação. Só a primeira (o aviso para a equipa) é
// obrigatória: se uma das seguintes falhar, fica no registo de erros e o pedido conta como enviado.
function enviar(array $c, array $mensagens): void
{
    $host = (string) ($c['host'] ?? 'smtp-pt.securemail.pro');
    $porta = (int) ($c['porta'] ?? 465);
    $tls_direto = !in_array($porta, [25, 587, 2525], true);
    $tls = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);
    $contexto = stream_context_create(['ssl' => ['peer_name' => $host, 'crypto_method' => $tls]]);
    $s = @stream_socket_client(($tls_direto ? 'tls://' : 'tcp://') . $host . ':' . $porta, $n, $erro, 15, STREAM_CLIENT_CONNECT, $contexto);
    if (!$s) {
        error_log('formulário: sem ligação a ' . $host . ':' . $porta . ': ' . $erro);
        throw new FalhaEnvio('ligacao');
    }
    stream_set_timeout($s, 20);
    smtp($s, null, [220], 'ligacao');
    $ehlo = smtp($s, 'EHLO ' . DOMINIO, [250], 'ligacao');
    if (!$tls_direto) {
        smtp($s, 'STARTTLS', [220], 'ligacao');
        if (!@stream_socket_enable_crypto($s, true, $tls)) {
            error_log('formulário: falha no STARTTLS com ' . $host);
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
    foreach ($mensagens as $i => [$para, $dados]) {
        try {
            smtp($s, 'MAIL FROM:<' . REMETENTE . '>', [250], 'envio');
            foreach ($para as $d) {
                smtp($s, 'RCPT TO:<' . $d . '>', [250, 251], 'envio');
            }
            smtp($s, 'DATA', [354], 'envio');
            smtp($s, preg_replace('/^\./m', '..', $dados) . "\r\n.", [250], 'envio');
        } catch (FalhaEnvio $e) {
            if ($i === 0) {
                throw $e;
            }
            break;
        }
    }
    fwrite($s, "QUIT\r\n");
    fclose($s);
}

// Mensagem completa: cabeçalhos comuns e corpo em texto, ou em texto e HTML (multipart/alternative), em base64
function mensagem(string $de, array $para, string $assunto, array $extra, string $texto, ?string $html = null): string
{
    $agora = new DateTime('now', new DateTimeZone('Europe/Lisbon'));
    $base64 = function (string $t): string {
        return rtrim(chunk_split(base64_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], $t))));
    };
    $cabecalhos = array_merge([
        'Date: ' . $agora->format(DATE_RFC2822),
        'From: ' . $de,
        'To: ' . implode(', ', $para),
        'Subject: ' . cabecalho($assunto),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . DOMINIO . '>',
        'MIME-Version: 1.0',
    ], $extra);
    if ($html === null) {
        $cabecalhos[] = 'Content-Type: text/plain; charset=UTF-8';
        $cabecalhos[] = 'Content-Transfer-Encoding: base64';
        return implode("\r\n", $cabecalhos) . "\r\n\r\n" . $base64($texto);
    }
    $fronteira = 'tl-' . bin2hex(random_bytes(8));
    $cabecalhos[] = 'Content-Type: multipart/alternative; boundary="' . $fronteira . '"';
    $parte = function (string $tipo, string $conteudo) use ($fronteira, $base64): string {
        return "--$fronteira\r\nContent-Type: $tipo; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $base64($conteudo) . "\r\n";
    };
    return implode("\r\n", $cabecalhos) . "\r\n\r\n" . $parte('text/plain', $texto) . $parte('text/html', $html) . "--$fronteira--";
}

// Limite de envios por janela de 24 horas, para os formulários não servirem para inundar caixas de correio: guarda
// só resumos (HMAC) dos identificadores, numa pasta temporária do servidor. Sem pasta disponível, não limita.
// $limites: pares [identificador, máximo], por exemplo [['email:a@b.pt', 3], ['total', 100]].
function dentro_do_limite(array $c, array $limites): bool
{
    $pasta = null;
    foreach ([dirname(__DIR__) . '/tmp', sys_get_temp_dir()] as $p) {
        if (@is_dir($p) && @is_writable($p)) {
            $pasta = $p . '/tantielorraine-contacto';
            break;
        }
    }
    if ($pasta === null || (!@is_dir($pasta) && !@mkdir($pasta, 0700))) {
        return true;
    }
    $agora = time();
    if (random_int(1, 50) === 1) {
        foreach (glob($pasta . '/*') ?: [] as $f) {
            if (@filemtime($f) < $agora - 86400) {
                @unlink($f);
            }
        }
    }
    $chave = (string) $c['palavra_passe'];
    foreach ($limites as [$id, $limite]) {
        $f = $pasta . '/' . hash_hmac('sha256', $id, $chave);
        $registos = array_filter(array_map('intval', @file($f) ?: []), function (int $t) use ($agora): bool {
            return $t > $agora - 86400;
        });
        if (count($registos) >= $limite) {
            return false;
        }
        $registos[] = $agora;
        @file_put_contents($f, implode("\n", $registos) . "\n", LOCK_EX);
    }
    return true;
}

// Selos em linha, para mudarem de linha em ecrãs estreitos; $i é a língua (0 português, 1 inglês, 2 espanhol)
function selos_html(int $i): string
{
    $selos = '';
    foreach (SELOS as [$ficheiro, $alts, $ligacao]) {
        $dimensoes = @getimagesize(__DIR__ . '/assets/img/selos/' . $ficheiro);
        if (!$dimensoes) {
            continue;
        }
        $largura = (int) round($dimensoes[0] * 64 / max(1, $dimensoes[1]));
        $img = '<img src="' . SITE . '/assets/img/selos/' . $ficheiro . '" width="' . $largura . '" height="64" alt="' . h($alts[$i]) . '" style="display:inline-block;vertical-align:middle;border:0;height:64px;width:' . $largura . 'px;margin:0 12px 12px 0;">';
        $selos .= $ligacao ? '<a href="' . GUILD . '" target="_blank">' . $img . '</a>' : $img;
    }
    return $selos;
}

// Documento HTML de um email: fundo névoa, caixa branca de 600px com o conteúdo e rodapé vermelho com os selos
// (se $tituloSelos não for vazio), os contactos e a nota final. O logótipo fica no rodapé ou, com $topo, numa faixa
// vermelha no cimo.
function email_html(string $codigo, int $i, string $titulo, string $conteudo, string $tituloSelos, string $nota, bool $topo = false): string
{
    $logo = '<a href="' . SITE . '" target="_blank"><img src="' . SITE . '/assets/img/logo.png" width="140" height="80" alt="' . h(NOME_MARCA) . '" style="display:block;border:0;width:140px;height:80px;' . ($topo ? 'margin:0 auto;' : '') . '"></a>';
    $selos = $tituloSelos === '' ? '' : selos_html($i);
    $blocoSelos = $selos === '' ? '' : '<p style="margin:' . ($topo ? '0' : '24px') . ' 0 12px;' . ESTILO_TITULO . 'font-size:16px;line-height:21px;">' . h($tituloSelos) . '</p>'
        . '<p style="margin:0;font-size:0;line-height:0;">' . $selos . '</p>';
    return '<!doctype html><html lang="' . $codigo . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="color-scheme" content="light"><title>' . h($titulo) . '</title>'
        . '<style>@font-face{font-family:Anton;src:url(' . SITE . '/assets/fonts/anton-latin-400-normal.woff2) format("woff2");}</style></head>'
        . '<body style="margin:0;padding:0;background:#f0f0f0;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;"><tr><td align="center" style="padding:32px 12px;">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;">'
        . ($topo ? '<tr><td align="center" style="background:#ff2524;padding:24px 32px;">' . $logo . '</td></tr>' : '')
        . '<tr><td style="padding:32px;' . ESTILO_CORPO . '">' . $conteudo . '</td></tr>'
        . '<tr><td style="background:#ff2524;padding:32px;' . ESTILO_PEQUENO . '">'
        . ($topo ? '' : $logo)
        . $blocoSelos
        . '<p style="margin:12px 0 0;' . ESTILO_PEQUENO . '"><a href="mailto:' . REMETENTE . '" style="color:#000000;">' . REMETENTE . '</a> · <a href="' . SITE . '" style="color:#000000;">' . DOMINIO . '</a></p>'
        . '<p style="margin:12px 0 0;' . ESTILO_PEQUENO . '">' . h($nota) . '<br>GADGETMÉTODO, LDA · Rua das Cruzes, n.º 625, 4100-195 Porto</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}
