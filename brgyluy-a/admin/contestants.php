<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Upload directory
$upload_dir = '../assets/images/contestants/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Function to upload image
function uploadProfileImage($file, $upload_dir) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5MB
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

// Handle form submissions
$message = '';
$error = '';

// Search filters (GET)
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$selected_category_ids = [];
if (isset($_GET['categories'])) {
    $selected_category_ids = is_array($_GET['categories']) ? $_GET['categories'] : [$_GET['categories']];
    $selected_category_ids = array_values(array_filter(array_map('intval', $selected_category_ids), fn($v) => $v > 0));
}


if ($_POST) {
    if (isset($_POST['add_contestant']) || isset($_POST['edit_contestant'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category_ids = isset($_POST['categories']) ? $_POST['categories'] : [];
        $image_filename = '';
        
        // Handle image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_filename = uploadProfileImage($_FILES['profile_image'], $upload_dir);
            if (!$image_filename) {
                $error = 'Invalid image. Use JPG/PNG under 5MB.';
            }
        }
        
        if (empty($name)) {
            $error = 'Name is required.';
        } elseif (empty($error)) {
            if (isset($_POST['add_contestant'])) {
                // Add new
                $stmt = $conn->prepare("INSERT INTO contestants (name, description, profile_image) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $description, $image_filename);
                if ($stmt->execute()) {
                    $contestant_id = $conn->insert_id;
                    foreach ($category_ids as $cat_id) {
                        $stmt_assign = $conn->prepare("INSERT INTO contestant_category (contestant_id, category_id) VALUES (?, ?)");
                        $stmt_assign->bind_param("ii", $contestant_id, $cat_id);
                        $stmt_assign->execute();
                    }
                    $message = 'Contestant added successfully.';
                } else {
                    $error = 'Error adding contestant.';
                }
            } else {
                // Edit
                $id = (int)$_POST['id'];
                // Delete old image if new uploaded
                if ($image_filename) {
                    $stmt_old = $conn->prepare("SELECT profile_image FROM contestants WHERE id = ?");
                    $stmt_old->bind_param("i", $id);
                    $stmt_old->execute();
                    $old_image = $stmt_old->get_result()->fetch_assoc()['profile_image'];
                    if ($old_image && file_exists($upload_dir . $old_image)) {
                        unlink($upload_dir . $old_image);
                    }
                    $stmt = $conn->prepare("UPDATE contestants SET name = ?, description = ?, profile_image = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $description, $image_filename, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE contestants SET name = ?, description = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $name, $description, $id);
                }
                if ($stmt->execute()) {
                    $stmt_clear = $conn->prepare("DELETE FROM contestant_category WHERE contestant_id = ?");
                    $stmt_clear->bind_param("i", $id);
                    $stmt_clear->execute();
                    foreach ($category_ids as $cat_id) {
                        $stmt_assign = $conn->prepare("INSERT INTO contestant_category (contestant_id, category_id) VALUES (?, ?)");
                        $stmt_assign->bind_param("ii", $id, $cat_id);
                        $stmt_assign->execute();
                    }
                    $message = 'Contestant updated successfully.';
                } else {
                    $error = 'Error updating contestant.';
                }
            }
        }
    } elseif (isset($_POST['delete_contestant'])) {
        $id = (int)$_POST['id'];
        // Get image to delete
        $stmt_img = $conn->prepare("SELECT profile_image FROM contestants WHERE id = ?");
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $img = $stmt_img->get_result()->fetch_assoc()['profile_image'];
        if ($img && file_exists($upload_dir . $img)) {
            unlink($upload_dir . $img);
        }
        // Delete rest
        $stmt_assign = $conn->prepare("DELETE FROM contestant_category WHERE contestant_id = ?");
        $stmt_assign->bind_param("i", $id);
        $stmt_assign->execute();
        $stmt_scores = $conn->prepare("DELETE FROM scores WHERE contestant_id = ?");
        $stmt_scores->bind_param("i", $id);
        $stmt_scores->execute();
        $stmt = $conn->prepare("DELETE FROM contestants WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Contestant deleted successfully.';
        } else {
            $error = 'Error deleting contestant.';
        }
    }
}

// Get contestants with categories and image (with optional filters)
$contestants = [];

$where = " WHERE c.status = 'approved' ";
$params = [];
$types = "";

if ($q !== '') {
    $where .= " AND c.name LIKE ? ";
    $params[] = '%' . $q . '%';
    $types .= 's';
}

if (!empty($selected_category_ids)) {
    // Match contestants that have ALL selected categories
    // (change to IN-style if you prefer OR)
    foreach ($selected_category_ids as $cat_id) {
        $where .= " AND EXISTS (SELECT 1 FROM contestant_category cc2 WHERE cc2.contestant_id = c.id AND cc2.category_id = ?) ";
        $params[] = $cat_id;
        $types .= 'i';
    }
}

$sql = "
    SELECT c.*, c.status, GROUP_CONCAT(cat.name SEPARATOR ', ') as categories 
    FROM contestants c 
    LEFT JOIN contestant_category cc ON c.id = cc.contestant_id 
    LEFT JOIN categories cat ON cc.category_id = cat.id 
    " . $where . "
    GROUP BY c.id 
    ORDER BY c.name
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $contestants[] = $row;
}


// Get all categories
$categories = [];
$result = $conn->query("
    SELECT c.* FROM categories c 
    JOIN events e ON c.event_id = e.id 
    ORDER BY e.event_date DESC, c.sort_order
");
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contestants - Tabulation System</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f1f1f1; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-secondary { background: #6c757d; color: white; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .profile-img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: end; }

        /* Modal styles */
        .contestant-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .contestant-modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            overflow-y: auto;
            animation: slideIn 0.3s;
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }
        .close-modal:hover {
            color: #000;
        }
        .modal-categories {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .modal-categories label {
            display: flex;
            align-items: center;
            margin: 0 0 0.5rem;
            cursor: pointer;
        }
        .modal-categories input[type="checkbox"] {
            width: auto;
            margin: 0 0.5rem 0 0;
        }
        .current-image-preview {
            margin-top: 0.5rem;
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        @media (max-width: 768px) {
            .contestant-modal-content {
                margin: 10% 1rem;
                width: calc(100% - 2rem);
                padding: 1.5rem;
            }
            .form-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 5px;">

          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
            <h1>Manage Contestants</h1>
            
            </div>
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php $pending_count = $conn->query("SELECT COUNT(*) as cnt FROM contestants WHERE status = 'pending'")->fetch_assoc()['cnt'] ?? 0; ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
            <a href="pending.php" class="btn btn-success pending-link" style="padding:1rem 1rem; font-size:1.1rem; position: relative; display: inline-flex; align-items: center; justify-content:center;">Pendings
                <span class="pending-badge" style="position:absolute; top:-3px; right: -2.5px; background:#dc3545; color:#fff; font-size:.75rem; font-weight:700; padding:2px 8px; border-radius:999px; line-height:1.5; display: <?= ((int)$pending_count) > 0 ? 'inline-flex' : 'none'; ?>;">
                    <?= (int)$pending_count ?>
                </span>
            </a>
            <button onclick="openContestantModal('add')"class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem;">➕ Add New Contestant</button>
        </div>

<!-- Search / Filters -->
        <div style="margin-bottom: 1.5rem; background:#fafafa; border:1px solid #eee; padding: 1rem; border-radius: 8px;">
            <form method="GET" id="contestantFilterForm" style="display:flex; gap: 1rem; flex-wrap: wrap; align-items: end;">

                <div style="flex: 2; min-width: 260px;">
                    <label style="display:block; font-weight: 600; margin-bottom: .35rem;">Search by name</label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Type a contestant name..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>

                <div style="min-width: 260px; flex: 1;">
                    <label style="display:block; font-weight: 600; margin-bottom: .35rem;">Filter by categories</label>
                    <select name="categories" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#fff;">
                        <option value="" <?php echo empty($selected_category_ids) ? 'selected' : ''; ?>>All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <?php $cat_id = (int)$cat['id']; ?>
                            <option value="<?php echo $cat_id; ?>" <?php echo (count($selected_category_ids) === 1 && in_array($cat_id, $selected_category_ids, true)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; flex-direction: column; gap:.5rem;">
                    <a href="contestants.php" class="btn btn-secondary" style="padding: 10px 18px;">Clear</a>
                </div>
            </form>
        </div>

<!-- Table --> 
        <div class="table-container">
            <h3>Contestants List (<span id="contestantCount"><?php echo count($contestants); ?></span>)</h3>
            <table>

                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Categories</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contestants as $c): ?>
                    <?php 
                    $current_cats = [];
                    $stmt_curr = $conn->prepare("SELECT category_id FROM contestant_category WHERE contestant_id = ?");
                    $stmt_curr->bind_param("i", $c['id']);
                    $stmt_curr->execute();
                    $result_curr = $stmt_curr->get_result();
                    while ($row_curr = $result_curr->fetch_assoc()) {
                        $current_cats[] = $row_curr['category_id'];
                    }
                    ?>
                    <tr data-contestant-id="<?php echo $c['id']; ?>"
                        data-contestant-name="<?php echo htmlspecialchars($c['name']); ?>"
                        data-contestant-description="<?php echo htmlspecialchars($c['description'] ?: ''); ?>"
                        data-contestant-image="<?php echo htmlspecialchars($c['profile_image'] ?: ''); ?>"
                        data-contestant-categories='<?php echo json_encode($current_cats); ?>'>
                        <td>
                            <?php if ($c['profile_image']): ?>
                                <img src="../assets/images/contestants/<?php echo htmlspecialchars($c['profile_image']); ?>" class="profile-img" alt="Profile" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div style="width:60px;height:60px;background:#eee;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#999;">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['description'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($c['categories'] ?: 'None'); ?></td>
                        <td><span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;"><?= strtoupper($c['status']) ?></span></td>
                        <td>
                                
                            <button onclick="openContestantModal(<?php echo $c['id']; ?>)" class="btn btn-warning">✏️</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?php echo addslashes($c['name']); ?>?');">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" name="delete_contestant" class="btn btn-danger">❌</button>
                            </form>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                    <?php if (empty($contestants)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#666;">No contestants yet. Add one above! 📸</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Contestant Modal -->
    <div id="contestantModal" class="contestant-modal">
        <div class="contestant-modal-content">
            <span class="close-modal" onclick="closeContestantModal()">&times;</span>
            <h3 id="modalTitle">Add New Contestant</h3>
            <form id="contestantForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="contestantId">
                <div class="form-row">
                    <div style="flex:2;">
                        <label>Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" id="modalName" required style="width:100%; padding:10px;">
                    </div>
                    <div>
                        <label>Profile Image (JPG/PNG 5MB)</label>
                        <input type="file" name="profile_image" id="modalImageInput" accept="image/jpeg,image/png">
                        <div id="currentImagePreview"></div>
                    </div>
                </div>
                <div style="margin:1rem 0;">
                    <label>Description</label>
                    <textarea name="description" id="modalDescription" rows="2" style="width:100%; padding:10px;"></textarea>
                </div>
                <div>
                    <label>Categories</label>
                    <div id="modalCategories" class="modal-categories">
                        <!-- Populated by JS -->
                    </div>
                </div>
                <div style="display:flex; gap:1rem; justify-content:flex-end; margin-top:1.5rem;">
                    <button type="button" onclick="closeContestantModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" id="modalSubmitBtn" name="add_contestant" class="btn btn-success">Add Contestant</button>
                </div>
            </form>
        </div>
    </div>
    </div>

<script>
        const categories = <?php echo json_encode($categories); ?>;

        let currentContestantId = null;

        function openContestantModal(contestantIdOrMode) {
            const modal = document.getElementById('contestantModal');
            const form = document.getElementById('contestantForm');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('modalSubmitBtn');
            const contestantIdInput = document.getElementById('contestantId');
            const currentImagePreview = document.getElementById('currentImagePreview');
            const categoriesContainer = document.getElementById('modalCategories');

            // Reset form and preview
            form.reset();
            currentImagePreview.innerHTML = '';

            let row = null;
            let currentContestantId = null;

            if (contestantIdOrMode !== 'add') {
                row = document.querySelector(`tr[data-contestant-id="${contestantIdOrMode}"]`);
                console.log('Edit clicked for ID:', contestantIdOrMode, 'Row found:', !!row);
                if (!row) {
                    alert('Contestant not found. Please refresh page.');
                    return;
                }
                currentContestantId = parseInt(contestantIdOrMode);
            }

            // Set mode
            if (contestantIdOrMode === 'add') {
                title.textContent = 'Add New Contestant';
                submitBtn.name = 'add_contestant';
                submitBtn.textContent = 'Add Contestant';
                submitBtn.className = 'btn btn-success';
            } else {
                contestantIdInput.value = currentContestantId;
                title.textContent = 'Edit Contestant';
                submitBtn.name = 'edit_contestant';
                submitBtn.textContent = 'Update Contestant';
                submitBtn.className = 'btn btn-primary';

                // Populate fields from row
                document.getElementById('modalName').value = row.dataset.contestantName || '';
                document.getElementById('modalDescription').value = row.dataset.contestantDescription || '';

                // Image preview
                const currentImage = row.dataset.contestantImage;
                if (currentImage) {
                    currentImagePreview.innerHTML = `
                        <small>Current: <img src="../assets/images/contestants/${currentImage}" class="current-image-preview" alt="Current"></small>
                    `;
                }
                console.log('Populated fields from row data');
            }

            // Populate categories checkboxes
            let html = '';
            categories.forEach(cat => {
                let checked = false;
                if (currentContestantId && row) {
                    try {
                        const catIds = JSON.parse(row.dataset.contestantCategories || '[]');
                        console.log('Parsed categories JSON:', catIds);
                        checked = catIds.includes(cat.id);
                    } catch (e) {
                        console.error('JSON parse error:', e, 'Raw data:', row.dataset.contestantCategories);
                        checked = false;
                    }
                }
                html += `
                    <label>
                        <input type="checkbox" name="categories[]" value="${cat.id}" ${checked ? 'checked' : ''}>
                        ${cat.name}
                    </label>
                `;
            });
            categoriesContainer.innerHTML = html;
            console.log('Categories populated');


            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeContestantModal() {
            document.getElementById('contestantModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Auto-submit filter on typing / changing filters
        (function () {
            const filterForm = document.getElementById('contestantFilterForm');
            if (!filterForm) return;

            const qInput = filterForm.querySelector('input[name="q"]');
            let timer = null;

            if (qInput) {
                qInput.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        filterForm.submit();
                    }, 400);
                });
            }

            const catsSelect = filterForm.querySelector('select[name="categories"]');
            if (catsSelect) {
                catsSelect.addEventListener('change', function () {
                    filterForm.submit();
                });
            }


        })();


        // Close on X click
        document.querySelector('.close-modal').onclick = closeContestantModal;

        // Close on overlay click
        window.onclick = function(event) {
            const modal = document.getElementById('contestantModal');
            if (event.target === modal) {
                closeContestantModal();
            }
        };

        // Close on ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeContestantModal();
            }
        });
    </script>
</body>
</html>
