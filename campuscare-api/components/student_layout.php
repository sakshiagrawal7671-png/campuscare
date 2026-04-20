<?php
// components/student_layout.php
require_once __DIR__ . '/header.php';

$sUser  = $_SESSION['user'] ?? [];
$sName  = $sUser['name'] ?? 'Student';
$sRole  = $sUser['role'] ?? 'national';
$sInit  = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $sName), 0, 2)));
$isIntl = $sRole === 'international';
$cur    = $currentPage ?? 'dashboard';
?>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'Inter',sans-serif;background:#060d09;color:#e8ede9;}
  ::-webkit-scrollbar{width:4px;height:4px;}
  ::-webkit-scrollbar-track{background:#060d09;}
  ::-webkit-scrollbar-thumb{background:#1e3d26;border-radius:4px;}
  ::-webkit-scrollbar-thumb:hover{background:#2a5235;}

  /* NAV */
  .s-nav{position:sticky;top:0;z-index:50;display:flex;align-items:center;height:56px;
    background:#09140c;border-bottom:1px solid #162b1c;}
  .s-nav-logo{display:flex;align-items:center;gap:10px;padding:0 20px;height:100%;border-right:1px solid #162b1c;min-width:200px;}
  .s-nav-links{display:flex;align-items:center;gap:2px;padding:0 16px;flex:1;}
  .s-nav-link{display:flex;align-items:center;gap:7px;padding:6px 12px;border-radius:8px;
    font-size:13px;font-weight:500;color:#6b8f72;text-decoration:none;transition:all .15s;}
  .s-nav-link:hover{color:#c8e6cb;background:#142019;}
  .s-nav-link.active{color:#060d09;background:#22c55e;font-weight:600;}
  .s-nav-link.iro-link{color:#8b5cf6;}
  .s-nav-link.iro-link:hover{background:#1a0f2e;color:#a78bfa;}
  .s-nav-right{display:flex;align-items:center;gap:10px;padding:0 16px;}

  /* CARDS */
  .card{background:#0c1a10;border:1px solid #1a2e1d;border-radius:14px;}
  .card-sm{background:#0c1a10;border:1px solid #1a2e1d;border-radius:10px;}
  .divider{border:none;border-top:1px solid #1a2e1d;}

  /* BUTTONS */
  .btn-primary{background:#22c55e;color:#060d09;font-weight:700;border:none;cursor:pointer;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
  .btn-primary:hover{background:#16a34a;}
  .btn-ghost{background:transparent;border:1px solid #1a2e1d;color:#8fa490;font-weight:500;cursor:pointer;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
  .btn-ghost:hover{border-color:#22c55e44;color:#c8e6cb;}
  .btn-iro{background:rgba(139,92,246,.12);border:1px solid rgba(139,92,246,.3);color:#a78bfa;font-weight:600;cursor:pointer;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
  .btn-iro:hover{background:rgba(139,92,246,.2);}

  /* INPUTS */
  .sc-input{background:#07100a;border:1px solid #1a2e1d;color:#e8ede9;outline:none;transition:.2s;width:100%;}
  .sc-input:focus{border-color:#22c55e;box-shadow:0 0 0 2px rgba(34,197,94,.1);}
  select.sc-input{appearance:none;}
  .sc-label{font-size:10px;font-weight:700;color:#22c55e;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:8px;}

  /* BADGE */
  .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;}
  .badge-green{background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.2);}
  .badge-blue{background:rgba(59,130,246,.1);color:#60a5fa;border:1px solid rgba(59,130,246,.2);}
  .badge-orange{background:rgba(249,115,22,.1);color:#fb923c;border:1px solid rgba(249,115,22,.2);}
  .badge-red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);}
  .badge-gray{background:rgba(107,114,128,.1);color:#9ca3af;border:1px solid rgba(107,114,128,.2);}
  .badge-purple{background:rgba(139,92,246,.1);color:#a78bfa;border:1px solid rgba(139,92,246,.2);}

  /* AVATAR */
  .avatar-green{background:linear-gradient(135deg,#22c55e,#16a34a);color:#060d09;font-weight:800;}
  .avatar-purple{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;font-weight:800;}
  .avatar-orange{background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-weight:800;}

  /* MODAL BACKDROP */
  .modal-backdrop{background:rgba(4,9,6,.8);backdrop-filter:blur(6px);}

  /* SIDEBAR SECTION LABEL */
  .sidebar-label{font-size:9px;font-weight:700;color:#2e5232;text-transform:uppercase;letter-spacing:.1em;padding:0 4px;margin-bottom:6px;}

  /* PROGRESS STEP */
  .step-line-done{background:rgba(34,197,94,.4);}
  .step-line-pending{background:#1a2e1d;}
</style>

<div class="min-h-screen" style="background:#060d09;">

  <!-- TOP NAV -->
  <nav class="s-nav">
    <div class="s-nav-logo">
      <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i data-lucide="shield-check" style="width:16px;height:16px;color:#060d09;"></i>
      </div>
      <div>
        <div style="font-size:14px;font-weight:800;color:#e8ede9;letter-spacing:-.02em;">CampusCare</div>
        <div style="font-size:9px;color:#2e5232;font-weight:600;text-transform:uppercase;letter-spacing:.08em;">ENTERPRISE</div>
      </div>
    </div>

    <div class="s-nav-links">
      <?php
      $links = [
          'dashboard' => ['Dashboard',  'layout-dashboard', '/campuscare/campuscare-api/student/dashboard.php'],
          'create'    => ['New Ticket', 'plus-circle',      '/campuscare/campuscare-api/student/create_complaint.php'],
      ];
      if ($isIntl) $links['iro'] = ['IRO Support', 'globe', '/campuscare/campuscare-api/student/create_complaint.php?type=iro'];
      foreach ($links as $key => [$label, $icon, $href]):
          $active = $cur === $key;
          $isIro  = $key === 'iro';
      ?>
      <a href="<?= $href ?>" class="s-nav-link <?= $active ? 'active' : ($isIro ? 'iro-link' : '') ?>">
        <i data-lucide="<?= $icon ?>" style="width:14px;height:14px;"></i><?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="s-nav-right">
      <!-- Search -->
      <div style="position:relative;">
        <i data-lucide="search" style="width:13px;height:13px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#2e5232;"></i>
        <input type="text" placeholder="Search complaints..." class="sc-input" style="padding:7px 12px 7px 30px;border-radius:8px;font-size:12px;width:190px;">
      </div>
      <!-- Badge -->
      <?php if ($isIntl): ?>
      <span class="badge badge-purple"><i data-lucide="globe" style="width:9px;height:9px;"></i>Intl</span>
      <?php else: ?>
      <span class="badge badge-green"><i data-lucide="user" style="width:9px;height:9px;"></i>National</span>
      <?php endif; ?>
      <!-- Avatar + Name -->
      <div style="display:flex;align-items:center;gap:8px;padding:4px 4px 4px 8px;border-radius:10px;border:1px solid #1a2e1d;background:#0c1a10;">
        <div style="width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;" class="avatar-green">
          <?= htmlspecialchars($sInit) ?>
        </div>
        <div style="line-height:1.2;">
          <div style="font-size:12px;font-weight:600;color:#e8ede9;"><?= htmlspecialchars($sName) ?></div>
          <div style="font-size:10px;color:#2e5232;"><?= ucfirst($sRole) ?> Student</div>
        </div>
        <a href="/campuscare/campuscare-api/auth/logout.php" style="margin-left:4px;padding:4px;color:#2e5232;transition:.15s;" title="Sign out">
          <i data-lucide="log-out" style="width:13px;height:13px;"></i>
        </a>
      </div>
    </div>
  </nav>

  <!-- MAIN -->
  <main style="max-width:1320px;margin:0 auto;padding:28px 24px 60px;">
    <?= $pageContent ?? '' ?>
  </main>
</div>

<script>
lucide.createIcons();
<?= $pageScript ?? '' ?>
</script>
</body>
</html>
