<?php /* Shared page top — every view includes this so the HTML skeleton is written once. */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'FMS') ?> — Field Application Management System</title>
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<?php $flash = flash_get(); ?>
<?php if ($flash !== null): ?>
    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
