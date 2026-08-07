<?php
/**
 * Weight-based product variants: carry the chosen variant from the cart through
 * to the order line.
 *
 * Written as PHP rather than .sql because SQLite cannot drop the inline UNIQUE
 * constraints on shopping_cart — those need a table rebuild, while MySQL just
 * drops and re-adds the index. One file, two paths, same result.
 *
 * shopping_cart.variant_id is NOT NULL DEFAULT 0 rather than nullable: it sits in
 * a UNIQUE key, and on both engines NULLs never compare equal, so a nullable
 * column would let the same product be added twice as separate rows. 0 means
 * "the product itself, no variant chosen".
 *
 * order_items.variant_label stores the weight as text at the time of sale. The
 * variant it came from may later be renamed, re-weighed, or deleted, and a past
 * invoice has to keep saying what the customer actually bought.
 *
 * Re-runnable: every step checks first.
 *
 * Usage: /opt/alt/php83/usr/bin/php database/migrations/20260807_weight_variants.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require dirname(__DIR__, 2) . '/config/config.php';
require BASE_PATH . '/core/bootstrap.php';

$db = Database::connection();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Driver: $driver\n";

/** @return string[] */
$columns = static function (string $table) use ($db, $driver): array {
    if ($driver === 'sqlite') {
        return array_column($db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    }
    return array_column($db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
};

$addColumn = static function (string $table, string $column, string $definition) use ($db, $columns): void {
    if (in_array($column, $columns($table), true)) {
        echo "  $table.$column already present\n";
        return;
    }
    $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    echo "  $table.$column added\n";
};

// ---- order_items: remember which variant was sold, and how it was labelled ----
$addColumn('order_items', 'variant_id', 'INT UNSIGNED NULL');
$addColumn('order_items', 'variant_label', 'VARCHAR(60) NULL');

// ---- shopping_cart: the same product in two weights must be two lines ----
$cartHasVariantColumn = in_array('variant_id', $columns('shopping_cart'), true);

if ($driver === 'sqlite' && $cartHasVariantColumn) {
    echo "  shopping_cart.variant_id already present — skipping rebuild\n";
} elseif ($driver === 'sqlite') {
    // Rebuild: the UNIQUE(user_id, product_id) / UNIQUE(cart_token, product_id)
    // constraints are inline, so there is no index to drop.
    $db->exec('PRAGMA foreign_keys = OFF');
    $db->beginTransaction();
    try {
        $db->exec(
            'CREATE TABLE shopping_cart_new (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER NULL,
                cart_token  VARCHAR(64) NULL,
                product_id  INTEGER NOT NULL,
                variant_id  INTEGER NOT NULL DEFAULT 0,
                qty         INT NOT NULL,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE (user_id, product_id, variant_id),
                UNIQUE (cart_token, product_id, variant_id)
            )'
        );
        $db->exec(
            'INSERT INTO shopping_cart_new (id, user_id, cart_token, product_id, variant_id, qty, created_at, updated_at)
             SELECT id, user_id, cart_token, product_id, 0, qty, created_at, updated_at FROM shopping_cart'
        );
        $db->exec('DROP TABLE shopping_cart');
        $db->exec('ALTER TABLE shopping_cart_new RENAME TO shopping_cart');
        $db->commit();
        echo "  shopping_cart rebuilt with variant_id\n";
    } catch (Throwable $e) {
        $db->rollBack();
        $db->exec('PRAGMA foreign_keys = ON');
        throw $e;
    }
    $db->exec('PRAGMA foreign_keys = ON');
} else {
    if (!$cartHasVariantColumn) {
        $db->exec('ALTER TABLE shopping_cart ADD COLUMN variant_id INT UNSIGNED NOT NULL DEFAULT 0');
        echo "  shopping_cart.variant_id added\n";
    }

    // Index repair runs whether or not the column was just added: a half-applied run
    // can leave the column in place with the old narrow indexes still enforcing one
    // row per product, which silently blocks a second weight from reaching the cart.
    // Discover the narrow unique indexes rather than assuming the names in schema.sql:
    // databases built from an older dump carry different ones (uniq_token_product vs
    // uniq_cart_token_product), and leaving one behind silently blocks a second weight
    // of the same product from ever reaching the cart.
    $existing = [];
    foreach ($db->query('SHOW INDEX FROM shopping_cart')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ((int) $row['Non_unique'] === 0 && $row['Key_name'] !== 'PRIMARY') {
            $existing[$row['Key_name']][] = $row['Column_name'];
        }
    }
    // Widened keys go on FIRST. fk_cart_user needs an index led by user_id, and MySQL
    // refuses to drop the last one satisfying a foreign key ("Cannot drop index ...:
    // needed in a foreign key constraint"). Adding the replacement first means the FK
    // is covered at every moment, so the narrow index becomes redundant and droppable.
    foreach (['uniq_cart_user_product' => 'user_id', 'uniq_cart_token_product' => 'cart_token'] as $index => $owner) {
        if (isset($existing[$index])) {
            continue;
        }
        $db->exec("ALTER TABLE shopping_cart ADD UNIQUE KEY $index ($owner, product_id, variant_id)");
        echo "  added widened unique key $index\n";
    }

    foreach ($existing as $name => $cols) {
        sort($cols);
        if ($cols === ['product_id', 'user_id'] || $cols === ['cart_token', 'product_id']) {
            $db->exec("ALTER TABLE shopping_cart DROP INDEX `$name`");
            echo "  dropped narrow unique index $name\n";
        }
    }
}

echo "\nResulting columns:\n";
foreach (['shopping_cart', 'order_items'] as $table) {
    echo "  $table: " . implode(', ', $columns($table)) . "\n";
}
echo "\nDone.\n";
