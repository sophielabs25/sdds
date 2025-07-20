<?php
session_start();

// Only allow admin (Superadmin) access to this dashboard.
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'level2') {
    header('Location: login.php');
    exit();
}

// Database connection using PDO
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// --- POST HANDLING: Update user permission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    $role = $_POST['role'];
    $site_id = $_POST['site_id'] ?? null; // Only applicable if role is site-specific
    $allowed_pages = trim($_POST['allowed_pages']);

    // Example: You might want to validate allowed_pages (e.g., by checking against a list of valid page identifiers)
    try {
        $updateSql = "UPDATE users SET role = :role, site_id = :site_id, allowed_pages = :allowed_pages WHERE id = :id";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([
            ':role' => $role,
            ':site_id' => $site_id,
            ':allowed_pages' => $allowed_pages,
            ':id' => $userId
        ]);
        header("Location: user_permission_dashboard.php?success=" . urlencode("User permissions updated successfully."));
        exit();
    } catch(PDOException $e) {
        header("Location: user_permission_dashboard.php?error=" . urlencode("Error updating user: " . $e->getMessage()));
        exit();
    }
}

// --- GET: Fetch users list for display ---
// (For simplicity, no pagination is added here but it can be added similar to previous examples.)
$stmt = $conn->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// (Optional) Fetch sites for dropdown if needed
$stmtSites = $conn->prepare("SELECT * FROM sites ORDER BY site_name ASC");
$stmtSites->execute();
$sites = $stmtSites->fetchAll(PDO::FETCH_ASSOC);

// Define an array of valid roles and available pages (you can extend this list)
$roles = ["Superadmin", "Level2", "Site User"];
$availablePages = ["dashboard.php", "manage_incident_reports.php", "manage_complaints.php", "manage_safeguarding_referrals.php", "manage_properties.php", "manage_rooms.php"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Permission Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table td, .table th { vertical-align: middle; }
    .no-print { display: none; }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container mt-5">
    <h2>User Permission Dashboard</h2>
    <?php if(isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php elseif(isset($_GET['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <p>Click the "Edit" button next to a user to change their role, site (if applicable), and allowed pages. Allowed pages should be a comma-separated list of page filenames (e.g., <code>dashboard.php,manage_incident_reports.php</code>).</p>

    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Role</th>
          <th>Site</th>
          <th>Allowed Pages</th>
          <th class="no-print">Actions</th>
        </tr>
      </thead>
      <tbody>
  <?php foreach($users as $user): ?>
    <tr>
      <td><?= htmlspecialchars($user['id']) ?></td>
      <td><?= htmlspecialchars($user['username']) ?></td>
      <td><?= htmlspecialchars($user['role']) ?></td> <!-- Updated to display the role -->
      <td>
        <?php 
          if (isset($user['site_id']) && $user['site_id']) {
            $siteName = "N/A";
            foreach ($sites as $site) {
              if ($site['id'] == $user['site_id']) {
                $siteName = htmlspecialchars($site['site_name']);
                break;
              }
            }
            echo $siteName;
          } else {
            echo "N/A";
          }
        ?>
      </td>
      <td><?= !empty($user['allowed_pages']) ? htmlspecialchars($user['allowed_pages']) : 'N/A' ?></td>
      <td class="no-print">
        <button class="btn btn-warning btn-sm" onclick='showEditModal(<?= json_encode($user) ?>)'>Edit</button>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>
    </table>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="user_permission_dashboard.php">
        <input type="hidden" name="user_id" id="user_id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Edit User Permissions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Role Selection -->
            <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <select class="form-select" id="role" name="role" required>
                <?php foreach($roles as $r): ?>
                  <option value="<?= $r ?>"><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- Site Selection: Only applicable if the role is not Superadmin/Level2 -->
            <div class="mb-3">
              <label for="site_id" class="form-label">Site</label>
              <select class="form-select" id="site_id" name="site_id">
                <option value="">Select Site (if applicable)</option>
                <?php foreach($sites as $site): ?>
                  <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['site_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- Allowed Pages -->
            <div class="mb-3">
              <label for="allowed_pages" class="form-label">Allowed Pages</label>
              <input type="text" class="form-control" id="allowed_pages" name="allowed_pages" placeholder="Comma-separated list" required>
              <small class="text-muted">Example: dashboard.php,manage_incident_reports.php,manage_properties.php</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function showEditModal(user) {
  document.getElementById('user_id').value = user.id;
  document.getElementById('role').value = user.role; // Set the role
  if (user.site_id) {
    document.getElementById('site_id').value = user.site_id;
  } else {
    document.getElementById('site_id').value = "";
  }
  document.getElementById('allowed_pages').value = user.allowed_pages || "";
  new bootstrap.Modal(document.getElementById('editModal')).show();
}
  </script>
</body>
</html>
