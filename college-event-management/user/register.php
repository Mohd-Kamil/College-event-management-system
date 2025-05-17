<?php
require_once '../includes/db.php';
$name = $email = $password = $confirm_password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if ($name === '' || $email === '' || $password === '' || $confirm_password === '') {
    $errors[] = 'All fields are required.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
  }
  if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
  }

  if (!$errors) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
    $stmt->bind_param('sss', $name, $email, $hashed_password);
    if ($stmt->execute()) {
      $success = true;
    } else {
      $errors[] = 'Email already in use or registration error.';
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Registration Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:linear-gradient(135deg,#24c6dc,#5433ff,#ff7e5f);min-height:100vh;display:flex;align-items:center;justify-content:center;}</style>
</head>
<body>
  <div class="card p-5" style="min-width:330px;max-width:420px">
    <?php if (!empty($success)): ?>
      <div class="alert alert-success">Registration successful! <a href="../student-login.html">Login now &rarr;</a></div>
    <?php else: ?>
      <div class="alert alert-danger mb-2">
        <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
      </div>
      <a href="../student-register.html" class="btn btn-secondary w-100 mt-2">Back to Registration</a>
    <?php endif; ?>
  </div>
</body>
</html>
