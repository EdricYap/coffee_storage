<?php

// Form routing and server-side validation.
$transactionOpen = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedPage = (string) ($_POST['return_page'] ?? 'dashboard');
    if (!in_array($postedPage, $validPages, true)) {
        $postedPage = 'dashboard';
    }

    try {
        if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
            throw new ValidationException('Your session expired. Refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');

        // Item actions
        if ($action === 'save_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $name = postString('item_name');
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $unit = postString('unit_of_measure');
            $reorder = postNumber('reorder_level', 'Reorder level', true);
            $perishable = isset($_POST['is_perishable']) ? 1 : 0;

            if ($name === '' || $categoryId < 1 || $unit === '') {
                throw new ValidationException('Item name, category, and unit are required.');
            }
            if (mb_strlen($name) > 150 || mb_strlen($unit) > 30) {
                throw new ValidationException('Please shorten the item name or unit to fit the allowed length.');
            }

            if ($itemId > 0) {
                $stmt = $conn->prepare(
                    'UPDATE items SET category_id = ?, item_name = ?, unit_of_measure = ?, reorder_level = ?, is_perishable = ? WHERE item_id = ?'
                );
                $stmt->bind_param('issdii', $categoryId, $name, $unit, $reorder, $perishable, $itemId);
                $stmt->execute();
                notify('Item details updated.');
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO items (category_id, item_name, unit_of_measure, reorder_level, is_perishable) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('issdi', $categoryId, $name, $unit, $reorder, $perishable);
                $stmt->execute();
                notify('Item added to your inventory.');
            }
            go('inventory');
        }

        if ($action === 'delete_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);

            $conn->begin_transaction();
            $transactionOpen = true;

            $batchIds = [];
            $stmt = $conn->prepare('SELECT batch_id FROM stock_batches WHERE item_id = ?');
            $stmt->bind_param('i', $itemId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($batch = $result->fetch_assoc()) {
                $batchIds[] = (int) $batch['batch_id'];
            }

            $deleteTransaction = $conn->prepare(
                'DELETE FROM inventory_transactions WHERE batch_id = ?'
            );
            foreach ($batchIds as $batchId) {
                $deleteTransaction->bind_param('i', $batchId);
                $deleteTransaction->execute();
            }

            $stmt = $conn->prepare('DELETE FROM stock_batches WHERE item_id = ?');
            $stmt->bind_param('i', $itemId);
            $stmt->execute();

            $stmt = $conn->prepare('DELETE FROM supplier_items WHERE item_id = ?');
            $stmt->bind_param('i', $itemId);
            $stmt->execute();

            $stmt = $conn->prepare('DELETE FROM items WHERE item_id = ?');
            $stmt->bind_param('i', $itemId);
            $stmt->execute();

            $conn->commit();
            $transactionOpen = false;
            notify('Item deleted.');
            go('inventory');
        }

        // Supplier and supplier-pricing actions
        if ($action === 'save_supplier') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $name = postString('supplier_name');
            $phone = postString('phone');
            $email = postString('email');
            $address = postString('address');
            if ($name === '') {
                throw new ValidationException('Supplier name is required.');
            }
            if ($supplierId > 0) {
                $stmt = $conn->prepare(
                    'UPDATE suppliers SET supplier_name = ?, phone = ?, email = ?, address = ? WHERE supplier_id = ?'
                );
                $stmt->bind_param('ssssi', $name, $phone, $email, $address, $supplierId);
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO suppliers (supplier_name, phone, email, address) VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param('ssss', $name, $phone, $email, $address);
            }
            $stmt->execute();
            notify($supplierId > 0 ? 'Supplier details updated.' : 'Supplier added.');
            go('suppliers');
        }

        if ($action === 'delete_supplier') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);

            $conn->begin_transaction();
            $transactionOpen = true;

            $stmt = $conn->prepare('DELETE FROM supplier_items WHERE supplier_id = ?');
            $stmt->bind_param('i', $supplierId);
            $stmt->execute();

            $stmt = $conn->prepare(
                'UPDATE stock_batches SET supplier_id = NULL WHERE supplier_id = ?'
            );
            $stmt->bind_param('i', $supplierId);
            $stmt->execute();

            $stmt = $conn->prepare('DELETE FROM suppliers WHERE supplier_id = ?');
            $stmt->bind_param('i', $supplierId);
            $stmt->execute();

            $conn->commit();
            $transactionOpen = false;
            notify('Supplier deleted.');
            go('suppliers');
        }

        if ($action === 'save_supplier_item') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $unitPrice = postNumber('supplier_unit_price', 'Unit price', true);
            $originalSupplierId = (int) ($_POST['original_supplier_id'] ?? 0);
            $originalItemId = (int) ($_POST['original_item_id'] ?? 0);
            if ($supplierId < 1 || $itemId < 1) {
                throw new ValidationException('Choose both a supplier and an item.');
            }

            if ($originalSupplierId > 0 && $originalItemId > 0) {
                $stmt = $conn->prepare(
                    'UPDATE supplier_items
                     SET supplier_id = ?, item_id = ?, unit_price = ?
                     WHERE supplier_id = ? AND item_id = ?'
                );
                $stmt->bind_param('iidii', $supplierId, $itemId, $unitPrice, $originalSupplierId, $originalItemId);
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO supplier_items (supplier_id, item_id, unit_price)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE unit_price = VALUES(unit_price)'
                );
                $stmt->bind_param('iid', $supplierId, $itemId, $unitPrice);
            }
            $stmt->execute();
            notify($originalSupplierId > 0 ? 'Supplier item price updated.' : 'Supplier item price saved.');
            go('suppliers');
        }

        if ($action === 'delete_supplier_item') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $stmt = $conn->prepare(
                'DELETE FROM supplier_items WHERE supplier_id = ? AND item_id = ?'
            );
            $stmt->bind_param('ii', $supplierId, $itemId);
            $stmt->execute();
            notify('Supplier item price deleted.');
            go('suppliers');
        }

        // Storage-location actions
        if ($action === 'save_location') {
            $locationId = (int) ($_POST['location_id'] ?? 0);
            $name = postString('location_name');
            $type = postString('location_type');
            $conditions = postString('conditions_note');
            if ($name === '') {
                throw new ValidationException('Storage location name is required.');
            }
            if ($locationId > 0) {
                $stmt = $conn->prepare(
                    'UPDATE storage_locations SET location_name = ?, location_type = ?, conditions_note = ? WHERE location_id = ?'
                );
                $stmt->bind_param('sssi', $name, $type, $conditions, $locationId);
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO storage_locations (location_name, location_type, conditions_note) VALUES (?, ?, ?)'
                );
                $stmt->bind_param('sss', $name, $type, $conditions);
            }
            $stmt->execute();
            notify($locationId > 0 ? 'Storage location updated.' : 'Storage location added.');
            go('storage');
        }

        if ($action === 'delete_location') {
            $locationId = (int) ($_POST['location_id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM storage_locations WHERE location_id = ?');
            $stmt->bind_param('i', $locationId);
            $stmt->execute();
            notify('Storage location deleted.');
            go('storage');
        }

        // Receipt, usage, and wastage actions
        if ($action === 'record_transaction') {
            $type = postString('transaction_type');
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $quantity = postNumber('quantity', 'Quantity');
            $reference = postString('reference_number');
            $notes = postString('notes');
            $unitCostRaw = postString('unit_cost');
            $unitCost = $unitCostRaw === '' ? null : postNumber('unit_cost', 'Unit price', true);

            if (!in_array($type, ['RECEIPT', 'USAGE', 'WASTAGE'], true) || $itemId < 1) {
                throw new ValidationException('Choose a valid item and transaction type.');
            }

            if ($type === 'RECEIPT') {
                $supplierId = (int) ($_POST['supplier_id'] ?? 0);
                $locationId = (int) ($_POST['location_id'] ?? 0);
                $receivedDate = postString('received_date');
                $expiryDate = postString('expiry_date');

                if ($locationId < 1 || $receivedDate === '') {
                    throw new ValidationException('Choose a storage location and received date for a delivery.');
                }
                $stmt = $conn->prepare('SELECT is_perishable FROM items WHERE item_id = ?');
                $stmt->bind_param('i', $itemId);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                if (!$item) {
                    throw new ValidationException('The selected item could not be found.');
                }
                if ((int) $item['is_perishable'] === 1 && $expiryDate === '') {
                    throw new ValidationException('An expiry date is required for perishable items.');
                }
                if ($expiryDate !== '' && $expiryDate < $receivedDate) {
                    throw new ValidationException('Expiry date cannot be before the received date.');
                }

                $conn->begin_transaction();
                $transactionOpen = true;
                $sourceReference = $reference !== '' ? $reference : null;
                $supplierValue = $supplierId > 0 ? $supplierId : null;
                $expiryValue = $expiryDate !== '' ? $expiryDate : null;
                $stmt = $conn->prepare(
                    'INSERT INTO stock_batches (item_id, supplier_id, location_id, received_date, expiry_date, source_reference) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('iiisss', $itemId, $supplierValue, $locationId, $receivedDate, $expiryValue, $sourceReference);
                $stmt->execute();
                $batchId = (int) $conn->insert_id;
                $stmt = $conn->prepare(
                    "INSERT INTO inventory_transactions (batch_id, transaction_type, quantity, unit_cost, reference_number, notes) VALUES (?, 'RECEIPT', ?, ?, ?, ?)"
                );
                $stmt->bind_param('iddss', $batchId, $quantity, $unitCost, $reference, $notes);
                $stmt->execute();
                $conn->commit();
                $transactionOpen = false;
                notify('Delivery recorded and stock updated.');
            } else {
                $conn->begin_transaction();
                $transactionOpen = true;
                $stmt = $conn->prepare(
                    "SELECT b.batch_id, b.expiry_date,
                        COALESCE(SUM(CASE
                            WHEN t.transaction_type IN ('RECEIPT', 'ADJUSTMENT_IN') THEN t.quantity
                            WHEN t.transaction_type IN ('USAGE', 'WASTAGE', 'ADJUSTMENT_OUT') THEN -t.quantity
                            ELSE 0
                        END), 0) AS available
                     FROM stock_batches b
                     LEFT JOIN inventory_transactions t ON t.batch_id = b.batch_id
                     WHERE b.item_id = ?
                     GROUP BY b.batch_id, b.expiry_date
                     ORDER BY b.expiry_date IS NULL, b.expiry_date, b.batch_id
                     FOR UPDATE"
                );
                $stmt->bind_param('i', $itemId);
                $stmt->execute();
                $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $remaining = $quantity;
                $allocations = [];
                foreach ($batches as $batch) {
                    if ($type === 'USAGE' && $batch['expiry_date'] !== null && $batch['expiry_date'] < date('Y-m-d')) {
                        continue;
                    }
                    $available = (float) $batch['available'];
                    if ($available <= 0) {
                        continue;
                    }
                    $take = min($available, $remaining);
                    $allocations[] = [(int) $batch['batch_id'], $take];
                    $remaining -= $take;
                    if ($remaining <= 0.00001) {
                        break;
                    }
                }

                if ($remaining > 0.00001) {
                    $conn->rollback();
                    $transactionOpen = false;
                    throw new ValidationException(
                        $type === 'USAGE'
                            ? 'Not enough unexpired stock for this usage. The system will not let stock go below zero.'
                            : 'Not enough stock for this wastage. The system will not let stock go below zero.'
                    );
                }

                $stmt = $conn->prepare(
                    'INSERT INTO inventory_transactions (batch_id, transaction_type, quantity, reference_number, notes) VALUES (?, ?, ?, ?, ?)'
                );
                foreach ($allocations as [$batchId, $allocatedQuantity]) {
                    $stmt->bind_param('isdss', $batchId, $type, $allocatedQuantity, $reference, $notes);
                    $stmt->execute();
                }
                $conn->commit();
                $transactionOpen = false;
                notify(($type === 'USAGE' ? 'Usage' : 'WASTAGE') . ' recorded using earliest-expiring stock first.');
            }

            go('transactions');
        }

        if ($action === 'update_transaction') {
            $transactionId = (int) ($_POST['transaction_id'] ?? 0);
            $quantity = postNumber('quantity', 'Quantity');
            $reference = postString('reference_number');
            $notes = postString('notes');
            $unitCostRaw = postString('unit_cost');
            $unitCost = $unitCostRaw === '' ? null : postNumber('unit_cost', 'Unit price', true);

            $stmt = $conn->prepare(
                'SELECT transaction_type, batch_id, quantity
                 FROM inventory_transactions
                 WHERE transaction_id = ?'
            );
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            if (!$old) {
                throw new ValidationException('The transaction could not be found.');
            }

            $conn->begin_transaction();
            $transactionOpen = true;

            $batchId = (int) $old['batch_id'];
            $oldType = (string) $old['transaction_type'];
            $oldQuantity = (float) $old['quantity'];

            $stmt = $conn->prepare(
                'SELECT COALESCE(SUM(CASE
                    WHEN transaction_type IN (\'RECEIPT\', \'ADJUSTMENT_IN\') THEN quantity
                    WHEN transaction_type IN (\'USAGE\', \'WASTAGE\', \'ADJUSTMENT_OUT\') THEN -quantity
                    ELSE 0
                 END), 0) AS available
                 FROM inventory_transactions
                 WHERE batch_id = ?'
            );
            $stmt->bind_param('i', $batchId);
            $stmt->execute();
            $available = (float) $stmt->get_result()->fetch_assoc()['available'];

            if (in_array($oldType, ['RECEIPT', 'ADJUSTMENT_IN'], true)) {
                $available -= $oldQuantity;
            } else {
                $available += $oldQuantity;
            }

            if (in_array($oldType, ['USAGE', 'WASTAGE', 'ADJUSTMENT_OUT'], true)) {
                if ($quantity > $available) {
                    $conn->rollback();
                    $transactionOpen = false;
                    throw new ValidationException('This quantity would make the batch stock negative.');
                }
            } elseif ($oldType === 'RECEIPT') {
                if ($available + $quantity < 0) {
                    $conn->rollback();
                    $transactionOpen = false;
                    throw new ValidationException('This receipt is too small for stock already used or wasted.');
                }
            }

            $stmt = $conn->prepare(
                'UPDATE inventory_transactions
                 SET quantity = ?, unit_cost = ?, reference_number = ?, notes = ?
                 WHERE transaction_id = ?'
            );
            $stmt->bind_param('ddssi', $quantity, $unitCost, $reference, $notes, $transactionId);
            $stmt->execute();

            $conn->commit();
            $transactionOpen = false;
            notify('Transaction updated.');
            go('transactions');
        }

        if ($action === 'delete_transaction') {
            $transactionId = (int) ($_POST['transaction_id'] ?? 0);

            $stmt = $conn->prepare(
                'SELECT transaction_type, batch_id, quantity
                 FROM inventory_transactions
                 WHERE transaction_id = ?'
            );
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            if (!$old) {
                throw new ValidationException('The transaction could not be found.');
            }

            $conn->begin_transaction();
            $transactionOpen = true;

            $batchId = (int) $old['batch_id'];
            $oldType = (string) $old['transaction_type'];
            $oldQuantity = (float) $old['quantity'];

            $stmt = $conn->prepare(
                'SELECT COALESCE(SUM(CASE
                    WHEN transaction_type IN (\'RECEIPT\', \'ADJUSTMENT_IN\') THEN quantity
                    WHEN transaction_type IN (\'USAGE\', \'WASTAGE\', \'ADJUSTMENT_OUT\') THEN -quantity
                    ELSE 0
                 END), 0) AS available
                 FROM inventory_transactions
                 WHERE batch_id = ?'
            );
            $stmt->bind_param('i', $batchId);
            $stmt->execute();
            $available = (float) $stmt->get_result()->fetch_assoc()['available'];

            if (in_array($oldType, ['RECEIPT', 'ADJUSTMENT_IN'], true)) {
                $available -= $oldQuantity;
                if ($available < 0) {
                    $conn->rollback();
                    $transactionOpen = false;
                    throw new ValidationException('This receipt cannot be deleted because stock was already used or wasted.');
                }
            }

            $stmt = $conn->prepare('DELETE FROM inventory_transactions WHERE transaction_id = ?');
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();

            $stmt = $conn->prepare(
                'SELECT COUNT(*) AS total
                 FROM inventory_transactions
                 WHERE batch_id = ?'
            );
            $stmt->bind_param('i', $batchId);
            $stmt->execute();
            $remaining = (int) $stmt->get_result()->fetch_assoc()['total'];
            if ($remaining === 0) {
                $stmt = $conn->prepare('DELETE FROM stock_batches WHERE batch_id = ?');
                $stmt->bind_param('i', $batchId);
                $stmt->execute();
            }

            $conn->commit();
            $transactionOpen = false;
            notify('Transaction deleted.');
            go('transactions');
        }

        throw new ValidationException('That action is not available.');
    } catch (ValidationException $error) {
        if ($transactionOpen) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                error_log('Coffee Storage rollback error: ' . $rollbackError->getMessage());
            }
        }
        notify($error->getMessage(), 'error');
        go($postedPage, isset($_POST['item_id']) ? ['edit' => (int) $_POST['item_id']] : []);
    } catch (Throwable $error) {
        if ($transactionOpen) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                error_log('Coffee Storage rollback error: ' . $rollbackError->getMessage());
            }
        }
        error_log('Coffee Storage app error: ' . $error->getMessage());
        if ((string) $error->getCode() === '1062') {
            $message = 'That name already exists. Please use a different name.';
        } elseif ((string) $error->getCode() === '1451') {
            $message = 'This record is linked to stock history and cannot be deleted.';
        } else {
            $message = 'We could not save that change. Check the required fields and try again.';
        }
        notify($message, 'error');
        go($postedPage);
    }
}
