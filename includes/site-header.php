<?php

declare(strict_types=1);

require_once __DIR__ . '/site-brand.php';

/**
 * Itens do menu principal do site.
 *
 * @return list<array{href: string, label: string, class?: string, current?: bool, short_label?: string}>
 */
function site_nav_default_items(bool $blogCurrent = false): array
{
    return [
        ['href' => '/#servicos', 'label' => 'Serviços'],
        ['href' => '/blog/', 'label' => 'Blog', 'current' => $blogCurrent],
        [
            'href' => '/ecommerce-analise/',
            'label' => 'Análise e-commerce',
            'short_label' => 'Análise',
            'class' => 'btn btn--primary btn--header',
        ],
    ];
}

/**
 * @param list<array{href: string, label: string, class?: string, current?: bool, short_label?: string, attrs?: string}> $items
 */
function site_header_render(string $brandHref = '/', array $items = [], string $menuId = 'site-nav-menu'): void
{
    if ($items === []) {
        $items = site_nav_default_items();
    }
    ?>
    <header class="header">
        <nav class="nav container" aria-label="Principal">
            <div class="nav__logo">
                <?php site_brand_link($brandHref); ?>
            </div>
            <button
                type="button"
                class="nav__toggle"
                aria-expanded="false"
                aria-controls="<?= htmlspecialchars($menuId) ?>"
                aria-label="Abrir menu de navegação"
            >
                <span class="nav__toggle-bar" aria-hidden="true"></span>
                <span class="nav__toggle-bar" aria-hidden="true"></span>
                <span class="nav__toggle-bar" aria-hidden="true"></span>
            </button>
            <ul class="nav__menu" id="<?= htmlspecialchars($menuId) ?>">
                <?php foreach ($items as $item): ?>
                    <?php
                    $href = (string) ($item['href'] ?? '#');
                    $label = (string) ($item['label'] ?? '');
                    $short = (string) ($item['short_label'] ?? '');
                    $class = trim((string) ($item['class'] ?? ''));
                    $extra = (string) ($item['attrs'] ?? '');
                    $isCurrent = !empty($item['current']);
                    if ($label === '') {
                        continue;
                    }
                    ?>
                    <li>
                        <a
                            href="<?= htmlspecialchars($href) ?>"
                            <?= $class !== '' ? ' class="' . htmlspecialchars($class) . '"' : '' ?>
                            <?= $isCurrent ? ' aria-current="page"' : '' ?>
                            <?= $extra !== '' ? ' ' . $extra : '' ?>
                        >
                            <?php if ($short !== ''): ?>
                                <span class="nav__label nav__label--full"><?= htmlspecialchars($label) ?></span>
                                <span class="nav__label nav__label--short"><?= htmlspecialchars($short) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($label) ?>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </header>
    <?php
}

function site_nav_script(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
    <script src="/assets/site-nav.js" defer></script>
    <?php
}
