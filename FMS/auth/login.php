<?php
/**
 * Login Page
 *
 * This page handles login for BOTH students and staff users.
 * The identifier is matched against registration numbers and usernames.
 *
 * For staff users, we also check account status (active/suspended/deleted),
 * failed login attempts, and locked-until time.
 */
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to the appropriate dashboard
if (!empty($_SESSION['student_id'])) {
    redirect('/FMS/student/dashboard.php');
}
if (!empty($_SESSION['user_id'])) {
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="login-box" style="margin: 5% auto; max-width: 420px;">
    <div class="card">
        <div class="card-body login-card-body">
            <div class="login-logo text-center mb-3">
                <b>FMS</b>
            </div>
            <p class="login-box-msg">Sign in to start your session</p>

            <form action="/FMS/actions/login.php" method="post">
                <?= csrf_field() ?>

                <div class="input-group mb-3">
                    <input type="text" name="identifier" class="form-control" placeholder="Registration No / Username" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </div>
            </form>

            <p class="mb-1 text-center mt-3">
                <a href="/FMS/auth/register.php">Register as a student</a>
            </p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
