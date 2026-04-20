<?php
require_once __DIR__ . '/bootstrap.php';
$pdo = getDbConnection();

// The common password based on previous context is "admin123"
$password = 'admin123';
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Find a hostel
$stmtHostel = $pdo->query("SELECT id FROM hostels LIMIT 1");
$hostel = $stmtHostel->fetchColumn();

// Find a mentor
$stmtMentor = $pdo->query("SELECT id FROM users WHERE role = 'mentor' LIMIT 1");
$mentor = $stmtMentor->fetchColumn();

// Find an IRO
$stmtIro = $pdo->query("SELECT id FROM users WHERE role = 'iro' LIMIT 1");
$iro = $stmtIro->fetchColumn();

// Dummy international student details
$name = 'John Doe (Intl)';
$email = 'john.doe@international.example';
$role = 'international';
$roll_number = 'INTL-987654';
$country = 'United States';
$gender = 'Male';
$phone = '1234567890';

try {
    // 1. Insert User
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, roll_number, gender, phone, hostel_id, country, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name), password = VALUES(password)
    ");
    $stmt->execute([
        $name, $email, $hashed, $role, $roll_number, $gender, $phone, $hostel, $country
    ]);
    
    // Get the student ID
    $studentIdStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $studentIdStmt->execute([$email]);
    $studentId = $studentIdStmt->fetchColumn();
    
    // 2. Assign Mentor
    if ($mentor && $studentId) {
         $pdo->prepare("INSERT IGNORE INTO mentor_students (mentor_id, student_id) VALUES (?, ?)")
             ->execute([$mentor, $studentId]);
    }
    
    // 3. Assign IRO
    if ($iro && $studentId) {
         $pdo->prepare("INSERT IGNORE INTO iro_students (iro_id, student_id) VALUES (?, ?)")
             ->execute([$iro, $studentId]);
    }

    echo "International Student created successfully!\n";
    echo "-----------------------------------------\n";
    echo "Login URL: http://localhost/campuscare/campuscare-api/auth/login.php\n";
    echo "Name:      $name\n";
    echo "Email:     $email\n";
    echo "Password:  $password\n";
    echo "Role:      $role\n";
    echo "Hostel ID: $hostel\n";
    echo "Mentor ID: $mentor\n";
    echo "IRO ID:    $iro\n";
    echo "Country:   $country\n";
    echo "-----------------------------------------\n";
    
} catch (PDOException $e) {
    echo "Error creating student: " . $e->getMessage() . "\n";
}
