<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Handle session for current event
if (!isset($_SESSION['current_event_id'])) {
    $_SESSION['current_event_id'] = null;
}

// Handle form submissions
$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_event'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $event_date = $_POST['event_date'];
        
        if (empty($name) || empty($event_date)) {
            $error = 'Name and date are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO events (name, description, event_date) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $name, $description, $event_date);
            if ($stmt->execute()) {
                $msg = 'Event created successfully!';
                $new_event_id = $conn->insert_id;
                $conn->query("UPDATE events SET is_current = 1 WHERE id = $new_event_id");
                $_SESSION['current_event_id'] = $new_event_id;
            } else {
                $error = 'Error creating event.';
            }
        }
    } elseif (isset($_POST['edit_event']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $event_date = $_POST['event_date'];
        
        if (empty($name) || empty($event_date)) {
            $error = 'Name and date are required.';
        } else {
            $stmt = $conn->prepare("UPDATE events SET name=?, description=?, event_date=? WHERE id=?");
            $stmt->bind_param('sssi', $name, $description, $event_date, $id);
            if ($stmt->execute()) {
                $msg = 'Event updated successfully!';
                if ($_SESSION['current_event_id'] == $id) {
                    $_SESSION['current_event_id'] = $id;
                }
            } else {
                $error = 'Error updating event.';
            }
        }
    } elseif (isset($_POST['set_current']) || isset($_POST['unset_current'])) {
        $event_id = (int)$_POST['event_id'];
        $is_current = isset($_POST['set_current']);
        $stmt = $conn->prepare("UPDATE events SET is_current = ? WHERE id = ?");
        $current_value = $is_current ? 1 : 0;
        $stmt->bind_param('ii', $current_value, $event_id);
        if ($stmt->execute()) {
            if ($is_current) {
                $_SESSION['current_event_id'] = $event_id;
                $msg = 'Current event set!';
            } else {
                if ((int)$_SESSION['current_event_id'] === $event_id) {
                    $_SESSION['current_event_id'] = null;
                }
                $msg = 'Event removed from current events.';
            }
        } else {
            $error = 'Error updating event status.';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = $conn->query("SELECT COUNT(*) as cnt FROM categories WHERE event_id = $id")->fetch_assoc()['cnt'];
    if ($check == 0) {
        $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $msg = 'Event deleted.';
            if ($_SESSION['current_event_id'] == $id) {
                $_SESSION['current_event_id'] = null;
            }
        } else {
            $error = 'Error deleting event.';
        }
    } else {
        $error = 'Cannot delete event with categories.';
    }
}

// Keep the session's selected event aligned with the set of current events.
$current_events_result = $conn->query("SELECT id FROM events WHERE is_current = 1 ORDER BY event_date DESC, id DESC");
$current_event_ids = [];
while ($current_events_result && $current_event = $current_events_result->fetch_assoc()) {
    $current_event_ids[] = (int)$current_event['id'];
}
if (!in_array((int)($_SESSION['current_event_id'] ?? 0), $current_event_ids, true)) {
    $_SESSION['current_event_id'] = $current_event_ids[0] ?? null;
}

// Fetch all events
$events = getEvents();

// Get data for current/selected event
$current_event = null;
$categories = $contestants_count = $judges_count = 0;
$selected_event_id = $_SESSION['current_event_id'] ?? (isset($_GET['view']) ? (int)$_GET['view'] : null);

if ($selected_event_id) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param('i', $selected_event_id);
    $stmt->execute();
    $current_event = $stmt->get_result()->fetch_assoc();
    
    if ($current_event) {
        $categories = $conn->query("SELECT COUNT(*) as cnt FROM categories WHERE event_id = $selected_event_id")->fetch_assoc()['cnt'];
        $contestants_count = $conn->query("SELECT COUNT(DISTINCT c.id) as cnt FROM contestants c JOIN contestant_category cc ON c.id = cc.contestant_id JOIN categories cat ON cc.category_id = cat.id WHERE cat.event_id = $selected_event_id")->fetch_assoc()['cnt'];
        $judges_count = $conn->query("SELECT COUNT(DISTINCT j.id) as cnt FROM judges j JOIN judge_category jc ON j.id = jc.judge_id JOIN categories cat ON jc.category_id = cat.id WHERE cat.event_id = $selected_event_id")->fetch_assoc()['cnt'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management - Tabulation System</title>
    <link rel="icon" type="image/png" href="../assets/images/sk_logo.png">
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        .calendar-container { max-width: 900px; margin: 0 auto 2rem; }
        .event-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; }
        .activities-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .activity-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 12px; text-align: center; }
        .activity-number { font-size: 2rem; font-weight: 700; color: #1e40af; }
        .events-table { margin-top: 1rem; }
        .current-badge { background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 20px; font-size: 0.8rem; }
        #activities-panel { display: none; }
        .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; }
        .actions-col { display: flex; flex-direction: column; gap: 0.25rem; align-items: stretch; }
        @media (min-width: 768px) { .actions-col { flex-direction: row; align-items: center; gap: 0.5rem; } }
        .actions-col .btn { padding: 0.4rem 0.8rem; font-size: 0.85rem; flex: 1; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid #eee; border-radius: 12px 12px 0 0; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #eee; border-radius: 0 0 12px 12px; text-align: right; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        @media (max-width: 600px) { .modal-content { width: 95%; margin: 10% auto; } }
    </style>
</head>
<body>
    <div class="container">
        
        <?php if ($msg): ?><div class="form-messages success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="form-messages error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        
        <!-- Calendar -->
        <div class="calendar-container">
            <h1>📅 Event Calendar</h1>
            <div id="calendar"></div>
        </div>

        <!-- Add New Event Button -->
        <div class="form-section">
            <button id="addEventBtn" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem; margin-bottom:1rem;">➕ Add New Event</button>

        <!-- Events Table -->
        <div class="table-responsive">
            <h3>Events List (<?= count($events) ?>)</h3>
            <table class="events-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['name']) ?></td>
                        <td><?= date('M j, Y', strtotime($event['event_date'])) ?></td>
                        <td>
                            <?php if ((int)$event['is_current'] === 1): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <span class="current-badge">⭐</span>
                                    <button type="submit" name="unset_current" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.8rem;">Stop</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" name="set_current" class="btn btn-secondary" style="padding:.3rem .6rem; font-size:.8rem;">Set Current</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-col">
                                <button class="btn btn-warning edit-event-btn" data-id="<?= $event['id'] ?>" data-name="<?= htmlspecialchars($event['name']) ?>" data-date="<?= $event['event_date'] ?>" data-desc="<?= htmlspecialchars($event['description'] ?? '') ?>">Edit</button>
                                <a href="?delete=<?= $event['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete <?= htmlspecialchars($event['name']) ?>?')">Delete</a>
                                <a href="categories.php?event_id=<?= $event['id'] ?>" class="btn btn-secondary">Categories</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($events)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:2rem;">No events yet. Create one above! 🌟</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>

        <!-- Event Modal -->
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">Add New Event</h3>
                    <span class="close">&times;</span>
                </div>
                <form id="eventForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="eventId" name="id" value="">
                        <div class="form-row two-col">
                            <div>
                                <label>Event Name <span style="color:red;">*</span></label>
                                <input type="text" id="eventName" name="name" required style="width:100%;">
                            </div>
                            <div>
                                <label>Event Date <span style="color:red;">*</span></label>
                                <input type="date" id="eventDate" name="event_date" required style="width:100%;">
                            </div>
                        </div>
                        <div>
                            <label>Description</label>
                            <textarea id="eventDesc" name="description" rows="3" style="width:100%;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="submitBtn" name="add_event" class="btn btn-primary">Create Event</button>
                        <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const modal = document.getElementById('eventModal');
            const modalTitle = document.getElementById('modalTitle');
            const eventForm = document.getElementById('eventForm');
            const eventIdInput = document.getElementById('eventId');
            const eventNameInput = document.getElementById('eventName');
            const eventDateInput = document.getElementById('eventDate');
            const eventDescInput = document.getElementById('eventDesc');
            const submitBtn = document.getElementById('submitBtn');
            
            // Open modal functions
            window.openAddModal = function() {
                modalTitle.textContent = 'Add New Event';
                submitBtn.name = 'add_event';
                submitBtn.textContent = 'Create Event';
                eventIdInput.value = '';
                eventNameInput.value = '';
                eventDateInput.value = '';
                eventDescInput.value = '';
                modal.style.display = 'block';
            };
            
            window.openEditModal = function(id, name, date, desc) {
                modalTitle.textContent = 'Edit Event';
                submitBtn.name = 'edit_event';
                submitBtn.textContent = 'Update Event';
                eventIdInput.value = id;
                eventNameInput.value = name;
                eventDateInput.value = date;
                eventDescInput.value = desc || '';
                modal.style.display = 'block';
            };
            
            // Close modal
            const closeModal = function() {
                modal.style.display = 'none';
            };
            
            document.querySelector('.close').onclick = closeModal;
            document.getElementById('cancelBtn').onclick = closeModal;
            window.onclick = function(event) {
                if (event.target === modal) closeModal();
            };
            
            // Form submit
            eventForm.onsubmit = function(e) {
                if (submitBtn.name === 'edit_event' && !eventIdInput.value) {
                    e.preventDefault();
                    alert('Edit ID missing');
                    return;
                }
            };
            
            // Add button
            document.getElementById('addEventBtn').onclick = openAddModal;
            
            // Edit buttons
            document.querySelectorAll('.edit-event-btn').forEach(btn => {
                btn.onclick = function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const date = this.dataset.date;
                    const desc = this.dataset.desc;
                    openEditModal(id, name, date, desc);
                };
            });
            
            // Auto-open if ?edit= param (for direct links)
            <?php if (isset($_GET['edit'])): ?>
            const editId = <?= (int)$_GET['edit'] ?>;
            // Find event data
            const eventData = <?= json_encode($events) ?> .find(e => e.id == editId);
            if (eventData) {
                openEditModal(eventData.id, eventData.name, eventData.event_date, eventData.description);
            }
            <?php endif; ?>
            
            // FullCalendar
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                editable: false,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: 3,
                events: <?= json_encode(array_map(function($event) {
                    return [
                        'title' => $event['name'],
                        'start' => $event['event_date'],
                        'extendedProps' => $event,
                        'backgroundColor' => (int)$event['is_current'] === 1 ? '#10b981' : '#667eea',
                        'borderColor' => '#764ba2'
                    ];
                }, $events)) ?>,
                eventClick: function(info) {
                    const eventData = info.event.extendedProps;
                    openEditModal(eventData.id, eventData.name, eventData.event_date, eventData.description || '');
                    info.jsEvent.stopPropagation();
                },
                dateClick: function(info) {
                    // Allow creating event on date click
                    const name = prompt('Event name:');
                    if (name) {
                        const dateInput = document.querySelector('input[name="event_date"]');
                        dateInput.value = info.dateStr;
                        dateInput.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
            calendar.render();

            // Activities modal
            const activitiesModal = document.getElementById('activitiesModal');
            const closeActivitiesBtn = document.getElementById('closeActivitiesBtn');
            const openActivitiesBtn = document.getElementById('openActivitiesBtn');

            const modalClose = function() {
                activitiesModal.style.display = 'none';
            };

            if (closeActivitiesBtn) closeActivitiesBtn.onclick = modalClose;
            if (activitiesModal) {
                window.addEventListener('click', function(e) {
                    if (e.target === activitiesModal) modalClose();
                });
            }

            if (openActivitiesBtn && activitiesModal) {
                openActivitiesBtn.addEventListener('click', function() {
                    activitiesModal.style.display = 'block';
                });
            }

            // Open activities for a specific event id (uses already-rendered PHP values for current selected event)
            window.openActivitiesFor = function(eventId) {
                // If you clicked a different event than the selected one, keep current behavior by navigating
                // so PHP recomputes counts.
                window.location.href = '?view=' + encodeURIComponent(eventId);
                return false;
            };

            <?php if (isset($_GET['view']) && $current_event): ?>
                // Auto-open when arriving with ?view=ID
                const _activitiesAutoOpen = document.getElementById('openActivitiesBtn');
                if (activitiesModal && _activitiesAutoOpen) {
                    activitiesModal.style.display = 'block';
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>
