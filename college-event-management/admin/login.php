<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }
    if (!$errors) {
        $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email=? AND role='admin' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $hashed_pwd, $name);
            $stmt->fetch();
            if (password_verify($password, $hashed_pwd)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_role'] = 'admin';
                $_SESSION['user_name'] = $name;
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Incorrect password.';
            }
        } else {
            $errors[] = 'Invalid email or not an admin account.';
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
  <title>Admin Login Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:linear-gradient(135deg,#ff7e5f,#24c6dc,#5433ff);min-height:100vh;display:flex;align-items:center;justify-content:center;}</style>
</head>
<body>
  <div class="card p-5" style="min-width:330px;max-width:420px">
    <div class="alert alert-danger mb-2">
      <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
    </div>
    <a href="../admin-login.html" class="btn btn-secondary w-100 mt-2">Back to Admin Login</a>
  </div>
</body>
</html>
