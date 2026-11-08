<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';

// Handle Add - Hash password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = trim(trim($_POST['password']), PASSWORD_DEFAULT);
    $fullname = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("INSERT INTO judges (username, password, full_name, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $username, $password, $fullname, $email);
    if ($stmt->execute()) {
        $msg = "Judge added successfully!";
    } else {
        $msg = "Error adding judge: " . $stmt->error;
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $fullname = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    if (!empty($_POST['password'])) {
        $password = trim(trim($_POST['password']), PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE judges SET username=?, password=?, full_name=?, email=? WHERE id=?");
        $stmt->bind_param('ssssi', $username, $password, $fullname, $email, $id);
    } else {
        $stmt = $conn->prepare("UPDATE judges SET username=?, full_name=?, email=? WHERE id=?");
        $stmt->bind_param('sssi', $username, $fullname, $email, $id);
    }
    if ($stmt->execute()) {
        $msg = "Judge updated successfully!";
    } else {
        $msg = "Error updating judge: " . $stmt->error;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM judges WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $msg = "Judge deleted successfully.";
    } else {
        $msg = "Error deleting judge.";
    }
    header('Location: judges.php');
    exit;
}

// $all_categories_query filtered to current event (for consistency)
$current_event_id = $_SESSION['current_event_id'] ?? null;

if ($current_event_id && $current_event_id > 0) {
    $all_categories_query = "
        SELECT c.id, c.name, e.name as event_name, c.sort_order
        FROM categories c 
        JOIN events e ON c.event_id = e.id 
        WHERE c.event_id = $current_event_id
        ORDER BY c.sort_order
    ";
} else {
    $all_categories_query = "
        SELECT c.id, c.name, e.name as event_name, c.sort_order
        FROM categories c 
        JOIN events e ON c.event_id = e.id 
        JOIN (
            SELECT id FROM events 
            WHERE event_date >= CURDATE() 
            ORDER BY event_date DESC LIMIT 1
        ) ce ON e.id = ce.id
        ORDER BY c.sort_order
    ";
}

// AJAX endpoint for getting judge categories data - ONLY CURRENT EVENT
if (isset($_GET['ajax_get_judge_cats'])) {
    $judge_id = (int)$_GET['ajax_get_judge_cats'];
    
    // Determine current event ID
    $current_event_id = $_SESSION['current_event_id'] ?? null;
    
    if ($current_event_id && $current_event_id > 0) {
        // Use session current event
        $stmt = $conn->prepare("
            SELECT c.id, c.name, e.name as event_name, c.sort_order
            FROM categories c 
            JOIN events e ON c.event_id = e.id 
            WHERE c.event_id = ?
            ORDER BY c.sort_order
        ");
        $stmt->bind_param('i', $current_event_id);
        $stmt->execute();
        $cats_result = $stmt->get_result();
    } else {
        // Fallback: most recent active event
        $stmt = $conn->prepare("
            SELECT c.id, c.name, e.name as event_name, c.sort_order
            FROM categories c 
            JOIN events e ON c.event_id = e.id 
            JOIN (
                SELECT id FROM events 
                WHERE event_date >= CURDATE() 
                ORDER BY event_date DESC LIMIT 1
            ) ce ON e.id = ce.id
            ORDER BY c.sort_order
        ");
        $stmt->execute();
        $cats_result = $stmt->get_result();
    }
    
    $categories = [];
    while ($cat = $cats_result->fetch_assoc()) {
        $categories[] = $cat;
    }
    
    // Get assigned categories (all, but UI shows only current - user selects from current)
    $stmt = $conn->prepare("SELECT category_id FROM judge_category WHERE judge_id = ?");
    $stmt->bind_param('i', $judge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assigned = [];
    while ($row = $result->fetch_assoc()) {
        $assigned[] = $row['category_id'];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'categories' => $categories,
        'assigned' => $assigned
    ]);
    exit;
}

// Handle Category Assignments
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_categories'])) {
    $judge_id = (int)$_POST['judge_id_for_assign'];
    $selected_categories = $_POST['categories'] ?? [];

    // Delete existing assignments for this judge
    $stmt = $conn->prepare("DELETE FROM judge_category WHERE judge_id = ?");
    $stmt->bind_param('i', $judge_id);
    $stmt->execute();

    // Insert new assignments
    if (!empty($selected_categories)) {
        $stmt = $conn->prepare("INSERT INTO judge_category (judge_id, category_id) VALUES (?, ?)");
        foreach ($selected_categories as $cat_id) {
            $cat_id_int = (int)$cat_id;
            $stmt->bind_param('ii', $judge_id, $cat_id_int);
            $stmt->execute();
        }
    }
    $msg = "Categories assigned successfully!";
}



$stmt = $conn->prepare("SHOW TABLES LIKE 'judges'");
$stmt->execute();
$table_exists = $stmt->get_result()->num_rows > 0;

$judges_query = $conn->query("SELECT * FROM judges ORDER BY full_name ASC");
$judges = $judges_query ? $judges_query : false;

$all_categories = $conn->query($all_categories_query);

// Current judge categories for display/edit
$current_judge_categories = [];
$assign_judge_id = 0;
$judge_info = null;
if (isset($_GET['assign']) || isset($_POST['judge_id_for_assign'])) {
    $assign_judge_id = (int)($_GET['assign'] ?? $_POST['judge_id_for_assign']);
    
    // Get judge info
    $jstmt = $conn->prepare("SELECT full_name FROM judges WHERE id = ?");
    $jstmt->bind_param('i', $assign_judge_id);
    $jstmt->execute();
    $jresult = $jstmt->get_result();
    $judge_info = $jresult->fetch_assoc();
    
    // Get current assignments
    $stmt = $conn->prepare("SELECT category_id FROM judge_category WHERE judge_id = ?");
    $stmt->bind_param('i', $assign_judge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_judge_categories[] = $row['category_id'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Judges - Tabulation System</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
<style>
    .password-toggle {
    cursor: pointer;
    position: absolute;
    right: 12px;
    top: 70%;
    transform: translateY(-50%);
    font-size: 20px;
    background: none;
    padding: 0;
    border-radius: 4px;
    transition: all 0.2s;
    z-index: 2;
    line-height: 1;
    }

    /* Judge Modal Styles */
    .judge-modal {
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
    .judge-modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s;
    position: relative;
    }
    .judge-modal h3 {
    margin-top: 0;
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
    .judge-actions {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        width: max-content;
    }
    .judge-actions .btn {
        width: auto;
        padding: 0.3rem 0.6rem;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .assigned-categories {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }
    .assigned-categories .btn {
        width: auto;
        margin: 0;
        padding: 0.2rem 0.4rem;
        font-size: 0.8rem;
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
    .judge-modal-content {
        margin: 10% 1rem;
        width: calc(100% - 2rem);
        padding: 1.5rem;
    }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem;">
                <h2>Manage Judges</h2>
                <a href="judges.php" class="btn btn-secondary" style="padding:.5rem 2rem; font-size:1rem;">Refresh</a>
            </div>
            
            <?php if (isset($msg)): ?>
                <p class="form-messages success"><?= htmlspecialchars($msg) ?></p>
            <?php endif; ?>

            <?php if (!$table_exists): ?>
                <div class="form-messages error">
                    <strong>Database Setup Required:</strong> judges table missing. 
                    <a href="../tabualtion_system.sql" download>Download SQL</a> and import via phpMyAdmin (localhost/phpmyadmin).
                </div>
            <?php endif; ?>

            <button onclick="openJudgeModal('add')" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem;">Add New Judge</button>

            <h3 style="margin-top:2rem;">Existing Judges (<?= $judges ? $judges->num_rows : 0 ?>)</h3>
            <?php if ($judges && $judges->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Assigned Cats</th>
                                <th>Created</th>
                                <th>Actions</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php $judges->data_seek(0); while($row = $judges->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                                <td>
                                    <?php 
                                    $assigned_q = $conn->query("SELECT COUNT(*) as cnt FROM judge_category WHERE judge_id = " . $row['id']);
                                    $assigned_count = $assigned_q ? $assigned_q->fetch_assoc()['cnt'] : 0;
                                    ?>
                                    <div class="assigned-categories">
                                        <span><?= $assigned_count ?></span>
                                        <button onclick="openCatAssignModal(<?= $row['id'] ?>, '<?= addslashes($row['full_name']) ?>')" class="btn btn-secondary"><?= $assigned_count > 0 ? 'Edit' : 'Assign' ?></button>
                                    </div>
                                </td>

                                <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="judge-actions">
                                        <button onclick="openJudgeModal('edit', <?= $row['id'] ?>, '<?= addslashes($row['username']) ?>', '<?= addslashes($row['full_name']) ?>', '<?= addslashes($row['email'] ?? '') ?>')" class="btn btn-secondary">Edit</button>
                                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-secondary" style="background:#ef4444; border-color:#dc2626;" onclick="return confirm('Delete <?= htmlspecialchars($row['full_name']) ?>? This cannot be undone.')">Delete</a>
                                    </div>
                                </td>

                            </tr>
                            <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="form-messages">No judges yet. Add your first judge above!</p>
            <?php endif; ?>

            <!-- Judge Modal -->
            <div id="judgeModal" class="judge-modal">
                <div class="judge-modal-content">
                    <span class="close-modal" onclick="closeJudgeModal()">&times;</span>
                    <h3 id="modalTitle">Add New Judge</h3>
                    <form id="judgeForm" method="post">
                        <input type="hidden" name="id" id="judgeId">
                        <div class="form-row two-col">
                            <div>
                                <label>Username <span style="color:red;">*</span></label>
                                <input type="text" name="username" id="modalUsername" required maxlength="50">
                            </div>
                            <div style="position: relative;">
                                <label id="modalPasswordLabel">Password <span style="color:red;">*</span></label>
                                <input type="password" name="password" id="modalPassword" required style="padding-right: 40px;">
                                <span class="password-toggle" onclick="togglePassword('modalPassword')" title="Show password">👁️</span>
                            </div>
                        </div>
                        <div class="form-row two-col">
                            <div>
                                <label>Full Name <span style="color:red;">*</span></label>
                                <input type="text" name="full_name" id="modalFullName" required maxlength="100">
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" name="email" id="modalEmail" maxlength="100">
                            </div>
                        </div>
                        <div style="display:flex; gap:1rem; justify-content: flex-end;">
                            <button type="button" onclick="closeJudgeModal()" class="btn btn-secondary">Cancel</button>
                            <button type="submit" id="modalSubmit" name="add" class="btn btn-primary">Add Judge</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Category Assignment Modal -->
            <div id="catAssignModal" class="judge-modal">
                <div class="judge-modal-content" style="max-width: 900px; width: 95%;">
                    <span class="close-modal" onclick="closeCatAssignModal()">&times;</span>
                    <h3 id="catModalTitle">Assign Categories</h3>
                    <form id="catAssignForm" method="post">
                        <input type="hidden" id="catJudgeId" name="judge_id_for_assign">
                        <div id="catListContainer" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem; border-radius: 0.5rem; background: #f9f9f9;">   
                            <!-- Categories loaded here -->
                            <p style="text-align: center; color: #666;">Click Edit/Assign to load categories</p>
                        </div>
                        <div style="display:flex; gap:1rem; justify-content: flex-end; margin-top:1.5rem;">
                            <button type="button" onclick="closeCatAssignModal()" class="btn btn-secondary">Cancel</button>
                            <button type="submit" name="assign_categories" class="btn btn-primary" id="catSaveBtn" disabled>Save Assignments</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
<script>
// Category Assignment Modal Functions
function openCatAssignModal(judgeId, judgeName) {
    document.getElementById('catJudgeId').value = judgeId;
    document.getElementById('catModalTitle').textContent = `Assign Categories to: ${judgeName}`;
    document.getElementById('catAssignModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Load categories via AJAX
    loadCategoriesForJudge(judgeId);
}

function closeCatAssignModal() {
    document.getElementById('catAssignModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('catListContainer').innerHTML = '<p style="text-align: center; color: #666;">Click Edit/Assign to load categories</p>';
    document.getElementById('catSaveBtn').disabled = true;
}

function toggleAllCatCheckboxes() {
    const selectAll = document.getElementById('cat_select_all');
    const checkboxes = document.querySelectorAll('#catListContainer input[name="categories[]"]');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSaveButton();
}

function loadCategoriesForJudge(judgeId) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `judges.php?ajax_get_judge_cats=${judgeId}`, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            populateCategories(data.categories, data.assigned);
        }
    };
    xhr.send();
}

function populateCategories(allCats, assignedCats) {
    let html = '';
    let currentEvent = '';
    
    allCats.forEach(cat => {
        if (cat.event_name !== currentEvent) {
            currentEvent = cat.event_name;
            html += `<h4 style="margin: 1.5rem 0 0.5rem 0; border-bottom: 2px solid #007bff; padding-bottom: 0.25rem;">
                ${cat.event_name}
            </h4>`;
        }
        const checked = assignedCats.includes(cat.id.toString()) ? 'checked' : '';
        html += `
            <label style="display: block; margin: 0.5rem 0; padding: 0.25rem; cursor: pointer; border-radius: 0.25rem; transition: background 0.2s;">
                <input type="checkbox" name="categories[]" value="${cat.id}" 
                       ${checked} style="margin-right: 0.5rem;" onchange="updateSaveButton()">
                ${cat.name}
            </label>`;
    });
    
    document.getElementById('catListContainer').innerHTML = html;
    document.getElementById('cat_select_all').checked = false;
    updateSaveButton();
}

function updateSaveButton() {
    const checkboxes = document.querySelectorAll('#catListContainer input[name="categories[]"]:checked');
    document.getElementById('catSaveBtn').disabled = checkboxes.length === 0;
}

// Cat form submit - reload page on success
document.getElementById('catAssignForm').addEventListener('submit', function(e) {
    // Form submits normally, page reloads with success msg
});

function openJudgeModal(mode, id = '', username = '', fullName = '', email = '') {

    const modal = document.getElementById('judgeModal');
    const form = document.getElementById('judgeForm');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('modalSubmit');
    const passwordLabel = document.getElementById('modalPasswordLabel');
    const passwordInput = document.getElementById('modalPassword');
    const judgeId = document.getElementById('judgeId');
    
    // Reset form
    form.reset();
    
    if (mode === 'add') {
        title.textContent = 'Add New Judge';
        submitBtn.name = 'add';
        submitBtn.textContent = 'Add Judge';
        passwordLabel.innerHTML = 'Password <span style="color:red;">*</span>';
        passwordInput.required = true;
    } else {
        title.textContent = 'Edit Judge';
        submitBtn.name = 'edit';
        submitBtn.textContent = 'Update Judge';
        passwordLabel.innerHTML = 'New Password';
        passwordInput.required = false;
        passwordInput.value = '';
        judgeId.value = id;
        document.getElementById('modalUsername').value = username;
        document.getElementById('modalFullName').value = fullName;
        document.getElementById('modalEmail').value = email;
    }
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeJudgeModal() {
    document.getElementById('judgeModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = passwordInput.nextElementSibling;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
        toggleIcon.title = 'Hide password';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
        toggleIcon.title = 'Show password';
    }
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('judgeModal');
    if (event.target === modal) {
        closeJudgeModal();
    }
}

// Form submission with reload on success
document.getElementById('judgeForm').addEventListener('submit', function(e) {
    // Let form submit normally (POST to same page)
    // Page will reload with success message
});
</script>
</body>
</html>
