<?php

/**
 * Geração de imagens de seção com IA (painel admin).
 *
 * POST JSON:
 *   { "acao": "icones", "tema": "Natal", "contexto": "Peru assado, Rabanada" }
 *     → { "icones": "roast turkey, ...", "cores": ["#c8102e", ...] }  (OpenRouter)
 *
 *   { "acao": "imagem", "tipo": "fundo"|"titulo", "tema": "...",
 *     "icones": "...", "cores": ["#c8102e", "#1b7f5b"], "seed": 123 }
 *     → { "imagem": "data:image/jpeg;base64,..." }       (Pollinations)
 *
 * A imagem NÃO é salva aqui: o painel mostra as opções e só a escolhida
 * passa pelo upload_imagem.php de sempre (evita arquivos órfãos).
 */

require_once __DIR__ . '/../../config/core.php';

use App\Model\AdminSecaoModel;
use App\Service\AiService;

header('Content-Type: application/json; charset=UTF-8');

function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) getRequestHeader('X-User-Id');
if (!$userId || !(new AdminSecaoModel())->isAdmin($userId)) {
    responder(403, ['mensagem' => 'Acesso negado.']);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$acao = $body['acao'] ?? '';
$tema = trim(mb_substr((string) ($body['tema'] ?? ''), 0, 120));

if ($tema === '') {
    responder(422, ['mensagem' => 'Informe o título da seção.']);
}

// ── Sugestão de ícones (texto) ───────────────────────────────────────────────
if ($acao === 'icones') {
    $contexto = trim(mb_substr((string) ($body['contexto'] ?? ''), 0, 600));
    try {
        responder(200, (new AiService())->sugerirIcones($tema, $contexto ?: $tema));
    } catch (Throwable $e) {
        responder(502, ['mensagem' => 'Não foi possível sugerir os ícones agora.']);
    }
}

// ── Geração da imagem ────────────────────────────────────────────────────────
if ($acao !== 'imagem') {
    responder(400, ['mensagem' => 'Ação inválida.']);
}

$chave = $_ENV['POLLINATIONS_KEY'] ?? '';
if ($chave === '') {
    responder(500, ['mensagem' => 'POLLINATIONS_KEY não configurada no .env.']);
}

$tipo   = ($body['tipo'] ?? 'fundo') === 'titulo' ? 'titulo' : 'fundo';
$icones = trim(mb_substr((string) ($body['icones'] ?? ''), 0, 400)) ?: $tema;
// Até 3 cores em hex; o resto é descartado
$cores = array_values(array_filter(
    array_map('trim', (array) ($body['cores'] ?? [])),
    fn($c) => is_string($c) && preg_match('/^#[0-9a-f]{6}$/i', $c)
));
$cores = array_slice($cores, 0, 3) ?: ['#8b5cf6'];
$descCores = count($cores) === 1
    ? "a single accent color: {$cores[0]}"
    : 'a limited palette of accent colors: ' . implode(', ', $cores);
$seed   = (int) ($body['seed'] ?? random_int(1, 999999));

// Prompt fixo, no estilo dos fundos já usados (Natal, Junina, Internacional)
if ($tipo === 'fundo') {
    $prompt = "Seamless doodle pattern wallpaper, {$tema} theme. Small hand-drawn icons: {$icones}. "
        . "Thin black line art with partial flat fill in {$descCores}. "
        . "Plain white background. Tiny sparkles and confetti between icons. "
        . "Many small icons evenly scattered covering the whole image, flat vector illustration, "
        . "no text, no letters, no shadows, no gradients.";
    [$largura, $altura] = [1456, 416];
} else {
    $prompt = "Small decorative doodle ornament for a title banner, {$tema} theme. "
        . "A few hand-drawn icons ({$icones}) arranged around an empty wide horizontal center space. "
        . "Thin black line art with partial flat fill in {$descCores}. "
        . "Plain white background, tiny sparkles, flat vector illustration, "
        . "no text, no letters, no shadows, no gradients.";
    [$largura, $altura] = [1024, 320];
}

$url = 'https://gen.pollinations.ai/image/' . rawurlencode($prompt) . '?' . http_build_query([
    'model'  => 'black-forest-labs/flux.2-klein-4b',
    'width'  => $largura,
    'height' => $altura,
    'seed'   => $seed,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $chave],
]);
$imagem   = curl_exec($ch);
$http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tipoMime = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$erro     = curl_error($ch);
curl_close($ch);

if ($imagem === false || $http !== 200 || !str_starts_with($tipoMime, 'image/')) {
    error_log("gerar_imagem: HTTP {$http} {$erro} " . mb_substr((string) $imagem, 0, 300));
    $msg = $http === 402
        ? 'Créditos do Pollinations esgotados.'
        : 'Não foi possível gerar a imagem agora. Tente de novo.';
    responder(502, ['mensagem' => $msg]);
}

responder(200, ['imagem' => 'data:' . $tipoMime . ';base64,' . base64_encode($imagem)]);
