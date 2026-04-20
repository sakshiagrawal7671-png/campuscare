<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/roleGuard.php';

$student = requireRole(['national', 'international']);
$pdo     = getDbConnection();
$isIntl  = $student['role'] === 'international';
$sId     = (int)$student['id'];

// Mentor
$mentorQ = $pdo->prepare("SELECT u.id,u.name,u.email,u.phone FROM users u INNER JOIN mentor_students ms ON ms.mentor_id=u.id WHERE ms.student_id=:sid LIMIT 1");
$mentorQ->execute(['sid'=>$sId]);
$mentor = $mentorQ->fetch();

// IRO
$iro = null;
if ($isIntl) {
    $iroQ = $pdo->prepare("SELECT u.id,u.name,u.email,u.phone FROM users u INNER JOIN iro_students i ON i.iro_id=u.id WHERE i.student_id=:sid LIMIT 1");
    $iroQ->execute(['sid'=>$sId]);
    $iro = $iroQ->fetch();
}

// Hostel + Warden
$hostelQ = $pdo->prepare("SELECT h.hostel_name, u.id AS wid, u.name AS wname, u.email AS wemail, u.phone AS wphone FROM users s LEFT JOIN hostels h ON h.id=s.hostel_id LEFT JOIN hostel_wardens hw ON hw.hostel_id=s.hostel_id LEFT JOIN users u ON u.id=hw.warden_id AND u.status='active' WHERE s.id=:sid LIMIT 1");
$hostelQ->execute(['sid'=>$sId]);
$hi = $hostelQ->fetch();
$hostelName = $hi['hostel_name'] ?? null;
$warden = ($hi && $hi['wid']) ? ['id'=>$hi['wid'],'name'=>$hi['wname'],'email'=>$hi['wemail'],'phone'=>$hi['wphone']] : null;

// Complaints
$cstmt = $pdo->prepare("SELECT c.id,c.title,c.description,c.status,c.created_at,cat.name AS cat,u.name AS staff FROM complaints c INNER JOIN categories cat ON cat.id=c.category_id LEFT JOIN users u ON u.id=c.assigned_to WHERE c.student_id=:sid ORDER BY c.created_at DESC");
$cstmt->execute(['sid'=>$sId]);
$complaints = $cstmt->fetchAll();

$sMap = [
    'submitted'   => ['label'=>'Submitted',   'clr'=>'#60a5fa','bg'=>'rgba(59,130,246,.1)', 'bdr'=>'rgba(59,130,246,.2)', 'idx'=>0],
    'in_progress' => ['label'=>'In Progress', 'clr'=>'#fb923c','bg'=>'rgba(249,115,22,.1)', 'bdr'=>'rgba(249,115,22,.2)', 'idx'=>1],
    'escalated'   => ['label'=>'Escalated',   'clr'=>'#f87171','bg'=>'rgba(239,68,68,.1)',  'bdr'=>'rgba(239,68,68,.2)',  'idx'=>1],
    'resolved'    => ['label'=>'Resolved',    'clr'=>'#22c55e','bg'=>'rgba(34,197,94,.1)',  'bdr'=>'rgba(34,197,94,.2)',  'idx'=>3],
    'closed'      => ['label'=>'Closed',      'clr'=>'#9ca3af','bg'=>'rgba(156,163,175,.08)','bdr'=>'rgba(156,163,175,.15)','idx'=>3],
];
$steps = ['Submitted','Review','Investigation','Resolved'];

$total    = count($complaints);
$open     = count(array_filter($complaints, fn($c)=>in_array($c['status'],['submitted','in_progress','escalated'])));
$resolved = count(array_filter($complaints, fn($c)=>in_array($c['status'],['resolved','closed'])));
$escalated= count(array_filter($complaints, fn($c)=>$c['status']==='escalated'));

function ini(string $name): string {
    return implode('', array_map(fn($w)=>strtoupper($w[0]), array_slice(explode(' ',$name),0,2)));
}

ob_start();
?>

<!-- Page wrapper -->
<div style="display:grid;grid-template-columns:1fr 288px;gap:22px;align-items:start;">

<!-- ═══════════════════════════════════════════════════════
     LEFT COLUMN
══════════════════════════════════════════════════════════ -->
<div style="display:flex;flex-direction:column;gap:18px;">

  <!-- Welcome Header -->
  <div style="background:linear-gradient(135deg,#0c1a10 0%,#091409 100%);border:1px solid #1a2e1d;border-radius:14px;padding:24px 28px;display:flex;align-items:center;justify-content:space-between;gap:20px;">
    <div>
      <p style="font-size:10px;font-weight:700;color:#22c55e;letter-spacing:.1em;text-transform:uppercase;margin-bottom:6px;">
        <?= $isIntl ? '🌐  International Student' : '🎓  National Student' ?>
      </p>
      <h1 style="font-size:24px;font-weight:800;color:#f0f5f0;letter-spacing:-.02em;line-height:1.1;">
        Welcome back, <?= htmlspecialchars(explode(' ',$student['name'])[0]) ?>
      </h1>
      <p style="font-size:13px;color:#3d6b42;margin-top:6px;font-weight:500;">
        Roll No: <span style="color:#6b8f72;"><?= htmlspecialchars($student['roll_number'] ?? '—') ?></span>
        &nbsp;·&nbsp; <?= htmlspecialchars($hostelName ?? 'No Hostel') ?>
      </p>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0;">
      <a href="create_complaint.php" class="btn-primary" style="padding:9px 18px;border-radius:9px;font-size:13px;">
        <i data-lucide="plus" style="width:15px;height:15px;"></i> New Complaint
      </a>
      <?php if ($isIntl): ?>
      <a href="create_complaint.php?type=iro" class="btn-iro" style="padding:9px 16px;border-radius:9px;font-size:13px;">
        <i data-lucide="globe" style="width:14px;height:14px;"></i> IRO
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats Row -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
    <?php foreach ([
      ['Total',    $total,     '#22c55e','rgba(34,197,94,.08)','rgba(34,197,94,.15)','activity'],
      ['Open',     $open,      '#fb923c','rgba(249,115,22,.08)','rgba(249,115,22,.15)','clock'],
      ['Resolved', $resolved,  '#60a5fa','rgba(59,130,246,.08)', 'rgba(59,130,246,.15)','check-circle'],
      ['Escalated',$escalated, '#f87171','rgba(239,68,68,.08)',  'rgba(239,68,68,.15)', 'alert-triangle'],
    ] as [$lbl,$val,$clr,$bgc,$bdr,$ico]): ?>
    <div style="background:<?= $bgc ?>;border:1px solid <?= $bdr ?>;border-radius:12px;padding:16px 18px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <span style="font-size:10px;font-weight:700;color:<?= $clr ?>;text-transform:uppercase;letter-spacing:.07em;"><?= $lbl ?></span>
        <i data-lucide="<?= $ico ?>" style="width:14px;height:14px;color:<?= $clr ?>;opacity:.7;"></i>
      </div>
      <div style="font-size:28px;font-weight:900;color:#e8ede9;line-height:1;letter-spacing:-.03em;"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Complaints Section -->
  <div class="card" style="overflow:hidden;">
    <div style="padding:16px 22px;border-bottom:1px solid #1a2e1d;display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:10px;">
        <i data-lucide="inbox" style="width:16px;height:16px;color:#22c55e;"></i>
        <span style="font-size:14px;font-weight:700;color:#e8ede9;">Complaints</span>
        <span style="background:#162b1c;color:#3d6b42;font-size:10px;font-weight:700;padding:2px 8px;border-radius:100px;border:1px solid #1a3a21;"><?= $total ?></span>
      </div>
      <a href="create_complaint.php" style="font-size:12px;font-weight:600;color:#22c55e;text-decoration:none;display:flex;align-items:center;gap:4px;">
        <i data-lucide="plus" style="width:13px;height:13px;"></i> New
      </a>
    </div>

    <?php if (empty($complaints)): ?>
    <div style="padding:52px 24px;text-align:center;">
      <div style="width:48px;height:48px;border-radius:12px;background:#0c1a10;border:1px solid #1a2e1d;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i data-lucide="inbox" style="width:22px;height:22px;color:#2e5232;"></i>
      </div>
      <p style="font-size:14px;font-weight:600;color:#4b7553;margin-bottom:6px;">No complaints yet</p>
      <p style="font-size:12px;color:#2e5232;margin-bottom:18px;">Submit your first issue to get started</p>
      <a href="create_complaint.php" class="btn-primary" style="padding:9px 20px;border-radius:9px;font-size:13px;">
        <i data-lucide="plus-circle" style="width:14px;height:14px;"></i> File Complaint
      </a>
    </div>
    <?php else: ?>
    <div>
      <?php foreach ($complaints as $idx => $c):
        $sc = $sMap[$c['status']] ?? $sMap['submitted'];
        $stepIdx = $sc['idx'];
      ?>
      <div style="padding:18px 22px;<?= $idx > 0 ? 'border-top:1px solid #111e15;' : '' ?> transition:background .12s;" onmouseover="this.style.background='#0a160d'" onmouseout="this.style.background=''">
        <!-- Row header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px;">
          <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;flex-wrap:wrap;">
              <span style="font-size:10px;color:#2e5232;font-weight:600;">#CS-<?= str_pad((string)$c['id'],4,'0',STR_PAD_LEFT) ?></span>
              <span style="width:3px;height:3px;border-radius:50%;background:#1a2e1d;flex-shrink:0;"></span>
              <span style="font-size:10px;color:#2e5232;"><?= htmlspecialchars($c['cat']) ?></span>
            </div>
            <a href="../shared/view_complaint.php?id=<?= $c['id'] ?>"
               style="font-size:14px;font-weight:700;color:#d4edda;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:.12s;"
               onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#d4edda'">
              <?= htmlspecialchars($c['title']) ?>
            </a>
            <p style="font-size:12px;color:#3d6b42;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars(substr($c['description'],0,70)) ?>…
            </p>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
            <span style="background:<?= $sc['bg'] ?>;color:<?= $sc['clr'] ?>;border:1px solid <?= $sc['bdr'] ?>;font-size:10px;font-weight:700;padding:3px 10px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em;">
              <?= $sc['label'] ?>
            </span>
            <span style="font-size:11px;color:#2e5232;"><?= date('M j, Y', strtotime($c['created_at'])) ?></span>
          </div>
        </div>

        <!-- Progress bar -->
        <div style="display:flex;align-items:center;margin-bottom:10px;">
          <?php foreach ($steps as $si => $slabel):
            $done   = $si <= $stepIdx;
            $active = $si === $stepIdx;
          ?>
          <div style="display:flex;align-items:center;<?= $si < count($steps)-1 ? 'flex:1;' : '' ?>">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
              <div style="width:20px;height:20px;border-radius:50%;border:1.5px solid <?= $done ? $sc['clr'] : '#1a2e1d' ?>;background:<?= $done ? $sc['clr'].'22' : 'transparent' ?>;display:flex;align-items:center;justify-content:center;<?= $active ? 'box-shadow:0 0 8px '.$sc['clr'].'44;' : '' ?>">
                <?php if ($done): ?>
                <i data-lucide="<?= $active ? 'dot' : 'check' ?>" style="width:10px;height:10px;color:<?= $sc['clr'] ?>;"></i>
                <?php else: ?>
                <div style="width:5px;height:5px;border-radius:50%;background:#1a2e1d;"></div>
                <?php endif; ?>
              </div>
              <span style="font-size:8px;font-weight:600;white-space:nowrap;color:<?= $done ? $sc['clr'] : '#2e5232' ?>;"><?= $slabel ?></span>
            </div>
            <?php if ($si < count($steps)-1): ?>
            <div style="flex:1;height:1px;margin:0 6px;margin-bottom:14px;background:<?= $si < $stepIdx ? $sc['clr'].'44' : '#1a2e1d' ?>;"></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Footer row -->
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <?php if ($c['staff']): ?>
          <span style="font-size:11px;color:#2e5232;display:flex;align-items:center;gap:5px;">
            <i data-lucide="user" style="width:11px;height:11px;"></i>
            <?= htmlspecialchars($c['staff']) ?>
          </span>
          <?php else: ?>
          <span style="font-size:11px;color:#1a2e1d;">Unassigned</span>
          <?php endif; ?>
          <a href="../shared/view_complaint.php?id=<?= $c['id'] ?>"
             style="font-size:11px;font-weight:600;color:#3d6b42;text-decoration:none;display:flex;align-items:center;gap:4px;transition:.12s;"
             onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#3d6b42'">
            View Details <i data-lucide="arrow-right" style="width:11px;height:11px;"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /left -->

<!-- ═══════════════════════════════════════════════════════
     RIGHT COLUMN
══════════════════════════════════════════════════════════ -->
<div style="display:flex;flex-direction:column;gap:14px;position:sticky;top:76px;">

  <!-- Mentor Card -->
  <div class="card" style="overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid #1a2e1d;">
      <p style="font-size:9px;font-weight:700;color:#22c55e;text-transform:uppercase;letter-spacing:.1em;">Assigned Mentor</p>
    </div>
    <?php if ($mentor): ?>
    <div style="padding:20px 16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;" class="avatar-green">
          <?= htmlspecialchars(ini($mentor['name'])) ?>
        </div>
        <div>
          <p style="font-size:13px;font-weight:700;color:#e8ede9;"><?= htmlspecialchars($mentor['name']) ?></p>
          <p style="font-size:11px;color:#3d6b42;margin-top:1px;">Faculty Mentor</p>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a href="mailto:<?= htmlspecialchars($mentor['email']) ?>" class="btn-ghost" style="padding:8px 12px;border-radius:8px;font-size:12px;justify-content:center;">
          <i data-lucide="mail" style="width:13px;height:13px;"></i> <?= htmlspecialchars($mentor['email']) ?>
        </a>
        <?php if ($mentor['phone']): ?>
        <a href="tel:<?= htmlspecialchars($mentor['phone']) ?>" class="btn-ghost" style="padding:8px 12px;border-radius:8px;font-size:12px;justify-content:center;">
          <i data-lucide="phone" style="width:13px;height:13px;"></i> <?= htmlspecialchars($mentor['phone']) ?>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div style="padding:24px 16px;text-align:center;">
      <i data-lucide="user-x" style="width:28px;height:28px;color:#1a2e1d;margin-bottom:8px;"></i>
      <p style="font-size:12px;color:#2e5232;">No mentor assigned yet.</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- Warden Card -->
  <div class="card" style="overflow:hidden;border-color:rgba(249,115,22,.18);">
    <div style="padding:14px 16px;border-bottom:1px solid rgba(249,115,22,.12);display:flex;align-items:center;justify-content:space-between;">
      <p style="font-size:9px;font-weight:700;color:#fb923c;text-transform:uppercase;letter-spacing:.1em;">Hostel Warden</p>
      <?php if ($hostelName): ?>
      <span class="badge badge-orange" style="font-size:9px;"><?= htmlspecialchars($hostelName) ?></span>
      <?php endif; ?>
    </div>
    <?php if ($warden): ?>
    <div style="padding:20px 16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;" class="avatar-orange">
          <?= htmlspecialchars(ini($warden['name'])) ?>
        </div>
        <div>
          <p style="font-size:13px;font-weight:700;color:#e8ede9;"><?= htmlspecialchars($warden['name']) ?></p>
          <p style="font-size:11px;color:#c2410c;margin-top:1px;">Hostel Warden</p>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a href="mailto:<?= htmlspecialchars($warden['email']) ?>" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;border:1px solid rgba(249,115,22,.2);background:rgba(249,115,22,.06);color:#fb923c;text-decoration:none;transition:.15s;">
          <i data-lucide="mail" style="width:13px;height:13px;"></i> Email Warden
        </a>
        <?php if ($warden['phone']): ?>
        <a href="tel:<?= htmlspecialchars($warden['phone']) ?>" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;border:1px solid rgba(249,115,22,.2);background:rgba(249,115,22,.06);color:#fb923c;text-decoration:none;transition:.15s;">
          <i data-lucide="phone" style="width:13px;height:13px;"></i> <?= htmlspecialchars($warden['phone']) ?>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div style="padding:24px 16px;text-align:center;">
      <i data-lucide="home" style="width:28px;height:28px;color:rgba(249,115,22,.15);margin-bottom:8px;"></i>
      <p style="font-size:12px;color:#6b5e56;">No warden assigned to your hostel yet.</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- IRO Card (International only) -->
  <?php if ($isIntl): ?>
  <div class="card" style="overflow:hidden;border-color:rgba(139,92,246,.2);background:linear-gradient(135deg,#0a0815,#080f0b);">
    <div style="padding:14px 16px;border-bottom:1px solid rgba(139,92,246,.12);display:flex;align-items:center;justify-content:space-between;">
      <p style="font-size:9px;font-weight:700;color:#a78bfa;text-transform:uppercase;letter-spacing:.1em;">IRO Officer</p>
      <span class="badge badge-purple" style="font-size:9px;">Intl Support</span>
    </div>
    <?php if ($iro): ?>
    <div style="padding:20px 16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;" class="avatar-purple">
          <?= htmlspecialchars(ini($iro['name'])) ?>
        </div>
        <div>
          <p style="font-size:13px;font-weight:700;color:#e8ede9;"><?= htmlspecialchars($iro['name']) ?></p>
          <p style="font-size:11px;color:#7c3aed;margin-top:1px;">IRO Officer</p>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a href="mailto:<?= htmlspecialchars($iro['email']) ?>" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;border:1px solid rgba(139,92,246,.25);background:rgba(139,92,246,.08);color:#a78bfa;text-decoration:none;">
          <i data-lucide="mail" style="width:13px;height:13px;"></i> Email IRO
        </a>
        <?php if ($iro['phone']): ?>
        <a href="tel:<?= htmlspecialchars($iro['phone']) ?>" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;border:1px solid rgba(139,92,246,.25);background:rgba(139,92,246,.08);color:#a78bfa;text-decoration:none;">
          <i data-lucide="phone" style="width:13px;height:13px;"></i> <?= htmlspecialchars($iro['phone']) ?>
        </a>
        <?php endif; ?>
      </div>
      <a href="create_complaint.php?type=iro" class="btn-iro" style="width:100%;margin-top:10px;padding:9px;border-radius:8px;font-size:12px;justify-content:center;">
        <i data-lucide="globe" style="width:13px;height:13px;"></i> File IRO Complaint
      </a>
    </div>
    <?php else: ?>
    <div style="padding:24px 16px;text-align:center;">
      <i data-lucide="globe" style="width:28px;height:28px;color:rgba(139,92,246,.2);margin-bottom:8px;"></i>
      <p style="font-size:12px;color:#4b3f7a;">No IRO assigned yet.</p>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Quick Actions -->
  <div class="card" style="padding:16px;">
    <p style="font-size:9px;font-weight:700;color:#22c55e;text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;">Quick Actions</p>
    <div style="display:flex;flex-direction:column;gap:6px;">
      <a href="create_complaint.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;border:1px solid #1a2e1d;background:#091409;text-decoration:none;transition:border-color .15s;" onmouseover="this.style.borderColor='rgba(34,197,94,.3)'" onmouseout="this.style.borderColor='#1a2e1d'">
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i data-lucide="plus-circle" style="width:13px;height:13px;color:#22c55e;"></i>
        </div>
        <div>
          <p style="font-size:12px;font-weight:600;color:#d4edda;">File New Complaint</p>
          <p style="font-size:10px;color:#2e5232;margin-top:1px;">Submit a hostel or academic issue</p>
        </div>
        <i data-lucide="chevron-right" style="width:12px;height:12px;color:#2e5232;margin-left:auto;"></i>
      </a>
      <?php if ($isIntl): ?>
      <a href="create_complaint.php?type=iro" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;border:1px solid rgba(139,92,246,.2);background:#07040f;text-decoration:none;">
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i data-lucide="globe" style="width:13px;height:13px;color:#a78bfa;"></i>
        </div>
        <div>
          <p style="font-size:12px;font-weight:600;color:#d4edda;">IRO Complaint</p>
          <p style="font-size:10px;color:#4b3f7a;margin-top:1px;">International concerns</p>
        </div>
        <i data-lucide="chevron-right" style="width:12px;height:12px;color:#4b3f7a;margin-left:auto;"></i>
      </a>
      <?php endif; ?>
      <a href="/campuscare/campuscare-api/auth/logout.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;border:1px solid #1a2e1d;background:#091409;text-decoration:none;" onmouseover="this.style.borderColor='rgba(239,68,68,.2)'" onmouseout="this.style.borderColor='#1a2e1d'">
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i data-lucide="log-out" style="width:13px;height:13px;color:#f87171;"></i>
        </div>
        <div>
          <p style="font-size:12px;font-weight:600;color:#d4edda;">Sign Out</p>
          <p style="font-size:10px;color:#2e5232;margin-top:1px;">End session securely</p>
        </div>
        <i data-lucide="chevron-right" style="width:12px;height:12px;color:#2e5232;margin-left:auto;"></i>
      </a>
    </div>
  </div>

</div><!-- /right -->
</div><!-- /grid -->

<?php
$pageContent = ob_get_clean();
$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../components/student_layout.php';
