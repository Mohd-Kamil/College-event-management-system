<?php
// Authentication helper
session_start();
// Example check (expand in later steps)
function isLoggedIn() {
  return isset($_SESSION['user_id']);
}
function isAdmin() {
  return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?>
