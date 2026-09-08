<?php
/**
 * Toggle Organization Active/Inactive Action
 *
 * Switches the is_active flag for an organization.
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

$orgId = (int) ($_POST['organization_id'] ?? 0);
if ($orgId === 0) {
    set_flash('error', 'Invalid organization.');
    redirect('/FMS/admin/organizations.php');
}

// Fetch current state
$stmt = $pdo->prepare("SELECT is_active FROM organizations WHERE id = ?");
$stmt->execute([$orgId]);
$org = $stmt->fetch();
if (!$org) {
    set_flash('error', 'Organization not found.');
    redirect('/FMS/admin/organizations.php');
}

// Toggle the active flag
$newActive = $org['is_active'] ? 0 : 1;
$stmt = $pdo->prepare("UPDATE organizations SET is_active = ? WHERE id = ?");
$stmt->execute([$newActive, $orgId]);

set_flash('success', $newActive ? 'Organization activated.' : 'Organization deactivated.');
redirect('/FMS/admin/organizations.php');
