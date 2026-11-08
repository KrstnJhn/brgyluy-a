<?php
session_start();
unset($_SESSION['admin_id']);
session_regenerate_id(true);
header('Location: log-in.php');
exit;