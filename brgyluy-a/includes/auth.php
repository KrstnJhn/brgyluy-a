<?php
session_start();

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isJudgeLoggedIn() {
    return isset($_SESSION['judge_id']);
}

function redirectIfNotAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function redirectIfNotJudge() {
    if (!isJudgeLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>