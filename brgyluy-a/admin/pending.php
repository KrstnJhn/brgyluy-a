<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Upload directory for image deletion on decline
$upload_dir = '../assets/images/contestants/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['approve'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE contestants SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = 'Contestant approved successfully.';
            } else {
                $error = 'Contestant not found or already processed.';
            }
        } else {
            $error = 'Error approving contestant.';
        }
        $stmt->close();
    } elseif (isset($_POST['decline'])) {
        $id = (int)$_POST['id'];
        // Get image
        $stmt_img = $conn->prepare("SELECT profile_image FROM contestants WHERE id = ?");
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        $img = $result_img->fetch_assoc()['profile_image'] ?? '';
        if ($img && file_exists($upload_dir . $img)) {
            unlink($upload_dir . $img);
        }
        // Delete links and contestant
        $stmt_cat = $conn->prepare("DELETE FROM contestant_category WHERE contestant_id = ?");
        $stmt_cat->bind_param("i", $id);
        $stmt_cat->execute();
$stmt = $conn->prepare("DELETE FROM contestants WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = 'Contestant declined and removed.';
        } else {
            $error = 'Error declining contestant.';
        }
        $stmt->close();
    }
}

// Fetch pending contestants
$pending = [];
$result = $conn->query("
    SELECT c.*, GROUP_CONCAT(cat.name SEPARATOR ', ') as categories 
    FROM contestants c 
    LEFT JOIN contestant_category cc ON c.id = cc.contestant_id 
    LEFT JOIN categories cat ON cc.category_id = cat.id 
    WHERE c.status = 'pending'
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
while ($row = $result->fetch_assoc()) {
    $pending[] = $row;
}

// Get all categories for display
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Admin</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Same styles as contestants.php */
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f1f1f1; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .action-buttons { align-items: center; gap: 4px; }
        .action-buttons form { margin-bottom: 1px; }
        .action-buttons .btn { margin-top: 1px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .profile-img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; }
        .status-badge { background: #ff9800; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        
        /* Photo modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90vw;
            max-height: 90vh;
            width: auto;
            height: auto;
            max-width: 700px;
            object-fit: contain;
            border-radius: 8px;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #ddd;
        }
        .caption {
            margin: auto;
            padding: 10px;
            width: 90%;
            text-align: center;
            color: #ccc;
            font-size: 18px;
        }
        .clickable {
            transition: transform 0.2s;
        }
        .clickable:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        @media (max-width: 768px) { table { font-size: 0.9rem; } }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h1>⏳ Pending Contestant - <?= count($pending) ?></h1>
            <a href="pending.php" class="btn btn-secondary">Refresh</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>🎉 No pending approvals!</h3>
                <p>All submitted contestants have been processed.</p>
                <a href="contestants.php" class="btn btn-success">View Approved Contestants</a>
            </div>
        <?php else: ?>
            <a href="contestants.php" class="btn btn-primary" style="display: flex; justify-content: space-between; align-items: center; font-size:1.1rem; margin-bottom: 1.5rem">Contestant List</a>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Categories</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td>
                                <?php if ($c['profile_image']): ?>
                                    <img src="../assets/images/contestants/<?= htmlspecialchars($c['profile_image']) ?>" 
                                         alt="<?= htmlspecialchars($c['name']) ?>" 
                                         class="profile-img clickable" 
                                         style="width:60px;height:60px;object-fit:cover;border-radius:50%;cursor:pointer;border:2px solid #ddd;"
                                         data-fullsrc="../assets/images/contestants/<?= htmlspecialchars($c['profile_image']) ?>"
                                         data-name="<?= htmlspecialchars($c['name']) ?>"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div style="width:60px;height:60px;background:#eee;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['description'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars($c['categories'] ?: 'None') ?></td>
                            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" onsubmit="return confirm('Approve <?= addslashes($c['name']) ?>?')">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" name="approve" class="btn btn-success">✅</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Decline and delete <?= addslashes($c['name']) ?>?')">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" name="decline" class="btn btn-danger">❌</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        </div>
        

        <!-- Photo Modal -->
        <div id="photoModal" class="modal">
            <span class="close">&times;</span>
            <img class="modal-content" id="modalImg">
            <div id="caption" class="caption"></div>
        </div>

    <script>
    // Modal functionality
    const modal = document.getElementById('photoModal');
    const modalImg = document.getElementById('modalImg');
    const captionText = document.getElementById('caption');
    const closeBtn = document.querySelector('.close');

    // Open modal on image click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('clickable')) {
            modal.style.display = 'block';
            modalImg.src = e.target.dataset.fullsrc;
            captionText.innerHTML = e.target.dataset.name;
        }
    });

    // Close modal
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
</body>
</html>
