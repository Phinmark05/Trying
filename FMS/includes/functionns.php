<?php
/**
 * Helper Functions for FMS
 * 
 * These functions are used throughout the application for common tasks:
 * - Escaping output to prevent XSS
 * - Generating CSRF tokens and verifying them
 * - Flash messages (one-time session messages for success/error feedback)
 * - Redirecting safely
 * - Generating unique application reference numbers
 * - Checking roles and permissions
 */

require_once __DIR__ . '/database.php';

/**
 * Escape output for safe HTML display.
 * This prevents Cross-Site Scripting (XSS) by converting special
 * characters to HTML entities.
 */
function e($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate and store a CSRF token in the session.
 * The token is a random string that must be submitted with POST forms
 * to verify the request genuinely came from our own site.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden input field containing the CSRF token.
 * Place this inside every <form> that processes POST actions.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify the CSRF token submitted with a POST request.
 * If the token does not match (or is missing), the request is rejected.
 */
function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Store a one-time "flash" message in the session.
 * The message is displayed once on the next page load, then cleared.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message.
 * Returns null if no message is set.
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect to a given path and stop execution.
 * Always call exit() after sending the Location header to prevent
 * further code from running after the redirect.
 */
function redirect(string $path): void
{
    header("Location: " . $path);
    exit;
}

/**
 * Generate a unique application reference number.
 * Format: FMS-YYYYMMDD-XXXXX (date + random suffix)
 * The uniqueness is enforced by the database column, but we
 * generate a candidate that is very unlikely to collide.
 */
function generate_reference_number(): string
{
    return 'FMS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
}

/**
 * Get the client's IP address for audit logging.
 * We check multiple headers because the app may run behind a proxy.
 */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get the user's browser user agent string for audit logging.
 * Truncated to fit the database column length (255 chars).
 */
function user_agent(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return substr($ua, 0, 255);
}

/**
 * Check whether the logged-in staff user has a given role name.
 * Roles are stored in the `roles` table and linked via `user_roles`.
 */
function user_has_role(PDO $pdo, int $userId, string $roleName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ? AND r.name = ?
    ");
    $stmt->execute([$userId, $roleName]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Check whether the logged-in staff user has a given permission description.
 * Permissions are linked to roles via `role_permissions`.
 */
function user_has_permission(PDO $pdo, int $userId, string $permissionDesc): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_roles ur
        JOIN role_permissions rp ON ur.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = ? AND p.description = ?
    ");
    $stmt->execute([$userId, $permissionDesc]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get all role names for a given user, as a simple array.
 */
function get_user_roles(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT r.name FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Create an audit log entry for an application action.
 * Uses JSON for old_values and new_values as specified in the schema.
 */
function log_application_action(
    PDO $pdo,
    int $applicationId,
    ?int $userId,
    string $action,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO application_audit_logs
            (application_id, user_id, action, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $userId,
        $action,
        $oldValues !== null ? json_encode($oldValues) : null,
        $newValues !== null ? json_encode($newValues) : null,
        client_ip(),
        user_agent(),
    ]);
}

/**
 * Create an audit log entry for a placement action.
 */
function log_placement_action(
    PDO $pdo,
    int $placementId,
    ?int $userId,
    string $action,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO placement_audit_logs
            (placement_id, user_id, action, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $placementId,
        $userId,
        $action,
        $oldValues !== null ? json_encode($oldValues) : null,
        $newValues !== null ? json_encode($newValues) : null,
        client_ip(),
        user_agent(),
    ]);
}

/**
 * Create a comment linked to an application (polymorphic comments table).
 * commentable_type is 'application' for application comments.
 */
function add_comment(
    PDO $pdo,
    ?int $userId,
    ?int $studentId,
    string $commentableType,
    int $commentableId,
    string $commentText
): int {
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, student_id, commentable_type, commentable_id, comment_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $studentId, $commentableType, $commentableId, $commentText]);
    return (int) $pdo->lastInsertId();
}

/**
 * Get all comments for a given commentable entity (e.g. an application).
 */
function get_comments(PDO $pdo, string $commentableType, int $commentableId): array
{
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.full_name AS staff_name,
               s.full_name AS student_name
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN students s ON c.student_id = s.id
        WHERE c.commentable_type = ? AND c.commentable_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$commentableType, $commentableId]);
    return $stmt->fetchAll();
}

/**
 * Get all audit logs for a given application, newest first.
 */
function get_application_audit_logs(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name AS user_name
        FROM application_audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.application_id = ?
        ORDER BY al.created_at DESC
    ");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}

/**
 * Get all audit logs for a given placement, newest first.
 */
function get_placement_audit_logs(PDO $pdo, int $placementId): array
{
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name AS user_name
        FROM placement_audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.placement_id = ?
        ORDER BY al.created_at DESC
    ");
    $stmt->execute([$placementId]);
    return $stmt->fetchAll();
}

/**
 * Get the current application status counts for the admin dashboard.
 * Returns an associative array of status => count.
 */
function get_application_status_counts(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM applications
        GROUP BY status
    ");
    $results = $stmt->fetchAll();
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['status']] = (int) $row['cnt'];
    }
    return $counts;
}

/**
 * Get the active application windows for display.
 */
function get_active_application_windows(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT * FROM application_windows
        WHERE is_active = 1
          AND open_date <= NOW()
          AND close_date >= NOW()
        ORDER BY close_date ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get all application windows (for admin management).
 */
function get_all_application_windows(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT aw.*, u.full_name AS creator_name
        FROM application_windows aw
        LEFT JOIN users u ON aw.created_by = u.id
        ORDER BY aw.open_date DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Get all organizations.
 */
function get_all_organizations(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM organizations ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get all departments for a given organization.
 */
function get_departments_by_organization(PDO $pdo, int $orgId): array
{
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE organization_id = ? ORDER BY name ASC");
    $stmt->execute([$orgId]);
    return $stmt->fetchAll();
}

/**
 * Get all departments (with their organization name).
 */
function get_all_departments(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT d.*, o.name AS organization_name
        FROM departments d
        JOIN organizations o ON d.organization_id = o.id
        ORDER BY o.name ASC, d.name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get all specializations (active only by default).
 */
function get_specializations(PDO $pdo, bool $activeOnly = true): array
{
    $sql = "SELECT * FROM specializations";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Get specializations selected for a given application.
 */
function get_application_specializations(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT s.* FROM application_specializations aps
        JOIN specializations s ON aps.specialization_id = s.id
        WHERE aps.application_id = ?
    ");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}

/**
 * Get all training types.
 */
function get_training_types(PDO $pdo, bool $activeOnly = true): array
{
    $sql = "SELECT * FROM training_types";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Get all study levels.
 */
function get_study_levels(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM study_levels ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get all nationalities.
 */
function get_nationalities(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM nationalities ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get all staff users who have the supervisor role.
 */
function get_supervisors(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.* FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name IN ('academic_supervisor', 'industrial_supervisor', 'supervisor')
          AND u.status = 'active'
        ORDER BY u.full_name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get all staff users (for admin management).
 */
function get_all_users(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') AS role_names
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.deleted_at IS NULL
        GROUP BY u.id
        ORDER BY u.full_name ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get all students (for admin management).
 */
function get_all_students(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM students ORDER BY full_name ASC");
    return $stmt->fetchAll();
}

/**
 * Get a single student by ID.
 */
function get_student(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get a single user by ID.
 */
function get_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get a single application by ID, with related student info.
 */
function get_application(PDO $pdo, int $applicationId): ?array
{
    $stmt = $pdo->prepare("
        SELECT a.*, 
               s.full_name AS student_name,
               s.registration_no,
               s.email AS student_email,
               s.course_of_study,
               s.gender,
               tw.name AS window_name,
               tt.name AS training_type_name,
               sl.name AS study_level_name
        FROM applications a
        JOIN students s ON a.student_id = s.id
        LEFT JOIN application_windows tw ON a.application_window_id = tw.id
        LEFT JOIN training_types tt ON a.training_type_id = tt.id
        LEFT JOIN study_levels sl ON a.study_level_id = sl.id
        WHERE a.id = ?
    ");
    $stmt->execute([$applicationId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get a single placement by ID, with related names.
 */
function get_placement(PDO $pdo, int $placementId): ?array
{
    $stmt = $pdo->prepare("
        SELECT p.*,
               a.reference_number,
               s.full_name AS student_name,
               s.registration_no,
               o.name AS organization_name,
               d.name AS department_name,
               au.full_name AS academic_supervisor_name,
               iu.full_name AS industrial_supervisor_name
        FROM placements p
        JOIN applications a ON p.application_id = a.id
        JOIN students s ON a.student_id = s.id
        JOIN organizations o ON p.organization_id = o.id
        JOIN departments d ON p.department_id = d.id
        LEFT JOIN users au ON p.academic_supervisor_id = au.id
        LEFT JOIN users iu ON p.industrial_supervisor_id = iu.id
        WHERE p.id = ?
    ");
    $stmt->execute([$placementId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get the placement for a given application, if one exists.
 */
function get_placement_by_application(PDO $pdo, int $applicationId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM placements WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get the placement for a given student (via their application).
 */
function get_placement_by_student(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare("
        SELECT p.*,
               a.reference_number,
               o.name AS organization_name,
               d.name AS department_name,
               au.full_name AS academic_supervisor_name,
               iu.full_name AS industrial_supervisor_name
        FROM placements p
        JOIN applications a ON p.application_id = a.id
        JOIN organizations o ON p.organization_id = o.id
        JOIN departments d ON p.department_id = d.id
        LEFT JOIN users au ON p.academic_supervisor_id = au.id
        LEFT JOIN users iu ON p.industrial_supervisor_id = iu.id
        WHERE a.student_id = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Get reviews for a given application.
 */
function get_application_reviews(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name AS reviewer_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.application_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}

/**
 * Get the current student's applications.
 */
function get_student_applications(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare("
        SELECT a.*,
               tw.name AS window_name,
               tt.name AS training_type_name,
               sl.name AS study_level_name
        FROM applications a
        LEFT JOIN application_windows tw ON a.application_window_id = tw.id
        LEFT JOIN training_types tt ON a.training_type_id = tt.id
        LEFT JOIN study_levels sl ON a.study_level_id = sl.id
        WHERE a.student_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

/**
 * Get all roles (for admin user management).
 */
function get_all_roles(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get roles for a given user.
 */
function get_user_role_ids(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get total student count.
 */
function count_students(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
}

/**
 * Get total application count.
 */
function count_applications(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
}

/**
 * Get total placement count.
 */
function count_placements(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn();
}

/**
 * Get total organization count.
 */
function count_organizations(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
}

/**
 * Get total active application window count.
 */
function count_active_windows(PDO $pdo): int
{
    return (int) $pdo->query("
        SELECT COUNT(*) FROM application_windows
        WHERE is_active = 1 AND open_date <= NOW() AND close_date >= NOW()
    ")->fetchColumn();
}

/**
 * Format a date/time for display. Returns a readable string.
 */
function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (empty($datetime)) return '—';
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

/**
 * Format a date/time with time for display.
 */
function format_datetime(?string $datetime): string
{
    return format_date($datetime, 'M j, Y g:i A');
}

/**
 * Convert a status string to a Bootstrap CSS class for badges.
 */
function status_badge_class(string $status): string
{
    return match ($status) {
        'draft'                   => 'bg-secondary',
        'submitted'               => 'bg-info',
        'under_review'            => 'bg-warning',
        'returned_for_correction' => 'bg-warning text-dark',
        'accepted'                => 'bg-success',
        'rejected'                => 'bg-danger',
        'cancelled'               => 'bg-dark',
        'placement_assigned'      => 'bg-primary',
        'in_training'             => 'bg-info',
        'completed'               => 'bg-success',
        default                   => 'bg-secondary',
    };
}

/**
 * Convert a status string to a human-readable label.
 */
function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

// ---------------------------------------------------------------------------
// MULTI-STAGE REVIEW WORKFLOW HELPERS
//
// The application review pipeline is:
//   Student submits
//     → Secretary (checks completeness)
//     → Field Coordinator (verifies academic details)
//     → HOD (approves or rejects)
//     → Placement Officer (assigns placement)
//     → Done
//
// Each stage maps to a role. Only users with that role (or admin) can act.
// ---------------------------------------------------------------------------

/**
 * Ordered list of review stages. The order defines the pipeline.
 */
function review_stages(): array
{
    return ['secretary', 'field_coordinator', 'hod', 'placement_officer'];
}

/**
 * Map each stage to the role that is allowed to act on it.
 */
function stage_to_role(): array
{
    return [
        'secretary'         => 'secretary',
        'field_coordinator' => 'field_coordinator',
        'hod'               => 'hod',
        'placement_officer' => 'placement_officer',
    ];
}

/**
 * Map each stage to the permission required to act on it.
 */
function stage_to_permission(): array
{
    return [
        'secretary'         => 'review_stage_secretary',
        'field_coordinator' => 'review_stage_field_coordinator',
        'hod'               => 'review_stage_hod',
        'placement_officer' => 'review_stage_placement',
    ];
}

/**
 * Human-readable label for a review stage.
 */
function stage_label(string $stage): string
{
    $labels = [
        'secretary'         => 'Secretary',
        'field_coordinator' => 'Field Coordinator',
        'hod'               => 'HOD',
        'placement_officer' => 'Placement Officer',
        'done'              => 'Completed',
    ];
    return $labels[$stage] ?? ucwords(str_replace('_', ' ', $stage));
}

/**
 * Bootstrap badge class for a review stage.
 */
function stage_badge_class(string $stage): string
{
    return match ($stage) {
        'secretary'         => 'bg-info',
        'field_coordinator' => 'bg-primary',
        'hod'               => 'bg-warning text-dark',
        'placement_officer' => 'bg-success',
        'done'              => 'bg-secondary',
        default             => 'bg-secondary',
    };
}

/**
 * The next stage after a given stage, or 'done' if there is no next.
 */
function next_stage(string $currentStage): string
{
    $stages = review_stages();
    $idx = array_search($currentStage, $stages, true);
    if ($idx === false || $idx === count($stages) - 1) {
        return 'done';
    }
    return $stages[$idx + 1];
}

/**
 * Check whether the logged-in staff user can act on the application's
 * current review stage. Admins can act on any stage.
 */
function can_user_act_on_stage(PDO $pdo, int $userId, string $stage): bool
{
    if ($stage === 'done') return false;
    if (user_has_role($pdo, $userId, 'admin')) return true;
    $map = stage_to_role();
    $requiredRole = $map[$stage] ?? null;
    if (!$requiredRole) return false;
    return user_has_role($pdo, $userId, $requiredRole);
}

/**
 * Check whether the logged-in staff user can act on a specific stage
 * using the permission system (alternative to role check).
 */
function can_user_act_on_stage_by_permission(PDO $pdo, int $userId, string $stage): bool
{
    if ($stage === 'done') return false;
    $map = stage_to_permission();
    $requiredPerm = $map[$stage] ?? null;
    if (!$requiredPerm) return false;
    return user_has_permission($pdo, $userId, $requiredPerm);
}

/**
 * Get applications filtered by review stage (for the applications list).
 */
function get_application_stage_counts(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT current_review_stage, COUNT(*) AS cnt
        FROM applications
        WHERE status NOT IN ('draft', 'rejected', 'cancelled')
        GROUP BY current_review_stage
    ");
    $results = $stmt->fetchAll();
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['current_review_stage']] = (int) $row['cnt'];
    }
    return $counts;
}

/**
 * Get the review stage(s) the current user is responsible for.
 * Returns an array of stage names (may be empty for non-reviewers).
 */
function get_user_stages(PDO $pdo, int $userId): array
{
    if (user_has_role($pdo, $userId, 'admin')) {
        return review_stages();
    }
    $stages = [];
    $map = stage_to_role();
    foreach ($map as $stage => $roleName) {
        if (user_has_role($pdo, $userId, $roleName)) {
            $stages[] = $stage;
        }
    }
    return $stages;
}

/**
 * Count applications waiting for action at the given user's stage(s).
 */
function count_pending_for_user(PDO $pdo, int $userId): int
{
    $stages = get_user_stages($pdo, $userId);
    if (empty($stages)) return 0;
    $placeholders = implode(',', array_fill(0, count($stages), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications
        WHERE status IN ('submitted', 'under_review', 'accepted')
          AND current_review_stage IN ($placeholders)
    ");
    $stmt->execute($stages);
    return (int) $stmt->fetchColumn();
}

/**
 * Get applications waiting for action at the given user's stage(s).
 */
function get_pending_for_user(PDO $pdo, int $userId): array
{
    $stages = get_user_stages($pdo, $userId);
    if (empty($stages)) return [];
    $placeholders = implode(',', array_fill(0, count($stages), '?'));
    $stmt = $pdo->prepare("
        SELECT a.*, s.full_name AS student_name, s.registration_no,
               tw.name AS window_name, tt.name AS training_type_name
        FROM applications a
        JOIN students s ON a.student_id = s.id
        LEFT JOIN application_windows tw ON a.application_window_id = tw.id
        LEFT JOIN training_types tt ON a.training_type_id = tt.id
        WHERE a.status IN ('submitted', 'under_review', 'accepted')
          AND a.current_review_stage IN ($placeholders)
        ORDER BY a.created_at ASC
    ");
    $stmt->execute($stages);
    return $stmt->fetchAll();
}
