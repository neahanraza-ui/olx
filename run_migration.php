<?php
// d:/xampp/htdocs/olx1/run_migration.php
require_once 'includes/db.php';

try {
    // Get the migration file from the query string, default to v3_locations
    $migrationFile = $_GET['file'] ?? 'database/migration_v3_locations.sql';

    // Validate the file path to prevent directory traversal
    $allowedFiles = [
        'database/migration_v2_part1.sql',
        'database/migration_v2_part2.sql',
        'database/migration_v3_locations.sql'
    ];

    if (!in_array($migrationFile, $allowedFiles)) {
        throw new Exception("Invalid migration file specified.");
    }

    if (!file_exists($migrationFile)) {
         throw new Exception("Migration file not found: " . htmlspecialchars($migrationFile));
    }

    $sql = file_get_contents($migrationFile);
    if ($sql) {
        $pdo->exec($sql);
        echo "Migration successful for: " . htmlspecialchars($migrationFile) . "<br>";
    } else {
        echo "Migration file is empty or could not be read.";
    }

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
