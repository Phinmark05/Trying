<?php
/**
 * AuthController — handles register, login and logout requests.
 *
 * The flow for every action is the same:
 *   1. Read + validate input from $_POST
 *   2. Ask the model (User) to do the database work
 *   3. Decide what happens next (redirect or show a view with errors)
 */
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    /** Show the registration form (GET) or process it (POST). */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        verify_csrf();

        // trim() removes accidental spaces; never trust raw input.
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        // Server-side validation. The browser's `required` attribute is
        // convenience only — anyone can bypass it, so PHP must re-check.
        $errors = [];
        if ($username === '' || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (empty($errors) && User::exists($username, $email)) {
            $errors[] = 'That username or email is already registered.';
        }

        if (!empty($errors)) {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        // Public registration always creates a STUDENT. Admin accounts are
        // created deliberately (see database/create_admin.php), never via
        // a public form.
        User::create($username, $email, $password, 'student');
        flash_set('success', 'Account created. You can now log in.');
        header('Location: index.php?page=login');
        exit;
    }

    /** Show the login form (GET) or process it (POST). */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        verify_csrf();

        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        $user = User::findByUsernameOrEmail($identifier);

        // password_verify() re-hashes the typed password with the salt
        // stored inside the hash and compares in constant time.
        // NOTE: we use ONE generic error for "no such user" and "wrong
        // password" so an attacker cannot discover which usernames exist.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $errors = ['Invalid username/email or password.'];
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        if ($user['status'] !== 'active') {
            $errors = ['This account is suspended. Contact the coordinator.'];
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        // Prevent SESSION FIXATION: give the user a brand-new session ID
        // at the moment of login, so a pre-login ID an attacker may have
        // planted becomes worthless.
        session_regenerate_id(true);

        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'] ?? 'student';

        User::touchLastLogin((int) $user['id']);

        // Send each role to its own dashboard.
        $target = $_SESSION['role'] === 'admin' ? 'admin_dashboard' : 'student_dashboard';
        header('Location: index.php?page=' . $target);
        exit;
    }

    /** Destroy the session and return to the login page. */
    public function logout(): void
    {
        $_SESSION = [];                    // forget everything server-side
        session_destroy();                 // delete the session file
        setcookie(session_name(), '', time() - 3600); // expire the cookie
        header('Location: index.php?page=login');
        exit;
    }
}
