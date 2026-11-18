<div class="page-heading">
    <p class="eyebrow">EVERY MOVEMENT, ACCOUNTED FOR</p>
    <h1>Transactions</h1>
    <p class="subheading">
        Receive deliveries, record what’s used, and keep an accurate stock trail.
    </p>
</div>

<div class="transaction-layout">
    <section class="panel transaction-form-panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">MAKE A STOCK ENTRY</p>
                <h2>Record transaction</h2>
            </div>
            <span class="form-badge">LEDGER ENTRY</span>
        </div>

        <?php if (!$itemsForSelect): ?>
            <div class="empty-state compact">
                <strong>Add an item first</strong>
                <p>Create an inventory item before recording stock.</p>
                <a class="text-link" href="?page=inventory">Go to inventory →</a>
            </div>
        <?php else: ?>
            <form method="post" class="stack-form" id="transaction-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="return_page" value="transactions">
                <input type="hidden" name="action" value="<?= $editTransaction ? 'update_transaction' : 'record_transaction' ?>">
                <input type="hidden" name="transaction_id" value="<?= e($editTransaction['transaction_id'] ?? '') ?>">

                <label>
                    Transaction type
                    <?php if ($editTransaction): ?>
                        <input type="hidden" name="transaction_type" value="<?= e($editTransaction['transaction_type']) ?>">
                        <input value="<?= e(str_replace('_', ' ', $editTransaction['transaction_type'])) ?>" readonly>
                    <?php else: ?>
                        <select name="transaction_type" id="transaction-type" required>
                            <option value="RECEIPT">Delivery received</option>
                            <option value="USAGE">Stock used</option>
                            <option value="WASTAGE">Stock wasted</option>
                        </select>
                    <?php endif; ?>
                </label>

                <label>
                    Item
                    <?php if ($editTransaction): ?>
                        <?php
                        $editItemName = '';
                        foreach ($itemsForSelect as $item) {
                            if ((int) $item['item_id'] === (int) $editTransaction['item_id']) {
                                $editItemName = $item['item_name'] . ' (' . $item['unit_of_measure'] . ')';
                                break;
                            }
                        }
                        ?>
                        <input type="hidden" name="item_id" value="<?= e($editTransaction['item_id']) ?>">
                        <input value="<?= e($editItemName) ?>" readonly>
                    <?php else: ?>
                        <select name="item_id" required>
                            <option value="">Choose item</option>
                            <?php foreach ($itemsForSelect as $item): ?>
                                <option value="<?= e($item['item_id']) ?>"
                                        data-perishable="<?= e($item['is_perishable']) ?>">
                                    <?= e($item['item_name']) ?>
                                    (<?= e($item['unit_of_measure']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </label>

                <label>
                    Quantity
                    <input type="number" name="quantity" step="0.01" min="0.01" placeholder="0.00" required
                           value="<?= e($editTransaction['quantity'] ?? '') ?>">
                    <small>Use a positive quantity. Stock is adjusted automatically.</small>
                </label>

                <?php if (!$editTransaction || $editTransaction['transaction_type'] === 'RECEIPT'): ?>
                    <div class="receipt-fields">
                        <div class="form-two">
                            <label>
                                Storage location
                                <select name="location_id">
                                    <option value="">Choose location</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?= e($location['location_id']) ?>">
                                            <?= e($location['location_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                Supplier
                                <span class="optional">(optional)</span>
                                <select name="supplier_id">
                                    <option value="0">Not specified</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= e($supplier['supplier_id']) ?>">
                                            <?= e($supplier['supplier_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>

                        <div class="form-two">
                            <label>
                                Received date
                                <input type="date" name="received_date" value="<?= e(date('Y-m-d')) ?>">
                            </label>

                            <label>
                                Expiry date
                                <input type="date" name="expiry_date">
                            </label>
                        </div>

                        <?php if ($editTransaction && $editTransaction['transaction_type'] === 'RECEIPT'): ?>
                        <label>
                            Unit price
                            <span class="price-input">
                                <span aria-hidden="true">₱</span>
                                <input type="number" name="unit_cost" step="0.01" min="0" placeholder="0.00"
                                       value="<?= e($editTransaction['unit_cost'] ?? '') ?>">
                            </span>
                        </label>
                        <?php endif; ?>
                        <?php if (!$editTransaction): ?>
                        <label>
                            Unit price
                            <span class="price-input">
                                <span aria-hidden="true">₱</span>
                                <input type="number" name="unit_cost" step="0.01" min="0" placeholder="0.00">
                            </span>
                        </label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <label>
                    Reference
                    <span class="optional">(optional)</span>
                    <input name="reference_number" maxlength="100" placeholder="Delivery slip or note number"
                           value="<?= e($editTransaction['reference_number'] ?? '') ?>">
                </label>

                <label>
                    Notes
                    <span class="optional">(optional)</span>
                    <textarea name="notes" rows="2" maxlength="255"
                              placeholder="Add a short note"><?= e($editTransaction['notes'] ?? '') ?></textarea>
                </label>

                <button class="button button-dark button-wide" type="submit">
                    <?= $editTransaction ? 'Update transaction' : 'Save transaction' ?>
                    <span>→</span>
                </button>

                <p class="form-hint">
                    Usage and wastage are automatically assigned to earliest-expiring stock first.
                </p>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel transaction-history">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">YOUR INVENTORY JOURNAL</p>
                <h2>Recent activity</h2>
            </div>
        </div>

        <?php if (!$recentTransactions): ?>
            <div class="empty-state">
                <strong>No transactions yet</strong>
                <p>Record a delivery to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>TRANSACTION</th>
                            <th>ITEM</th>
                            <th>QTY</th>
                            <th>EXPIRY</th>
                            <th>WHEN</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $row): ?>
                            <tr>
                                <td>
                                    <span class="type-mark type-<?= e(strtolower($row['transaction_type'])) ?>">
                                        <?= e(str_replace('_', ' ', $row['transaction_type'])) ?>
                                    </span>

                                    <?php if ($row['reference_number']): ?>
                                        <small class="table-sub"><?= e($row['reference_number']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="item-name"><?= e($row['item_name']) ?></strong>

                                    <?php if ($row['notes']): ?>
                                        <small class="table-sub"><?= e($row['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= in_array($row['transaction_type'], ['USAGE', 'WASTAGE', 'ADJUSTMENT_OUT'], true) ? '−' : '+' ?>
                                    <?= e(number_format((float) $row['quantity'], 2)) ?>
                                    <small class="table-sub"><?= e($row['unit_of_measure']) ?></small>
                                    <?php if ($row['transaction_type'] === 'RECEIPT' && $row['unit_cost'] !== null): ?>
                                        <small class="table-sub">
                                            ₱<?= e(number_format((float) $row['unit_cost'], 2)) ?> per unit
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['expiry_date'])): ?>
                                        <?= e(date('M j, Y', strtotime($row['expiry_date']))) ?>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="muted">
                                    <?= e(date('M j, Y', strtotime($row['transaction_date']))) ?>
                                    <small class="table-sub">
                                        <?= e(date('g:i A', strtotime($row['transaction_date']))) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="action-link"
                                           href="?page=transactions&amp;edit_transaction=<?= e($row['transaction_id']) ?>">
                                            Edit
                                        </a>
                                        <form method="post"
                                              onsubmit="return confirm('Delete this transaction? Stock totals will be recalculated.');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="return_page" value="transactions">
                                            <input type="hidden" name="action" value="delete_transaction">
                                            <input type="hidden" name="transaction_id" value="<?= e($row['transaction_id']) ?>">
                                            <button class="action-link danger-link" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
