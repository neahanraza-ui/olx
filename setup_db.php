<?php
// setup_db.php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS olx_marketplace");
    echo "Database 'olx_marketplace' created or already exists.<br>";

    // Connect to the new database
    $pdo->exec("USE olx_marketplace");

    // Read and execute schema.sql
    $schema = file_get_contents('database/schema.sql');
    if ($schema) {
        $pdo->exec($schema);
        echo "Schema imported successfully.<br>";
    } else {
        echo "Error: Could not read database/schema.sql<br>";
    }

    echo "Setup complete. You can now delete this file.";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
