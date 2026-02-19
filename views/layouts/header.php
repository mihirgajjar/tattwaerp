<?php
$app = config('app');
$user = Auth::user();
$currentRoute = $_GET['route'] ?? 'dashboard/index';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice & Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <h2><?= e($app['business_name']) ?></h2>
        <p class="muted">GSTIN: <?= e($app['business_gstin']) ?></p>
        <nav>
            <a class="<?= str_starts_with($currentRoute, 'dashboard/') ? 'active' : '' ?>" href="index.php?route=dashboard/index">Dashboard</a>
            <a class="<?= str_starts_with($currentRoute, 'product/') ? 'active' : '' ?>" href="index.php?route=product/index">Products</a>
            <a class="<?= str_starts_with($currentRoute, 'supplier/') ? 'active' : '' ?>" href="index.php?route=supplier/index">Suppliers</a>
            <a class="<?= str_starts_with($currentRoute, 'customer/') ? 'active' : '' ?>" href="index.php?route=customer/index">Customers</a>
            <a class="<?= str_starts_with($currentRoute, 'purchase/') ? 'active' : '' ?>" href="index.php?route=purchase/index">Purchases</a>
            <a class="<?= str_starts_with($currentRoute, 'sale/') ? 'active' : '' ?>" href="index.php?route=sale/index">Sales</a>
            <a class="<?= str_starts_with($currentRoute, 'inventory/') ? 'active' : '' ?>" href="index.php?route=inventory/index">Inventory</a>
            <a class="<?= str_starts_with($currentRoute, 'report/') ? 'active' : '' ?>" href="index.php?route=report/index">Reports</a>
            <a class="<?= str_starts_with($currentRoute, 'smart/') ? 'active' : '' ?>" href="index.php?route=smart/index">Smart Ops</a>
            <a class="<?= str_starts_with($currentRoute, 'setting/') ? 'active' : '' ?>" href="index.php?route=setting/index">Settings</a>
            <a href="index.php?route=auth/logout">Logout (<?= e($user['username'] ?? 'admin') ?>)</a>
        </nav>
    </aside>
    <main class="content">
        <?php if ($msg = flash('success')): ?>
            <div class="alert success"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
            <div class="alert error"><?= e($msg) ?></div>
        <?php endif; ?>
