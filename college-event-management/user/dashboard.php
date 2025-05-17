<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'student') {
    header('Location: ../student-login.html');
    exit;
}
$student_id = $_SESSION['user_id'];
$msg = '';
// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $eid = intval($_POST['register_event']);
    // Check not already registered
    $check = $conn->query("SELECT 1 FROM registrations WHERE user_id=$student_id AND event_id=$eid")->num_rows;
    if (!$check) {
        $stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $student_id, $eid);
        if ($stmt->execute()) {
            $msg = '<div class=\'alert alert-success\'>Registered for event!</div>';
        }
        $stmt->close();
    } else {
        $msg = '<div class=\'alert alert-warning\'>Already registered!</div>';
    }
}
// Get events (grouped by department)
$eventsQ = $conn->query("SELECT events.*, departments.name AS deptname FROM events JOIN departments ON events.department_id=departments.id ORDER BY departments.name, events.date");
$regsQ = $conn->query("SELECT event_id FROM registrations WHERE user_id=$student_id");
$registered = [];
while ($r = $regsQ->fetch_assoc()) $registered[$r['event_id']] = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard - Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(120deg,#5433ff,#24c6dc,#ff7e5f);
      min-height: 100vh;
    }
    main {
      max-width: 900px;
      margin: 2em auto;
    }
    .event-card {
      border-radius: 1.3rem;
      box-shadow: 0 6px 36px 4px rgba(44,44,144,0.11);
      background: rgba(255,255,255,0.98);
      margin-bottom: 1.5em;
    }
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
  <h2 class="fw-bold mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']);?>!</h2>
  <div><?php echo $msg; ?></div>
  <h4 class="mt-4">Upcoming Events</h4>
  <?php $lastDept = null; while ($ev = $eventsQ->fetch_assoc()):
    if ($lastDept !== $ev['deptname']):
      if ($lastDept !== null) echo '</div>';
      echo '<h5 class="mt-4">'.$ev['deptname'].'</h5><div class="row">';
    endif;
  ?>
    <div class="col-md-6 col-lg-4">
      <div class="event-card p-3 mb-3">
        <?php if (!empty($ev['banner_img'])): ?>
          <img src="../assets/events/<?php echo htmlspecialchars($ev['banner_img']); ?>" alt="Banner" class="img-fluid rounded mb-2" style="max-height:110px;object-fit:cover;width:100%;">
        <?php endif; ?>
        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($ev['name']); ?></h6>
        <div><small class="text-muted"><?php echo htmlspecialchars($ev['date']); ?></small></div>
        <div class="mb-2"><?php echo htmlspecialchars($ev['description']); ?></div>
        <?php if (isset($registered[$ev['id']])): ?>
          <span class="badge bg-success">Registered</span>
        <?php else: ?>
          <form method="post" class="d-inline">
            <button type="submit" name="register_event" value="<?php echo $ev['id']; ?>" class="btn btn-primary btn-sm">Register</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php $lastDept = $ev['deptname']; endwhile;
     if ($lastDept !== null) echo '</div>'; ?>
</main>
</body>
</html>
