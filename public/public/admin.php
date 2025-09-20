<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    exit('Access denied.');
}
echo '<h1>Admin Dashboard</h1><p>Manage site features here.</p>';