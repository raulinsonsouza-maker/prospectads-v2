<?php

declare(strict_types=1);

if (!function_exists('blog_published_slugs')) {
/**
 * Slugs publicados — gere com: php scripts/export-blog-slugs.php
 * Só use blog_link() com slugs desta lista para evitar link quebrado no site.
 */
function blog_published_slugs(): array
{
    static $slugs = null;
    if ($slugs !== null) {
        return $slugs;
    }
    $file = __DIR__ . '/published-slugs.php';
    if (!is_file($file)) {
        $slugs = [];

        return $slugs;
    }
    $loaded = require $file;
    $slugs = is_array($loaded) ? array_values(array_filter($loaded, 'is_string')) : [];

    return $slugs;
}
}

if (!function_exists('blog_link_label_slug_map')) {
    /**
     * Rótulos usados em blog_link() → slug (para restaurar href perdido no HTML salvo).
     *
     * @return array<string, string> chave = rótulo em minúsculas
     */
    function blog_link_label_slug_map(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [];
        $files = glob(__DIR__ . '/articles-*.php') ?: [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            if (preg_match_all(
                "/blog_link\\(\\s*'([^']+)'\\s*,\\s*'((?:[^'\\\\]|\\\\.)*)'\\s*\\)/",
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    $slug = slugify(stripslashes($match[1]));
                    $label = html_entity_decode(stripslashes($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $key = utf8_strtolower(trim($label));
                    if ($slug !== '' && $key !== '') {
                        $map[$key] = $slug;
                    }
                }
            }
        }

        return $map;
    }
}

if (!function_exists('blog_link')) {
    /** Link interno para outro artigo do blog (URL canônica /blog/slug/). */
    function blog_link(string $slug, string $label): string
    {
        $slug = slugify($slug);
        $label = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($slug === '') {
            return $label;
        }

        return '<a href="/blog/' . rawurlencode($slug) . '/">' . $label . '</a>';
    }
}

if (!function_exists('blog_cta')) {
function blog_cta(string $headline = 'Quer descobrir o gargalo do seu e-commerce?'): string
{
    $text = 'Solicite a análise comercial do seu e-commerce. Olhamos anúncios, site, oferta, WhatsApp e o que priorizar para escalar.';

    return <<<HTML
<blockquote>
<p><strong>{$headline}</strong> {$text}</p>
<p><a href="/ecommerce-analise/">Solicitar uma análise do meu e-commerce</a></p>
</blockquote>
HTML;
}
}
