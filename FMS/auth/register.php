<?php
/**
 * Student Registration Page
 *
 * Students register themselves using their registration number and email.
 * The password is hashed with password_hash() before storage.
 *
 * Only students register via this page. Staff/admin/supervisor accounts
 * are created by an administrator (via the admin users page).
 */
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect away
if (!empty($_SESSION['student_id'])) {
    redirect('/FMS/student/dashboard.php');
}
if (!empty($_SESSION['user_id'])) {
    redirect('/FMS/admin/dashboard.php');
}

// Load lookup data needed for the registration form
$nationalities = get_nationalities($pdo);
$studyLevels   = get_study_levels($pdo);

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>
<div class="login-box" style="margin: 3% auto; max-width: 520px;">
    <div class="card">
        <div class="card-body register-card-body">
            <div class="text-center mb-3"><b>LinkFlow</b> Registration</div>

            <form action="/FMS/actions/register.php" method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="registration_no" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirm" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">— Select —</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>
                <div class="form-group">
                    <label>Nationality</label>
                    <select name="nationality_id" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach ($nationalities as $n): ?>
                            <option value="<?= (int) $n['id'] ?>"><?= e($n['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Study Level</label>
                    <select name="study_level_id" class="form-control">
                        <option value="">— Select —</option>
                        <?php foreach ($studyLevels as $sl): ?>
                            <option value="<?= (int) $sl['id'] ?>"><?= e($sl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Course of Study</label>
                    <input type="text" name="course_of_study" class="form-control">
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                </div>
            </form>
            <p class="mb-1 text-center mt-3">
                <a href="/FMS/auth/login.php">Already have an account? Login</a>
            </p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
