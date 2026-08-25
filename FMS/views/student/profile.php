<?php $pageTitle = 'My Profile'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-box">
    <h1><?= $student === null ? 'Complete your profile' : 'Edit your profile' ?></h1>

    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
    // Pre-fill: values just typed (failed validation) win over stored values.
    $value = fn(string $field) => e($_POST[$field] ?? $student[$field] ?? '');
    $selected = fn(string $field, $id) =>
        ((int) ($_POST[$field] ?? $student[$field] ?? 0) === (int) $id) ? 'selected' : '';
    ?>

    <form method="post" action="index.php?page=profile">
        <?= csrf_field() ?>

        <label for="full_name">Full name *</label>
        <input type="text" id="full_name" name="full_name" value="<?= $value('full_name') ?>" required>

        <label for="registration_no">Registration number *</label>
        <input type="text" id="registration_no" name="registration_no" value="<?= $value('registration_no') ?>" required>

        <label for="institution_id">Institution *</label>
        <select id="institution_id" name="institution_id" required>
            <option value="">— select —</option>
            <?php foreach ($institutions as $institution): ?>
                <option value="<?= (int) $institution['id'] ?>" <?= $selected('institution_id', $institution['id']) ?>>
                    <?= e($institution['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="gender">Gender</label>
        <select id="gender" name="gender">
            <option value="">— select —</option>
            <?php foreach (['Male', 'Female', 'Other'] as $gender): ?>
                <option <?= ($_POST['gender'] ?? $student['gender'] ?? '') === $gender ? 'selected' : '' ?>>
                    <?= $gender ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="nationality_id">Nationality</label>
        <select id="nationality_id" name="nationality_id">
            <option value="">— select —</option>
            <?php foreach ($nationalities as $nationality): ?>
                <option value="<?= (int) $nationality['id'] ?>" <?= $selected('nationality_id', $nationality['id']) ?>>
                    <?= e($nationality['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="dob">Date of birth</label>
        <input type="date" id="dob" name="dob" value="<?= $value('dob') ?>">

        <label for="study_level_id">Study level</label>
        <select id="study_level_id" name="study_level_id">
            <option value="">— select —</option>
            <?php foreach ($studyLevels as $level): ?>
                <option value="<?= (int) $level['id'] ?>" <?= $selected('study_level_id', $level['id']) ?>>
                    <?= e($level['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="course_of_study">Course of study</label>
        <input type="text" id="course_of_study" name="course_of_study" value="<?= $value('course_of_study') ?>">

        <button type="submit">Save profile</button>
    </form>

    <p><a href="index.php?page=student_dashboard">Back to dashboard</a></p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
