<?php

declare(strict_types=1);

/**
 * Corrige content_html com âncoras sem href e re-sincroniza lotes PHP quando pedido.
 * Uso:
 *   php scripts/repair-blog-content-links.php
 *   php scripts/repair-blog-content-links.php --sync=31-40
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';

$syncArg = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--sync=')) {
        $syncArg = substr($arg, 7);
    }
}

if ($syncArg !== null) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/sync-blog-articles-from-php.php') . ' ' . escapeshellarg($syncArg), $code);
    if ($code !== 0) {
        exit($code);
    }
}

$pdo = get_pdo();
ensure_schema($pdo);

$stmt = $pdo->query("SELECT id, slug, content_html FROM blog_posts WHERE status = 'published'");
$fixed = 0;

while ($row = $stmt->fetch()) {
    $before = (string) $row['content_html'];
    $after = blog_restore_content_links($before, $pdo);
    if ($after === $before) {
        continue;
    }

    $pdo->prepare('UPDATE blog_posts SET content_html = :c, updated_at = :u WHERE id = :id')
        ->execute([
            ':c' => $after,
            ':u' => gmdate('c'),
            ':id' => $row['id'],
        ]);
    echo "Corrigido: {$row['slug']}\n";
    $fixed++;
}

echo "\nReparo concluído ({$fixed} posts atualizados no banco).\n";
