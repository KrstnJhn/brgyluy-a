<?php
require_once 'db.php';

function getEvents() {
    global $conn;
    $result = $conn->query("SELECT *, CASE WHEN event_date >= CURDATE() THEN 'active' ELSE 'expired' END as status FROM events ORDER BY event_date DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getActiveEvents() {
    global $conn;
    $result = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCategories($event_id = null) {
    global $conn;
    if ($event_id) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE event_id = ? ORDER BY sort_order");
        $stmt->bind_param('i', $event_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $result = $conn->query("SELECT c.*, e.name AS event_name FROM categories c JOIN events e ON c.event_id = e.id ORDER BY e.event_date DESC, c.sort_order");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

function getCriteria($category_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM criteria WHERE category_id = ? ORDER BY sort_order");
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getJudges() {
    global $conn;
    $result = $conn->query("SELECT id, username, full_name, email FROM judges ORDER BY full_name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getContestants() {
    global $conn;
    $result = $conn->query("SELECT * FROM contestants ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>