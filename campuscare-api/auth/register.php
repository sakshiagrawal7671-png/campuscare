<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// Redirect logged-in users
if (isset($_SESSION['user']['id'])) {
    $r = $_SESSION['user']['role'];
    $dp = in_array($r, ['national','international']) ? 'student' : $r;
    header('Location: /campuscare/campuscare-api/' . $dp . '/dashboard.php');
    exit;
}

$pdo = getDbConnection();

// Fetch hostels for dropdown
$hostels = $pdo->query('SELECT id, hostel_name FROM hostels ORDER BY hostel_name ASC')->fetchAll();

// Fetch active mentors
$mentors = $pdo->query("SELECT id, name FROM users WHERE role = 'mentor' AND status = 'active' ORDER BY name ASC")->fetchAll();

// Fetch active IRO officers
$iroOfficers = $pdo->query("SELECT id, name FROM users WHERE role = 'iro' AND status = 'active' ORDER BY name ASC")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';
    $role        = $_POST['role'] ?? '';
    $rollNumber  = trim($_POST['roll_number'] ?? '');
    $gender      = $_POST['gender'] ?? '';
    $phone       = trim($_POST['phone'] ?? '');
    $hostelId    = (int) ($_POST['hostel_id'] ?? 0);
    $mentorId    = (int) ($_POST['mentor_id'] ?? 0);
    $iroId       = (int) ($_POST['iro_id'] ?? 0);

    // Validation
    if (!$name || !$email || !$password || !$role || !$rollNumber || !$gender || $hostelId === 0 || $mentorId === 0) {
        $error = 'All fields marked with * are required.';
    } elseif ($role === 'international' && $iroId === 0) {
        $error = 'International students must select an IRO Officer.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['national', 'international'], true)) {
        $error = 'Invalid student type selected.';
    } else {
        // Check for existing email or roll number
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR roll_number = :roll LIMIT 1');
        $checkStmt->execute(['email' => $email, 'roll' => $rollNumber]);
        if ($checkStmt->fetch()) {
            $error = 'An account with this email or roll number already exists.';
        } else {
            try {
                $pdo->beginTransaction();

                $insert = $pdo->prepare(
                    'INSERT INTO users (name, email, password, role, roll_number, gender, phone, hostel_id, status)
                     VALUES (:name, :email, :password, :role, :roll_number, :gender, :phone, :hostel_id, :status)'
                );
                $insert->execute([
                    'name'        => $name,
                    'email'       => $email,
                    'password'    => password_hash($password, PASSWORD_ARGON2ID),
                    'role'        => $role,
                    'roll_number' => $rollNumber,
                    'gender'      => $gender,
                    'phone'       => $phone ?: null,
                    'hostel_id'   => $hostelId,
                    'status'      => 'active',
                ]);

                $studentId = (int) $pdo->lastInsertId();

                // Manual Mentor Assignment
                $insertMentor = $pdo->prepare('INSERT INTO mentor_students (mentor_id, student_id) VALUES (:m_id, :s_id)');
                $insertMentor->execute(['m_id' => $mentorId, 's_id' => $studentId]);

                // Manual IRO Assignment for international
                if ($role === 'international') {
                    $insertIro = $pdo->prepare('INSERT INTO iro_students (iro_id, student_id) VALUES (:i_id, :s_id)');
                    $insertIro->execute(['i_id' => $iroId, 's_id' => $studentId]);
                }

                $pdo->commit();

                // Auto-login after registration
                $newUser = $pdo->prepare('SELECT id, name, email, role, roll_number, gender, phone, hostel_id, status FROM users WHERE id = :id');
                $newUser->execute(['id' => $studentId]);
                $_SESSION['user'] = $newUser->fetch();

                // Route national/international → student dashboard
                header('Location: /campuscare/campuscare-api/student/dashboard.php');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../components/header.php';
?>

<div class="min-h-screen flex flex-col md:flex-row">
    <!-- Left Branding -->
    <div class="hidden md:flex md:w-1/2 bg-[#1e1e1e] border-r border-[#333] flex-col justify-between p-12 relative overflow-hidden">
        <div class="relative z-10 flex items-center gap-2">
            <i data-lucide="shield-check" class="w-8 h-8 text-[#13ec87]"></i>
            <span class="text-xl font-bold tracking-tight text-white">CampusCare</span>
        </div>

        <!-- Feature Cards -->
        <div class="relative z-10 flex flex-col gap-4 my-auto">
            <div class="p-5 bg-[#252525] border border-[#333] rounded-2xl">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Step 1</div>
                <h3 class="text-white font-semibold text-sm mb-1">Set Up Your Profile</h3>
                <p class="text-gray-500 text-xs">Register with your unique roll number and official campus email.</p>
            </div>
            <div class="p-5 bg-[#252525] border border-[#333] rounded-2xl">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Step 2</div>
                <h3 class="text-white font-semibold text-sm mb-1">Choose Your Mentor</h3>
                <p class="text-gray-500 text-xs">Select your preferred faculty mentor from the list during registration.</p>
            </div>
            <div class="p-5 bg-[#252525] border border-[#333] rounded-2xl">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Step 3</div>
                <h3 class="text-white font-semibold text-sm mb-1">Stay Connected</h3>
                <p class="text-gray-500 text-xs">Track your resolutions and stay updated with notifications from your mentor.</p>
            </div>
        </div>

        <!-- Bottom Text -->
        <div class="relative z-10">
            <div class="inline-block px-3 py-1 mb-4 rounded-md border border-[#13ec87]/30 bg-[#13ec87]/10 text-[#13ec87] text-xs font-semibold tracking-wide uppercase">
                Student Enrollment
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-3 leading-tight">Your voice matters. Register to get started.</h1>
            <p class="text-gray-400">Once registered, your dashboard will link you directly with your selected faculty advisors.</p>
        </div>

        <!-- Decoration -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#13ec87]/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-cyan-400/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 28px 28px;">
        </div>
    </div>

    <!-- Right Registration Form -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-8 overflow-y-auto">
        <div class="w-full max-w-lg py-8">

            <!-- Mobile Logo -->
            <div class="md:hidden flex items-center gap-2 mb-10">
                <i data-lucide="shield-check" class="w-8 h-8 text-[#13ec87]"></i>
                <span class="text-xl font-bold tracking-tight text-white">CampusCare</span>
            </div>

            <div class="mb-8">
                <h2 class="text-3xl font-bold text-white mb-2">Create Account</h2>
                <p class="text-gray-400">Join the portal and select your preferred advisors.</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/40 rounded-lg flex items-start gap-3 text-red-400 text-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- Student Type -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Student Type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-4 bg-[#1e1e1e] border border-[#333] rounded-xl cursor-pointer hover:border-[#13ec87]/50 transition-colors has-[:checked]:border-[#13ec87] has-[:checked]:bg-[#13ec87]/10">
                            <input type="radio" name="role" value="national" class="accent-[#13ec87] role-toggle" <?= ($_POST['role'] ?? '') === 'national' || !isset($_POST['role']) ? 'checked' : '' ?> required>
                            <div>
                                <p class="text-white font-medium text-sm">National</p>
                                <p class="text-gray-500 text-xs">Domestic Student</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 bg-[#1e1e1e] border border-[#333] rounded-xl cursor-pointer hover:border-[#13ec87]/50 transition-colors has-[:checked]:border-[#13ec87] has-[:checked]:bg-[#13ec87]/10">
                            <input type="radio" name="role" value="international" class="accent-[#13ec87] role-toggle" <?= ($_POST['role'] ?? '') === 'international' ? 'checked' : '' ?>>
                            <div>
                                <p class="text-white font-medium text-sm">International</p>
                                <p class="text-gray-500 text-xs">Foreign Student</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Mentor Selection -->
                    <div>
                        <label for="mentor_id" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Assign Mentor <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="mentor_id" name="mentor_id" required
                                class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] appearance-none transition-all">
                                <option value="" disabled <?= empty($_POST['mentor_id']) ? 'selected' : '' ?>>Select Mentor...</option>
                                <?php foreach ($mentors as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ((int)($_POST['mentor_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-4 text-gray-500 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- IRO Selection (Dynamic) -->
                    <div id="iro-container" class="<?= ($_POST['role'] ?? '') === 'international' ? '' : 'hidden' ?>">
                        <label for="iro_id" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Assign IRO Officer <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="iro_id" name="iro_id" <?= ($_POST['role'] ?? '') === 'international' ? 'required' : '' ?>
                                class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] appearance-none transition-all">
                                <option value="" disabled <?= empty($_POST['iro_id']) ? 'selected' : '' ?>>Select Officer...</option>
                                <?php foreach ($iroOfficers as $i): ?>
                                    <option value="<?= $i['id'] ?>" <?= ((int)($_POST['iro_id'] ?? 0) === (int)$i['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($i['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-4 text-gray-500 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <hr class="border-[#333] my-2">

                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        placeholder="Your full name"
                        class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all">
                </div>

                <!-- Email & Roll Number -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            placeholder="you@campus.edu"
                            class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all">
                    </div>
                    <div>
                        <label for="roll_number" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Roll Number <span class="text-red-500">*</span></label>
                        <input type="text" id="roll_number" name="roll_number" required
                            value="<?= htmlspecialchars($_POST['roll_number'] ?? '') ?>"
                            placeholder="e.g. 22CS001"
                            class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] focus:ring-1 focus:ring-[#13ec87] transition-all">
                    </div>
                </div>

                <!-- Gender & Hostel -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="gender" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Gender <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="gender" name="gender" required
                                class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] appearance-none transition-all">
                                <option value="" disabled <?= empty($_POST['gender']) ? 'selected' : '' ?>>Select...</option>
                                <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($_POST['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-4 text-gray-500 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label for="hostel_id" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Hostel <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select id="hostel_id" name="hostel_id" required
                                class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] appearance-none transition-all">
                                <option value="" disabled <?= empty($_POST['hostel_id']) ? 'selected' : '' ?>>Select Hostel...</option>
                                <?php foreach ($hostels as $h): ?>
                                    <option value="<?= $h['id'] ?>" <?= ((int)($_POST['hostel_id'] ?? 0) === (int)$h['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($h['hostel_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-4 text-gray-500 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required minlength="6"
                                placeholder="Min 6 chars"
                                class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] transition-all pr-12">
                            <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 pointer-events-auto" data-target="password">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Confirm Phone</label>
                        <input type="tel" id="phone" name="phone"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                            placeholder="+91..."
                            class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                placeholder="Repeat password"
                                class="w-full bg-[#1e1e1e] border border-[#333] text-white px-4 py-3 rounded-xl focus:outline-none focus:border-[#13ec87] transition-all pr-12">
                            <button type="button" class="toggle-password absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 pointer-events-auto" data-target="confirm_password">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-2">
                    <button type="submit" id="reg-btn"
                        class="w-full bg-[#13ec87] text-[#121212] font-bold py-3.5 px-4 rounded-xl shadow-[0_0_15px_rgba(19,236,135,0.2)] hover:bg-[#0fae62] hover:shadow-[0_0_25px_rgba(19,236,135,0.4)] transition-all flex items-center justify-center gap-2">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        Create My Account
                    </button>
                </div>

                <p class="text-center text-sm text-gray-500">
                    Already have an account? 
                    <a href="login.php" class="text-[#13ec87] hover:underline font-medium">Sign In</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    // Toggle IRO Dropdown based on role
    const toggles = document.querySelectorAll('.role-toggle');
    const iroContainer = document.getElementById('iro-container');
    const iroSelect = document.getElementById('iro_id');

    toggles.forEach(t => {
        t.addEventListener('change', () => {
            if (t.value === 'international') {
                iroContainer.classList.remove('hidden');
                iroSelect.required = true;
            } else {
                iroContainer.classList.add('hidden');
                iroSelect.required = false;
                iroSelect.value = '';
            }
        });
    });

    // Password Toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            lucide.createIcons();
        });
    });

    const form = document.querySelector('form');
    const btn = document.getElementById('reg-btn');
    form.addEventListener('submit', () => {
        btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Creating Account...';
        btn.disabled = true;
    });
</script>
</body>
</html>
