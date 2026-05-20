<?php

declare(strict_types=1);

/**
 * Bloco HTML com os últimos artigos do blog (links canônicos para SEO).
 */
function home_render_blog_recent_section(PDO $pdo, int $limit = 3): string
{
    $stmt = $pdo->prepare(
        "SELECT p.title, p.slug
         FROM blog_posts p
         WHERE p.status = 'published'
         ORDER BY p.published_at DESC
         LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    if ($posts === []) {
        return '';
    }

    ob_start();
    ?>
    <section class="home-blog-recent" id="blog-recent" aria-labelledby="home-blog-recent-title">
        <div class="container">
            <h2 id="home-blog-recent-title" class="section__title">Últimos artigos do blog</h2>
            <p class="home-blog-recent__intro">Conteúdo prático sobre tráfego, conversão e crescimento de e-commerce.</p>
            <ul class="home-blog-recent__list">
                <?php foreach ($posts as $post): ?>
                    <li>
                        <a href="<?= htmlspecialchars(blog_post_path((string) $post['slug'])) ?>">
                            <?= htmlspecialchars((string) $post['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="home-blog-recent__more"><a href="/blog/" class="btn btn--secondary">Ver todos os artigos</a></p>
        </div>
    </section>
    <?php

    return (string) ob_get_clean();
}
