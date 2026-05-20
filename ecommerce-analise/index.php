<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/site-gtag.php';

$htmlPath = __DIR__ . '/index.html';
if (!is_file($htmlPath)) {
    http_response_code(500);
    echo 'Página indisponível.';
    exit;
}

$html = file_get_contents($htmlPath);
if ($html === false) {
    http_response_code(500);
    echo 'Página indisponível.';
    exit;
}

$html = str_replace('<!-- SITE_GTAG -->', site_gtag_markup(), $html);

header('Content-Type: text/html; charset=utf-8');
echo $html;
