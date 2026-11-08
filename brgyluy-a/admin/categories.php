<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $event_id = $_POST['event_id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $sort_order = $_POST['sort_order'] ?: 0;
    $stmt = $conn->prepare("INSERT INTO categories (event_id, name, description, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('issi', $event_id, $name, $desc, $sort_order);
    $stmt->execute();
    $msg = "Category added successfully!";
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['category_action']) && $_POST['category_action'] == 'edit') {
    $id = $_POST['id'];
    $event_id = $_POST['event_id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $sort_order = $_POST['sort_order'] ?: 0;
    $stmt = $conn->prepare("UPDATE categories SET event_id=?, name=?, description=?, sort_order=? WHERE id=?");
    $stmt->bind_param('issii', $event_id, $name, $desc, $sort_order, $id);
    $stmt->execute();
    $msg = "Category updated successfully!";
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $msg = "Category deleted.";
}

// Handle Criteria Operations
if (isset($_GET['manage'])) {
    $current_category_id = (int)$_GET['manage'];
}

// Handle Add Criteria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_criteria'])) {
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $max_score = $_POST['max_score'];
    $sort_order = $_POST['sort_order'] ?: 0;
    $stmt = $conn->prepare("INSERT INTO criteria (category_id, name, description, max_score, sort_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issdi', $category_id, $name, $desc, $max_score, $sort_order);
    if ($stmt->execute()) {
        $msg = "Criterion added successfully!";
    } else {
        $error = "Error adding criterion.";
    }
}

// Handle Edit Criteria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_criteria'])) {
    $id = $_POST['id'];
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $max_score = $_POST['max_score'];
    $sort_order = $_POST['sort_order'] ?: 0;
    $stmt = $conn->prepare("UPDATE criteria SET category_id=?, name=?, description=?, max_score=?, sort_order=? WHERE id=?");
    $stmt->bind_param('issdii', $category_id, $name, $desc, $max_score, $sort_order, $id);
    if ($stmt->execute()) {
        $msg = "Criterion updated successfully!";
    } else {
        $error = "Error updating criterion.";
    }
}

// Handle Delete Criteria
if (isset($_GET['delete_criteria'])) {
    $id = (int)$_GET['delete_criteria'];
    $stmt = $conn->prepare("DELETE FROM criteria WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $msg = "Criterion deleted.";
    } else {
        $error = "Error deleting criterion.";
    }
    $stmt->close();
}

// Handle Toggle Lock Category
if (isset($_GET['toggle_lock'])) {
    $id = (int)$_GET['toggle_lock'];
    $stmt = $conn->prepare("UPDATE categories SET is_locked = 1 - is_locked WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $msg = "Category toggled.";
    } else {
        $error = "Error toggling lock status.";
    }
    $stmt->close();
}

// If event_id is provided in URL, filter categories to only that event
$filter_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Fetch categories with event names, lock status and criteria count (optionally filtered by event)
$categories_sql = "SELECT c.*, e.name AS event_name, c.is_locked, (SELECT COUNT(*) FROM criteria WHERE category_id = c.id) as criteria_count FROM categories c JOIN events e ON c.event_id = e.id";
if ($filter_event_id > 0) {
    $categories_sql .= " WHERE c.event_id = " . $filter_event_id;
}
$categories_sql .= " ORDER BY e.event_date DESC, c.sort_order";
$categories = $conn->query($categories_sql);

// Fetch all events for dropdown
$events = $conn->query("SELECT id, name FROM events ORDER BY event_date DESC");

$js_events = $conn->query("SELECT id, name FROM events ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);
$js_categories = $conn->query("SELECT c.id, c.event_id, c.name, c.description, c.sort_order FROM categories c ORDER BY c.sort_order")->fetch_all(MYSQLI_ASSOC);
$js_criteria = $conn->query("SELECT * FROM criteria ORDER BY category_id, sort_order")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Tabulation System</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
<link rel="stylesheet" href="../assets/style.css">
    <style>
      .category-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
      }
      .category-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        position: relative;
      }
      #criteriaManagerModal .category-modal-content {
        width: 95%;
        max-width: 1100px;
      }
      .category-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .4rem;
        min-width: 150px;
      }
      .category-actions .btn {
        box-sizing: border-box;
        text-align: center;
        white-space: nowrap;
      }
      .criteria-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .4rem;
        min-width: 130px;
      }
      .criteria-actions .btn {
        box-sizing: border-box;
        text-align: center;
      }
      .category-close {
        color: #aaa;
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
      }
      .category-close:hover {
        color: #000;
      }
        .criteria-modal {
          display: none;
          position: fixed;
          z-index: 1001;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0,0,0,0.5);
        }
        .criteria-modal-content {
          background-color: #fefefe;
          margin: 5% auto;
          padding: 30px;
          border-radius: 10px;
          width: 90%;
          max-width: 500px;
          max-height: 80vh;
          overflow-y: auto;
          position: relative;
        }
        .criteria-close {
          color: #aaa;
          position: absolute;
          right: 20px;
          top: 20px;
          font-size: 28px;
          font-weight: bold;
          cursor: pointer;
        }
        .criteria-close:hover {
          color: #000;
        }
      </style>
    </head>

<body>
    <div class="container">
        <div class="main-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h2>Manage Categories</h2>
                <a href="events.php" class="btn btn-secondary">BACK</a>
            </div>
            <?php if (isset($msg)): ?>
                <p class="form-messages success"><?= htmlspecialchars($msg) ?></p>
            <?php endif; 
            if (isset($error)): ?>
                <p class="form-messages error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

   

    <?php if (isset($_GET['manage'])): 
        $current_category_id = (int)$_GET['manage'];
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param('i', $current_category_id);
        $stmt->execute();
        $cat_result = $stmt->get_result();
        $category = $cat_result->fetch_assoc();
        if (!$category) {
            echo "<p>Category not found.</p>";
        } else {
            $criteria = getCriteria($current_category_id);
            $event_stmt = $conn->prepare("SELECT e.name FROM events e JOIN categories c ON e.id = c.event_id WHERE c.id = ?");
            $event_stmt->bind_param('i', $current_category_id);
            $event_stmt->execute();
            $event_result = $event_stmt->get_result();
            $event_name = $event_result->fetch_assoc()['name'] ?? 'N/A';
            $event_stmt->close();
        }
    
    endif; ?>
      
        <!-- Criteria Popup Modal -->
        <div id="criteriaModal" class="criteria-modal">
          <div class="criteria-modal-content">
            <span class="criteria-close" id="criteriaClose">&times;</span>
              <h3 id="criteriaModalTitle">Add Criterion</h3>
              <form id="criteriaForm" method="POST">
              <input type="hidden" name="category_id" id="criteriaCatId">
              <input type="hidden" name="id" id="criteriaId">
              <input type="hidden" name="criteria_action" id="criteriaAction">

              <div class="form-row two-col">
                <div>
                  <label>Criterion Name <span style="color:red;">*</span></label>
                  <input type="text" name="name" id="criName" required>
                </div>
                <div>
                  <label>Max Score <span style="color:red;">*</span></label>
                  <input type="number" step="0.01" min="0" name="max_score" id="criMaxScore" required>
                </div>
              </div>
              <div class="form-row two-col">
                <div style="grid-column: span 2;">
                  <label>Description</label>
                  <textarea name="description" id="criDesc" style="width:100%; min-height:80px;"></textarea>
                </div>
              </div>
              <div class="form-row two-col">
                <div>
                  <label>Sort Order</label>
                  <input type="number" name="sort_order" id="criSort" value="0">
                </div>
              </div>
              <div style="text-align: right; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Save Criterion</button>
                <button type="button" class="btn btn-secondary" onclick="closeCriteriaModal()">Cancel</button>
              </div>
            </form>
          </div>
        </div>


        
        <?php if (isset($criteria) && empty($criteria)): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem;">
                <h3>Criteria for "<?= htmlspecialchars($category['name']) ?>" (Event: <?= htmlspecialchars($event_name) ?>)</h3>
                <button type="button" onclick="openAddCriteriaModal(<?= $category['id'] ?>)" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem;">➕ Add Criterion</button>
            </div>
            <p class="form-messages">No criteria yet. Add some above!</p>
            
            
        <?php else: ?>
            <?php if (isset($criteria)): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem;">
                <h3>Criteria for "<?= htmlspecialchars($category['name']) ?>" (Event: <?= htmlspecialchars($event_name) ?>)</h3>
                <button type="button" onclick="openAddCriteriaModal(<?= $category['id'] ?>)" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem;">➕ Add Criterion</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Max Score</th>
                            <th>Sort Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($criteria as $cri): ?>
                        <tr>
                            <td><?= htmlspecialchars($cri['name']) ?></td>
                            <td><?= htmlspecialchars($cri['description']) ?></td>
                            <td><?= $cri['max_score'] ?></td>
                            <td><?= $cri['sort_order'] ?></td>
                            <td>
                                <button type="button" onclick="openEditCriteriaModal(<?= $cri['id'] ?>)" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.85rem;">Edit</button>
                                <a href="categories.php?delete_criteria=<?= $cri['id'] ?>&manage=<?= $current_category_id ?>" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.85rem; background:#ef4444;" onclick="return confirm('Delete this criterion?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        
            <!-- Categories -->
            <?php if ($categories->num_rows > 0): ?>
                <div class="table-responsive">
                <button type="button" onclick="openAddModal()" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem; margin-bottom:rem;">➕ Add Category</button>
                <h3 style="margin-top:1rem;">Existing Categories (<?= $categories->num_rows ?>)</h3>   
                <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Sort Order</th>
                                <th>#Criteria</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $categories->data_seek(0); while($row = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['event_name']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= $row['sort_order'] ?></td>
                                <td><?= $row['criteria_count'] ?></td>
                                <td>
                                    <div class="category-actions">
                                        <button type="button" onclick="openEditCategoryModal(<?= $row['id'] ?>)" class="btn btn-secondary" style="padding:.3rem .3rem; font-size:.85rem;">Edit</button>
                                        <a href="categories.php?delete=<?= $row['id'] ?>" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.85rem; background:#ef4444; border-color:#dc2626;" onclick="return confirm('Delete this category? All associated criteria and scores will also be deleted.')">Delete</a>
                                        <button type="button" onclick="openCriteriaManager(<?= $row['id'] ?>)" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.85rem;">Criteria</button>
                                        <a href="categories.php?toggle_lock=<?= $row['id'] ?>" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.85rem;"><?= $row['is_locked'] ? '🔓 Unlock' : '🔒 Lock' ?></a>
                                    </div>
                                </td>
                                
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
              <button type="button" onclick="openAddModal()" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem; margin-bottom:rem;">➕ Add Category</button>
            <p class="form-messages">No categories yet. Add your first category above!</p>
            <?php endif; ?>

                <!-- Criteria Management Popup Modal -->
                <div id="criteriaManagerModal" class="category-modal">
                  <div class="category-modal-content">
                    <span class="category-close" id="criteriaManagerClose">&times;</span>
                    <h3 id="criteriaManagerTitle">Manage Criteria</h3>
                    <button type="button" id="addManagedCriterion" class="btn btn-primary" style="margin-bottom:1rem;">➕ Add Criterion</button>
                    <div id="criteriaManagerList"></div>
                  </div>
                </div>

                <!-- Category Popup Modal -->
                <div id="categoryModal" class="category-modal">
              <div class="category-modal-content">
                <span class="category-close" id="categoryClose">&times;</span>
                <h3 id="categoryModalTitle">Add Category</h3>
                <form id="categoryForm" method="POST">
                  <input type="hidden" name="category_action" id="categoryAction">
                  <input type="hidden" name="id" id="categoryId">
                  <input type="hidden" name="event_id" id="eventIdHidden" value="">

                  <div class="form-row two-col">
                    <div>
                      <label>Event <span style="color:red;"></span></label>
                      <select name="event_id" required>
                          <?php while($event = $events->fetch_assoc()): ?>
                              <option value="<?= $event['id'] ?>">
                                  <?= htmlspecialchars($event['name']) ?>
                              </option>
                          <?php endwhile; ?>
                      </select>
                    </div>
                    <div>
                      <label>Sort Order</label>
                      <input type="number" name="sort_order" id="catSort" value="0" min="0">
                    </div>
                  </div>
                   <div>
                      <label>Category Name <span style="color:red;">*</span></label>
                      <input type="text" name="name" id="catName" required>
                    </div>
                  <div class="form-row two-col">
                    <div style="grid-column: span 2;">
                      <label>Description</label>
                      <textarea name="description" id="catDesc" style="width:100%; min-height:80px;"></textarea>
                    </div>
                  </div>
                  <div style="text-align: right; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Save Category</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                  </div>
                </form>
              </div>
            </div>

        <?php 
        // If editing category
        if (isset($_GET['edit'])): 
            $id = (int)$_GET['edit'];
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $cat = $res->fetch_assoc();
            $events->data_seek(0);
            if ($cat):
        ?>
            <h3 style="margin-top:5rem;" id="category-edit">Edit "<?= htmlspecialchars($cat['name']) ?>"</h3>
            <form method="post" style="max-width:600px;">
                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                <div class="form-row two-col">
                    <div>
                        <label>Event <span style="color:red;">*</span></label>
                        <select name="event_id" required>
                            <?php while($event = $events->fetch_assoc()): ?>
                                <option value="<?= $event['id'] ?>" <?= ($event['id'] == $cat['event_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label>Category Name <span style="color:red;">*</span></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                    </div>
                </div>
                <div class="form-row two-col">
                    <div style="grid-column: span 2;">
                        <label>Description</label>
                        <textarea name="description"><?= htmlspecialchars($cat['description']) ?></textarea>
                    </div>
                </div>
                <div class="form-row two-col">
                    <div>
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="<?= $cat['sort_order'] ?>">
                    </div>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="submit" name="edit" class="btn btn-primary">Update Category</button>
                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; endif; ?>
        </div>
    </div>
<script>
const eventsData = <?= json_encode($js_events) ?>;
const categoriesData = <?= json_encode($js_categories) ?>;
const criteriaData = <?= json_encode($js_criteria) ?>;

function populateEventHiddenField(selectedId) {
  const hidden = document.getElementById('eventIdHidden');
  if (!hidden) return;
  hidden.value = selectedId != null ? String(selectedId) : '';
}

function getManagedEventId() {
  const url = new URL(window.location.href);
  const eventId = url.searchParams.get('event_id');
  return eventId ? parseInt(eventId) : null;
}

function openAddModal() {
  document.getElementById('categoryAction').value = 'add';
  document.getElementById('categoryId').value = '';
  document.getElementById('categoryModalTitle').textContent = 'Add New Category';
  document.getElementById('catName').value = '';
  document.getElementById('catDesc').value = '';
  document.getElementById('catSort').value = 0;

  // Auto-select event based on current URL (e.g. ?event_id=...)
  const managedEventId = getManagedEventId();
  populateEventHiddenField(managedEventId);

  document.getElementById('categoryModal').style.display = 'block';
}

function openEditCategoryModal(catId) {
  // Open modal + fill hidden id/event_id for AJAX edit submission.
  // The modal uses #categoryForm submit handler (category_action=edit + add_category/edit_category flags).

  const cat = categoriesData.find(c => parseInt(c.id) === parseInt(catId));
  if (!cat) {
    alert('Category not found');
    return;
  }
  document.getElementById('categoryAction').value = 'edit';
  document.getElementById('categoryId').value = cat.id;
  document.getElementById('categoryModalTitle').textContent = 'Edit: ' + cat.name;
  document.getElementById('catName').value = cat.name;
  document.getElementById('catDesc').value = cat.description || '';
  document.getElementById('catSort').value = cat.sort_order || 0;
  populateEventHiddenField(cat.event_id);
  document.getElementById('categoryModal').style.display = 'block';
}

function closeCategoryModal() {
  document.getElementById('categoryModal').style.display = 'none';
}

function openAddCriteriaModal(catId) {
  document.getElementById('criteriaAction').value = 'add';
  document.getElementById('criteriaId').value = '';
  document.getElementById('criteriaCatId').value = catId;
  document.getElementById('criteriaModalTitle').textContent = 'Add Criterion';
  document.getElementById('criName').value = '';
  document.getElementById('criDesc').value = '';
  document.getElementById('criMaxScore').value = '';
  document.getElementById('criSort').value = 0;
  document.getElementById('criteriaModal').style.display = 'block';
}

function openEditCriteriaModal(criId) {
  const cri = criteriaData.find(c => parseInt(c.id) === parseInt(criId));
  if (!cri) {
    alert('Criterion not found');
    return;
  }
  document.getElementById('criteriaAction').value = 'edit';
  document.getElementById('criteriaId').value = cri.id;
  document.getElementById('criteriaCatId').value = cri.category_id;
  document.getElementById('criteriaModalTitle').textContent = 'Edit: ' + cri.name;
  document.getElementById('criName').value = cri.name;
  document.getElementById('criDesc').value = cri.description || '';
  document.getElementById('criMaxScore').value = cri.max_score;
  document.getElementById('criSort').value = cri.sort_order || 0;
  document.getElementById('criteriaModal').style.display = 'block';
}

function closeCriteriaModal() {
  document.getElementById('criteriaModal').style.display = 'none';
}

function openCriteriaManager(categoryId) {
  const category = categoriesData.find(c => parseInt(c.id) === parseInt(categoryId));
  if (!category) {
    alert('Category not found');
    return;
  }

  document.getElementById('criteriaManagerTitle').textContent = 'Criteria for "' + category.name + '"';
  document.getElementById('addManagedCriterion').onclick = () => openAddCriteriaModal(categoryId);

  const list = document.getElementById('criteriaManagerList');
  list.replaceChildren();
  const categoryCriteria = criteriaData.filter(c => parseInt(c.category_id) === parseInt(categoryId));
  if (categoryCriteria.length === 0) {
    const emptyMessage = document.createElement('p');
    emptyMessage.className = 'form-messages';
    emptyMessage.textContent = 'No criteria yet. Add some above!';
    list.appendChild(emptyMessage);
  } else {
    const table = document.createElement('table');
    const headers = ['Name', 'Description', 'Max Score', 'Sort Order', 'Actions'];
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    headers.forEach(label => {
      const th = document.createElement('th');
      th.textContent = label;
      headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = document.createElement('tbody');
    categoryCriteria.forEach(criterion => {
      const row = document.createElement('tr');
      [criterion.name, criterion.description, criterion.max_score, criterion.sort_order].forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = value == null ? '' : String(value);
        row.appendChild(cell);
      });

      const actions = document.createElement('td');
      const actionButtons = document.createElement('div');
      actionButtons.className = 'criteria-actions';
      const editButton = document.createElement('button');
      editButton.type = 'button';
      editButton.className = 'btn btn-secondary';
      editButton.style.cssText = 'padding:.3rem .6rem; font-size:.85rem;';
      editButton.textContent = 'Edit';
      editButton.onclick = () => openEditCriteriaModal(criterion.id);
      actionButtons.appendChild(editButton);

      const deleteLink = document.createElement('a');
      deleteLink.href = 'categories.php?delete_criteria=' + encodeURIComponent(criterion.id) + '&manage=' + encodeURIComponent(categoryId);
      deleteLink.className = 'btn btn-secondary';
      deleteLink.style.cssText = 'padding:.3rem .6rem; font-size:.85rem; background:#ef4444;';
      deleteLink.textContent = 'Delete';
      deleteLink.onclick = () => confirm('Delete this criterion?');
      actionButtons.appendChild(deleteLink);
      actions.appendChild(actionButtons);
      row.appendChild(actions);
      tbody.appendChild(row);
    });
    table.appendChild(tbody);
    const tableContainer = document.createElement('div');
    tableContainer.className = 'table-responsive';
    tableContainer.appendChild(table);
    list.appendChild(tableContainer);
  }

  document.getElementById('criteriaManagerModal').style.display = 'block';
}

function closeCriteriaManager() {
  document.getElementById('criteriaManagerModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('categoryClose').onclick = closeCategoryModal;
  document.getElementById('criteriaClose').onclick = closeCriteriaModal;
  document.getElementById('criteriaManagerClose').onclick = closeCriteriaManager;
  window.onclick = function(e) {
    if (e.target.id === 'categoryModal') closeCategoryModal();
    if (e.target.id === 'criteriaModal') closeCriteriaModal();
    if (e.target.id === 'criteriaManagerModal') closeCriteriaManager();
  };

  document.getElementById('categoryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const action = document.getElementById('categoryAction').value;
    if (action === 'add') {
      formData.append('add_category', '1');
    } else {
      formData.append('category_action', 'edit');
    }
    try {
      const response = await fetch('', {
        method: 'POST',
        body: formData
      });
      if (response.ok) {
        location.reload();
      } else {
        alert('Error saving. Check console.');
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    }
  });


  // Criteria form handler added
  document.getElementById('criteriaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const action = document.getElementById('criteriaAction').value;
    if (action === 'add') {
      formData.append('add_criteria', '1');
    } else if (action === 'edit') {
      formData.append('edit_criteria', '1');
    }
    try {
      const response = await fetch('', {
        method: 'POST',
        body: formData
      });
      if (response.ok) {
        location.reload();
      } else {
        alert('Error saving criterion. Check console.');
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    }
  });

});
                            
</script>
</body>
</html>
