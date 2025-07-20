<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
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

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM incident_reports WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        echo "<script>alert('Incident report deleted successfully!');</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error deleting report: " . $e->getMessage() . "');</script>";
    }
}

// Build filter conditions and parameters
$filters = [];
$filter_sql = "1=1";

if (!empty($_GET['filter_person_reporting'])) {
    $filters['filter_person_reporting'] = '%' . $_GET['filter_person_reporting'] . '%';
    $filter_sql .= " AND person_reporting LIKE :filter_person_reporting";
}

if (!empty($_GET['filter_su_name'])) {
    $filters['filter_su_name'] = '%' . $_GET['filter_su_name'] . '%';
    $filter_sql .= " AND su_name LIKE :filter_su_name";
}

if (!empty($_GET['filter_site_name'])) {
    $filters['filter_site_name'] = '%' . $_GET['filter_site_name'] . '%';
    $filter_sql .= " AND site_name LIKE :filter_site_name";
}

if (!empty($_GET['filter_incident_type'])) {
    $filters['filter_incident_type'] = '%' . $_GET['filter_incident_type'] . '%';
    $filter_sql .= " AND incident_type LIKE :filter_incident_type";
}
if (!empty($_GET['filter_status'])) {
    $filters['filter_status'] = $_GET['filter_status'];
    $filter_sql .= " AND status = :filter_status";
}
if (!empty($_GET['filter_date_from']) && !empty($_GET['filter_date_to'])) {
    $filters['filter_date_from'] = $_GET['filter_date_from'];
    $filters['filter_date_to'] = $_GET['filter_date_to'];
    $filter_sql .= " AND incident_datetime BETWEEN :filter_date_from AND :filter_date_to";
}

// Determine user access level and apply site filter if necessary
$site_name = $_SESSION['site_name'];
if ($site_name === 'Superadmin' || $site_name === 'level2') {
    $sql_base = "SELECT * FROM incident_reports WHERE $filter_sql";
    $count_sql = "SELECT COUNT(*) FROM incident_reports WHERE $filter_sql";
} else {
    // For site-specific users, force site filter exactly (exact match)
    $filters['site_name'] = $site_name; // Overwrite any filter_site_name
    $sql_base = "SELECT * FROM incident_reports WHERE site_name = :site_name AND $filter_sql";
    $count_sql = "SELECT COUNT(*) FROM incident_reports WHERE site_name = :site_name AND $filter_sql";
}

// Pagination parameters
$limit = 10; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// First, get the total number of records
$count_stmt = $conn->prepare($count_sql);
foreach ($filters as $key => $value) {
    // For filters using LIKE, binding wildcards have been done already
    $count_stmt->bindValue(":$key", $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Append LIMIT clause to main query
$sql = $sql_base . " LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);

// Bind filters
foreach ($filters as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
// Bind pagination parameters
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Incident Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, nav {
                display: none;
            }
        }
        .pagination-control a {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Incident Reports</h2>

    <!-- Filter Form -->
    <form method="GET" class="row g-2 align-items-center no-print mb-3">
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_person_reporting" placeholder="Person Reporting" value="<?= htmlspecialchars($_GET['filter_person_reporting'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_su_name" placeholder="SU Name" value="<?= htmlspecialchars($_GET['filter_su_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_site_name" placeholder="Site Name" value="<?= htmlspecialchars($_GET['filter_site_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_incident_type" placeholder="Incident Type" value="<?= htmlspecialchars($_GET['filter_incident_type'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="date" class="form-control form-control-sm" name="filter_date_from" value="<?= htmlspecialchars($_GET['filter_date_from'] ?? '') ?>">
                <input type="date" class="form-control form-control-sm" name="filter_date_to" value="<?= htmlspecialchars($_GET['filter_date_to'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-12 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="manage_incident_reports.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="printTable()">Print</button>
        </div>
    </form>

    <!-- Incident Reports Table -->
    <div id="printableArea">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Person Reporting</th>
                    <th>Incident Date/Time</th>
                    <th>SU Name</th>
                    <th>Site Name</th>
                    <th>Incident Type</th>
                    <th>Status</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?= htmlspecialchars($report['id']) ?></td>
                        <td><?= htmlspecialchars($report['person_reporting']) ?></td>
                        <td><?= htmlspecialchars($report['incident_datetime']) ?></td>
                        <td><?= htmlspecialchars($report['su_name']) ?></td>
                        <td><?= htmlspecialchars($report['site_name']) ?></td>
                        <td><?= htmlspecialchars($report['incident_type']) ?></td>
                        <td><?= htmlspecialchars($report['status']) ?></td>
                        <td class="no-print">
                            <a href="view_incident_report.php?id=<?= $report['id'] ?>" class="btn btn-info btn-sm">View</a>
                            <a href="update_incident_report.php?id=<?= $report['id'] ?>" class="btn btn-warning btn-sm">Update</a>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $report['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
        <!-- Pagination Controls -->
        <div class="pagination-control">
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-secondary btn-sm <?= ($page == 1) ? 'disabled' : '' ?>">First</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-secondary btn-sm <?= ($page == 1) ? 'disabled' : '' ?>">Previous</a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn btn-primary btn-sm <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-secondary btn-sm <?= ($page == $total_pages) ? 'disabled' : '' ?>">Next</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="btn btn-secondary btn-sm <?= ($page == $total_pages) ? 'disabled' : '' ?>">Last</a>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="delete_id" id="delete_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this incident report?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDelete(id) {
        document.getElementById('delete_id').value = id;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function printTable() {
        var originalContents = document.body.innerHTML;
        var printContents = document.getElementById('printableArea').innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // Reload to restore scripts and styles
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
