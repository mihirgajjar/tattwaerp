<h2>Database Migration Center</h2>
<section class="card">
    <h3>Safe Upgrade Actions</h3>
    <p class="muted">Always take database backup before running migrations on live server.</p>
    <div class="toolbar">
        <form method="post" action="index.php?route=migration/run" onsubmit="return confirm('Run all pending migrations now?');">
            <?= csrf_field() ?>
            <button type="submit">Run Pending Migrations</button>
        </form>
        <form method="post" action="index.php?route=migration/baseline" onsubmit="return confirm('Use baseline only if live DB already has these changes. Continue?');">
            <?= csrf_field() ?>
            <button type="submit" class="secondary">Mark Baseline (No SQL Run)</button>
        </form>
    </div>
</section>

<section class="card">
    <h3>Pending Migrations (<?= count($pending) ?>)</h3>
    <table>
        <thead><tr><th>Filename</th></tr></thead>
        <tbody>
        <?php foreach ($pending as $m): ?>
            <tr><td><?= e($m) ?></td></tr>
        <?php endforeach; ?>
        <?php if (count($pending) === 0): ?>
            <tr><td>No pending migrations.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Applied Migrations</h3>
    <table>
        <thead><tr><th>Migration</th><th>Applied At</th></tr></thead>
        <tbody>
        <?php foreach ($applied as $m): ?>
            <tr><td><?= e($m['migration']) ?></td><td><?= e($m['applied_at']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
