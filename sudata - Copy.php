<?php
// Simulate a logged-in site-specific user with access to 2 sites
session_start();
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'SiteUser'; // Not Superadmin/Level2
$_SESSION['site_name'] = ['Atlantic BW', 'Parmiter']; // Simulate multi-site access

// DB connection
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection error: " . $e->getMessage());
}

// Grab session data
$site_names = $_SESSION['site_name'];
$role = $_SESSION['role'];
$isAllAccess = in_array($role, ['Superadmin', 'level2']);

if (!is_array($site_names)) {
    $site_names = [$site_names];
}

// Build site condition (for multiple sites)
$site_clause = '';
$site_params = [];

if (!$isAllAccess) {
    $placeholders = [];
    foreach ($site_names as $i => $site) {
        $key = ":site$i";
        $placeholders[] = $key;
        $site_params[$key] = $site;
    }
    $site_clause = "WHERE site_name IN (" . implode(',', $placeholders) . ")";
}

$sql = "SELECT booking_id, su_name, site_name, dob, gender FROM sudata $site_clause ORDER BY site_name";

$stmt = $conn->prepare($sql);

// Bind site names
foreach ($site_params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>SU Test Viewer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Testing: SU Records for Sites: <?= htmlspecialchars(implode(', ', $site_names)) ?></h2>
    <table class="table table-bordered mt-4">
    <thead>
    <tr>
        <th>Booking ID</th>
        <th>SU Name</th>
        <th>Site</th>
        <th>DOB</th>
        <th>Gender</th>
    </tr>
</thead>
<tbody>
    <?php if (count($results)): ?>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['booking_id']) ?></td>
                <td><?= htmlspecialchars($row['su_name']) ?></td>
                <td><?= htmlspecialchars($row['site_name']) ?></td>
                <td><?= htmlspecialchars($row['dob']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">No records found.</td></tr>
    <?php endif; ?>
</tbody>

    </table>
</div>
</body>
</html>
