<?php
/**
 * Update Staff Profile Action
 *
 * Processes the staff profile form. Updates only the fields the staff
 * user is allowed to change: full_name, email, phone_number, and
 * password (if provided). Role, designation, and username are NOT
 * editable by the staff user.
 */
require_once __DIR__ . '/../includes/functions.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/FMS/auth/login.php');
}

// Must be a logged-in staff user
if (empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

// Verify CSRF token
if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/staff/profile.php');
}

$userId = (int) $_SESSION['user_id'];

// Load current user record
$user = get_user($pdo, $userId);
if (!$user) {
    session_destroy();
    redirect('/FMS/auth/login.php');
}

// Admins should use the admin system, not this form
if (user_has_role($pdo, $userId, 'admin')) {
    redirect('/FMS/admin/dashboard.php');
}

// --- Collect submitted values ---
$fullName       = trim($_POST['full_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phoneNumber   = trim($_POST['phone_number'] ?? '');
$newPassword     = $_POST['new_password'] ?? '';
$newPasswordConf = $_POST['new_password_confirm'] ?? '';

// --- Validate ---
$errors = [];

if ($fullName === '') $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

// Check email uniqueness (excluding current user)
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $userId]);
if ($stmt->fetch()) $errors[] = 'That email is already in use.';

// Validate password change if provided
if ($newPassword !== '') {
    if (strlen($newPassword) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($newPassword !== $newPasswordConf) $errors[] = 'New passwords do not match.';
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    redirect('/FMS/staff/profile.php');
}

// --- Build the update query ---
if ($newPassword !== '') {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE users SET
            full_name = ?, email = ?, phone_number = ?, password = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email,
        $phoneNumber !== '' ? $phoneNumber : null,
        $hashedPassword,
        $userId,
    ]);
} else {
    $stmt = $pdo->prepare("
        UPDATE users SET
            full_name = ?, email = ?, phone_number = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email,
        $phoneNumber !== '' ? $phoneNumber : null,
        $userId,
    ]);
}

set_flash('success', 'Profile updated successfully.');
redirect('/FMS/staff/profile.php');
