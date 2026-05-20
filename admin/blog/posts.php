<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/api/bootstrap.php';

require_admin();

$pdo = get_pdo();
ensure_schema($pdo);

$statusFilter = (string) ($_GET['status'] ?? '');
$search = sanitize_string((string) ($_GET['q'] ?? ''), 100);

$sql = 'SELECT p.*, c.name AS category_name FROM blog_posts p
        LEFT JOIN blog_categories c ON c.id = p.category_id WHERE 1=1';
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, BLOG_POST_STATUSES, true)) {
    $sql .= ' AND p.status = :status';
    $params[':status'] = $statusFilter;
} else {
    $sql .= ' AND p.status != :trash';
    $params[':trash'] = 'trash';
}

if ($search !== '') {
    $sql .= ' AND p.title LIKE :q';
    $params[':q'] = '%' . $search . '%';
}

$sql .= ' ORDER BY COALESCE(p.published_at, p.updated_at) DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$stats = [
    'total_posts' => 0,
    'published_posts' => 0,
    'draft_posts' => 0,
    'trash_posts' => 0,
    'total_views' => 0,
];
$statsRow = $pdo->query(
    "SELECT
        COUNT(*) AS total_posts,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_posts,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_posts,
        SUM(CASE WHEN status = 'trash' THEN 1 ELSE 0 END) AS trash_posts,
        SUM(COALESCE(view_count, 0)) AS total_views
     FROM blog_posts"
)->fetch();
if (is_array($statsRow)) {
    $stats = array_merge($stats, $statsRow);
}

$publishedCount = (int) ($stats['published_posts'] ?? 0);
$totalViews = (int) ($stats['total_views'] ?? 0);
$avgViews = $publishedCount > 0 ? (int) round($totalViews / $publishedCount) : 0;

$visiblePostsCount = count($posts);
$visibleViews = 0;
foreach ($posts as $postRow) {
    $visibleViews += (int) ($postRow['view_count'] ?? 0);
}

$topPostStmt = $pdo->query(
    "SELECT slug, title, view_count
     FROM blog_posts
     WHERE status = 'published'
     ORDER BY view_count DESC, id DESC
     LIMIT 1"
);
$topPost = $topPostStmt ? $topPostStmt->fetch() : null;

$flash = (string) ($_GET['msg'] ?? '');

ob_start();
?>
<section class="admin-stats posts-hero-stats" aria-label="Resumo de desempenho dos posts">
    <article class="stat-card">
        <strong><?= number_format((int) ($stats['published_posts'] ?? 0), 0, ',', '.') ?></strong>
        <span>Posts publicados</span>
    </article>
    <article class="stat-card">
        <strong><?= number_format((int) ($stats['draft_posts'] ?? 0), 0, ',', '.') ?></strong>
        <span>Rascunhos</span>
    </article>
    <article class="stat-card">
        <strong><?= number_format($totalViews, 0, ',', '.') ?></strong>
        <span>Total de visualizações</span>
    </article>
    <article class="stat-card">
        <strong><?= number_format($avgViews, 0, ',', '.') ?></strong>
        <span>Média de views por post publicado</span>
    </article>
    <article class="stat-card stat-card--spotlight">
        <?php if (is_array($topPost)): ?>
            <strong><?= number_format((int) ($topPost['view_count'] ?? 0), 0, ',', '.') ?></strong>
            <span>Post com maior audiência</span>
            <a class="stat-card__meta-link" href="/blog/<?= htmlspecialchars((string) $topPost['slug']) ?>/" target="_blank" rel="noopener">
                <?= htmlspecialchars((string) $topPost['title']) ?>
            </a>
        <?php else: ?>
            <strong>0</strong>
            <span>Post com maior audiência</span>
            <small class="stat-card__meta">Publique artigos para começar a medir.</small>
        <?php endif; ?>
    </article>
</section>

<p class="posts-hero-summary">
    Exibindo <strong><?= number_format($visiblePostsCount, 0, ',', '.') ?></strong> posts no filtro atual,
    com <strong><?= number_format($visibleViews, 0, ',', '.') ?></strong> visualizações acumuladas.
</p>

<div class="admin-toolbar">
    <a href="post-edit.php" class="btn-primary-link">+ Adicionar novo post</a>
</div>

<?php if ($flash === 'saved'): ?>
    <div class="alert alert-success">Post salvo.</div>
<?php elseif ($flash === 'deleted'): ?>
    <div class="alert alert-success">Post movido para a lixeira.</div>
<?php elseif ($flash === 'purged'): ?>
    <div class="alert alert-success">Post excluído permanentemente.</div>
<?php endif; ?>

<form class="filters" method="get">
    <input type="search" name="q" placeholder="Buscar por título" value="<?= htmlspecialchars($search) ?>">
    <select name="status">
        <option value="">Publicados e rascunhos</option>
        <?php foreach (BLOG_POST_STATUSES as $st): ?>
            <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= post_status_label($st) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
        <a href="posts.php" class="filter-clear">Limpar</a>
    <?php endif; ?>
</form>

<?php if (empty($posts)): ?>
    <p class="empty-state">Nenhum post encontrado.</p>
<?php else: ?>
    <table class="leads-table posts-table">
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Status</th>
                <th>Visualizações</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($post['title']) ?></strong>
                        <br><small>/blog/<?= htmlspecialchars($post['slug']) ?>/</small>
                    </td>
                    <td><?= htmlspecialchars($post['category_name'] ?? '—') ?></td>
                    <td>
                        <span class="status-badge status-post-<?= htmlspecialchars($post['status']) ?>">
                            <?= post_status_label($post['status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(blog_format_view_count((int) ($post['view_count'] ?? 0))) ?></td>
                    <td>
                        <?php
                        $date = $post['published_at'] ?? $post['updated_at'];
                        echo htmlspecialchars(date('d/m/Y H:i', strtotime((string) $date)));
                        ?>
                    </td>
                    <td class="post-actions">
                        <a href="post-edit.php?id=<?= (int) $post['id'] ?>">Editar</a>
                        <?php if ($post['status'] === 'published'): ?>
                            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" target="_blank" rel="noopener">Ver</a>
                        <?php endif; ?>
                        <form method="post" action="post-delete.php" class="inline-form" onsubmit="return confirm('Mover para lixeira?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <input type="hidden" name="action" value="trash">
                            <button type="submit" class="btn-link-danger">Lixeira</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Posts';
$pageSubtitle = 'Gerencie artigos do blog público';
$activeNav = 'posts';
require dirname(__DIR__) . '/includes/layout.php';
