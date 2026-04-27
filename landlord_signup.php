<?php
// landlord_signup.php - Landlord Registration Page
session_start();

// Enable error reporting to catch issues causing 500 error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$message = '';
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
    // If DB connection fails, this will now show the error instead of a 500 page
    die("DB Connection failed: " . $e->getMessage());
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $agreed = isset($_POST['agree']);

    // Basic Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($phone) || empty($address)) {
        $error = "All required fields must be filled.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!$agreed) {
        $error = "You must agree to the Landlord Terms.";
    } else {
        try {
            // FIX: Ensure the table name is EXACTLY 'landlordsTBL' (plural) as per your DB
            $check = $pdo->prepare("SELECT email FROM landlordsTBL WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                // Hash password and insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // FIX: Ensure table name is 'landlordsTBL'
                $sql = "INSERT INTO landlordsTBL (full_name, email, password_hash, company_name, phone_number, property_address) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$full_name, $email, $hashed_password, $company, $phone, $address])) {
                    $message = "Account created successfully! <a href='landlord_login.php' style='color:inherit; font-weight:bold;'>Login here</a>";
                }
            }
        } catch (PDOException $e) {
            // This captures database-specific errors (like missing columns)
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Registration | ResidenceFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .navbar {
            background-color: #2563eb;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-brand { font-size: 1.25rem; font-weight: bold; display: flex; align-items: center; gap: 0.5rem; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; }

        .container {
            max-width: 800px;
            margin: 3rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        h1 { text-align: center; margin-bottom: 2rem; font-size: 1.75rem; color: #111827; }

        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group.full-width { grid-column: span 2; }

        label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.85rem; color: #374151; }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background-color: #1a3321;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-submit:hover { background-color: #122417; }

        .login-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; }
        .login-footer a { color: #2563eb; text-decoration: none; font-weight: 600; }

        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } .form-group.full-width { grid-column: span 1; } }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-brand"><i class="fa-solid fa-house-chimney"></i> StudentResidenceFinder</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="landlord_signup.php">Landlord Signup</a>
            <a href="landlord_login.php">Login</a>
        </div>
    </nav>

    <div class="container">
        <h1>Landlord Registration</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter your name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Company Name (Optional)</label>
                    <input type="text" name="company" class="form-control" placeholder="Enter company name">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+27 ..." required>
                </div>
                <div class="form-group full-width">
                    <label>Property Address</label>
                    <input type="text" name="address" class="form-control" placeholder="Enter full address of your property" required>
                </div>
            </div>

            <div class="checkbox-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="checkbox" name="agree" id="agree" required>
                <label for="agree" style="font-weight: normal; margin-bottom: 0;">I agree to the <a href="#" style="color: #2563eb; font-weight: 600;">Landlord Terms</a></label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-user-plus"></i> Create Landlord Account
            </button>
        </form>

        <div class="login-footer">
            Already have an account? <a href="landlord_login.php">Log in</a>
        </div>
    </div>

</body>
</html>