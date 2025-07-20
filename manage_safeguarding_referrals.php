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

// Initialize messages
$delete_success = $delete_error = $update_success = $update_error = "";

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'], $_POST['delete_port_nass_ref'])) {
    $delete_id = $_POST['delete_id'];
    $delete_port_nass_ref = $_POST['delete_port_nass_ref'];

    $check_sql = "SELECT * FROM safeguarding_referrals WHERE id = :id AND port_nass_ref = :port_nass_ref";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->execute([':id' => $delete_id, ':port_nass_ref' => $delete_port_nass_ref]);

    if ($stmt_check->rowCount() > 0) {
        $delete_sql = "DELETE FROM safeguarding_referrals WHERE id = :id";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->execute([':id' => $delete_id]);
        $delete_success = "Safeguarding referral deleted successfully.";
    } else {
        $delete_error = "Port/Nass Reference does not match. Unable to delete.";
    }
    // Redirect to avoid form resubmission
    header("Location: manage_safeguarding_referrals.php?delete_success=" . urlencode($delete_success) . "&delete_error=" . urlencode($delete_error));
    exit();
}

// Handle Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $status = $_POST['update_status'];
    $referral_notes = $_POST['update_referral_notes'];

    try {
        $update_sql = "UPDATE safeguarding_referrals SET 
                            status = :status,
                            referral_notes = :referral_notes
                        WHERE id = :id";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->execute([
            ':status' => $status,
            ':referral_notes' => $referral_notes,
            ':id' => $update_id
        ]);
        $update_success = "Safeguarding referral updated successfully.";
    } catch (PDOException $e) {
        $update_error = "Error updating referral: " . $e->getMessage();
    }
    // Redirect to avoid form resubmission
    header("Location: manage_safeguarding_referrals.php?update_success=" . urlencode($update_success) . "&update_error=" . urlencode($update_error));
    exit();
}

// Handle Filters
$filters = [];
$filter_sql = "1=1"; // Base condition

if (!empty($_GET['filter_council'])) {
    $filters['filter_council'] = '%' . $_GET['filter_council'] . '%'; // Allow partial matches
    $filter_sql .= " AND referral_council LIKE :filter_council";
}

if (!empty($_GET['filter_site_name'])) {
    $filters['filter_site_name'] = '%' . $_GET['filter_site_name'] . '%'; // Allow partial matches
    $filter_sql .= " AND site_name LIKE :filter_site_name";
}

if (!empty($_GET['filter_status'])) {
    $filters['filter_status'] = $_GET['filter_status'];
    $filter_sql .= " AND status = :filter_status";
}

if (!empty($_GET['filter_date_from']) && !empty($_GET['filter_date_to'])) {
    $filters['filter_date_from'] = $_GET['filter_date_from'];
    $filters['filter_date_to'] = $_GET['filter_date_to'];
    $filter_sql .= " AND date_referred BETWEEN :filter_date_from AND :filter_date_to";
}

// Determine user access and apply site filter if needed
$session_site = $_SESSION['site_name'];
if ($session_site === 'Superadmin' || $session_site === 'level2') {
    $sql_base = "SELECT * FROM safeguarding_referrals WHERE $filter_sql";
    $count_sql = "SELECT COUNT(*) FROM safeguarding_referrals WHERE $filter_sql";
} else {
    $sql_base = "SELECT * FROM safeguarding_referrals WHERE site_name = :site_name AND $filter_sql";
    $count_sql = "SELECT COUNT(*) FROM safeguarding_referrals WHERE site_name = :site_name AND $filter_sql";
    // For non-superadmin users, enforce site filter exactly
    $filters['site_name'] = $session_site;
}

// --- Pagination Setup ---
$limit = 10; // Number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total record count (for pagination)
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
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Safeguarding Referrals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
        .pagination-control a {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Safeguarding Referrals</h2>

    <!-- Alerts -->
    <?php if ($delete_success || $update_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($delete_success ?: $update_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($delete_error || $update_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($delete_error ?: $update_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 align-items-center no-print mb-3">
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" id="filter_council" name="filter_council" placeholder="Referral Council" value="<?= htmlspecialchars($_GET['filter_council'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" id="filter_site_name" name="filter_site_name" placeholder="Site Name" value="<?= htmlspecialchars($_GET['filter_site_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" id="filter_status" name="filter_status">
                <option value="">Status</option>
                <option value="Open" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'Open') ? 'selected' : '' ?>>Open</option>
                <option value="Close" <?= (isset($_GET['filter_status']) && $_GET['filter_status'] === 'Close') ? 'selected' : '' ?>>Close</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="date" class="form-control form-control-sm" name="filter_date_from" value="<?= htmlspecialchars($_GET['filter_date_from'] ?? '') ?>">
                <input type="date" class="form-control form-control-sm" name="filter_date_to" value="<?= htmlspecialchars($_GET['filter_date_to'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="manage_safeguarding_referrals.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-secondary btn-sm ms-2" onclick="printTable()">Print</button>
        </div>
    </form>

    <!-- Referrals Table -->
    <div id="printableArea">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Referral Council</th>
                    <th>Site Name</th>
                    <th>Port/Nass Ref</th>
                    <th>SU Name</th>
                    <th>Officer Leading</th>
                    <th>Status</th>
                    <th>Date Referred</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($referrals as $referral): ?>
                    <tr>
                        <td><?= htmlspecialchars($referral['id']) ?></td>
                        <td><?= htmlspecialchars($referral['referral_council']) ?></td>
                        <td><?= htmlspecialchars($referral['site_name']) ?></td>
                        <td><?= htmlspecialchars($referral['port_nass_ref']) ?></td>
                        <td><?= htmlspecialchars($referral['su_name']) ?></td>
                        <td><?= htmlspecialchars($referral['officer_leading']) ?></td>
                        <td><?= htmlspecialchars($referral['status']) ?></td>
                        <td><?= htmlspecialchars($referral['date_referred']) ?></td>
                        <td class="no-print">
                            <a href="view_safeguarding_referral.php?id=<?= $referral['id'] ?>" class="btn btn-info btn-sm">View</a>
                            <button class="btn btn-warning btn-sm" onclick="showUpdateModal(<?= htmlspecialchars(json_encode($referral)) ?>)">Update</button>
                            <button class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $referral['id'] ?>)">Delete</button>
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
                    <h5 class="modal-title" id="updateModalLabel">Update Safeguarding Referral</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_status" class="form-label">Status</label>
                        <select class="form-select" id="update_status" name="update_status" required>
                            <option value="Open">Open</option>
                            <option value="Close">Close</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="update_referral_notes" class="form-label">Referral Notes</label>
                        <textarea class="form-control" id="update_referral_notes" name="update_referral_notes" rows="4" required></textarea>
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
                    <h5 class="modal-title" id="deleteModalLabel">Delete Safeguarding Referral</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please enter the Port/Nass Reference to confirm deletion:</p>
                    <input type="text" class="form-control" name="delete_port_nass_ref" required>
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

    function showUpdateModal(referral) {
        document.getElementById('update_id').value = referral.id;
        document.getElementById('update_status').value = referral.status;
        document.getElementById('update_referral_notes').value = referral.referral_notes;
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
