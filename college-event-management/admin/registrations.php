<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin-login.html');
    exit;
}

// Admin delete registration
$delMsg = '';
if (isset($_GET['del_reg']) && isset($_GET['uid']) && isset($_GET['eid'])) {
    $uid = intval($_GET['uid']);
    $eid = intval($_GET['eid']);
    $conn->query("DELETE FROM registrations WHERE user_id=$uid AND event_id=$eid LIMIT 1");
    $delMsg = '<div class="alert alert-success mb-2">Registration deleted.</div>';
}

// Get filter values
$filter_dept = $_GET['dept'] ?? '';
$filter_event = $_GET['ev'] ?? '';
$search = trim($_GET['search'] ?? '');

// Fetch filter options
$deptQ = $conn->query("SELECT id, name FROM departments ORDER BY name");
$depts = $deptQ ? $deptQ->fetch_all(MYSQLI_ASSOC) : [];
$evs = [];
if ($filter_dept) {
  $evQ = $conn->query("SELECT id, name FROM events WHERE department_id=".intval($filter_dept)." ORDER BY name");
  $evs = $evQ ? $evQ->fetch_all(MYSQLI_ASSOC) : [];
} else {
  $evQ = $conn->query("SELECT id, name FROM events ORDER BY name");
  $evs = $evQ ? $evQ->fetch_all(MYSQLI_ASSOC) : [];
}

// Build query
$where = [];
if ($filter_dept) $where[] = "d.id = ".intval($filter_dept);
if ($filter_event) $where[] = "e.id = ".intval($filter_event);
if ($search) {
  $searchSql = $conn->real_escape_string($search);
  $where[] = "(u.name LIKE '%$searchSql%' OR u.email LIKE '%$searchSql%')";
}
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
$sql = "SELECT r.registered_at, e.name as event_name, e.date as event_date, d.name as dept_name, u.name as student_name, u.email as student_email, u.id as user_id, e.id as event_id FROM registrations r JOIN events e ON r.event_id = e.id JOIN departments d ON e.department_id = d.id JOIN users u ON r.user_id = u.id $whereSql ORDER BY e.date DESC, r.registered_at DESC";
$regs = $conn->query($sql);

// CSV Export logic
if (isset($_GET['export']) && $_GET['export']==='csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename="registrations.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Event','Department','Event Date','Student Name','Student Email','Registered At']);
  foreach($regs as $r) {
    fputcsv($out, [$r['event_name'],$r['dept_name'],$r['event_date'],$r['student_name'],$r['student_email'],$r['registered_at']]);
  }
  fclose($out);
  exit;
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Event Registrations</title>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
  <style>
    body {background: linear-gradient(135deg,#ffecd2 50%,#fc67fa,#0379a4); min-height: 100vh;}
    main {max-width: 1050px; margin:2em auto;}
    .filter-bar{background:rgba(255,255,255,0.85); border-radius:1em; box-shadow:0 2px 12px 1px #bbb4; margin-bottom:1em;}
  </style>
  <script>
    function reloadWithFilter() {
      document.getElementById('filterForm').submit();
    }
    function confirmDelete(student, event) {
      return confirm('Are you sure you want to delete the registration of "' + student + '" for event "' + event + '"?');
    }
  </script>
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
<main>
  <h2 class='fw-bold mb-4'>All Event Registrations</h2>
  <form id="filterForm" class="row gx-2 gy-1 align-items-end filter-bar py-3 px-3" method="get" action="registrations.php">
    <div class="col-md-3">
      <label class="form-label mb-0">Department</label>
      <select class="form-select" name="dept" onchange="reloadWithFilter()">
        <option value="">All</option>
        <?php foreach($depts as $d): ?>
          <option value="<?php echo $d['id']; ?>"<?php if($filter_dept==$d['id'])echo ' selected';?>><?php echo htmlspecialchars($d['name']);?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label mb-0">Event</label>
      <select class="form-select" name="ev" onchange="reloadWithFilter()">
        <option value="">All</option>
        <?php foreach($evs as $e): ?>
          <option value="<?php echo $e['id']; ?>"<?php if($filter_event==$e['id'])echo ' selected';?>><?php echo htmlspecialchars($e['name']);?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label mb-0">Search (Student)</label>
      <input class="form-control" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or email">
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-secondary mt-2" type="submit">Filter/Search</button>
      <a class="btn btn-success mt-2" href="registrations.php?dept=<?php echo urlencode($filter_dept);?>&ev=<?php echo urlencode($filter_event);?>&search=<?php echo urlencode($search);?>&export=csv">Export CSV</a>
    </div>
  </form>
  <?php if ($delMsg) echo $delMsg; ?>
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Event (Department)</th>
          <th>Event Date</th>
          <th>Student Name</th>
          <th>Student Email</th>
          <th>Registered At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; while ($r = $regs->fetch_assoc()): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><?php echo htmlspecialchars($r['event_name']); ?><br><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r['dept_name']); ?></span></td>
          <td><?php echo htmlspecialchars($r['event_date']); ?></td>
          <td><?php echo htmlspecialchars($r['student_name']); ?></td>
          <td><?php echo htmlspecialchars($r['student_email']); ?></td>
          <td><?php echo htmlspecialchars($r['registered_at']); ?></td>
          <td>
            <a
              href="registrations.php?del_reg=1&uid=<?php echo $r['user_id']; ?>&eid=<?php echo $r['event_id']; ?>&dept=<?php echo urlencode($filter_dept);?>&ev=<?php echo urlencode($filter_event);?>&search=<?php echo urlencode($search); ?>"
              class="btn btn-sm btn-danger"
              onclick="return confirmDelete('<?php echo htmlspecialchars(addslashes($r['student_name'])); ?>', '<?php echo htmlspecialchars(addslashes($r['event_name'])); ?>');"
              title="Delete this registration"
            >Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($i===1): ?><tr><td colspan="7" class="text-center text-muted">No registrations yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
