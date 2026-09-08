<?php
/**
 * Save Organization Action
 *
 * Creates a new organization in the `organizations` table.
 * Validates the organization type enum and required fields.
 */
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/organizations.php');
}

$name        = trim($_POST['name'] ?? '');
$type        = $_POST['type'] ?? 'company';
$email       = trim($_POST['email'] ?? '');
$phone       = trim($_POST['phone_number'] ?? '');
$address     = trim($_POST['address'] ?? '');

$validTypes = ['company', 'government', 'ngo', 'university', 'research_institution', 'other'];
if (!in_array($type, $validTypes, true)) $type = 'company';

$errors = [];
if ($name === '') $errors[] = 'Organization name is required.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

// Check for duplicate name+type (database has a UNIQUE constraint on this)
if ($name !== '') {
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = ? AND type = ?");
    $stmt->execute([$name, $type]);
    if ($stmt->fetch()) $errors[] = 'An organization with this name and type already exists.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/organizations.php');
}

$stmt = $pdo->prepare("
    INSERT INTO organizations (name, type, email, phone_number, address)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $type, $email !== '' ? $email : null, $phone !== '' ? $phone : null, $address !== '' ? $address : null]);

set_flash('success', 'Organization created successfully.');
redirect('/FMS/admin/organizations.php');
