<?php

// Shared flash messages and navigation data.
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Select lists used by forms.
$categories = $conn->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetch_all(MYSQLI_ASSOC);
$itemsForSelect = $conn->query('SELECT item_id, item_name, unit_of_measure, is_perishable FROM items ORDER BY item_name')->fetch_all(MYSQLI_ASSOC);
$suppliers = $conn->query('SELECT supplier_id, supplier_name, phone, email, address FROM suppliers ORDER BY supplier_name')->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query('SELECT location_id, location_name, location_type, conditions_note FROM storage_locations ORDER BY location_name')->fetch_all(MYSQLI_ASSOC);

// Dashboard totals and low-stock alerts.
$itemCount = (int) $conn->query('SELECT COUNT(*) AS total FROM items')->fetch_assoc()['total'];
$lowStockCount = (int) $conn->query('SELECT COUNT(*) AS total FROM item_stock WHERE current_stock <= reorder_level')->fetch_assoc()['total'];
$transactionCount = (int) $conn->query('SELECT COUNT(*) AS total FROM inventory_transactions')->fetch_assoc()['total'];
$lowStockItems = $conn->query(
    'SELECT item_id, item_name, category_name, unit_of_measure, current_stock, reorder_level
     FROM item_stock WHERE current_stock <= reorder_level
     ORDER BY current_stock ASC LIMIT 6'
)->fetch_all(MYSQLI_ASSOC);

// Inventory search and edit-state data.
$inventorySearch = trim((string) ($_GET['q'] ?? ''));
if ($inventorySearch !== '') {
    $stmt = $conn->prepare(
        'SELECT item_id, item_name, category_name, unit_of_measure, reorder_level, current_stock
         FROM item_stock
         WHERE item_name LIKE ? OR category_name LIKE ?
         ORDER BY item_name'
    );
    $like = '%' . $inventorySearch . '%';
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $inventoryRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $inventoryRows = $conn->query('SELECT * FROM item_stock ORDER BY item_name')->fetch_all(MYSQLI_ASSOC);
}

$editItem = null;
if ($page === 'inventory' && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM items WHERE item_id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc() ?: null;
}
$editSupplier = null;
if ($page === 'suppliers' && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM suppliers WHERE supplier_id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editSupplier = $stmt->get_result()->fetch_assoc() ?: null;
}
$editLocation = null;
if ($page === 'storage' && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM storage_locations WHERE location_id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editLocation = $stmt->get_result()->fetch_assoc() ?: null;
}

// Supplier pricing and transaction history.
$supplierPrices = $conn->query(
    'SELECT si.supplier_id, si.item_id, si.unit_price,
            s.supplier_name, i.item_name, i.unit_of_measure
     FROM supplier_items si
     JOIN suppliers s ON s.supplier_id = si.supplier_id
     JOIN items i ON i.item_id = si.item_id
     ORDER BY s.supplier_name, i.item_name'
)->fetch_all(MYSQLI_ASSOC);

$editSupplierPrice = null;
if ($page === 'suppliers' && isset($_GET['edit_supplier'], $_GET['edit_item'])) {
    $supplierId = (int) $_GET['edit_supplier'];
    $itemId = (int) $_GET['edit_item'];
    $stmt = $conn->prepare(
        'SELECT supplier_id, item_id, unit_price
         FROM supplier_items
         WHERE supplier_id = ? AND item_id = ?'
    );
    $stmt->bind_param('ii', $supplierId, $itemId);
    $stmt->execute();
    $editSupplierPrice = $stmt->get_result()->fetch_assoc() ?: null;
}

$recentTransactions = $conn->query(
    'SELECT t.transaction_id, t.transaction_type, t.quantity, t.unit_cost, t.transaction_date,
            t.reference_number, t.notes, i.item_name, i.unit_of_measure, b.expiry_date
     FROM inventory_transactions t
     JOIN stock_batches b ON b.batch_id = t.batch_id
     JOIN items i ON i.item_id = b.item_id
     ORDER BY t.transaction_date DESC, t.transaction_id DESC
     LIMIT 12'
)->fetch_all(MYSQLI_ASSOC);

$editTransaction = null;
if ($page === 'transactions' && isset($_GET['edit_transaction'])) {
    $transactionId = (int) $_GET['edit_transaction'];
    $stmt = $conn->prepare(
        'SELECT t.transaction_id, t.batch_id, t.transaction_type, t.quantity,
                t.unit_cost, t.reference_number, t.notes,
                b.item_id
         FROM inventory_transactions t
         JOIN stock_batches b ON b.batch_id = t.batch_id
         WHERE t.transaction_id = ?'
    );
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $editTransaction = $stmt->get_result()->fetch_assoc() ?: null;
}
$nav = [
    'dashboard' => ['Overview', '⌂'],
    'inventory' => ['Inventory', '▤'],
    'transactions' => ['Transactions', '↕'],
    'suppliers' => ['Suppliers', '♧'],
    'storage' => ['Storage', '▦'],
];
$title = $nav[$page][0];
