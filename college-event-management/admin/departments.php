<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin-login.html');
    exit;
}
$addMsg = $editMsg = '';
// Edit logic
$editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dept'])) {
    $eid = intval($_POST['edit_id']);
    $ename = trim($_POST['edit_name'] ?? '');
    $edesc = trim($_POST['edit_desc'] ?? '');
    if ($ename !== '') {
        $stmt = $conn->prepare("UPDATE departments SET name=?, description=? WHERE id=?");
        $stmt->bind_param('ssi', $ename, $edesc, $eid);
        if ($stmt->execute()) {
            $editMsg = '<div class=\'alert alert-success mt-2\'>Department updated!</div>';
        } else {
            $editMsg = '<div class=\'alert alert-danger mt-2\'>Update error.</div>';
        }
        $stmt->close();
    }
    $editing = 0;
}
// Add department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dept'])) {
    $dname = trim($_POST['dept_name'] ?? '');
    $desc = trim($_POST['dept_desc'] ?? '');
    if ($dname !== '') {
        $stmt = $conn->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
        $stmt->bind_param('ss', $dname, $desc);
        if ($stmt->execute()) {
            $addMsg = '<div class="alert alert-success mt-2">Department added!</div>';
        } else {
            $addMsg = '<div class=\'alert alert-danger mt-2\'>Error: ' . htmlspecialchars($conn->error) . '</div>';
        }
        $stmt->close();
    }
}
// Delete department
if (isset($_GET['delete'])) {
    $delid = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE id=$delid");
    header('Location: departments.php');
    exit;
}
// List departments
$depts = $conn->query("SELECT * FROM departments ORDER BY id DESC");
$editDept = $editing ? $conn->query("SELECT * FROM departments WHERE id=$editing")->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Departments Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:linear-gradient(135deg,#ff7e5f,#24c6dc,#5433ff);min-height:100vh;}main{max-width:680px;margin:2em auto;}</style>
</head>
<body>
<nav class="navbar navbar-expand bg-light mb-4 p-2 shadow">
  <div class="container-fluid">
    <span class="navbar-brand fw-bolder"><img src="../assets/integral-university-logo.png" alt="Integral University Logo" style="width:42px;height:42px;margin-right:.5em;vertical-align:middle;border-radius:8px;background:#fff;">Integral University</span>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
    <a href="departments.php" class="btn btn-info btn-sm ms-2">Departments</a>
    <a href="events.php" class="btn btn-success btn-sm ms-2">Events</a>
    <a href="registrations.php" class="btn btn-warning btn-sm ms-2">Registrations</a>
    <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Log Out</a>
  </div>
</nav>
<main>
  <h2 class="fw-bold mb-4">Departments Management</h2>
  <?php if ($editing && $editDept): ?>
  <form class="row g-2 align-items-end mb-3" method="post">
    <div class="col-sm-5">
      <label class="form-label">Department Name</label>
      <input name="edit_name" value="<?php echo htmlspecialchars($editDept['name']); ?>" class="form-control" required>
    </div>
    <div class="col-sm-5">
      <label class="form-label">Description</label>
      <input name="edit_desc" value="<?php echo htmlspecialchars($editDept['description']); ?>" class="form-control">
    </div>
    <div class="col-sm-2">
      <input type="hidden" name="edit_id" value="<?php echo $editDept['id']; ?>">
      <button class="btn btn-primary w-100" name="edit_dept" value="1">Update</button>
      <a href="departments.php" class="btn btn-link d-block px-0 mt-1">Cancel</a>
    </div>
    <?php echo $editMsg; ?>
  </form>
  <?php endif; ?>
  <form class="row g-2 align-items-end<?= $editing ? ' d-none' : '' ?>" method="post">
    <div class="col-sm-5">
      <label class="form-label">Department Name</label>
      <input name="dept_name" class="form-control" required>
    </div>
    <div class="col-sm-5">
      <label class="form-label">Description</label>
      <input name="dept_desc" class="form-control">
    </div>
    <div class="col-sm-2">
      <input type="hidden" name="add_dept" value="1">
      <button class="btn btn-success w-100">Add</button>
    </div>
    <?php echo $addMsg; ?>
  </form>
  <div class="table-responsive mt-4">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Description</th><th>Action</th></tr></thead>
      <tbody>
      <?php while ($row = $depts->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id'];?></td>
        <td><?php echo htmlspecialchars($row['name']);?></td>
        <td><?php echo htmlspecialchars($row['description']);?></td>
        <td>
          <a class="btn btn-warning btn-sm" href="departments.php?edit=<?php echo $row['id']; ?>">Edit</a>
          <a class="btn btn-danger btn-sm" href="departments.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
