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

$topPostsStmt = $pdo->query(
    "SELECT id, slug, title, view_count, category_id
     FROM blog_posts
     WHERE status = 'published'
     ORDER BY view_count DESC, id DESC
     LIMIT 3"
);
$topPosts = $topPostsStmt ? $topPostsStmt->fetchAll() : [];
$topPost = $topPosts[0] ?? null;

$trashCount = (int) ($stats['trash_posts'] ?? 0);
$totalPosts = (int) ($stats['total_posts'] ?? 0);
$publishedCountStat = (int) ($stats['published_posts'] ?? 0);
$draftCount = (int) ($stats['draft_posts'] ?? 0);

$maxViewsInList = 1;
foreach ($posts as $postRow) {
    $maxViewsInList = max($maxViewsInList, (int) ($postRow['view_count'] ?? 0));
}

$publishedPct = $totalPosts > 0 ? (int) round(($publishedCountStat / $totalPosts) * 100) : 0;

$filterLabel = 'Publicados e rascunhos';
if ($statusFilter === 'published') {
    $filterLabel = 'Somente publicados';
} elseif ($statusFilter === 'draft') {
    $filterLabel = 'Somente rascunhos';
} elseif ($statusFilter === 'trash') {
    $filterLabel = 'Lixeira';
} elseif ($statusFilter !== '') {
    $filterLabel = post_status_label($statusFilter);
}

$flash = (string) ($_GET['msg'] ?? '');

ob_start();
?>
<section class="posts-dashboard" aria-label="Painel de conteúdo do blog">
    <div class="posts-dashboard__kpis">
        <article class="posts-kpi">
            <div class="posts-kpi__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </div>
            <div class="posts-kpi__body">
                <span class="posts-kpi__label">Publicados</span>
                <p class="posts-kpi__value"><?= number_format($publishedCountStat, 0, ',', '.') ?></p>
                <span class="posts-kpi__hint"><?= $publishedPct ?>% do acervo total</span>
            </div>
        </article>

        <article class="posts-kpi">
            <div class="posts-kpi__icon posts-kpi__icon--muted" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="posts-kpi__body">
                <span class="posts-kpi__label">Rascunhos</span>
                <p class="posts-kpi__value"><?= number_format($draftCount, 0, ',', '.') ?></p>
                <span class="posts-kpi__hint">Aguardando publicação</span>
            </div>
        </article>

        <article class="posts-kpi posts-kpi--accent">
            <div class="posts-kpi__icon posts-kpi__icon--accent" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="1.75"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="1.75"/></svg>
            </div>
            <div class="posts-kpi__body">
                <span class="posts-kpi__label">Visualizações</span>
                <p class="posts-kpi__value"><?= number_format($totalViews, 0, ',', '.') ?></p>
                <span class="posts-kpi__hint">Soma de todos os artigos</span>
            </div>
        </article>

        <article class="posts-kpi">
            <div class="posts-kpi__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="posts-kpi__body">
                <span class="posts-kpi__label">Média por publicado</span>
                <p class="posts-kpi__value"><?= number_format($avgViews, 0, ',', '.') ?></p>
                <span class="posts-kpi__hint">Views / artigo ativo</span>
            </div>
        </article>
    </div>

    <div class="posts-dashboard__panels">
        <article class="posts-panel posts-panel--leader">
            <header class="posts-panel__head">
                <div>
                    <h2 class="posts-panel__title">Destaque de audiência</h2>
                    <p class="posts-panel__desc">Artigo com mais visualizações no blog público</p>
                </div>
                <?php if (is_array($topPost) && (int) ($topPost['view_count'] ?? 0) > 0): ?>
                    <span class="posts-panel__badge"><?= number_format((int) $topPost['view_count'], 0, ',', '.') ?> views</span>
                <?php endif; ?>
            </header>

            <?php if (is_array($topPost)): ?>
                <h3 class="posts-leader__title"><?= htmlspecialchars((string) $topPost['title']) ?></h3>
                <p class="posts-leader__slug">/blog/<?= htmlspecialchars((string) $topPost['slug']) ?>/</p>
                <div class="posts-leader__actions">
                    <a href="/blog/<?= htmlspecialchars((string) $topPost['slug']) ?>/" class="posts-leader__btn posts-leader__btn--primary" target="_blank" rel="noopener">Ver no site</a>
                    <a href="post-edit.php?id=<?= (int) $topPost['id'] ?>" class="posts-leader__btn">Editar</a>
                </div>
            <?php else: ?>
                <p class="posts-panel__empty">Nenhum artigo publicado ainda. Crie o primeiro post para começar a medir audiência.</p>
            <?php endif; ?>

            <?php if (count($topPosts) > 1): ?>
                <ul class="posts-leader__ranking" aria-label="Top 3 por visualizações">
                    <?php foreach (array_slice($topPosts, 1) as $i => $ranked): ?>
                        <li>
                            <span class="posts-leader__rank"><?= $i + 2 ?>º</span>
                            <a href="post-edit.php?id=<?= (int) $ranked['id'] ?>"><?= htmlspecialchars((string) $ranked['title']) ?></a>
                            <span class="posts-leader__views"><?= number_format((int) ($ranked['view_count'] ?? 0), 0, ',', '.') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="posts-panel posts-panel--status">
            <header class="posts-panel__head">
                <div>
                    <h2 class="posts-panel__title">Status do acervo</h2>
                    <p class="posts-panel__desc"><?= number_format($totalPosts, 0, ',', '.') ?> posts no total</p>
                </div>
            </header>

            <ul class="posts-status-list">
                <li>
                    <div class="posts-status-list__row">
                        <span>Publicados</span>
                        <strong><?= number_format($publishedCountStat, 0, ',', '.') ?></strong>
                    </div>
                    <div class="posts-status-list__bar" role="presentation"><span style="width: <?= $publishedPct ?>%"></span></div>
                </li>
                <li>
                    <div class="posts-status-list__row">
                        <span>Rascunhos</span>
                        <strong><?= number_format($draftCount, 0, ',', '.') ?></strong>
                    </div>
                    <div class="posts-status-list__bar posts-status-list__bar--draft" role="presentation"><span style="width: <?= $totalPosts > 0 ? (int) round(($draftCount / $totalPosts) * 100) : 0 ?>%"></span></div>
                </li>
                <li>
                    <div class="posts-status-list__row">
                        <span>Lixeira</span>
                        <strong><?= number_format($trashCount, 0, ',', '.') ?></strong>
                    </div>
                    <div class="posts-status-list__bar posts-status-list__bar--trash" role="presentation"><span style="width: <?= $totalPosts > 0 ? (int) round(($trashCount / $totalPosts) * 100) : 0 ?>%"></span></div>
                </li>
            </ul>

            <div class="posts-panel__context">
                <span class="posts-context-chip">
                    <strong><?= number_format($visiblePostsCount, 0, ',', '.') ?></strong> na listagem
                </span>
                <span class="posts-context-chip">
                    <strong><?= number_format($visibleViews, 0, ',', '.') ?></strong> views no filtro
                </span>
                <span class="posts-context-chip posts-context-chip--filter"><?= htmlspecialchars($filterLabel) ?></span>
            </div>
        </article>
    </div>
</section>

<?php if ($flash === 'saved'): ?>
    <div class="alert alert-success">Post salvo.</div>
<?php elseif ($flash === 'deleted'): ?>
    <div class="alert alert-success">Post movido para a lixeira.</div>
<?php elseif ($flash === 'purged'): ?>
    <div class="alert alert-success">Post excluído permanentemente.</div>
<?php endif; ?>

<form class="filters filters--posts" method="get">
    <div class="filters__field filters__field--grow">
        <label for="posts-search">Buscar artigo</label>
        <input type="search" id="posts-search" name="q" placeholder="Título ou palavra-chave no nome…" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="filters__field">
        <label for="posts-status">Status</label>
        <select id="posts-status" name="status">
            <option value="">Publicados e rascunhos</option>
            <?php foreach (BLOG_POST_STATUSES as $st): ?>
                <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= post_status_label($st) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn-primary">Aplicar filtro</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="posts.php" class="filter-clear">Limpar</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($posts)): ?>
    <p class="empty-state">Nenhum post encontrado para este filtro.</p>
<?php else: ?>
    <div class="posts-table-wrap">
    <table class="leads-table posts-table posts-table--enhanced">
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Status</th>
                <th class="col-views">Visualizações</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post):
                $postViews = (int) ($post['view_count'] ?? 0);
                $viewPct = $maxViewsInList > 0 ? min(100, (int) round(($postViews / $maxViewsInList) * 100)) : 0;
                ?>
                <tr>
                    <td class="col-title">
                        <strong class="posts-table__title"><?= htmlspecialchars($post['title']) ?></strong>
                        <code class="posts-table__slug">/blog/<?= htmlspecialchars($post['slug']) ?>/</code>
                    </td>
                    <td><span class="posts-table__category"><?= htmlspecialchars($post['category_name'] ?? '—') ?></span></td>
                    <td>
                        <span class="status-badge status-post-<?= htmlspecialchars($post['status']) ?>">
                            <?= post_status_label($post['status']) ?>
                        </span>
                    </td>
                    <td class="col-views">
                        <div class="posts-views-cell">
                            <span class="posts-views-cell__num"><?= htmlspecialchars(blog_format_view_count($postViews)) ?></span>
                            <span class="posts-views-cell__bar" role="presentation"><span style="width: <?= $viewPct ?>%"></span></span>
                        </div>
                    </td>
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
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Posts';
$pageSubtitle = 'Visão geral do blog, audiência e gestão editorial';
$pageActions = '<a href="post-edit.php" class="btn-primary-link btn-primary-link--lg"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"/></svg> Novo artigo</a>';
$adminBodyClass = 'admin-page--posts';
$activeNav = 'posts';
require dirname(__DIR__) . '/includes/layout.php';
