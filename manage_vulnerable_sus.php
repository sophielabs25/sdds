<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['site_name'])) {
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

// -----------------
// POST HANDLING (PRG pattern)
// -----------------

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'], $_POST['delete_port_ref'])) {
    $delete_id = $_POST['delete_id'];
    $delete_port_ref = $_POST['delete_port_ref'];

    $check_sql = "SELECT * FROM vulnerable_sus WHERE id = :id AND port_ref = :port_ref";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->execute([':id' => $delete_id, ':port_ref' => $delete_port_ref]);

    if ($stmt_check->rowCount() > 0) {
        $delete_sql = "DELETE FROM vulnerable_sus WHERE id = :id";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->execute([':id' => $delete_id]);
        $success_message = "Vulnerable SU deleted successfully.";
    } else {
        $error_message = "Port/Nass Reference does not match. Unable to delete.";
    }
    header("Location: manage_vulnerable_sus.php");
    exit();
}

// Handle Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $vulnerability = $_POST['update_vulnerability'];
    $action_taken = $_POST['update_action_taken'];

    try {
        $update_sql = "UPDATE vulnerable_sus SET 
                        vulnerability = :vulnerability,
                        action_taken = :action_taken
                      WHERE id = :id";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->execute([
            ':vulnerability' => $vulnerability,
            ':action_taken' => $action_taken,
            ':id' => $update_id
        ]);
        $success_message = "Vulnerable SU updated successfully.";
    } catch (PDOException $e) {
        $error_message = "Error updating SU: " . $e->getMessage();
    }
    header("Location: manage_vulnerable_sus.php");
    exit();
}

// -----------------
// FILTER & PAGINATION SETUP
// -----------------

// Initialize filters and base condition
$filters = [];
$filter_sql = "1=1";

// Filter: Port Ref (partial match)
if (!empty($_GET['filter_port_ref'])) {
    $filters['filter_port_ref'] = '%' . $_GET['filter_port_ref'] . '%';
    $filter_sql .= " AND port_ref LIKE :filter_port_ref";
}

// Filter: SU Name (partial match)
if (!empty($_GET['filter_su_name'])) {
    $filters['filter_su_name'] = '%' . $_GET['filter_su_name'] . '%';
    $filter_sql .= " AND su_name LIKE :filter_su_name";
}

// Filter: Group (exact match)
if (!empty($_GET['filter_group'])) {
    $filters['filter_group'] = $_GET['filter_group'];
    $filter_sql .= " AND su_group = :filter_group";
}

// Filter: Gender (exact match)
if (!empty($_GET['filter_gender'])) {
    $filters['filter_gender'] = $_GET['filter_gender'];
    $filter_sql .= " AND gender = :filter_gender";
}

// For site-specific users, enforce site filter
if ($_SESSION['site_name'] !== 'level2') {
    $filters['site_name'] = $_SESSION['site_name'];
    $filter_sql .= " AND site_name = :site_name";
}

// Base SQL for fetching vulnerable SUs with filters
$sql_base = "SELECT * FROM vulnerable_sus WHERE $filter_sql";

// -----------------
// Pagination
// -----------------

$limit = 10; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total records
$count_sql = "SELECT COUNT(*) FROM vulnerable_sus WHERE $filter_sql";
$count_stmt = $conn->prepare($count_sql);
foreach ($filters as $key => $value) {
    $count_stmt->bindValue(":$key", $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Append LIMIT clause to main query
$sql = $sql_base . " LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);
foreach ($filters as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$vulnerable_sus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vulnerable SUs</title>
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

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Vulnerable SUs</h2>

    <!-- Alerts -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 align-items-center no-print mb-3">
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_port_ref" placeholder="Port Ref" value="<?= htmlspecialchars($_GET['filter_port_ref'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_su_name" placeholder="SU Name" value="<?= htmlspecialchars($_GET['filter_su_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="filter_group">
                <option value="">Group</option>
                <option value="Adult" <?= (isset($_GET['filter_group']) && $_GET['filter_group'] === 'Adult') ? 'selected' : '' ?>>Adult</option>
                <option value="Children" <?= (isset($_GET['filter_group']) && $_GET['filter_group'] === 'Children') ? 'selected' : '' ?>>Children</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="filter_gender">
                <option value="">Gender</option>
                <option value="Male" <?= (isset($_GET['filter_gender']) && $_GET['filter_gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (isset($_GET['filter_gender']) && $_GET['filter_gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
            </select>
        </div>
        <div class="col-md-4 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="manage_vulnerable_sus.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="window.print()">Print</button>
        </div>
    </form>

    <!-- Vulnerable SUs Table -->
    <div id="printableArea">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Port Ref</th>
                    <th>SU Name</th>
                    <th>DOB</th>
                    <th>Flat No</th>
                    <th>Group</th>
                    <th>Gender</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vulnerable_sus as $su): ?>
                    <tr>
                        <td><?= htmlspecialchars($su['id']) ?></td>
                        <td><?= htmlspecialchars($su['port_ref']) ?></td>
                        <td><?= htmlspecialchars($su['su_name']) ?></td>
                        <td><?= htmlspecialchars($su['dob']) ?></td>
                        <td><?= htmlspecialchars($su['flat_no']) ?></td>
                        <td><?= htmlspecialchars($su['su_group']) ?></td>
                        <td><?= htmlspecialchars($su['gender']) ?></td>
                        <td class="no-print">
                            <a href="view_vulnerable_su.php?id=<?= $su['id'] ?>" class="btn btn-info btn-sm">View</a>
                            <button class="btn btn-warning btn-sm" onclick="showUpdateModal(<?= htmlspecialchars(json_encode($su)) ?>)">Update</button>
                            <button class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $su['id'] ?>)">Delete</button>
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

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="update_id" id="update_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Vulnerable SU</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_vulnerability" class="form-label">Vulnerability</label>
                        <textarea class="form-control" id="update_vulnerability" name="update_vulnerability" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="update_action_taken" class="form-label">Action Taken</label>
                        <textarea class="form-control" id="update_action_taken" name="update_action_taken" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="delete_id" id="delete_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Vulnerable SU</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please enter the Port/Nass Reference to confirm deletion:</p>
                    <input type="text" class="form-control" name="delete_port_ref" required>
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
    function showDeleteModal(id) {
        document.getElementById('delete_id').value = id;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function showUpdateModal(su) {
        document.getElementById('update_id').value = su.id;
        document.getElementById('update_vulnerability').value = su.vulnerability;
        document.getElementById('update_action_taken').value = su.action_taken;
        var modal = new bootstrap.Modal(document.getElementById('updateModal'));
        modal.show();
    }

    function printTable() {
        var originalContents = document.body.innerHTML;
        var printContents = document.getElementById('printableArea').innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
