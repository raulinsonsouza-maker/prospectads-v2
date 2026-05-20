<?php

declare(strict_types=1);

/**
 * Falha se algum artigo em blog-articles/ tiver link /blog/ para slug inexistente.
 * Uso: php scripts/validate-blog-links.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$pdo = get_pdo();
ensure_schema($pdo);

$published = $pdo->query("SELECT slug FROM blog_posts WHERE status = 'published'")->fetchAll(PDO::FETCH_COLUMN);
$publishedMap = array_fill_keys($published, true);

$files = glob(__DIR__ . '/blog-articles/articles-*.php') ?: [];
$errors = [];
$linkCount = [];

$stmt = $pdo->query("SELECT slug, content_html FROM blog_posts WHERE status = 'published'");
while ($row = $stmt->fetch()) {
    $postSlug = (string) $row['slug'];
    $html = blog_prepare_post_content((string) $row['content_html'], $pdo);
    if (preg_match_all('#<a\b([^>]*)>(.*?)</a>#is', $html, $anchors, PREG_SET_ORDER)) {
        foreach ($anchors as $a) {
            if (!preg_match('/\bhref\s*=\s*(["\'])([^"\']+)\1/i', $a[1], $hm)) {
                $errors[] = "post {$postSlug}: âncora sem href → " . strip_tags($a[2]);
                continue;
            }
            $href = $hm[2];
            if (preg_match('#^/blog/([a-z0-9-]+)/?$#', $href, $sm) && !isset($publishedMap[$sm[1]])) {
                $errors[] = "post {$postSlug}: link quebrado → {$href}";
            }
        }
    }
}

foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    if (preg_match_all('#href="/blog/([a-z0-9-]+)/"#', $content, $m)) {
        foreach ($m[1] as $slug) {
            $linkCount[$slug] = ($linkCount[$slug] ?? 0) + 1;
            if (!isset($publishedMap[$slug])) {
                $errors[] = basename($file) . ": link quebrado no fonte → /blog/{$slug}/";
            }
        }
    }
}

// Limite recomendado: no máximo 3 links internos por arquivo de lote
foreach ($files as $file) {
    $content = file_get_contents($file) ?: '';
    $n = preg_match_all('#href="/blog/[a-z0-9-]+/"#', $content);
    if ($n > 15) {
        $errors[] = basename($file) . ": excesso de links internos ({$n}). Máx. recomendado: 3 por artigo (~15 por arquivo de 5).";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "Validação de links falhou:\n");
    foreach ($errors as $e) {
        fwrite(STDERR, "  - {$e}\n");
    }
    exit(1);
}

echo 'OK: ' . count($linkCount) . " slugs referenciados, todos publicados.\n";
