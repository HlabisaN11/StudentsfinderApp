<?php
session_start();
$error = '';

$host = 'localhost';
$dbname = 'student_residence_db'; 
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email']; // This acts as our username
    $password = $_POST['password'];
    $university = $_POST['university'];
    $student_number = $_POST['student_number'];
    $preferred_campus = $_POST['preferred_campus'];

    try {
        $pdo->beginTransaction();

        // 1. Save to users table for login
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt1 = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt1->execute([$email, $hashed]);

        // 2. Save to studentsTBL (matching your screenshot columns)
        $sql = "INSERT INTO studentsTBL (full_name, email, password_hash, university, student_number, preferred_campus) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt2 = $pdo->prepare($sql);
        $stmt2->execute([$full_name, $email, $hashed, $university, $student_number, $preferred_campus]);

        $pdo->commit();
        header('Location: student_login.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving data: " . $e->getMessage();
    }
}
?>

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $university = $_POST['university'] ?? '';
    $student_number = trim($_POST['student_number'] ?? '');
    $preferred_campus = trim($_POST['preferred_campus'] ?? '');
    $agreed_terms = isset($_POST['terms']) ? 1 : 0;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($university)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!$agreed_terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT student_id FROM studentTB WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'An account with this email already exists.';
        } else {
            // Hash password and insert
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO studentTB (full_name, email, password_hash, university, student_number, preferred_campus, newsletter, agreed_terms) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$full_name, $email, $password_hash, $university, $student_number, $preferred_campus, $newsletter, $agreed_terms])) {
                $_SESSION['user'] = $email;
                $_SESSION['role'] = 'student';
                header('Location: student_dashboard.php');
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | StudentResidenceFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }
        .header { background: #2563eb; padding: 1rem 5%; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .header .logo { font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .header nav a { color: #fff; text-decoration: none; margin-left: 1.5rem; font-weight: 500; }
        .main { display: flex; justify-content: center; padding: 3rem 1rem; min-height: calc(100vh - 64px); }
        .form-card {
            background: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 100%; max-width: 650px;
        }
        .form-card h1 { text-align: center; margin-bottom: 2rem; font-size: 1.75rem; color: #1e293b; }
        .alert { padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; color: #334155; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .checkbox-group { display: flex; align-items: start; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; color: #475569; }
        .checkbox-group a { color: #2563eb; text-decoration: none; }
        .btn-submit {
            width: 100%; background: #16a34a; color: #fff; border: none; padding: 0.875rem;
            border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .btn-submit:hover { background: #15803d; }
        .login-link { text-align: center; margin-top: 1.5rem; color: #64748b; font-size: 0.9rem; }
        .login-link a { color: #2563eb; font-weight: 600; text-decoration: none; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo"><i class="fa-solid fa-house"></i> StudentResidenceFinder</div>
        <nav>
            <a href="index.php">Home</a>
            <a href="student_signup.php">Create Account</a>
            <a href="student_login.php">Login</a>
        </nav>
    </header>

    <main class="main">
        <div class="form-card">
            <h1>Create Your Student Account</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="university">University</label>
                        <select id="university" name="university" class="form-control" required>
                            <option value="">Select your university</option>
                            <option value="University of Johannesburg">University of Johannesburg</option>
                            <option value="University of Pretoria">University of Pretoria</option>
                            <option value="Wits University">Wits University</option>
                            <option value="Tshwane University of Technology">Tshwane University of Technology</option>
                            <option value="UNISA">UNISA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="student_number">Student ID (Optional)</label>
                        <input type="text" id="student_number" name="student_number" class="form-control" value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="preferred_campus">Preferred Campus Location</label>
                    <input type="text" id="preferred_campus" name="preferred_campus" class="form-control" placeholder="e.g., Pretoria, North west, etc." value="<?= htmlspecialchars($_POST['preferred_campus'] ?? '') ?>">
                </div>

                <label class="checkbox-group">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                </label>

                <label class="checkbox-group">
                    <input type="checkbox" name="newsletter">
                    <span>Subscribe to our newsletter</span>
                </label>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="student_login.php">Log in</a>
            </div>
        </div>
    </main>
</body>
</html>