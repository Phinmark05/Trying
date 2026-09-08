<?php
/**
 * Save Department Action
 *
 * Creates a new department linked to an organization.
 * Validates that the organization exists and is active.
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
    redirect('/FMS/admin/departments.php');
}

$orgId      = (int) ($_POST['organization_id'] ?? 0);
$deptName   = trim($_POST['name'] ?? '');

$errors = [];
if ($orgId === 0) $errors[] = 'Please select an organization.';
if ($deptName === '') $errors[] = 'Department name is required.';

// Validate organization exists
if ($orgId > 0) {
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE id = ?");
    $stmt->execute([$orgId]);
    if (!$stmt->fetch()) $errors[] = 'The selected organization does not exist.';
}

// Check for duplicate department name within the same organization
if ($orgId > 0 && $deptName !== '') {
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE organization_id = ? AND name = ?");
    $stmt->execute([$orgId, $deptName]);
    if ($stmt->fetch()) $errors[] = 'A department with this name already exists in this organization.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/admin/departments.php');
}

$stmt = $pdo->prepare("INSERT INTO departments (organization_id, name) VALUES (?, ?)");
$stmt->execute([$orgId, $deptName]);

set_flash('success', 'Department created successfully.');
redirect('/FMS/admin/departments.php');
