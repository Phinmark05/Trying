<?php
/**
 * AJAX endpoint: Get Departments by Organization
 *
 * Returns a JSON array of departments belonging to a given organization.
 * This is called by the JavaScript on the view_application page when
 * the admin selects an organization in the placement form.
 *
 * Requires a logged-in staff user (for security, not public).
 */
require_once __DIR__ . '/../includes/functions.php';

// Must be a logged-in staff user
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$orgId = (int) ($_GET['organization_id'] ?? 0);
if ($orgId === 0) {
    echo json_encode([]);
    exit;
}

$departments = get_departments_by_organization($pdo, $orgId);
echo json_encode($departments);
