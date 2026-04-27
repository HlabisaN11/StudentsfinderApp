<?php
// student_login.php - Student Login Page
session_start();

$error = '';

// 1. Database Connection
$host = 'localhost';
$dbname = 'student_residence_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// 2. Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // We fetch the email, hashed password, and full name all in one query
            // This matches the columns seen in your phpMyAdmin screenshot
            $stmt = $pdo->prepare("SELECT email, password_hash, full_name FROM studentsTBL WHERE email = ?");
            $stmt->execute([$email]);
            $userAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            // One single, reliable verification check
            if ($userAccount && password_verify($password, $userAccount['password_hash'])) {
                
                // Set Session Variables for use in the dashboard
                $_SESSION['user'] = $userAccount['email'];
                $_SESSION['role'] = 'student';
                $_SESSION['student_name'] = $userAccount['full_name'];
                
                // Handle "Remember Me" cookie (valid for 30 days)
                if ($remember) {
                    setcookie('user_email', $email, time() + (86400 * 30), "/"); 
                }
                
                // Redirect to the dashboard upon success
                header('Location: student_dashboard.php');
                exit;
            } else {
                // Generic error for security (doesn't reveal if email or password was wrong)
                $error = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | ResidenceFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Background styling with overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1470&q=80') center/cover no-repeat;
            filter: blur(8px);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.7), rgba(139, 92, 246, 0.7));
            z-index: -1;
        }

        .login-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            margin: 1rem;
        }

        .logo {
            text-align: center;
            margin-bottom: 1rem;
            color: #2563eb;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-container h1 { text-align: center; font-size: 1.8rem; color: #1f2937; margin-bottom: 0.5rem; }
        .subtitle { text-align: center; color: #6b7280; margin-bottom: 2rem; font-size: 0.95rem; }
        
        /* Error Message Styling */
        .error-msg { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 0.75rem; 
            border-radius: 8px; 
            margin-bottom: 1.5rem; 
            font-size: 0.9rem; 
            text-align: center; 
            border: 1px solid #fecaca; 
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; font-size: 0.9rem; }
        
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 1rem; color: #9ca3af; }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

        .password-toggle { position: absolute; right: 1rem; background: none; border: none; color: #9ca3af; cursor: pointer; }

        .form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.85rem; }
        .checkbox-wrapper { display: flex; align-items: center; gap: 0.4rem; color: #4b5563; cursor: pointer; }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-login:hover { background: #1d4ed8; transform: translateY(-1px); }

        .divider { display: flex; align-items: center; text-align: center; margin: 1.5rem 0; color: #9ca3af; font-size: 0.8rem; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e5e7eb; }
        .divider span { padding: 0 10px; }

        .social-login { display: flex; justify-content: center; gap: 1rem; margin-bottom: 1.5rem; }
        .social-btn { width: 45px; height: 45px; border: 1px solid #d1d5db; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; font-size: 1.1rem; color: #4b5563; }
        .social-btn:hover { border-color: #2563eb; background: #eff6ff; }

        .register-link { text-align: center; color: #4b5563; font-size: 0.9rem; }
        .register-link a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fa-solid fa-house"></i>
            ResidenceFinder
        </div>
        
        <h1>Student Login</h1>
        <p class="subtitle">Welcome back! Please enter your details.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">University Email</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="name@university.ac.za"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="forgot_password.php" class="forgot-link" style="color: #2563eb; text-decoration: none;">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
        </form>

        <div class="divider"><span>or continue with</span></div>

        <div class="social-login">
            <button class="social-btn" type="button"><i class="fa-brands fa-google"></i></button>
            <button class="social-btn" type="button"><i class="fa-brands fa-apple"></i></button>
        </div>

        <div class="register-link">
            Don't have an account? <a href="student_signup.php">Sign up for free</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>