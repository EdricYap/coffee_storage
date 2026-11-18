<div class="page-heading heading-row">
    <div>
        <h1>Dashboard</h1>
        <p class="subheading">Inventory totals and stock alerts</p>
    </div>

    <a class="button button-dark" href="?page=transactions">
        <span>＋</span>
        Record stock
    </a>
</div>

<div class="stats-grid">
    <article class="stat-card stat-green">
        <div class="stat-top">
            <span class="stat-icon">▤</span>
            <span class="stat-kicker">ITEMS</span>
        </div>
        <div class="stat-number"><?= $itemCount ?></div>
        <div class="stat-label">Items tracked</div>
        <div class="stat-foot">Across all categories</div>
    </article>

    <article class="stat-card stat-cream">
        <div class="stat-top">
            <span class="stat-icon">⌁</span>
            <span class="stat-kicker">LOW STOCK</span>
        </div>
        <div class="stat-number">
            <?= $lowStockCount ?><small> items</small>
        </div>
        <div class="stat-label">Running low</div>
        <div class="stat-foot">At or below reorder level</div>
    </article>

    <article class="stat-card stat-lavender">
        <div class="stat-top">
            <span class="stat-icon">◷</span>
            <span class="stat-kicker">TRANSACTIONS</span>
        </div>
        <div class="stat-number"><?= $transactionCount ?></div>
        <div class="stat-label">Stock transactions</div>
        <div class="stat-foot">All-time inventory records</div>
    </article>
</div>

<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Items That Need Restocking</h2>
            </div>
            <a class="text-link" href="?page=inventory">
                View inventory
                <span>→</span>
            </a>
        </div>

        <?php if (!$lowStockItems): ?>
            <div class="empty-state">
                <span class="empty-icon">✦</span>
                    <strong>No low-stock items</strong>
                    <p>All items are above their reorder levels.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>CATEGORY</th>
                            <th>IN STOCK</th>
                            <th>REORDER LEVEL</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockItems as $row): ?>
                            <tr>
                                <td>
                                    <strong class="item-name"><?= e($row['item_name']) ?></strong>
                                </td>
                                <td class="muted"><?= e($row['category_name']) ?></td>
                                <td>
                                    <strong><?= e(number_format((float) $row['current_stock'], 2)) ?></strong>
                                    <span class="muted"><?= e($row['unit_of_measure']) ?></span>
                                </td>
                                <td class="muted">
                                    <?= e(number_format((float) $row['reorder_level'], 2)) ?>
                                    <?= e($row['unit_of_measure']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-low">Low</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>

<section class="panel activity-panel">
    <div class="panel-heading">
        <div>
            <h2>Recent transactions</h2>
        </div>
        <a class="text-link" href="?page=transactions">
            All transactions
            <span>→</span>
        </a>
    </div>

    <?php if (!$recentTransactions): ?>
        <div class="empty-state compact">
            <strong>No activity yet</strong>
            <p>Your stock movements will show up here.</p>
            <a class="button button-outline" href="?page=transactions">
                Record your first transaction
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>TYPE</th>
                        <th>ITEM</th>
                        <th>QUANTITY</th>
                        <th>REFERENCE</th>
                        <th>DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentTransactions, 0, 5) as $row): ?>
                        <tr>
                            <td>
                                <span class="type-mark type-<?= e(strtolower($row['transaction_type'])) ?>">
                                    <?= e(str_replace('_', ' ', $row['transaction_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <strong class="item-name"><?= e($row['item_name']) ?></strong>
                            </td>
                            <td>
                                <?= in_array($row['transaction_type'], ['USAGE', 'WASTAGE', 'ADJUSTMENT_OUT'], true) ? '−' : '+' ?>
                                <?= e(number_format((float) $row['quantity'], 2)) ?>
                                <span class="muted"><?= e($row['unit_of_measure']) ?></span>
                            </td>
                            <td class="muted"><?= e($row['reference_number'] ?: '—') ?></td>
                            <td class="muted">
                                <?= e(date('M j, g:i A', strtotime($row['transaction_date']))) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
