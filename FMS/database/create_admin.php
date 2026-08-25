<?php
/**
 * CLI script to create an administrator account.
 * Admins are NEVER created through the public registration form.
 *
 * Usage:
 *     php database/create_admin.php <username> <email> <password>
 */
if (PHP_SAPI !== 'cli') {
    exit('This script can only be run from the command line.');
}
if ($argc !== 4) {
    exit("Usage: php database/create_admin.php <username> <email> <password>\n");
}

require __DIR__ . '/../models/User.php';

[, $username, $email, $password] = $argv;

if (User::exists($username, $email)) {
    exit("A user with that username or email already exists.\n");
}

$id = User::create($username, $email, $password, 'admin');
echo "Admin account created (user id $id).\n";
