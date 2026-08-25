<?php
/**
 * Session bootstrap + small helpers used on every page.
 *
 * A PHP SESSION is how the server remembers who you are between requests.
 * HTTP itself is stateless: every page load is a brand-new conversation.
 * session_start() gives the visitor a random session ID (stored in a
 * cookie) and PHP keeps a matching $_SESSION array on the SERVER.
 * We only ever store the user's id and role there — never the password.
 */

// Harden the session cookie BEFORE starting the session.
session_set_cookie_params([
    'httponly' => true,   // JavaScript cannot read the cookie (blocks XSS cookie theft)
    'samesite' => 'Lax',  // cookie is not sent on cross-site POSTs (helps against CSRF)
]);
session_start();

/** Escape output for HTML. ALWAYS use this when echoing user data. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Is somebody logged in? */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Stop the request unless the visitor is logged in. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: index.php?page=login');
        exit;
    }
}

/** Stop the request unless the logged-in user has the given role. */
function require_role(string $role): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('403 Forbidden — you do not have permission to view this page.');
    }
}

/**
 * CSRF protection.
 * Every form gets a hidden random token; on submit we check it matches the
 * one in the session. A malicious site can make your browser POST to us,
 * but it cannot read our page, so it can never know the token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

/** One-time messages shown on the NEXT page load (e.g. "Account created!"). */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']); // show it only once
        return $flash;
    }
    return null;
}
