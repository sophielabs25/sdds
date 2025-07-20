<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Distinguish user role or site_name
$site_name = $_SESSION['site_name'] ?? '';
$isAllAccess = ($site_name === 'level2' || $site_name === 'Superadmin');

// Database
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Filters: date range
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date']   ?? '';

/**
 * Build date filter for "created_at" column
 * If you want to use "updated_at" for filtering, just change the column name below.
 */
function buildDateFilter() {
    global $start_date, $end_date;
    $filter = "1=1";
    if (!empty($start_date)) {
        $filter .= " AND created_at >= :start_date";
    }
    if (!empty($end_date)) {
        $filter .= " AND created_at <= :end_date";
    }
    return $filter;
}

// Incident Reports Query
// We'll treat open = (updated_at == created_at), closed = (updated_at > created_at)
$incidentFilter = buildDateFilter();
if ($isAllAccess) {
    $sql_incidents = "SELECT 
                        COUNT(*) AS total_incidents,
                        SUM(CASE WHEN updated_at = created_at THEN 1 ELSE 0 END) AS open_incidents,
                        SUM(CASE WHEN updated_at > created_at THEN 1 ELSE 0 END) AS closed_incidents
                      FROM incident_reports
                      WHERE $incidentFilter";
    $stmt = $conn->prepare($sql_incidents);
} else {
    $sql_incidents = "SELECT 
                        COUNT(*) AS total_incidents,
                        SUM(CASE WHEN updated_at = created_at THEN 1 ELSE 0 END) AS open_incidents,
                        SUM(CASE WHEN updated_at > created_at THEN 1 ELSE 0 END) AS closed_incidents
                      FROM incident_reports
                      WHERE site_name = :site_name AND $incidentFilter";
    $stmt = $conn->prepare($sql_incidents);
    $stmt->bindValue(':site_name', $site_name);
}

// Bind date filters
if (!empty($start_date)) {
    $stmt->bindValue(':start_date', $start_date);
}
if (!empty($end_date)) {
    $stmt->bindValue(':end_date', $end_date);
}

$stmt->execute();
$incidents = $stmt->fetch(PDO::FETCH_ASSOC);
$total_incidents = (int)($incidents['total_incidents'] ?? 0);
$open_incidents  = (int)($incidents['open_incidents']  ?? 0);
$closed_incidents= (int)($incidents['closed_incidents']?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Advanced Analytics - Incident Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container {
      height: 300px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">
  <h2>Advanced Analytics - Incident Reports</h2>
  <hr>

  <!-- Date Range Filter Form -->
  <form method="GET" class="row g-3 align-items-center mb-3">
    <div class="col-auto">
      <label for="start_date" class="col-form-label">Start Date</label>
    </div>
    <div class="col-auto">
      <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
             value="<?= htmlspecialchars($start_date) ?>">
    </div>
    <div class="col-auto">
      <label for="end_date" class="col-form-label">End Date</label>
    </div>
    <div class="col-auto">
      <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
             value="<?= htmlspecialchars($end_date) ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="advanced_analytics.php" class="btn btn-secondary btn-sm">Clear</a>
    </div>
  </form>

  <!-- Show Summaries -->
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header">Total Incident Reports</div>
        <div class="card-body">
          <h5><?= $total_incidents ?></h5>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header">Open (updated_at == created_at)</div>
        <div class="card-body">
          <h5><?= $open_incidents ?></h5>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mb-3">
        <div class="card-header">Closed (updated_at > created_at)</div>
        <div class="card-body">
          <h5><?= $closed_incidents ?></h5>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart Example -->
  <div class="chart-container">
    <canvas id="incidentChart"></canvas>
  </div>
</div>

<script>
const ctx = document.getElementById('incidentChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Total', 'Open', 'Closed'],
    datasets: [{
      label: 'Incidents',
      data: [<?= $total_incidents ?>, <?= $open_incidents ?>, <?= $closed_incidents ?>],
      backgroundColor: ['#36A2EB', '#FFCE56', '#FF6384']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false
  }
});
</script>

</body>
</html>
