<div class="page-heading heading-row">
    <div>
        <p class="eyebrow">THE PEOPLE BEHIND THE BEANS</p>
        <h1>Suppliers</h1>
        <p class="subheading">Keep your supplier contacts in one easy-to-find place.</p>
    </div>
</div>

<div class="management-layout">
    <section class="panel management-form">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">
                    <?= $editSupplier ? 'UPDATE YOUR PARTNER' : 'ADD A PARTNER' ?>
                </p>
                <h2><?= $editSupplier ? 'Edit supplier' : 'New supplier' ?></h2>
            </div>

            <?php if ($editSupplier): ?>
                <a class="text-link" href="?page=suppliers">Cancel</a>
            <?php endif; ?>
        </div>

        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="return_page" value="suppliers">
            <input type="hidden" name="action" value="save_supplier">
            <input type="hidden" name="supplier_id" value="<?= e($editSupplier['supplier_id'] ?? '') ?>">

            <label>
                Supplier name
                <input
                    name="supplier_name"
                    maxlength="150"
                    required
                    placeholder="Business name"
                    value="<?= e($editSupplier['supplier_name'] ?? '') ?>"
                >
            </label>

            <label>
                Phone
                <input
                    name="phone"
                    maxlength="30"
                    placeholder="Phone number"
                    value="<?= e($editSupplier['phone'] ?? '') ?>"
                >
            </label>

            <label>
                Email
                <input
                    name="email"
                    type="email"
                    maxlength="150"
                    placeholder="name@example.com"
                    value="<?= e($editSupplier['email'] ?? '') ?>"
                >
            </label>

            <label>
                Address
                <textarea name="address" maxlength="255" rows="2" placeholder="Optional address"><?= e($editSupplier['address'] ?? '') ?></textarea>
            </label>

            <button class="button button-dark button-wide" type="submit">
                <?= $editSupplier ? 'Save changes' : 'Add supplier' ?>
                <span>→</span>
            </button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">YOUR SUPPLY NETWORK</p>
                <h2>
                    Supplier directory
                    <span class="count-pill"><?= count($suppliers) ?></span>
                </h2>
            </div>
        </div>

        <?php if (!$suppliers): ?>
            <div class="empty-state">
                <span class="empty-icon">♧</span>
                <strong>No suppliers yet</strong>
                <p>Add your first supplier using the form.</p>
            </div>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($suppliers as $supplier): ?>
                    <article class="directory-card">
                        <span class="directory-avatar">
                            <?= e(strtoupper(substr($supplier['supplier_name'], 0, 1))) ?>
                        </span>

                        <div class="directory-info">
                            <strong><?= e($supplier['supplier_name']) ?></strong>
                            <span><?= e($supplier['email'] ?: 'No email provided') ?></span>
                            <span><?= e($supplier['phone'] ?: 'No phone provided') ?></span>

                            <?php if ($supplier['address']): ?>
                                <span><?= e($supplier['address']) ?></span>
                            <?php endif; ?>

                            <div class="directory-actions">
                                <a
                                    class="action-link"
                                    href="?page=suppliers&amp;edit=<?= e($supplier['supplier_id']) ?>"
                                >
                                    Edit
                                </a>

                                <form
                                    method="post"
                                    onsubmit="return confirm('Delete this supplier and remove its item-price links? Existing stock history will be kept.');"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="return_page" value="suppliers">
                                    <input type="hidden" name="action" value="delete_supplier">
                                    <input type="hidden" name="supplier_id" value="<?= e($supplier['supplier_id']) ?>">
                                    <button class="action-link danger-link" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">KEEP PRICES HANDY</p>
            <h2>
                Supplier item pricing
                <span class="count-pill"><?= count($supplierPrices) ?></span>
            </h2>
        </div>
    </div>

    <?php if (!$suppliers || !$itemsForSelect): ?>
        <div class="empty-state compact">
            <strong>Add suppliers and items first</strong>
            <p>Then you can record the price each supplier charges for an item.</p>
        </div>
    <?php else: ?>
        <form method="post" class="pricing-form">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="return_page" value="suppliers">
            <input type="hidden" name="action" value="save_supplier_item">
            <input type="hidden" name="original_supplier_id" value="<?= e($editSupplierPrice['supplier_id'] ?? '') ?>">
            <input type="hidden" name="original_item_id" value="<?= e($editSupplierPrice['item_id'] ?? '') ?>">

            <label>
                Supplier
                <select name="supplier_id" required>
                    <option value="">Choose supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= e($supplier['supplier_id']) ?>"
                            <?= (int) ($editSupplierPrice['supplier_id'] ?? 0) === (int) $supplier['supplier_id'] ? 'selected' : '' ?>>
                            <?= e($supplier['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Item
                <select name="item_id" required>
                    <option value="">Choose item</option>
                    <?php foreach ($itemsForSelect as $item): ?>
                        <option value="<?= e($item['item_id']) ?>"
                            <?= (int) ($editSupplierPrice['item_id'] ?? 0) === (int) $item['item_id'] ? 'selected' : '' ?>>
                            <?= e($item['item_name']) ?>
                            (<?= e($item['unit_of_measure']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Unit price/kg
                <span class="price-input">
                    <span aria-hidden="true">₱</span>
                    <input type="number" name="supplier_unit_price" min="0" step="0.01" required placeholder="0.00"
                           value="<?= e($editSupplierPrice['unit_price'] ?? '') ?>">
                </span>
            </label>

            <button class="button button-dark" type="submit">
                <?= $editSupplierPrice ? 'Update price' : 'Save price' ?>
            </button>
            <?php if ($editSupplierPrice): ?>
                <a class="text-link" href="?page=suppliers">Cancel</a>
            <?php endif; ?>
        </form>

        <?php if ($supplierPrices): ?>
            <div class="table-wrap pricing-table">
                <table>
                    <thead>
                        <tr>
                            <th>SUPPLIER</th>
                            <th>ITEM</th>
                            <th>UNIT PRICE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($supplierPrices as $price): ?>
                            <tr>
                                <td>
                                    <strong class="item-name">
                                        <?= e($price['supplier_name']) ?>
                                    </strong>
                                </td>
                                <td><?= e($price['item_name']) ?></td>
                                <td><strong>₱<?= e(number_format((float) $price['unit_price'], 2)) ?></strong></td>
                                <td class="row-actions">
                                    <a class="action-link"
                                       href="?page=suppliers&amp;edit_supplier=<?= e($price['supplier_id']) ?>&amp;edit_item=<?= e($price['item_id']) ?>">
                                        Edit
                                    </a>
                                    <form method="post"
                                          onsubmit="return confirm('Delete this supplier item price?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="return_page" value="suppliers">
                                        <input type="hidden" name="action" value="delete_supplier_item">
                                        <input type="hidden" name="supplier_id" value="<?= e($price['supplier_id']) ?>">
                                        <input type="hidden" name="item_id" value="<?= e($price['item_id']) ?>">
                                        <button class="action-link danger-link" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
