<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Coffee Storage</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php" aria-label="Coffee Storage home">
            <strong>Musang Storage</strong>
            <small>Inventory Management</small>
        </a>
        <div class="side-label">WORKSPACE</div>
        <nav class="side-nav">
            <?php foreach ($nav as $key => [$label, $icon]): ?>
                <a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="?page=<?= e($key) ?>">
                    <span class="nav-icon"><?= e($icon) ?></span><span><?= e($label) ?></span>
                    <?php if ($key === 'inventory' && $lowStockCount > 0): ?><span class="nav-count"><?= $lowStockCount ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">Coffee Storage <span>/</span> <strong><?= e($title) ?></strong></div>
            <div class="topbar-right">
                <span class="today"><?= date('D, M j, Y') ?></span>
                <span class="avatar">CS</span>
            </div>
        </header>

        <section class="page-wrap">
            <?php if ($flash): ?>
                <div class="flash <?= e($flash['type']) ?>" role="status">
                    <span><?= $flash['type'] === 'success' ? '✓' : '!' ?></span>
                    <?= e($flash['message']) ?>
                    <button class="flash-close" type="button" aria-label="Dismiss">×</button>
                </div>
            <?php endif; ?>

