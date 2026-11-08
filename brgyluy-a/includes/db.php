<?php
$host = 'localhost';
$user = 'root';        // default XAMPP user
$password = '';        // default XAMPP password (empty)
$dbname = 'tabulation_system';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the database has a shared active-event flag used by all devices.
$check = $conn->query("SHOW COLUMNS FROM events LIKE 'is_current'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE events ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 0 AFTER event_date");
}

// Do not auto-reset the current event here. That would overwrite the admin's chosen event on every page load.
// If no event is marked active, we pick the latest event once as a fallback.
$has_active = $conn->query("SELECT id FROM events WHERE is_current = 1 LIMIT 1");
if ($has_active && $has_active->num_rows === 0) {
    $latest = $conn->query("SELECT id FROM events ORDER BY event_date DESC, id DESC LIMIT 1");
    if ($latest && $latest->num_rows > 0) {
        $latest_id = (int)$latest->fetch_assoc()['id'];
        $conn->query("UPDATE events SET is_current = 1 WHERE id = $latest_id");
    }
}
?>