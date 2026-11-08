<?php
session_start();
unset($_SESSION['judge_id'], $_SESSION['judge_name']);
session_regenerate_id(true);
header('Location: login.php');
exit;