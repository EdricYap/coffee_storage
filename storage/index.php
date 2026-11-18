<div class="page-heading">
    <p class="eyebrow">EVERYTHING IN ITS PLACE</p>
    <h1>Storage locations</h1>
    <p class="subheading">Know where your stock lives, from the dry pantry to the chiller.</p>
</div>

<div class="management-layout">
    <section class="panel management-form">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">
                    <?= $editLocation ? 'UPDATE YOUR SPACE' : 'MAP YOUR SPACE' ?>
                </p>
                <h2><?= $editLocation ? 'Edit location' : 'New location' ?></h2>
            </div>

            <?php if ($editLocation): ?>
                <a class="text-link" href="?page=storage">Cancel</a>
            <?php endif; ?>
        </div>

        <form method="post" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="return_page" value="storage">
            <input type="hidden" name="action" value="save_location">
            <input type="hidden" name="location_id" value="<?= e($editLocation['location_id'] ?? '') ?>">

            <label>
                Location name
                <input
                    name="location_name"
                    maxlength="100"
                    required
                    placeholder="e.g. Freezer"
                    value="<?= e($editLocation['location_name'] ?? '') ?>"
                >
            </label>

            <label>
                Location type
                <input
                    name="location_type"
                    maxlength="50"
                    placeholder="e.g. Chilled, dry, frozen"
                    value="<?= e($editLocation['location_type'] ?? '') ?>"
                >
            </label>

            <label>
                Storage notes
                <textarea
                    name="conditions_note"
                    maxlength="255"
                    rows="3"
                    placeholder="Temperature or handling notes"
                ><?= e($editLocation['conditions_note'] ?? '') ?></textarea>
            </label>

            <button class="button button-dark button-wide" type="submit">
                <?= $editLocation ? 'Save changes' : 'Add location' ?>
                <span>→</span>
            </button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">YOUR STORAGE MAP</p>
                <h2>
                    Locations
                    <span class="count-pill"><?= count($locations) ?></span>
                </h2>
            </div>
        </div>

        <?php if (!$locations): ?>
            <div class="empty-state">
                <span class="empty-icon">▦</span>
                <strong>No locations yet</strong>
                <p>Add a storage location before recording deliveries.</p>
            </div>
        <?php else: ?>
            <div class="location-grid">
                <?php foreach ($locations as $location): ?>
                    <article class="location-card">
                        <span class="location-icon">▦</span>
                        <span class="location-kind">
                            <?= e($location['location_type'] ?: 'STORAGE AREA') ?>
                        </span>
                        <h3><?= e($location['location_name']) ?></h3>
                        <p><?= e($location['conditions_note'] ?: 'No storage notes added.') ?></p>

                        <div class="directory-actions">
                            <a
                                class="action-link"
                                href="?page=storage&amp;edit=<?= e($location['location_id']) ?>"
                            >
                                Edit
                            </a>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this location? Locations linked to stock batches cannot be deleted.');"
                            >
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="return_page" value="storage">
                                <input type="hidden" name="action" value="delete_location">
                                <input type="hidden" name="location_id" value="<?= e($location['location_id']) ?>">
                                <button class="action-link danger-link" type="submit">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
