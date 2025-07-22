<?php
// index.php
session_start();

// Routing sederhana
$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require 'src/views/login.php';
        break;
    case 'dashboard':
        require 'src/views/dashboard.php';
        break;
    default:
        require 'src/views/404.php';
        break;
} 