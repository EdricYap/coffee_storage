CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL UNIQUE,
    unit_of_measure VARCHAR(30) NOT NULL,
    reorder_level DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_perishable TINYINT(1) NOT NULL DEFAULT 0,
    INDEX (category_id),
    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    email VARCHAR(150),
    address VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE storage_locations (
    location_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL UNIQUE,
    location_type VARCHAR(50),
    conditions_note VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_items (
    supplier_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    supplier_sku VARCHAR(80),
    PRIMARY KEY (supplier_id, item_id),
    INDEX (item_id),
    CONSTRAINT fk_supplier_items_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    CONSTRAINT fk_supplier_items_item
        FOREIGN KEY (item_id) REFERENCES items(item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_batches (
    batch_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED,
    location_id INT UNSIGNED NOT NULL,
    received_date DATE NOT NULL,
    expiry_date DATE,
    source_reference VARCHAR(100),
    INDEX (supplier_id),
    INDEX (location_id),
    INDEX idx_batches_item_expiry (item_id, expiry_date),
    CONSTRAINT fk_stock_batches_item
        FOREIGN KEY (item_id) REFERENCES items(item_id),
    CONSTRAINT fk_stock_batches_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    CONSTRAINT fk_stock_batches_location
        FOREIGN KEY (location_id) REFERENCES storage_locations(location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_transactions (
    transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NOT NULL,
    transaction_type ENUM(
        'RECEIPT',
        'USAGE',
        'WASTAGE',
        'ADJUSTMENT_IN',
        'ADJUSTMENT_OUT'
    ) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2),
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(100),
    notes VARCHAR(255),
    INDEX idx_transactions_batch_date (batch_id, transaction_date),
    INDEX idx_transactions_type_date (transaction_type, transaction_date),
    CONSTRAINT fk_inventory_transactions_batch
        FOREIGN KEY (batch_id) REFERENCES stock_batches(batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (category_id, category_name, description) VALUES
    (1, 'Coffee', 'Coffee beans and coffee products'),
    (2, 'Dairy', 'Milk and other dairy products');

INSERT INTO items
    (item_id, category_id, item_name, unit_of_measure, reorder_level, is_perishable)
VALUES
    (1, 1, 'Coffee Beans', 'kg', 2.00, 0),
    (2, 2, 'Milk', 'L', 5.00, 1);

INSERT INTO suppliers (supplier_id, supplier_name, phone, email, address) VALUES
    (1, 'Sample Coffee Supplier', '000-000-0000', 'coffee@example.test', 'Demo address'),
    (2, 'Sample Dairy Supplier', '000-000-0000', 'dairy@example.test', 'Demo address');

INSERT INTO storage_locations (location_id, location_name, location_type, conditions_note) VALUES
    (1, 'Dry Storage', 'Dry', 'Store in a cool, dry place'),
    (2, 'Chiller', 'Refrigerated', 'Keep refrigerated');

INSERT INTO supplier_items (supplier_id, item_id, unit_price) VALUES
    (1, 1, 500.00),
    (2, 2, 100.00);

INSERT INTO stock_batches
    (batch_id, item_id, supplier_id, location_id, received_date, expiry_date, source_reference)
VALUES
    (1, 1, 1, 1, '2026-11-18', NULL, 'SAMPLE-DELIVERY-001'),
    (2, 2, 2, 2, '2026-11-18', '2026-11-25', 'SAMPLE-DELIVERY-001');

INSERT INTO inventory_transactions
    (batch_id, transaction_type, quantity, unit_cost, reference_number, notes)
VALUES
    (1, 'RECEIPT', 10.00, 500.00, 'SAMPLE-DELIVERY-001', 'Sample coffee bean delivery'),
    (2, 'RECEIPT', 20.00, 100.00, 'SAMPLE-DELIVERY-001', 'Sample milk delivery');

CREATE OR REPLACE VIEW item_stock AS
SELECT
    i.item_id,
    i.item_name,
    c.category_name,
    i.unit_of_measure,
    i.reorder_level,
    COALESCE(
        SUM(
            CASE
                WHEN t.transaction_type IN ('RECEIPT', 'ADJUSTMENT_IN')
                    THEN t.quantity
                WHEN t.transaction_type IN ('USAGE', 'WASTAGE', 'ADJUSTMENT_OUT')
                    THEN -t.quantity
                ELSE 0
            END
        ),
        0
    ) AS current_stock
FROM items AS i
JOIN categories AS c ON c.category_id = i.category_id
LEFT JOIN stock_batches AS b ON b.item_id = i.item_id
LEFT JOIN inventory_transactions AS t ON t.batch_id = b.batch_id
GROUP BY
    i.item_id,
    i.item_name,
    c.category_name,
    i.unit_of_measure,
    i.reorder_level;
