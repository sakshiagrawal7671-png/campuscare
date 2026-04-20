<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$iro = requireRole(['iro']);
$pdo = getDbConnection();

// All IRO complaints (currently just route_to iro/assigned to IROs, or all international complaints?)
// Existing DB logic queries "WHERE u.role = 'international'". I'll stick to that logic but refine the join.
$cstmt = $pdo->prepare(
    "SELECT c.id, c.title, c.status, c.created_at,
            cat.name AS category_name,
            u.name AS student_name, u.country
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users u ON u.id = c.student_id
     WHERE u.role = 'international'
     ORDER BY FIELD(c.status,'escalated','submitted','in_progress','resolved','closed'), c.created_at DESC"
);
$cstmt->execute();
$complaints = $cstmt->fetchAll();

$total     = count($complaints);
$open      = count(array_filter($complaints, fn($c) => in_array($c['status'], ['submitted','in_progress'])));
$escalated = count(array_filter($complaints, fn($c) => $c['status'] === 'escalated'));
$resolved  = count(array_filter($complaints, fn($c) => in_array($c['status'], ['resolved','closed'])));

ob_start();
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
body { font-family: 'Inter', sans-serif; }

.wc-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #1a3a25; border: 1px solid #1a3a25; border-radius: 10px; overflow: hidden; margin-bottom: 24px; }
.wc-stat  { background: #0a1510; padding: 20px 24px; }
.wc-stat-label { font-size: 11px; font-weight: 600; color: #4b7553; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
.wc-stat-value { font-size: 32px; font-weight: 700; color: #e8ede9; line-height: 1; }

.wc-table-wrap { border: 1px solid #1a3a25; border-radius: 10px; overflow: hidden; }
.wc-table { width: 100%; border-collapse: collapse; }
.wc-table thead tr { background: #071009; border-bottom: 1px solid #1a3a25; }
.wc-table thead th { padding: 11px 16px; text-align: left; font-size: 10px; font-weight: 700; color: #4b7553; text-transform: uppercase; letter-spacing: .07em; white-space: nowrap; }
.wc-table tbody tr { border-bottom: 1px solid #0f1f12; transition: background .1s; cursor: pointer; }
.wc-table tbody tr:last-child { border-bottom: none; }
.wc-table tbody tr:hover { background: #0a160d; }
.wc-table td { padding: 13px 16px; font-size: 13px; color: #c8dbc9; vertical-align: middle; }
.wc-table td.muted { color: #4b7553; font-size: 12px; }
.wc-table a { color: #c8dbc9; text-decoration: none; font-weight: 500; }
.wc-table a:hover { color: #13ec87; }

.status-pill { display: inline-block; padding: 2px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; border: 1px solid; }
.s-submitted   { color: #93c5fd; border-color: #1d3a5e; background: #0c1a2e; }
.s-in_progress { color: #fdba74; border-color: #5c3008; background: #1f1308; }
.s-escalated   { color: #fca5a5; border-color: #5c1a1a; background: #1f0a0a; }
.s-resolved    { color: #6ee7b7; border-color: #1a4030; background: #091a14; }
.s-closed      { color: #9ca3af; border-color: #2a2a2a; background: #111; }
</style>

<!-- Page Header -->
<div style="margin-bottom:24px;">
    <div style="font-size:12px;color:#4b7553;margin-bottom:4px;">IRO Console</div>
    <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <h1 style="font-size:22px;font-weight:700;color:#e8ede9;"><?= htmlspecialchars($iro['name']) ?></h1>
    </div>
</div>

<!-- Stats Bar -->
<div class="wc-stats">
    <div class="wc-stat">
        <div class="wc-stat-label">Total Assigned</div>
        <div class="wc-stat-value"><?= $total ?></div>
    </div>
    <div class="wc-stat">
        <div class="wc-stat-label">Open</div>
        <div class="wc-stat-value"><?= $open ?></div>
    </div>
    <div class="wc-stat">
        <div class="wc-stat-label">Escalated</div>
        <div class="wc-stat-value"><?= $escalated ?></div>
    </div>
    <div class="wc-stat">
        <div class="wc-stat-label">Resolved</div>
        <div class="wc-stat-value"><?= $resolved ?></div>
    </div>
</div>

<!-- Table -->
<div class="wc-table-wrap">
    <div style="padding:14px 16px;border-bottom:1px solid #1a3a25;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;font-weight:600;color:#c8dbc9;">International Students' Log</span>
        <span style="font-size:11px;color:#4b7553;"><?= $total ?> record<?= $total!==1?'s':'' ?></span>
    </div>

    <?php if (empty($complaints)): ?>
    <div style="padding:48px 24px;text-align:center;color:#4b7553;font-size:13px;">
        All international protocols are cleared and stable.
    </div>
    <?php else: ?>
    <table class="wc-table">
        <thead>
            <tr>
                <th style="width:72px;">ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Student</th>
                <th>Country</th>
                <th>Filed</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($complaints as $c):
            $sClass = 's-' . $c['status'];
            $sLabel = ucfirst(str_replace('_',' ',$c['status']));
            $viewUrl = '../shared/view_complaint.php?id=' . $c['id'];
        ?>
            <tr onclick="window.location='<?= $viewUrl ?>'">
                <td class="muted">#<?= str_pad((string)$c['id'],4,'0',STR_PAD_LEFT) ?></td>
                <td>
                    <a href="<?= $viewUrl ?>">
                        <?= htmlspecialchars($c['title']) ?>
                    </a>
                </td>
                <td class="muted"><?= htmlspecialchars($c['category_name']) ?></td>
                <td><?= htmlspecialchars($c['student_name']) ?></td>
                <td class="muted"><?= htmlspecialchars($c['country'] ?? 'Global') ?></td>
                <td class="muted" style="white-space:nowrap;"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td><span class="status-pill <?= $sClass ?>"><?= $sLabel ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php
$pageContent  = ob_get_clean();
$pageTitle    = 'IRO Console';
$currentPage  = 'dashboard';
require_once __DIR__ . '/../components/iro_layout.php';
