<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// 1. DB Connection
$host = 'localhost';
$dbname = 'student_residence_db'; 
$user = 'root';           
$pass = '';               
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

// 2. Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: student_login.php');
    exit;
}

// 3. Fetch Student Data - matches your studentsTBL
try {
    $stmt = $pdo->prepare("SELECT student_id, full_name, student_number, email, university, preferred_campus FROM studentsTBL WHERE student_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
} catch (PDOException $e) {
    die("studentsTBL error: " . $e->getMessage());
}

if (!$student) {
    session_destroy();
    header('Location: student_login.php');
    exit;
}

// 4. Fetch ALL Available Properties - FIXED: ORDER BY property_id not created_at
$residences = [];
try {
    $filter_sql = "SELECT p.*, l.full_name as landlord_name, l.phone_number 
                   FROM propertiesTBL p 
                   JOIN landlordTBL l ON p.landlord_id = l.landlord_id 
                   WHERE p.status = 'Available'";

    $params = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_filters'])) {
        if (!empty($_POST['title_search'])) {
            $filter_sql .= " AND p.title LIKE ?";
            $params[] = '%' . $_POST['title_search'] . '%';
        }
        if (!empty($_POST['location'])) {
            $filter_sql .= " AND p.address LIKE ?";
            $params[] = '%' . $_POST['location'] . '%';
        }
        if (!empty($_POST['min_rent'])) {
            $filter_sql .= " AND p.price >= ?";
            $params[] = $_POST['min_rent'];
        }
        if (!empty($_POST['max_rent'])) {
            $filter_sql .= " AND p.price <= ?";
            $params[] = $_POST['max_rent'];
        }
    }

    $filter_sql .= " ORDER BY p.property_id DESC";
    $stmt = $pdo->prepare($filter_sql);
    $stmt->execute($params);
    $residences = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = "Database error: " . $e->getMessage();
}

$saved = [];
$messages = [];
$viewings = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ResidenceFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; }
        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1470&q=80') center/cover no-repeat;
            opacity: 0.03; z-index: -1;
        }
        .container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 300px; background: #fff; border-right: 1px solid #e2e8f0;
            padding: 1.5rem; position: sticky; top: 0; height: 100vh; overflow-y: auto;
        }
        .filter-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: #fff; padding: 0.875rem 1rem; border-radius: 10px;
            font-weight: 600; margin: -1.5rem -1.5rem 1.5rem -1.5rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .filter-group { margin-bottom: 1.5rem; }
        .filter-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; color: #334155; }
        .filter-group input {
            width: 100%; padding: 0.65rem; border: 2px solid #e2e8f0; border-radius: 8px; 
            font-size: 0.9rem; transition: all 0.2s;
        }
        .filter-group input:focus {
            outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .range-inputs { display: flex; gap: 0.5rem; align-items: center; }
        .btn-apply {
            width: 100%; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
            color: #fff; border: none; padding: 0.875rem; border-radius: 8px; 
            font-weight: 600; cursor: pointer; transition: 0.3s;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-apply:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4); }
        .main { flex: 1; padding: 2rem 2.5rem; }
        .section-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem;
        }
        .section-title { 
            font-size: 1.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem;
            color: #0f172a;
        }
        .property-count-badge {
            background: #dbeafe; color: #1e40af; padding: 6px 16px;
            border-radius: 20px; font-weight: 600; font-size: 0.9rem;
        }
        .residence-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
            gap: 1.75rem; margin-bottom: 3rem;
        }
        .card {
            background: #fff; border-radius: 16px; overflow: hidden; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 40px rgba(0,0,0,0.08);
            transition: 0.3s; border: 1px solid #e2e8f0;
        }
        .card:hover { transform: translateY(-6px); box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
        .card-img { position: relative; height: 220px; background: #e0e7ff; }
        .card-img img { width: 100%; height: 100%; object-fit: cover; }
        .badge {
            position: absolute; top: 0.875rem; right: 0.875rem; padding: 0.375rem 0.875rem; 
            border-radius: 8px; font-size: 0.75rem; font-weight: 700; color: #fff;
            backdrop-filter: blur(10px);
        }
        .badge.Available { background: rgba(22, 163, 74, 0.95); }
        .card-body { padding: 1.25rem; }
        .card-title-row { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem; }
        .card-title { font-size: 1.15rem; font-weight: 700; color: #0f172a; }
        .heart-btn { 
            background: #f8fafc; border: 2px solid #e2e8f0; width: 36px; height: 36px; 
            border-radius: 50%; cursor: pointer; transition: 0.2s;
        }
        .heart-btn:hover { background: #fef2f2; border-color: #ef4444; color: #ef4444; }
        .card-location { 
            color: #64748b; font-size: 0.875rem; margin-bottom: 1rem; 
            display: flex; align-items: center; gap: 0.375rem;
        }
        .card-footer { display: flex; justify-content: space-between; align-items: center; }
        .price { font-size: 1.5rem; font-weight: 700; color: #2563eb; }
        .price span { font-size: 0.875rem; color: #64748b; font-weight: 500; }
        .btn-details {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
            color: #fff; border: none; padding: 0.625rem 1.125rem;
            border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.875rem;
            transition: 0.2s;
        }
        .btn-details:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .empty-state { 
            grid-column: 1 / -1; text-align: center; padding: 80px 20px; 
            background: white; border-radius: 16px; border: 2px dashed #cbd5e1;
        }
        .empty-state i { font-size: 4rem; color: #cbd5e1; margin-bottom: 20px; }
        .alert-error { 
            background: #fee2e2; color: #991b1b; padding: 14px 18px; 
            border-radius: 12px; margin-bottom: 24px; font-weight: 500;
        }
        .profile-section { margin-top: 3rem; }
        .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem; }
        .profile-card { 
            background: #fff; border-radius: 16px; padding: 2rem; text-align: center; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 40px rgba(0,0,0,0.08);
        }
        .profile-avatar {
            width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 1rem;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 2.5rem; font-weight: 700;
        }
        .form-card { 
            background: #fff; border-radius: 16px; padding: 2rem; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 40px rgba(0,0,0,0.08);
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-card input {
            width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; 
            border-radius: 8px; background: #f8fafc;
        }
        .form-card label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: #334155; }
        @media (max-width: 1024px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; }
            .profile-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .main { padding: 1.5rem 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="filter-header">
                <i class="fa-solid fa-sliders"></i> Search Filters
            </div>
            <form method="POST">
                <div class="filter-group">
                    <label>Search by Title</label>
                    <input type="text" name="title_search" placeholder="e.g. Studio, Apartment" value="<?= htmlspecialchars($_POST['title_search'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. Braamfontein" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label>Rent Range (ZAR)</label>
                    <div class="range-inputs">
                        <input type="number" name="min_rent" placeholder="Min" value="<?= htmlspecialchars($_POST['min_rent'] ?? '') ?>">
                        <span>to</span>
                        <input type="number" name="max_rent" placeholder="Max" value="<?= htmlspecialchars($_POST['max_rent'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" name="apply_filters" class="btn-apply">
                    <i class="fa-solid fa-magnifying-glass"></i> Apply Filters
                </button>
            </form>
        </aside>

        <main class="main">
            <?php if (isset($db_error)): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($db_error) ?></div>
            <?php endif; ?>

            <div class="section-header">
                <h1 class="section-title"><i class="fa-solid fa-building"></i> Available Residences</h1>
                <span class="property-count-badge"><?= count($residences) ?> Properties</span>
            </div>
            <div class="residence-grid">
                <?php if (count($residences) > 0): ?>
                    <?php foreach($residences as $r): ?>
                    <div class="card">
                        <div class="card-img">
                            <img src="<?= htmlspecialchars($r['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($r['title']) ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop'">
                            <span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span>
                        </div>
                        <div class="card-body">
                            <div class="card-title-row">
                                <h3 class="card-title"><?= htmlspecialchars($r['title']) ?></h3>
                                <button class="heart-btn"><i class="fa-regular fa-heart"></i></button>
                            </div>
                            <p class="card-location">
                                <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($r['address']) ?>
                            </p>
                            <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 1rem;">
                                <i class="fa-solid fa-user"></i> <?= htmlspecialchars($r['landlord_name']) ?>
                            </p>
                            <div class="card-footer">
                                <div class="price">R<?= number_format($r['price']) ?><span>/mo</span></div>
                                <button class="btn-details">View Details</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-building-circle-xmark"></i>
                        <h3>No properties found</h3>
                        <p>Try adjusting your filters or check back later</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-section">
                <h2 class="section-title" style="margin-bottom:1.5rem;"><i class="fa-solid fa-user"></i> My Profile</h2>
                <div class="profile-grid">
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                        </div>
                        <h3><?= htmlspecialchars($student['full_name']) ?></h3>
                        <p style="color: #64748b; margin-bottom: 1.5rem;"><?= htmlspecialchars($student['university']) ?></p>
                        <button class="btn-details" style="width: 100%;">Edit Profile</button>
                    </div>
                    <div class="form-card">
                        <h3 style="margin-bottom:1.5rem; color: #0f172a;">Personal Information</h3>
                        <div class="form-grid">
                            <div>
                                <label>Full Name</label>
                                <input type="text" value="<?= htmlspecialchars($student['full_name']) ?>" readonly>
                            </div>
                            <div>
                                <label>Student Number</label>
                                <input type="text" value="<?= htmlspecialchars($student['student_number'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                            </div>
                            <div>
                                <label>University</label>
                                <input type="text" value="<?= htmlspecialchars($student['university']) ?>">
                            </div>
                            <div>
                                <label>Preferred Campus</label>
                                <input type="text" value="<?= htmlspecialchars($student['preferred_campus'] ?? 'N/A') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn-apply" style="margin-top:1.5rem;">Save Changes</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>