<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <h1>Reset Password</h1>
    <?php if ($msg = flash('error')): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
    <form method="post" action="index.php?route=auth/resetPassword">
        <label>Token</label>
        <input type="text" name="token" value="<?= e($token ?? '') ?>" required>
        <label>New Password</label>
        <input type="password" name="new_password" required>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>
        <button type="submit">Reset Password</button>
    </form>
    <p><a href="index.php?route=auth/login">Back to login</a></p>
</div>
</body>
</html>
