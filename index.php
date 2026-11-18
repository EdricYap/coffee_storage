<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/validation.php';

$page = (string) ($_GET['page'] ?? 'dashboard');
$validPages = ['dashboard', 'inventory', 'transactions', 'suppliers', 'storage'];
if (!in_array($page, $validPages, true)) {
    $page = 'dashboard';
}

require __DIR__ . '/includes/form_actions.php';
require __DIR__ . '/includes/page_data.php';
require __DIR__ . '/includes/layout_header.php';
require __DIR__ . '/' . $page . '/index.php';
require __DIR__ . '/includes/layout_footer.php';
