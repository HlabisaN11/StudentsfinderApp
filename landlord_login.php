<?php
// landlord_login.php - Landlord Login Page
session_start();

// 1. Database Connection
$host = 'localhost';
$db   = 'student_residence_db'; 
$user = 'root';           
$pass = '';               

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     ]);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

$error = '';

// 2. Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Query matching your table landlordTBL
        $stmt = $pdo->prepare("SELECT landlord_id, full_name, email, password_hash FROM landlordTBL WHERE email = ?");
        $stmt->execute([$email]);
        $landlord = $stmt->fetch();

        if ($landlord && password_verify($password, $landlord['password_hash'])) {
            
            // --- SESSION START: This is what connects the Login to the Dashboard ---
            $_SESSION['user_id']   = $landlord['landlord_id'];
            $_SESSION['user']      = $landlord['email'];
            $_SESSION['role']      = 'landlord';
            $_SESSION['full_name'] = $landlord['full_name'];
            
            // REDIRECT: Sending the user to the next page
            header('Location: landlord_dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Login | StudentResidenceFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* YOUR ORIGINAL STYLING - UNCHANGED */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; flex-direction: column; position: relative; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1470&q=80') center/cover no-repeat; filter: blur(10px); z-index: -2; }
        body::after { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.85); z-index: -1; }
        .header { background: #2563eb; padding: 1rem 5%; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header .logo { font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem 1rem; }
        .login-card { background: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12); width: 100%; max-width: 420px; }
        .login-card h1 { text-align: center; font-size: 1.8rem; color: #1f2937; margin-bottom: 2rem; font-weight: 600; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; font-size: 0.95rem; }
        .input-group { position: relative; display: flex; align-items: stretch; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; transition: all 0.3s; }
        .input-addon { background: #f9fafb; border-right: 1px solid #d1d5db; padding: 0 1rem; display: flex; align-items: center; color: #6b7280; }
        .form-control { flex: 1; padding: 0.75rem 1rem; border: none; font-size: 0.95rem; outline: none; }
        .btn-login { width: 100%; padding: 0.875rem; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1.25rem; }
        .register-text { text-align: center; color: #4b5563; font-size: 0.9rem; }
        .register-text a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .footer { background: #1f2937; color: #d1d5db; text-align: center; padding: 1.25rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <header class="header"><div class="logo"><i class="fa-solid fa-house"></i> StudentResidenceFinder</div></header>
    <main class="main-content">
        <div class="login-card">
            <h1>Landlord Login</h1>
            
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-group">
                        <span class="input-addon"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <span class="input-addon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Login to Dashboard</button>
            </form>
            <div class="register-text">Not registered? <a href="landlord_signup.php">Create landlord account</a></div>
        </div>
    </main>
    <footer class="footer"><p>&copy; <?= date('Y') ?> StudentResidenceFinder</p></footer>
</body>
</html>