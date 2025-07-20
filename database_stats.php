<?php
// Database connection setup remains the same
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Fetch site names for dropdown
$sites = $conn->query("SELECT id, name FROM sites")->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$selected_site = $_GET['site'] ?? null;

// Fetch statistics based on selected site
try {
    if ($selected_site) {
        // Site-specific queries
        $where_clause = "WHERE site_id = $selected_site";
        
        // Properties and rooms stats
        $total_properties = $conn->query("SELECT COUNT(*) FROM properties $where_clause")->fetchColumn();
        $total_rooms = $conn->query("SELECT COUNT(*) FROM rooms $where_clause")->fetchColumn();
        $available_rooms = $conn->query("SELECT COUNT(*) FROM rooms $where_clause AND room_status = 'Available'")->fetchColumn();
        $occupied_rooms = $conn->query("SELECT COUNT(*) FROM rooms $where_clause AND room_status = 'Occupied'")->fetchColumn();
        $total_beds = $conn->query("SELECT SUM(quantity) FROM beds $where_clause")->fetchColumn();
        
        // Complaints stats
        $total_complaints = $conn->query("SELECT COUNT(*) FROM complaints $where_clause")->fetchColumn();
        $open_complaints = $conn->query("SELECT COUNT(*) FROM complaints $where_clause AND status = 'Open'")->fetchColumn();
        $closed_complaints = $conn->query("SELECT COUNT(*) FROM complaints $where_clause AND status = 'Close'")->fetchColumn();

        // Incident reports stats
        $total_incident_reports = $conn->query("SELECT COUNT(*) FROM incident_reports $where_clause")->fetchColumn();
        $open_incident_reports = $conn->query("SELECT COUNT(*) FROM incident_reports $where_clause AND status = 'Open'")->fetchColumn();
        $closed_incident_reports = $conn->query("SELECT COUNT(*) FROM incident_reports $where_clause AND status = 'Close'")->fetchColumn();

        // Safeguarding referrals stats
        $total_referrals = $conn->query("SELECT COUNT(*) FROM safeguarding_referrals $where_clause")->fetchColumn();
        $open_referrals = $conn->query("SELECT COUNT(*) FROM safeguarding_referrals $where_clause AND status = 'Open'")->fetchColumn();
        $closed_referrals = $conn->query("SELECT COUNT(*) FROM safeguarding_referrals $where_clause AND status = 'Close'")->fetchColumn();

        // Users and vulnerable SUs stats
        $total_users = $conn->query("SELECT COUNT(*) FROM users $where_clause")->fetchColumn();
        $total_vulnerable_sus = $conn->query("SELECT COUNT(*) FROM vulnerable_sus $where_clause")->fetchColumn();
        
    } else {
        // All stats without filtering
        $total_sites = $conn->query("SELECT COUNT(*) FROM sites")->fetchColumn();
        $total_properties = $conn->query("SELECT COUNT(*) FROM properties")->fetchColumn();
        $total_rooms = $conn->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        $available_rooms = $conn->query("SELECT COUNT(*) FROM rooms WHERE room_status = 'Available'")->fetchColumn();
        $occupied_rooms = $conn->query("SELECT COUNT(*) FROM rooms WHERE room_status = 'Occupied'")->fetchColumn();
        $total_beds = $conn->query("SELECT SUM(quantity) FROM beds")->fetchColumn();
        
        // Complaints stats
        $total_complaints = $conn->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
        $open_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Open'")->fetchColumn();
        $closed_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Close'")->fetchColumn();

        // Incident reports stats
        $total_incident_reports = $conn->query("SELECT COUNT(*) FROM incident_reports")->fetchColumn();
        $open_incident_reports = $conn->query("SELECT COUNT(*) FROM incident_reports WHERE status = 'Open'")->fetchColumn();
        $closed_incident_reports = $conn->query("SELECT COUNT(*) FROM incident_reports WHERE status = 'Close'")->fetchColumn();

        // Safeguarding referrals stats
        $total_referrals = $conn->query("SELECT COUNT(*) FROM safeguarding_referrals")->fetchColumn();
        $open_referrals = $conn->query("SELECT COUNT(*) FROM safeguarding_referrals WHERE status = 'Open'")->fetchColumn();
        $closed_referrals = $conn->query("SELECT COUNT(*) FROM safeguarding_referrals WHERE status = 'Close'")->fetchColumn();

        // Users and vulnerable SUs stats
        $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_vulnerable_sus = $conn->query("SELECT COUNT(*) FROM vulnerable_sus")->fetchColumn();
    }
} catch (PDOException $e) {
    die("Error fetching stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Database Statistics</h2>
    <form method="GET" class="mb-3">
        <div class="row">
            <div class="col-md-4">
                <select name="site" class="form-select">
                    <option value="">Select Site</option>
                    <?php foreach ($sites as $site): ?>
                        <option value="<?= htmlspecialchars($site['id']) ?>" <?= $selected_site == $site['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($site['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="database_stats.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Statistic</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$selected_site): ?>
            <tr>
                <td>Total Sites</td>
                <td><?= htmlspecialchars($total_sites) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Total Properties</td>
                <td><?= htmlspecialchars($total_properties) ?></td>
            </tr>
            <tr>
                <td>Total Rooms</td>
                <td><?= htmlspecialchars($total_rooms) ?></td>
            </tr>
            <tr>
                <td>Available Rooms</td>
                <td><?= htmlspecialchars($available_rooms) ?></td>
            </tr>
            <tr>
                <td>Occupied Rooms</td>
                <td><?= htmlspecialchars($occupied_rooms) ?></td>
            </tr>
            <tr>
                <td>Total Beds</td>
                <td><?= htmlspecialchars($total_beds) ?></td>
            </tr>
            <tr>
                <td>Total Complaints</td>
                <td><?= htmlspecialchars($total_complaints) ?></td>
            </tr>
            <tr>
                <td>Open Complaints</td>
                <td><?= htmlspecialchars($open_complaints) ?></td>
            </tr>
            <tr>
                <td>Closed Complaints</td>
                <td><?= htmlspecialchars($closed_complaints) ?></td>
            </tr>
            <tr>
                <td>Total Incident Reports</td>
                <td><?= htmlspecialchars($total_incident_reports) ?></td>
            </tr>
            <tr>
                <td>Open Incident Reports</td>
                <td><?= htmlspecialchars($open_incident_reports) ?></td>
            </tr>
            <tr>
                <td>Closed Incident Reports</td>
                <td><?= htmlspecialchars($closed_incident_reports) ?></td>
            </tr>
            <tr>
                <td>Total Safeguarding Referrals</td>
                <td><?= htmlspecialchars($total_referrals) ?></td>
            </tr>
            <tr>
                <td>Open Safeguarding Referrals</td>
                <td><?= htmlspecialchars($open_referrals) ?></td>
            </tr>
            <tr>
                <td>Closed Safeguarding Referrals</td>
                <td><?= htmlspecialchars($closed_referrals) ?></td>
            </tr>
            <tr>
                <td>Total Users</td>
                <td><?= htmlspecialchars($total_users) ?></td>
            </tr>
            <tr>
                <td>Total Vulnerable SUs</td>
                <td><?= htmlspecialchars($total_vulnerable_sus) ?></td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>