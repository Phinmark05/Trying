<?php $pageTitle = 'Register'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-box">
    <h1>Create a Student Account</h1>

    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="index.php?page=register">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?= e($_POST['username'] ?? '') ?>" required minlength="3">

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>" required>

        <label for="password">Password (min 8 characters)</label>
        <input type="password" id="password" name="password" required minlength="8">

        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">

        <button type="submit">Register</button>
    </form>

    <p>Already registered? <a href="index.php?page=login">Log in</a>.</p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
