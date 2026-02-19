<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Billing System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <h1>Admin Login</h1>
    <p class="muted">Essential Oils Billing Portal</p>
    <?php if ($msg = flash('error')): ?>
        <div class="alert error"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('success')): ?>
        <div class="alert success"><?= e($msg) ?></div>
    <?php endif; ?>
    <form method="post" action="index.php?route=auth/login">
        <label>Email or Username</label>
        <input type="text" name="identifier" value="<?= e(old('identifier')) ?>" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p><a href="index.php?route=auth/forgotPassword">Forgot password?</a></p>
</div>
</body>
</html>
