<?php
/**
 * AJAX endpoint: Get supervisors by organization.
 */
require_once __DIR__ . '/../includes/functions.php';

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

echo json_encode(get_supervisors_by_organization($pdo, $orgId));
