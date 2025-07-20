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
    die("Error connecting to database: " . $e->getMessage());
}

$success_message = null;
$error_message = null;

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    try {
        $update_sql = "UPDATE complaints SET status = :status, notes = :notes WHERE id = :id";
        $stmt = $conn->prepare($update_sql);
        $stmt->execute([
            ':status' => $status,
            ':notes' => $notes,
            ':id' => $id,
        ]);
        $success_message = "Complaint updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating complaint: " . $e->getMessage();
    }
    header("Location: manage_complaints.php?success=" . urlencode($success_message) . "&error=" . urlencode($error_message));
    exit();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $entered_port_nass = $_POST['entered_port_nass'];

    try {
        $stmt_check = $conn->prepare("SELECT * FROM complaints WHERE id = :id AND port_nass_ref = :port_nass_ref");
        $stmt_check->execute([':id' => $delete_id, ':port_nass_ref' => $entered_port_nass]);

        if ($stmt_check->rowCount() > 0) {
            $stmt_delete = $conn->prepare("DELETE FROM complaints WHERE id = :id");
            $stmt_delete->execute([':id' => $delete_id]);
            $success_message = "Complaint deleted successfully!";
        } else {
            $error_message = "Port/Nass reference does not match. Cannot delete.";
        }
    } catch (PDOException $e) {
        $error_message = "Error deleting complaint: " . $e->getMessage();
    }
    header("Location: manage_complaints.php?success=" . urlencode($success_message) . "&error=" . urlencode($error_message));
    exit();
}

// Handle Filters
$filters = [];
$filter_sql = "1=1";

if (!empty($_GET['filter_port_nass_ref'])) {
    $filters['filter_port_nass_ref'] = '%' . $_GET['filter_port_nass_ref'] . '%';
    $filter_sql .= " AND port_nass_ref LIKE :filter_port_nass_ref";
}

if (!empty($_GET['filter_su_name'])) {
    $filters['filter_su_name'] = '%' . $_GET['filter_su_name'] . '%';
    $filter_sql .= " AND su_name LIKE :filter_su_name";
}

if (!empty($_GET['filter_client_ref'])) {
    $filters['filter_client_ref'] = '%' . $_GET['filter_client_ref'] . '%';
    $filter_sql .= " AND client_ref_number LIKE :filter_client_ref";
}

if (!empty($_GET['filter_site_name'])) {
    $filters['filter_site_name'] = '%' . $_GET['filter_site_name'] . '%';
    $filter_sql .= " AND site_name LIKE :filter_site_name";
}

if (!empty($_GET['filter_officer_leading'])) {
    $filters['filter_officer_leading'] = '%' . $_GET['filter_officer_leading'] . '%';
    $filter_sql .= " AND officer_leading LIKE :filter_officer_leading";
}

if (!empty($_GET['filter_source'])) {
    $filters['filter_source'] = '%' . $_GET['filter_source'] . '%';
    $filter_sql .= " AND source_of_complaint LIKE :filter_source";
}

if (!empty($_GET['filter_status'])) {
    $filters['filter_status'] = $_GET['filter_status'];
    $filter_sql .= " AND status = :filter_status";
}

// Determine user access level
$site_name = $_SESSION['site_name'];
if ($site_name === 'Superadmin' || $site_name === 'level2') {
    $sql_base = "SELECT * FROM complaints WHERE $filter_sql";
    $count_sql = "SELECT COUNT(*) FROM complaints WHERE $filter_sql";
} else {
    $sql_base = "SELECT * FROM complaints WHERE site_name = :site_name AND $filter_sql";
    $count_sql = "SELECT COUNT(*) FROM complaints WHERE site_name = :site_name AND $filter_sql";
    // Overwrite filter_site_name with exact site value for non-superadmin users:
    $filters['site_name'] = $site_name;
}

// Pagination Setup
$limit = 10; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total record count for pagination
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
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints</title>
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
    <h2>Manage Complaints</h2>

    <!-- Alerts -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_port_nass_ref" placeholder="Port/Nass Ref" value="<?= htmlspecialchars($_GET['filter_port_nass_ref'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_su_name" placeholder="SU Name" value="<?= htmlspecialchars($_GET['filter_su_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_client_ref" placeholder="Client Ref Number" value="<?= htmlspecialchars($_GET['filter_client_ref'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_site_name" placeholder="Site Name" value="<?= htmlspecialchars($_GET['filter_site_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_officer_leading" placeholder="Officer Leading" value="<?= htmlspecialchars($_GET['filter_officer_leading'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="filter_status">
                <option value="">Status</option>
                <option value="Open" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'Open') ? 'selected' : '' ?>>Open</option>
                <option value="Close" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'Close') ? 'selected' : '' ?>>Close</option>
            </select>
        </div>
        <div class="col-md-3 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="manage_complaints.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="printTable()">Print</button>
        </div>
    </form>

    <!-- Complaints Table -->
    <div id="printableArea">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Port/Nass Reference</th>
                    <th>SU Name</th>
                    <th>Client Ref Number</th>
                    <th>Site Name</th>
                    <th>Officer Leading</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?= htmlspecialchars($complaint['port_nass_ref']) ?></td>
                        <td><?= htmlspecialchars($complaint['su_name']) ?></td>
                        <td><?= htmlspecialchars($complaint['client_ref_number']) ?></td>
                        <td><?= htmlspecialchars($complaint['site_name']) ?></td>
                        <td><?= htmlspecialchars($complaint['officer_leading']) ?></td>
                        <td><?= htmlspecialchars($complaint['source_of_complaint']) ?></td>
                        <td><?= htmlspecialchars($complaint['status']) ?></td>
                        <td class="no-print">
                            <a href="view_complaint.php?id=<?= $complaint['id'] ?>" class="btn btn-info btn-sm">View</a>
                            <button class="btn btn-warning btn-sm" onclick="showUpdateModal(<?= htmlspecialchars(json_encode($complaint)) ?>)">Update</button>
                            <button class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $complaint['id'] ?>)">Delete</button>
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
                    <h5 class="modal-title" id="updateModalLabel">Update Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_status" class="form-label">Status</label>
                        <select class="form-select" id="update_status" name="status" required>
                            <option value="Open">Open</option>
                            <option value="Close">Close</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="update_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="update_notes" name="notes" rows="3" required></textarea>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="delete_id" id="delete_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please enter the Port/Nass Reference to confirm deletion:</p>
                    <input type="text" class="form-control" name="entered_port_nass" required>
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
    function showUpdateModal(complaint) {
        document.getElementById('update_id').value = complaint.id;
        document.getElementById('update_status').value = complaint.status;
        document.getElementById('update_notes').value = complaint.notes || '';
        var updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
        updateModal.show();
    }

    function showDeleteModal(id) {
        document.getElementById('delete_id').value = id;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
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
