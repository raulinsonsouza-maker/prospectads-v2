<?php

declare(strict_types=1);

/**
 * Audita links no content_html de todos os posts publicados.
 * Uso: php scripts/audit-blog-content-links.php
 */

$root = dirname(__DIR__);
require $root . '/api/bootstrap.php';
require $root . '/scripts/blog-articles/helpers.php';

$pdo = get_pdo();
ensure_schema($pdo);

$published = $pdo->query("SELECT slug FROM blog_posts WHERE status = 'published'")->fetchAll(PDO::FETCH_COLUMN);
$publishedMap = array_fill_keys($published, true);

$stmt = $pdo->query("SELECT slug, title, content_html FROM blog_posts WHERE status = 'published' ORDER BY slug");
$issues = [];

while ($row = $stmt->fetch()) {
    $slug = (string) $row['slug'];
    $html = (string) $row['content_html'];
    $fixed = blog_prepare_post_content($html, $pdo);

    if (preg_match_all('#<a\b([^>]*)>(.*?)</a>#is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $attrs = $m[1];
            $inner = trim(strip_tags($m[2]));
            $hasHref = preg_match('#\bhref\s*=\s*["\']([^"\']+)#i', $attrs, $hrefM);
            $href = $hasHref ? $hrefM[1] : '';

            if ($href === '') {
                $issues[] = [
                    'post' => $slug,
                    'type' => 'sem_href',
                    'text' => mb_substr($inner, 0, 80),
                    'fixable' => str_contains($fixed, 'href=') && preg_match('#<a[^>]+href=[^>]+>' . preg_quote($inner, '#') . '#i', $fixed) === 0
                        ? 'restore_partial'
                        : (str_contains($fixed, 'href="/blog/') || str_contains($fixed, 'href="/ecommerce'))
                ];
                continue;
            }

            if (preg_match('#^/blog/([a-z0-9-]+)/?$#', $href, $sm)) {
                $target = $sm[1];
                if (!isset($publishedMap[$target])) {
                    $issues[] = [
                        'post' => $slug,
                        'type' => 'slug_inexistente',
                        'href' => $href,
                        'text' => mb_substr($inner, 0, 80),
                    ];
                }
            }
        }
    }

    if ($html !== $fixed && preg_match_all('#<a\b([^>]*)>#i', $html, $broken) && preg_match_all('#<a\b[^>]*href=#i', $fixed)) {
        $brokenCount = 0;
        foreach ($broken[1] as $attrs) {
            if (!preg_match('#\bhref\s*=#i', $attrs)) {
                $brokenCount++;
            }
        }
        if ($brokenCount > 0) {
            $issues[] = [
                'post' => $slug,
                'type' => 'reparavel_por_restore',
                'count' => $brokenCount,
            ];
        }
    }
}

if ($issues === []) {
    echo "OK: nenhum problema encontrado em " . count($published) . " posts.\n";
    exit(0);
}

echo "Problemas encontrados:\n";
foreach ($issues as $i) {
    echo '  - ' . json_encode($i, JSON_UNESCAPED_UNICODE) . "\n";
}
exit(1);
