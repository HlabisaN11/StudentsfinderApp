<?php
// index.php - StudentResidenceFinder Landing Page
// No PHP redirect needed anymore since we're using direct links
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Residence Finder | Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Hero Background - Updated with Unsplash image */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                        url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA==&auto=format&fit=crop&w=1470&q=80') center/cover no-repeat;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background: #0d5c63;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            gap: 3rem;
        }

        .hero-text {
            flex: 1;
            color: #fff;
            max-width: 600px;
        }

        .hero-text h1 {
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
        }

        .hero-text p {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
        }

        /* Login Card */
        .login-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-card h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            margin-bottom: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-student {
            background: #2563eb;
            color: #fff;
        }

        .btn-student:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-landlord {
            background: #fff;
            color: #2563eb;
            border: 2px solid #2563eb;
        }

        .btn-landlord:hover {
            background: #eff6ff;
            transform: translateY(-2px);
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 1.5rem 0;
        }

        .btn-admin {
            background: #16a34a;
            color: #fff;
        }

        .btn-admin:hover {
            background: #15803d;
            transform: translateY(-2px);
        }

        /* Footer */
        footer {
            background: #1f2937;
            color: #9ca3af;
            text-align: center;
            padding: 1.5rem;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .main-content {
                flex-direction: column;
                padding: 3rem 5%;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .nav-links {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <!-- Navbar -->
        <nav class="navbar">
            <a href="/" class="logo">
                <i class="fa-solid fa-house"></i>
                StudentResidenceFinder
            </a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="hero-text">
                <h1>Find Your Perfect<br>Student Home</h1>
                <p>Search for affordable, safe, and convenient student accommodations near your university.</p>
            </div>

            <div class="login-card">
                <h2>Welcome Back!</h2>
                
                <!-- Fixed: Changed to <a> tags with direct links -->
                <a href="student_login.php" class="btn btn-student">
                    <i class="fa-solid fa-user-graduate"></i>
                    Student Login
                </a>
                                    
                <a href="landlord_login.php" class="btn btn-landlord">
                    <i class="fa-solid fa-user-tie"></i>
                    Landlord Login
                </a>
                
                <div class="divider"></div>
                
                <a href="admin_login.php" class="btn btn-admin">
                    <i class="fa-solid fa-user-shield"></i>
                    Admin Login
                </a>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> StudentResidenceFinder. All rights reserved.</p>
    </footer>
</body>
</html>