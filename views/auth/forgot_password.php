<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <h1>Forgot Password</h1>
    <?php if ($msg = flash('error')): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
    <form method="post" action="index.php?route=auth/forgotPassword">
        <?= csrf_field() ?>
        <label>Email or Username</label>
        <input type="text" name="identifier" required>
        <button type="submit">Generate Reset Token</button>
    </form>
    <p><a href="index.php?route=auth/resetPassword">Already have token?</a></p>
    <p><a href="index.php?route=auth/login">Back to login</a></p>
</div>
</body>
</html>
