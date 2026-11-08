<?php
// Public contestant registration - no auth required
session_start();
require_once 'includes/db.php';

// Upload directory
$upload_dir = 'assets/images/contestants/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$message = '';
$error = '';

$events_result = $conn->query("SELECT id, name FROM events WHERE is_current = 1 ORDER BY event_date DESC, id DESC");
$events = $events_result ? $events_result->fetch_all(MYSQLI_ASSOC) : [];
$active_event_ids = array_map('intval', array_column($events, 'id'));
$session_event_id = (int)($_SESSION['current_event_id'] ?? 0);
$selected_event_id = in_array($session_event_id, $active_event_ids, true)
    ? $session_event_id
    : (int)($active_event_ids[0] ?? 0);

if ($selected_event_id > 0) {
    $_SESSION['current_event_id'] = $selected_event_id;
}

function uploadProfileImage($file, $upload_dir) {
    $allowed = ['jpg', 'jpeg', 'png'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Reject if PHP upload exceeded max size (e.g., post_max_size / upload_max_filesize)
        // so the user gets a proper message instead of failing silently.
        if (!empty($file['error']) && (int)$file['error'] === UPLOAD_ERR_INI_SIZE) {
            return false;
        }
        if (!empty($file['error']) && (int)$file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return false;
        }
    $target_dir = $upload_dir;
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $file['size'] <= $max_size) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                return $filename;
            }
        }
    }
    return false;
}

if ($_POST && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $event_id = (int)($_POST['event_id'] ?? 0);
    if (in_array($event_id, $active_event_ids, true)) {
        $selected_event_id = $event_id;
        $_SESSION['current_event_id'] = $event_id;
    } else {
        $event_id = 0;
    }
    $category_ids = isset($_POST['categories']) ? $_POST['categories'] : [];
    $image_filename = '';

    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Explicitly show size warning when file exceeds 5MB
        if (!empty($_FILES['profile_image']['size']) && (int)$_FILES['profile_image']['size'] > (5 * 1024 * 1024)) {
            $error = 'Image is too large. Maximum size is 5MB.';
        } else {
            $image_filename = uploadProfileImage($_FILES['profile_image'], $upload_dir);
            if (!$image_filename) {
                $error = 'Invalid image. Use JPG/PNG under 5MB.';
            }
        }
    }

    if (empty($name)) {
        $error = 'Full name is required.';
    } elseif ($event_id <= 0) {
        $error = 'Please select an active event.';
    } elseif (empty($category_ids)) {
        $error = 'Please select at least one category.';
    } elseif (empty($error)) {
// Insert contestant
        $stmt = $conn->prepare("INSERT INTO contestants (name, description, profile_image, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("sss", $name, $description, $image_filename);
        if ($stmt->execute()) {
            $contestant_id = $conn->insert_id;
            // Link categories
            foreach ($category_ids as $cat_id) {
                $stmt_assign = $conn->prepare("INSERT INTO contestant_category (contestant_id, category_id) VALUES (?, ?)");
                $stmt_assign->bind_param("ii", $contestant_id, $cat_id);
                $stmt_assign->execute();
            }
            $message = 'Registration Submitted successfully!';
                 } else {
                     $error = 'Registration failed. Please try again.';
                 }
    }
}

// Fetch categories grouped by event for JS filtering
$categories_by_event = [];
$result = $conn->query("SELECT c.id, c.name, c.event_id, e.name as event_name FROM categories c JOIN events e ON c.event_id = e.id ORDER BY e.event_date DESC, c.sort_order");
while ($row = $result->fetch_assoc()) {
    $event_id = $row['event_id'];
    if (!isset($categories_by_event[$event_id])) {
        $categories_by_event[$event_id] = [];
    }
    $categories_by_event[$event_id][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - Brgy. Luy-A SK</title>
    <link rel="icon" type="image/png" href="assets/images/sk_logo.png">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800x;
            margin: 0 auto;
        }
        .hero {
            text-align: center;
            color: #ffbb00;
            padding: 2rem 1rem;
        }
        .form-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .form-row > div {
            flex: 1;
            min-width: 250px;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        .categories-container {
            max-height: 150px;
            overflow-y: auto;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 1rem;
            background: #f8f9fa;
        }
        .categories-container label {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }
        .categories-container input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover { 
            background: #5a67d8; 
            transform: translateY(-2px); 
        }

        .btn-secondary {
            background: #ffbb00;
            color: white;
        }

            .footer-wrapper {
                color: #fff;
                text-align: center;
                padding: 1rem 0;
            }
            .footer a {
                color: #ffbb00;
                text-decoration: none;
                font-weight: 600;
            }
            .footer a:hover {
                text-decoration: underline;
            }
        @media (max-width: 640px) {
            .form-row { flex-direction: column; }
            .form-row > div { min-width: auto; }
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
             <div class="logo-container" style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <img src="assets/images/sk_logo.png" alt="SK Logo" style="max-width: 200px; height: 100px; object-fit: cover; border-radius: 12px;">
                <img src="assets/images/luy-a_seal.png" alt="LUY-A Logo" style="max-width: 200px; height: 100px; object-fit: cover; border-radius: 12px;">
                <///img src="assets/images/tukuran_logo.jpg" alt="Tukuran Logo" style="max-width: 200px; height: 100px; object-fit: cover; border-radius: 12px;">
            </div>
            <div style="display: flex; justify-content: center; gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap;">
                   
                    <div>
                        <h1>Brgy. Luy-A Online Event Registration</h1>
                        <p style="font-size: 0.9rem; font-weight: light; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">Luy-A, Tukuran, Zamboanga Del Sur</p>
                    </div>
                
            </div>
            <p style="color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">We’d Love to have you! Fill-up, Grab your spot by setting up your profile, and pick your categories in our event.</p>
        </section>

        <div class="form-section">
            <?php if (empty($events)): ?>
                <div class="message">Registration is currently unavailable. Please check back later.</div>
            <?php elseif (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <p style="text-align: center; margin-top: 1rem;">
                    <a href="register.php" class="btn btn-primary">Register Again</a>
                </p>
            <?php elseif (!empty($message)): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <p style="text-align: center; margin-top: 1rem;">
                    <div style="text-align: center; margin-top: 1rem;">
                         <a href="register.php" class="btn btn-primary">Register Another</a>
                    </div>
                </p>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="register" value="1">
                    
                    <div class="form-row">
                        <div>
                            <label>Full Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" required placeholder="Enter Team/Full name">
                        </div>
                        <div>
                            <label>Profile-Image/Team-Logo <span style="color:red;">*</span> (.jpg/.png 5MB) </label>
                            <input type="file" name="profile_image" required placeholder="Image (.jpg/.png/ 5MB)" accept="image/jpeg,image/png">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <?php if (count($events) > 1): ?>
                                <label>Event <span style="color:red;">*</span></label>
                                <select name="event_id" id="event_id" required onchange="filterCategories()">
                                    <option value="">Select Event</option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?= (int)$event['id'] ?>" <?= (int)$event['id'] === $selected_event_id ? 'selected' : '' ?>><?= htmlspecialchars($event['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif (count($events) === 1): ?>
                                <label>Event</label>
                                <p><?= htmlspecialchars($events[0]['name']) ?></p>
                                <input type="hidden" name="event_id" id="event_id" value="<?= (int)$events[0]['id'] ?>">
                            <?php else: ?>
                                <p>No active event is available for registration.</p>
                                <input type="hidden" name="event_id" id="event_id" value="">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label>Categories <span style="color:red;">*</span> (select your designated category)</label>
                        <div class="categories-container" id="categories_container">
                            <em>Select an event first to see categories</em>
                        </div>
                    </div>

                    <div class="form-row">
                        <div style="flex: 1 ;">
                            <label>Short Bio/Description</label>
                            <textarea name="description" rows="4" placeholder="Tell us about yourself... [if you are TEAM please list all your members]"></textarea>
                        </div>
                    </div>       
                        <button type="submit" class="btn btn-primary" style="background: #667eea; color: white;" >Register Now</button>
                        <//a href="index.php" class="btn btn-secondary"><//a>
                </form>
            <?php endif; ?>
        </div>
    </div>
     <div class="footer-wrapper">
        <div class="footer">
           &copy; <?= date('Y') ?> Brgy. Luy-A SKystem. Developed by <a href="https://www.facebook.com/krstnjhngngra623/" target="_blank">Krstn Jhn Gngr</a>
        </div>
        
    </div>

    <script>
        const categoriesByEvent = <?php echo json_encode($categories_by_event); ?>;

        function filterCategories() {
            const eventId = document.getElementById('event_id').value;
            const container = document.getElementById('categories_container');
            
            if (!eventId || !categoriesByEvent[eventId]) {
                container.innerHTML = '<em>Select an event first to see categories</em>';
                return;
            }

            let html = '';
            categoriesByEvent[eventId].forEach(cat => {
                html += `
                    <label>
                        <input type="checkbox" name="categories[]" value="${cat.id}">
                        ${cat.name}
                    </label>
                `;
            });
            container.innerHTML = html;
        }

        filterCategories();
    </script>
</body>
</html>
