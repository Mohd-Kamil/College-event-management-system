<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin-login.html');
    exit;
}
$name = $_SESSION['user_name'] ?? 'Admin';
$depts = $conn->query("SELECT COUNT(*) AS c FROM departments")->fetch_assoc();
$evs = $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc();
$regs = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg,#ff7e5f,#24c6dc,#5433ff);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card {
      background: rgba(255,255,255,0.92);
      border-radius: 1.5rem;
      box-shadow: 0 8px 36px 4px rgba(44,44,144,0.13);
      min-width: 330px;
      max-width: 480px;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand bg-light mb-4 p-3 shadow">
  <div class="container-fluid">
    <span class="navbar-brand fw-bolder"><img src="../assets/integral-university-logo.png" alt="Integral University Logo" style="width:42px;height:42px;margin-right:.5em;vertical-align:middle;border-radius:8px;background:#fff;">Integral University</span>
    <a href="dashboard.php" class="btn btn-sm btn-primary">Home</a>
    <a href="departments.php" class="btn btn-info btn-sm ms-2">Departments</a>
    <a href="events.php" class="btn btn-success btn-sm ms-2">Events</a>
    <a href="registrations.php" class="btn btn-warning btn-sm ms-2">Registrations</a>
    <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Log Out</a>
  </div>
</nav>
  <div class="card p-5 text-center">
    <h2 class="fw-bold mb-3">Welcome Admin, <?php echo htmlspecialchars($name); ?>!</h2>
    <div class="row g-2 mb-1 pb-2 mt-3 justify-content-center">
      <div class="col-auto">
        <div class="bg-info text-white rounded-3 p-3">
          <div class="display-6"><?php echo $depts['c'];?></div>
          <small>Departments</small>
        </div>
      </div>
      <div class="col-auto">
        <div class="bg-success text-white rounded-3 p-3">
          <div class="display-6"><?php echo $evs['c'];?></div>
          <small>Events</small>
        </div>
      </div>
      <div class="col-auto">
        <div class="bg-warning text-dark rounded-3 p-3">
          <div class="display-6"><?php echo $regs['c'];?></div>
          <small>Registrations</small>
        </div>
      </div>
    </div>
    <a href="departments.php" class="btn btn-info btn-lg m-2">Manage Departments</a>
    <a href="events.php" class="btn btn-success btn-lg m-2">Manage Events</a>
    <a href="logout.php" class="btn btn-outline-danger btn-lg m-2">Log Out</a>
  </div>
</body>
</html>
