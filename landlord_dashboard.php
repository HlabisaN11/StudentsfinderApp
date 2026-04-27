<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// 1. Database Connection
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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'landlord') {
    header('Location: landlord_login.php');
    exit;
}

// 3. Fetch Landlord Data
$stmt = $pdo->prepare("SELECT landlord_id, full_name, email FROM landlordTBL WHERE landlord_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$landlord = $stmt->fetch();

if (!$landlord) {
    session_destroy();
    header('Location: landlord_login.php');
    exit;
}

$msg = '';
$error = '';

// 4. Handle Property Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    $title = trim($_POST['title']);
    $price = $_POST['price'];
    $address = trim($_POST['address']);
    
    $target_dir = "uploads/properties/";
    
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            $error = "Failed to create upload directory.";
        }
    }
    
    if (empty($error) && isset($_FILES["property_image"]) && $_FILES["property_image"]["error"] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES["property_image"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_ext, $allowed)) {
            $error = "Only JPG, PNG, GIF, WEBP files allowed.";
        } else {
            $file_name = 'property_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["property_image"]["tmp_name"], $target_file)) {
                $stmt = $pdo->prepare("INSERT INTO propertiesTBL (landlord_id, title, price, address, image_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $price, $address, $target_file]);
                $msg = "Property added successfully!";
            } else {
                $error = "Error uploading image. Check folder permissions.";
            }
        }
    } else if (empty($error)) {
        $error = "Please select an image.";
    }
}

// 5. Fetch properties
$my_properties = [];
try {
    $propStmt = $pdo->prepare("SELECT * FROM propertiesTBL WHERE landlord_id = ? ORDER BY property_id DESC");
    $propStmt->execute([$_SESSION['user_id']]);
    $my_properties = $propStmt->fetchAll();
} catch (PDOException $e) {
    $error = "Properties table not found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard | ResidenceFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f8fafc; 
            padding-top: 80px;
            color: #1e293b;
        }
        
        /* Header */
        header {
            position: fixed; top: 0; width: 100%; height: 70px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
            color: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2.5rem; z-index: 1000;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
        }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 1.4rem; font-weight: 700; }
        .nav-right { display: flex; align-items: center; gap: 24px; }
        .nav-right a { 
            color: white; text-decoration: none; font-weight: 500; 
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 8px; transition: all 0.3s;
        }
        .nav-right a:hover { background: rgba(255,255,255,0.15); }

        .container { max-width: 1280px; margin: auto; padding: 30px 20px; }
        
        /* Alerts */
        .alert { 
            padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; 
            display: flex; align-items: center; gap: 12px; font-weight: 500;
            animation: slideDown 0.4s ease;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Form Card */
        .card { 
            background: white; padding: 32px; border-radius: 16px; 
            margin-bottom: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .card h3 { 
            font-size: 1.5rem; margin-bottom: 24px; display: flex; 
            align-items: center; gap: 10px; color: #0f172a;
        }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 0; }
        label { 
            display: block; margin-bottom: 8px; font-weight: 600; 
            color: #334155; font-size: 0.9rem;
        }
        input[type="text"], input[type="number"], input[type="file"] { 
            width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; 
            border-radius: 10px; font-size: 0.95rem; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        input:focus { 
            outline: none; border-color: #2563eb; 
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        input[type="file"] { padding: 10px; cursor: pointer; }
        
        .btn-save { 
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); 
            color: white; border: none; padding: 14px 24px; 
            border-radius: 10px; cursor: pointer; width: 100%; 
            font-weight: 600; font-size: 1rem; transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }
        .btn-save:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.4);
        }

        /* Property Section */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .section-header h2 { font-size: 1.75rem; color: #0f172a; }
        .property-count { 
            background: #dbeafe; color: #1e40af; padding: 6px 14px; 
            border-radius: 20px; font-weight: 600; font-size: 0.9rem;
        }

        /* Property Grid */
        .property-grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
            gap: 24px; 
        }
        .prop-card { 
            background: white; border-radius: 16px; overflow: hidden; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0; transition: all 0.3s;
        }
        .prop-card:hover { 
            transform: translateY(-6px); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        .prop-img-wrap { 
            width: 100%; height: 220px; overflow: hidden; 
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            position: relative;
        }
        .prop-img { 
            width: 100%; height: 100%; object-fit: cover; 
            transition: transform 0.4s;
        }
        .prop-card:hover .prop-img { transform: scale(1.05); }
        .prop-badge {
            position: absolute; top: 12px; right: 12px;
            background: rgba(22, 163, 74, 0.95); color: white;
            padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;
            font-weight: 600; backdrop-filter: blur(10px);
        }
        .prop-info { padding: 20px; }
        .prop-info h4 { 
            font-size: 1.25rem; margin-bottom: 8px; color: #0f172a;
            font-weight: 700;
        }
        .prop-location { 
            color: #64748b; font-size: 0.9rem; margin-bottom: 12px;
            display: flex; align-items: center; gap: 6px;
        }
        .price { 
            color: #2563eb; font-weight: 700; font-size: 1.5rem;
            display: flex; align-items: baseline; gap: 6px;
        }
        .price span { font-size: 0.9rem; color: #64748b; font-weight: 500; }
        
        .empty-state { 
            grid-column: 1 / -1; text-align: center; padding: 80px 20px; 
            background: white; border-radius: 16px; border: 2px dashed #cbd5e1;
        }
        .empty-state i { font-size: 4rem; color: #cbd5e1; margin-bottom: 20px; }
        .empty-state h3 { color: #475569; margin-bottom: 8px; }
        .empty-state p { color: #94a3b8; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .property-grid { grid-template-columns: 1fr; }
            header { padding: 0 1rem; }
            .container { padding: 20px 15px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo"><i class="fa-solid fa-house-chimney"></i> ResidenceFinder</div>
        <div class="nav-right">
            <a href="landlord_profile.php"><i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($landlord['full_name']) ?></a>
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <?php if ($msg): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fa-solid fa-circle-plus"></i> List a New Property</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Property Title</label>
                        <input type="text" name="title" placeholder="e.g. Modern Studio Apartment" required>
                    </div>
                    <div class="form-group">
                        <label>Monthly Rent (ZAR)</label>
                        <input type="number" name="price" placeholder="5000" min="0" step="100" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Full Address</label>
                    <input type="text" name="address" placeholder="123 Campus St, Soshanguve, 0152" required>
                </div>
                <div class="form-group">
                    <label>Property Image</label>
                    <input type="file" name="property_image" accept="image/png,image/jpeg,image/webp" required>
                </div>
                <button type="submit" name="add_property" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Save Property
                </button>
            </form>
        </div>

        <div class="section-header">
            <h2>My Listed Properties</h2>
            <span class="property-count"><?= count($my_properties) ?> Properties</span>
        </div>
        
        <div class="property-grid">
            <?php if (count($my_properties) > 0): ?>
                <?php foreach ($my_properties as $prop): ?>
                <div class="prop-card">
                    <div class="prop-img-wrap">
                        <img src="<?= htmlspecialchars($prop['image_path']) ?>" 
                             class="prop-img" 
                             alt="<?= htmlspecialchars($prop['title']) ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop'">
                        <span class="prop-badge"><?= htmlspecialchars($prop['status']) ?></span>
                    </div>
                    <div class="prop-info">
                        <h4><?= htmlspecialchars($prop['title']) ?></h4>
                        <p class="prop-location">
                            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($prop['address']) ?>
                        </p>
                        <p class="price">
                            R <?= number_format($prop['price']) ?> <span>/month</span>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-building-circle-xmark"></i>
                    <h3>No properties listed yet</h3>
                    <p>Use the form above to add your first property listing</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>