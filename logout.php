<?php
/**
 * logout.php
 * Proses logout & redirect ke login
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
header('Location: ' . BASE_URL . '/login.php?logout=1');
exit;
