<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Database Migration Runner
 * =============================================================================
 * Run this to execute database migrations
 * Usage: php migrate.php
 * =============================================================================
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Utils/Database.php';

use App\Utils\Database;

$config = require __DIR__ . '/config/app.php';

echo "TiredOfDoinTM - Database Migration\n";
echo "==================================\n\n";

try {
    // Initialize database
    Database::init($config['database']);

    echo "✓ Connected to database\n\n";

    // Create migrations tracking table
    Database::execute("
        CREATE TABLE IF NOT EXISTS migrations (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) UNIQUE NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Get executed migrations
    $executed = [];
    try {
        $rows = Database::query("SELECT name FROM migrations ORDER BY name");
        foreach ($rows as $row) {
            $executed[] = $row['name'];
        }
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Find migration files
    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    $pending = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (!in_array($name, $executed)) {
            $pending[] = $file;
        }
    }

    if (empty($pending)) {
        echo "No pending migrations.\n";
        exit(0);
    }

    echo "Pending migrations: " . count($pending) . "\n\n";

    // Run each migration
    foreach ($pending as $file) {
        $name = basename($file);
        echo "Running: $name ... ";

        $sql = file_get_contents($file);

        Database::beginTransaction();
        try {
            // Split by semicolons and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    Database::connect()->exec($statement);
                }
            }

            // Record migration
            Database::insert('migrations', ['name' => $name]);

            Database::commit();
            echo "✓\n";
        } catch (Exception $e) {
            Database::rollback();
            echo "✗\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\n✓ All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
