<div class="page-heading heading-row">
    <div>
        <p class="eyebrow">YOUR STOCK, ORGANIZED</p>
        <h1>Inventory</h1>
        <p class="subheading">Manage your catalog and keep an eye on what’s on hand.</p>
    </div>
    <button class="button button-dark" type="button" data-toggle="item-form">
        <span>＋</span> Add item
    </button>
</div>

<section class="panel form-panel <?= $editItem ? 'is-open' : '' ?>" id="item-form">
    <div class="panel-heading">
        <div>
            <p class="eyebrow"><?= $editItem ? 'UPDATE YOUR CATALOG' : 'GROW YOUR CATALOG' ?></p>
            <h2><?= $editItem ? 'Edit item' : 'Add an inventory item' ?></h2>
        </div>
        <?php if ($editItem): ?>
            <a class="text-link" href="?page=inventory">Cancel</a>
        <?php else: ?>
            <button class="icon-button" type="button" data-toggle="item-form" aria-label="Close form">×</button>
        <?php endif; ?>
    </div>

    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="return_page" value="inventory">
        <input type="hidden" name="action" value="save_item">
        <input type="hidden" name="item_id" value="<?= e($editItem['item_id'] ?? '') ?>">

        <label>
            Item name
            <input name="item_name" maxlength="150" placeholder="e.g. Oat milk"
                   required value="<?= e($editItem['item_name'] ?? '') ?>">
        </label>

        <label>
            Category
            <select name="category_id" required>
                <option value="">Choose category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['category_id']) ?>"
                        <?= (int) ($editItem['category_id'] ?? 0) === (int) $category['category_id'] ? 'selected' : '' ?>>
                        <?= e($category['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Unit of measure
            <input name="unit_of_measure" maxlength="30" placeholder="e.g. kg, L, pcs"
                   required value="<?= e($editItem['unit_of_measure'] ?? '') ?>">
        </label>

        <label>
            Reorder level
            <input name="reorder_level" type="number" step="0.01" min="0" placeholder="0.00"
                   required value="<?= e($editItem['reorder_level'] ?? '0') ?>">
        </label>

        <label class="check-label">
            <input type="checkbox" name="is_perishable" value="1"
                   <?= !empty($editItem['is_perishable']) ? 'checked' : '' ?>>
            <span>
                <strong>Perishable item</strong>
                <small>Expiry date will be required on deliveries.</small>
            </span>
        </label>

        <div class="form-actions">
            <button class="button button-dark" type="submit">
                <?= $editItem ? 'Save changes' : 'Add item' ?>
            </button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="inventory-toolbar">
        <div>
            <p class="eyebrow">LIVE STOCK LEVELS</p>
            <h2>
                All items
                <span class="count-pill"><?= count($inventoryRows) ?></span>
            </h2>
        </div>

        <form class="search-form" method="get">
            <input type="hidden" name="page" value="inventory">
            <span>⌕</span>
            <input type="search" name="q" value="<?= e($inventorySearch) ?>"
                   placeholder="Search items or categories">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (!$inventoryRows): ?>
        <div class="empty-state">
            <span class="empty-icon">▤</span>
            <strong><?= $inventorySearch ? 'No matching items' : 'Your inventory is waiting' ?></strong>
            <p><?= $inventorySearch ? 'Try another search term.' : 'Add your first item to start tracking stock.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ITEM</th>
                        <th>CATEGORY</th>
                        <th>ON HAND</th>
                        <th>REORDER LEVEL</th>
                        <th>STATUS</th>
                        <th class="right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryRows as $row): ?>
                        <?php
                        $isLow = (float) $row['current_stock'] <= (float) $row['reorder_level'];
                        ?>
                        <tr>
                            <td><strong class="item-name"><?= e($row['item_name']) ?></strong></td>
                            <td class="muted"><?= e($row['category_name']) ?></td>
                            <td>
                                <strong class="stock-number"><?= e(number_format((float) $row['current_stock'], 2)) ?></strong>
                                <span class="muted"><?= e($row['unit_of_measure']) ?></span>
                            </td>
                            <td class="muted">
                                <?= e(number_format((float) $row['reorder_level'], 2)) ?>
                                <?= e($row['unit_of_measure']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $isLow ? 'badge-low' : 'badge-good' ?>">
                                    <?= $isLow ? 'Restock soon' : 'In good shape' ?>
                                </span>
                            </td>
                            <td class="row-actions">
                                <a class="action-link" href="?page=inventory&amp;edit=<?= e($row['item_id']) ?>">Edit</a>
                                <form method="post"
                                      onsubmit="return confirm('Delete this item and all of its batches and transaction history?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="return_page" value="inventory">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_id" value="<?= e($row['item_id']) ?>">
                                    <button class="action-link danger-link" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

