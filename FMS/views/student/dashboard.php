<?php $pageTitle = 'Student Dashboard'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-box">
    <h1>Welcome, <?= e($_SESSION['username']) ?></h1>

    <?php if ($student === null): ?>
        <p class="errors">Your profile is incomplete. You must complete it
        before you can apply for field attachment.</p>
        <p><a href="index.php?page=profile"><button>Complete my profile</button></a></p>
    <?php else: ?>
        <table class="profile-table">
            <tr><th>Name</th><td><?= e($student['full_name']) ?></td></tr>
            <tr><th>Registration No</th><td><?= e($student['registration_no']) ?></td></tr>
            <tr><th>Institution</th><td><?= e($student['institution_name']) ?></td></tr>
            <tr><th>Course</th><td><?= e($student['course_of_study'] ?? '—') ?></td></tr>
            <tr><th>Study level</th><td><?= e($student['study_level_name'] ?? '—') ?></td></tr>
        </table>
        <p><a href="index.php?page=profile">Edit profile</a></p>
        <p><em>Application status: applications open in a later phase.</em></p>
    <?php endif; ?>

    <p><a href="index.php?page=logout">Log out</a></p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
