<?php
/**
 * FRONT CONTROLLER — the single entry point for the whole application.
 *
 * The web server points at the public/ folder only, so this is the ONLY
 * PHP file a browser can reach. Every request looks like:
 *
 *     index.php?page=login
 *
 * and this file decides which controller method runs. Because all other
 * source files live OUTSIDE public/, nobody can execute a model or view
 * directly from a browser.
 */
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../controllers/AuthController.php';
require __DIR__ . '/../controllers/StudentController.php';

// Which page was asked for? Default to the login page.
$page = $_GET['page'] ?? 'login';

$auth = new AuthController();
$studentController = new StudentController();

switch ($page) {
    case 'login':
        $auth->login();
        break;

    case 'register':
        $auth->register();
        break;

    case 'logout':
        $auth->logout();
        break;

    case 'student_dashboard':
        $studentController->dashboard();
        break;

    case 'profile':
        $studentController->profile();
        break;

    case 'admin_dashboard':
        require_role('admin');
        $pageTitle = 'Admin Dashboard';
        require __DIR__ . '/../views/layouts/header.php';
        echo '<div class="auth-box"><h1>Welcome, ' . e($_SESSION['username']) . '</h1>'
           . '<p>You are logged in as an <strong>administrator</strong>.</p>'
           . '<p><a href="index.php?page=logout">Log out</a></p></div>';
        require __DIR__ . '/../views/layouts/footer.php';
        break;

    default:
        http_response_code(404);
        echo '404 — page not found';
}
