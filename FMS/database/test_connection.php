<?php
/**
 * PHASE 3 TEST SCRIPT — run from the FMS folder:
 *
 *     php database/test_connection.php
 *
 * It proves three things:
 *   1. PHP can reach MySQL with your config.php credentials.
 *   2. Prepared statements work (the ? placeholder is filled in safely).
 *   3. The seed data from Phase 1 is really in the database.
 */
require __DIR__ . '/../config/Database.php';

$pdo = Database::getConnection();
echo "1. Connection OK\n";

// A prepared statement: the SQL is sent first with a ? placeholder,
// then the value 'student' is sent separately. Even if this value came
// from a user, it could never be executed as SQL.
$statement = $pdo->prepare('SELECT id, name, description FROM roles WHERE name = ?');
$statement->execute(['student']);
$role = $statement->fetch();

if ($role === false) {
    echo "2. FAILED: 'student' role not found. Did you run database/seed.sql?\n";
    exit(1);
}
echo "2. Prepared statement OK — found role: {$role['name']} (id {$role['id']})\n";

$count = $pdo->query('SELECT COUNT(*) AS total FROM roles')->fetch();
echo "3. roles table contains {$count['total']} rows\n";
echo "All Phase 3 checks passed.\n";
