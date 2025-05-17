<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'student') {
    header('Location: ../student-login.html');
    exit;
}
$student_id = $_SESSION['user_id'];
$q = $conn->query("SELECT e.*, d.name AS deptname FROM events e JOIN departments d ON e.department_id=d.id JOIN registrations r ON r.event_id=e.id WHERE r.user_id=$student_id ORDER BY e.date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {background:linear-gradient(120deg,#5433ff,#24c6dc,#ff7e5f);min-height:100vh;}
    main {max-width:880px;margin:2em auto;}
    .event-card {border-radius:1.3rem;box-shadow:0 6px 36px 4px rgba(44,44,144,0.09);background:rgba(255,255,255,0.97);margin-bottom:1em;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand bg-light mb-4 p-2 shadow">
  <div class="container-fluid">
    <span class="navbar-brand fw-bolder"><img src="../assets/integral-university-logo.png" alt="Integral University Logo" style="width:42px;height:42px;margin-right:.5em;vertical-align:middle;border-radius:8px;background:#fff;">Integral University</span>
    <a href="dashboard.php" class="btn btn-sm btn-secondary">Event Catalog</a>
    <a href="my-events.php" class="btn btn-sm btn-primary ms-2">My Events</a>
    <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Log Out</a>
  </div>
</nav>
<main>
  <h2 class="fw-bold mb-4">My Registered Events</h2>
  <div class="row">
  <?php if ($q->num_rows == 0): ?>
    <p class="text-muted">No event registrations yet.</p>
  <?php endif; while ($ev = $q->fetch_assoc()): ?>
    <div class="col-md-6 col-lg-4">
      <div class="event-card p-3 mb-2">
        <?php if (!empty($ev['banner_img'])): ?>
          <img src="../assets/events/<?php echo htmlspecialchars($ev['banner_img']); ?>" alt="Banner" class="img-fluid rounded mb-2" style="max-height:98px;width:100%;object-fit:cover;">
        <?php endif; ?>
        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($ev['name']); ?></h5>
        <div><span class="badge bg-info text-dark"> <?php echo htmlspecialchars($ev['deptname']); ?> </span></div>
        <div><small class="text-muted"><?php echo htmlspecialchars($ev['date']); ?></small></div>
        <div class="mb-1 mt-1"> <?php echo htmlspecialchars($ev['description']); ?> </div>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
</main>
</body>
</html>
