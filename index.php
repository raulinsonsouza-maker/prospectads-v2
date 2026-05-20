<?php

declare(strict_types=1);

require __DIR__ . '/api/bootstrap.php';

$pdo = get_pdo();
ensure_schema($pdo);

$htmlPath = __DIR__ . '/index.html';
if (!is_file($htmlPath)) {
    http_response_code(500);
    echo 'Página inicial indisponível.';
    exit;
}

require __DIR__ . '/includes/home-blog-recent.php';

$html = file_get_contents($htmlPath);
if ($html === false) {
    http_response_code(500);
    echo 'Página inicial indisponível.';
    exit;
}

$recent = home_render_blog_recent_section($pdo, 3);
$html = str_replace('<!-- HOME_BLOG_RECENT -->', $recent, $html);

header('Content-Type: text/html; charset=utf-8');
echo $html;
