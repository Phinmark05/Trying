<?php $pageTitle = 'Login'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-box">
    <h1>FMS Login</h1>

    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="index.php?page=login">
        <?= csrf_field() ?>
        <label for="identifier">Username or email</label>
        <input type="text" id="identifier" name="identifier"
               value="<?= e($_POST['identifier'] ?? '') ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Log in</button>
    </form>

    <p>No account yet? <a href="index.php?page=register">Register here</a>.</p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
