<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin-login.html');
    exit;
}
$showMsg = '';
// Get departments for select
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$deptMap = [];
while ($d = $departments->fetch_assoc()) $deptMap[$d['id']]=$d['name'];
$departments->data_seek(0);

$uploadDir = '../assets/events/';

// Add event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $ename = trim($_POST['event_name'] ?? '');
    $dept_id = intval($_POST['event_dept'] ?? 0);
    $edesc = trim($_POST['event_desc'] ?? '');
    $edate = $_POST['event_date'] ?? null;
    $banner_img = null;
    if (!empty($_FILES['event_banner']['name'])) {
        $file = $_FILES['event_banner'];
        $tmp = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg'];
        $mime = mime_content_type($tmp);
        if (in_array($ext, $allowed) && preg_match('/^image\/(png|jpe?g)$/', $mime)) {
            $fname = uniqid('event_').'.'.$ext;
            $target = $uploadDir.$fname;
            if (move_uploaded_file($tmp, $target)) {
                $banner_img = $fname;
            }
        } else {
            $showMsg = '<div class="alert alert-danger mt-2">Invalid image file type. Only PNG, JPG, JPEG allowed.</div>';
        }
    }
    if ($ename !== '' && $dept_id && !$showMsg) {
        $stmt = $conn->prepare("INSERT INTO events (department_id, name, description, date, banner_img) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $dept_id, $ename, $edesc, $edate, $banner_img);
        if ($stmt->execute()) {
            $showMsg = '<div class="alert alert-success mt-2">Event added!</div>';
        } else {
            $showMsg = '<div class="alert alert-danger mt-2">Error: ' . htmlspecialchars($conn->error) . '</div>';
        }
        $stmt->close();
    }
}

// Edit event
$editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event'])) {
    $eid = intval($_POST['edit_id']);
    $ename = trim($_POST['edit_name'] ?? '');
    $edept = intval($_POST['edit_dept'] ?? 0);
    $edesc = trim($_POST['edit_desc'] ?? '');
    $edate = $_POST['edit_date'] ?? null;
    $banner_img = $_POST['current_banner'] ?? null;
    if (!empty($_FILES['edit_banner']['name'])) {
        $file = $_FILES['edit_banner'];
        $tmp = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg'];
        $mime = mime_content_type($tmp);
        if (in_array($ext, $allowed) && preg_match('/^image\/(png|jpe?g)$/', $mime)) {
            $fname = uniqid('event_').'.'.$ext;
            $target = $uploadDir.$fname;
            if (move_uploaded_file($tmp, $target)) {
                $banner_img = $fname;
            }
        } else {
            $showMsg = '<div class="alert alert-danger mt-2">Invalid image file type. Only PNG, JPG, JPEG allowed.</div>';
        }
    }
    if ($ename !== '' && $edept && !$showMsg) {
        $stmt = $conn->prepare("UPDATE events SET department_id=?, name=?, description=?, date=?, banner_img=? WHERE id=?");
        $stmt->bind_param('issssi', $edept, $ename, $edesc, $edate, $banner_img, $eid);
        if ($stmt->execute()) {
            $showMsg = '<div class="alert alert-success mt-2">Event updated!</div>';
        } else {
            $showMsg = '<div class="alert alert-danger mt-2">Update error.</div>';
        }
        $stmt->close();
    }
    $editing = 0;
}

// Delete event
if (isset($_GET['delete'])) {
    $delid = intval($_GET['delete']);
    // Remove banner image file if exists
    $res = $conn->query("SELECT banner_img FROM events WHERE id=$delid");
    if ($res && ($row = $res->fetch_assoc()) && $row['banner_img']) {
        $imgPath = $uploadDir . $row['banner_img'];
        if (file_exists($imgPath)) @unlink($imgPath);
    }
    $conn->query("DELETE FROM events WHERE id=$delid");
    header('Location: events.php');
    exit;
}

// List events with department
$events = $conn->query("SELECT events.*, departments.name AS deptname FROM events JOIN departments ON events.department_id=departments.id ORDER BY events.date DESC, events.id DESC");
$editEv = $editing ? $conn->query("SELECT * FROM events WHERE id=$editing")->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Events Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:linear-gradient(120deg,#24c6dc,#5433ff,#ff7e5f);min-height:100vh;}
    main{max-width:820px;margin:2em auto;}
    .event-banner-thumb{max-width:120px;max-height:60px;object-fit:cover;border-radius:4px;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand bg-light mb-4 p-2 shadow">
  <div class="container-fluid">
    <span class="navbar-brand fw-bolder"><img src="../assets/integral-university-logo.png" alt="Integral University Logo" style="width:42px;height:42px;margin-right:.5em;vertical-align:middle;border-radius:8px;background:#fff;">Integral University</span>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">Dashboard</a>
    <a href="departments.php" class="btn btn-info btn-sm ms-2">Departments</a>
    <a href="registrations.php" class="btn btn-warning btn-sm ms-2">Registrations</a>
    <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Log Out</a>
  </div>
</nav>
<main>
  <h2 class="fw-bold mb-4">Events Management</h2>
  <?php if ($editing && $editEv): ?>
  <form class="row g-2 align-items-end mb-3" method="post" enctype="multipart/form-data">
    <input type="hidden" name="edit_id" value="<?php echo $editEv['id'] ?>">
    <div class="col-md-3">
      <label class="form-label">Department</label>
      <select name="edit_dept" class="form-control" required>
        <option value="">- Choose -</option>
        <?php foreach ($deptMap as $id => $dname): ?>
        <option value="<?php echo $id; ?>" <?php if ($editEv['department_id']==$id) echo 'selected'; ?>><?php echo htmlspecialchars($dname); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Event Name</label>
      <input name="edit_name" value="<?php echo htmlspecialchars($editEv['name']); ?>" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date</label>
      <input name="edit_date" type="date" value="<?php echo htmlspecialchars($editEv['date']); ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Description</label>
      <input name="edit_desc" value="<?php echo htmlspecialchars($editEv['description']); ?>" class="form-control">
    </div>
    <div class="col-md-6 mt-2">
      <label class="form-label">Banner Image (PNG/JPG/JPEG)</label>
      <input type="file" name="edit_banner" accept="image/png,image/jpeg,image/jpg" class="form-control" onchange="previewEditBanner(this)">
      <input type="hidden" name="current_banner" value="<?php echo htmlspecialchars($editEv['banner_img'] ?? ''); ?>">
      <?php if (!empty($editEv['banner_img']) && file_exists($uploadDir . $editEv['banner_img'])): ?>
        <div class="mt-2">
          <img src="<?php echo $uploadDir . $editEv['banner_img']; ?>" class="event-banner-thumb" id="editBannerPreview">
        </div>
      <?php else: ?>
        <div class="mt-2">
          <img src="" class="event-banner-thumb d-none" id="editBannerPreview">
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-12 mt-2">
      <button class="btn btn-primary" name="edit_event" value="1">Update Event</button>
      <a href="events.php" class="btn btn-link">Cancel</a>
      <?php echo $showMsg; ?>
    </div>
  </form>
  <?php endif; ?>
  <form class="row g-2 align-items-end<?= $editing ? ' d-none' : '' ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="add_event" value="1">
    <div class="col-md-3">
      <label class="form-label">Department</label>
      <select name="event_dept" class="form-control" required>
        <option value="">- Choose -</option>
        <?php foreach ($deptMap as $id => $dname): ?>
        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($dname); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Event Name</label>
      <input name="event_name" class="form-control" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date</label>
      <input name="event_date" type="date" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Description</label>
      <input name="event_desc" class="form-control">
    </div>
    <div class="col-md-6 mt-2">
      <label class="form-label">Banner Image (PNG/JPG/JPEG)</label>
      <input type="file" name="event_banner" accept="image/png,image/jpeg,image/jpg" class="form-control" onchange="previewAddBanner(this)">
      <div class="mt-2">
        <img src="" class="event-banner-thumb d-none" id="addBannerPreview">
      </div>
    </div>
    <div class="col-md-12 mt-2">
      <button class="btn btn-success">Add Event</button>
      <?php echo $showMsg; ?>
    </div>
  </form>
  <div class="table-responsive mt-4">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Name</th>
          <th>Date</th>
          <th>Description</th>
          <th>Banner</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $events->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id'];?></td>
        <td><?php echo htmlspecialchars($row['deptname']);?></td>
        <td><?php echo htmlspecialchars($row['name']);?></td>
        <td><?php echo htmlspecialchars($row['date']);?></td>
        <td><?php echo htmlspecialchars($row['description']);?></td>
        <td>
          <?php if (!empty($row['banner_img']) && file_exists($uploadDir . $row['banner_img'])): ?>
            <img src="<?php echo $uploadDir . $row['banner_img']; ?>" class="event-banner-thumb" alt="Banner">
          <?php else: ?>
            <span class="text-muted small">No Banner</span>
          <?php endif; ?>
        </td>
        <td>
          <a class="btn btn-warning btn-sm" href="events.php?edit=<?php echo $row['id']; ?>">Edit</a>
          <a class="btn btn-danger btn-sm" href="events.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>
<script>
function previewAddBanner(input) {
    const preview = document.getElementById('addBannerPreview');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (!file.type.match(/^image\/(png|jpeg|jpg)$/)) {
            preview.classList.add('d-none');
            preview.src = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('d-none');
        preview.src = '';
    }
}
function previewEditBanner(input) {
    const preview = document.getElementById('editBannerPreview');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (!file.type.match(/^image\/(png|jpeg|jpg)$/)) {
            preview.classList.add('d-none');
            preview.src = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>
