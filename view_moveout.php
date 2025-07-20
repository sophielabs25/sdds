<?php 
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['site_name'])) {
    header('Location: login.php');
    exit();
}

// Get the site name from the session
$site_name = $_SESSION['site_name'];

// Database connection
$host = 'localhost'; // Your host
$dbname = 'user_database'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Handle deletion of moveout record
if (isset($_POST['delete_moveout_id']) && isset($_POST['delete_dob'])) {
    $delete_moveout_id = $_POST['delete_moveout_id'];
    $delete_dob = $_POST['delete_dob'];

    // Check if the DOB matches for the selected record
    $check_dob_sql = "SELECT * FROM moveout WHERE moveout_id = :moveout_id AND dob = :dob";
    $stmt_check_dob = $conn->prepare($check_dob_sql);
    $stmt_check_dob->bindParam(':moveout_id', $delete_moveout_id);
    $stmt_check_dob->bindParam(':dob', $delete_dob);
    $stmt_check_dob->execute();

    if ($stmt_check_dob->rowCount() > 0) {
        // If DOB matches, delete the record
        $delete_sql = "DELETE FROM moveout WHERE moveout_id = :moveout_id";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bindParam(':moveout_id', $delete_moveout_id);
        $stmt_delete->execute();
        echo "<script>alert('Moveout record deleted successfully.');</script>";
    } else {
        echo "<script>alert('DOB does not match. Cannot delete the record.');</script>";
    }
}

// Handle updating of moveout record
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_moveout_id'])) {
    $update_moveout_id = $_POST['update_moveout_id'];
    $booking_id = $_POST['booking_id'];
    $su_name = $_POST['su_name'];
    $port_nass_ref = $_POST['port_nass_ref'];
    $site_name_post = $_POST['site_name']; // from form
    $country = $_POST['country'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $language = $_POST['language'];
    $flat_number = $_POST['flat_number'];
    $su_makeup = $_POST['su_makeup'];
    $move_out_option = $_POST['move_out_option'];
    $move_out_date = $_POST['move_out_date'];

    // Update the moveout record in the database
    $update_sql = "UPDATE moveout SET 
                   booking_id = :booking_id, su_name = :su_name, port_nass_ref = :port_nass_ref, site_name = :site_name,
                   country = :country, dob = :dob, gender = :gender, language = :language, flat_number = :flat_number, 
                   su_makeup = :su_makeup, move_out_option = :move_out_option, move_out_date = :move_out_date
                   WHERE moveout_id = :moveout_id";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->execute([
        ':booking_id' => $booking_id,
        ':su_name' => $su_name,
        ':port_nass_ref' => $port_nass_ref,
        ':site_name' => $site_name_post,
        ':country' => $country,
        ':dob' => $dob,
        ':gender' => $gender,
        ':language' => $language,
        ':flat_number' => $flat_number,
        ':su_makeup' => $su_makeup,
        ':move_out_option' => $move_out_option,
        ':move_out_date' => $move_out_date,
        ':moveout_id' => $update_moveout_id
    ]);
    echo "<script>alert('Moveout record updated successfully.');</script>";
}

// Initialize filters
$filters = [];
$filter_sql = "1=1"; // Always true condition to concatenate filters

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['filter_booking_id'])) {
        $filters['filter_booking_id'] = $_GET['filter_booking_id'];
        $filter_sql .= " AND booking_id LIKE :filter_booking_id";
    }

    if (!empty($_GET['filter_su_name'])) {
        $filters['filter_su_name'] = $_GET['filter_su_name'];
        $filter_sql .= " AND su_name LIKE :filter_su_name";
    }

    if (!empty($_GET['filter_port_nass_ref'])) {
        $filters['filter_port_nass_ref'] = $_GET['filter_port_nass_ref'];
        $filter_sql .= " AND port_nass_ref LIKE :filter_port_nass_ref";
    }

    if (!empty($_GET['filter_move_out_reason'])) {
        $filters['filter_move_out_reason'] = $_GET['filter_move_out_reason'];
        $filter_sql .= " AND move_out_option = :filter_move_out_reason";
    }

    if (!empty($_GET['filter_move_out_date'])) {
        $filters['filter_move_out_date'] = $_GET['filter_move_out_date'];
        $filter_sql .= " AND move_out_date = :filter_move_out_date";
    }
}

// Adjust query based on user role and filters
if ($site_name === 'Superadmin' || $site_name === 'level2') {
    $sql_base = "SELECT * FROM moveout WHERE $filter_sql";
} else {
    $sql_base = "SELECT * FROM moveout WHERE site_name = :site_name AND $filter_sql";
}

// Pagination Setup
$limit = 10; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// First, get total count (without limit)
if ($site_name === 'Superadmin' || $site_name === 'level2') {
    $count_sql = "SELECT COUNT(*) FROM moveout WHERE $filter_sql";
    $total_stmt = $conn->prepare($count_sql);
} else {
    $count_sql = "SELECT COUNT(*) FROM moveout WHERE site_name = :site_name AND $filter_sql";
    $total_stmt = $conn->prepare($count_sql);
    $total_stmt->bindValue(':site_name', $site_name);
}
foreach ($filters as $key => $value) {
    if ($key === 'filter_booking_id' || $key === 'filter_su_name' || $key === 'filter_port_nass_ref') {
        $total_stmt->bindValue(":$key", "%$value%");
    } else {
        $total_stmt->bindValue(":$key", $value);
    }
}
$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Append LIMIT to main query
$sql = $sql_base . " LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);

// If not all-access, site_name is already bound earlier in count query, so bind here as well.
if ($site_name !== 'Superadmin' && $site_name !== 'level2') {
    $stmt->bindValue(':site_name', $site_name);
}
foreach ($filters as $key => $value) {
    if ($key === 'filter_booking_id' || $key === 'filter_su_name' || $key === 'filter_port_nass_ref') {
        $stmt->bindValue(":$key", "%$value%");
    } else {
        $stmt->bindValue(":$key", $value);
    }
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$moveouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Move Out Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, nav {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Include the navigation bar -->
<?php include 'navbar.php'; ?>



<div class="container mt-5">
    <h2>Move Out Data</h2>

    <!-- Filters -->
    <form method="GET" class="row g-3 align-items-center mb-3 no-print">
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_booking_id" placeholder="Booking ID" value="<?= htmlspecialchars($_GET['filter_booking_id'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_su_name" placeholder="SU Name" value="<?= htmlspecialchars($_GET['filter_su_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_port_nass_ref" placeholder="Port/Nass Ref" value="<?= htmlspecialchars($_GET['filter_port_nass_ref'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="filter_move_out_reason">
                <option value="">Move Out Reason</option>
                <option value="NTV" <?= (isset($_GET['filter_move_out_reason']) && $_GET['filter_move_out_reason'] === 'NTV') ? 'selected' : '' ?>>NTV</option>
                <option value="Dispersal" <?= (isset($_GET['filter_move_out_reason']) && $_GET['filter_move_out_reason'] === 'Dispersal') ? 'selected' : '' ?>>Dispersal</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control form-control-sm" name="filter_move_out_date" value="<?= htmlspecialchars($_GET['filter_move_out_date'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="view_moveout.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="printTable()">Print</button>
        </div>
    </form>

    <!-- Table -->
    <div id="printableArea">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>SU Name</th>
                    <th>Port/Nass Ref</th>
                    <th>Country</th>
                    <th>DOB</th>
                    <th>Move In Date</th>
                    <th>Move Out Reason</th>
                    <th>Move Out Date</th>
                    <th class="no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moveouts as $moveout): ?>
                    <tr>
                        <td><?= htmlspecialchars($moveout['booking_id']) ?></td>
                        <td><?= htmlspecialchars($moveout['su_name']) ?></td>
                        <td><?= htmlspecialchars($moveout['port_nass_ref']) ?></td>
                        <td><?= htmlspecialchars($moveout['country']) ?></td>
                        <td><?= htmlspecialchars($moveout['dob']) ?></td>
                        <td><?= htmlspecialchars($moveout['move_in_date']) ?></td>
                        <td><?= htmlspecialchars($moveout['move_out_option']) ?></td>
                        <td><?= htmlspecialchars($moveout['move_out_date']) ?></td>
                        <td class="no-print">
                            <a href="view_moveout_detail.php?moveout_id=<?= $moveout['moveout_id'] ?>" class="btn btn-info btn-sm">View</a>
                            <?php if ($site_name === 'level2' || $site_name === 'Superadmin'): ?>
                                <button class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $moveout['moveout_id'] ?>)">Delete</button>
                                <button class="btn btn-warning btn-sm" onclick="showUpdateModal(<?= $moveout['moveout_id'] ?>, '<?= htmlspecialchars($moveout['booking_id']) ?>', '<?= htmlspecialchars($moveout['su_name']) ?>', '<?= htmlspecialchars($moveout['port_nass_ref']) ?>', '<?= htmlspecialchars($moveout['site_name']) ?>', '<?= htmlspecialchars($moveout['country']) ?>', '<?= htmlspecialchars($moveout['dob']) ?>', '<?= htmlspecialchars($moveout['gender']) ?>', '<?= htmlspecialchars($moveout['language']) ?>', '<?= htmlspecialchars($moveout['flat_number']) ?>', '<?= htmlspecialchars($moveout['su_makeup']) ?>', '<?= htmlspecialchars($moveout['move_out_option']) ?>', '<?= htmlspecialchars($moveout['move_out_date']) ?>')">Update</button>
                            <?php endif; ?>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="view_moveout.php" method="POST">
            <input type="hidden" name="delete_moveout_id" id="delete_moveout_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Moveout Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please enter the Date of Birth (DOB) of the SU to confirm deletion:</p>
                    <div class="mb-3">
                        <label for="delete_dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="delete_dob" name="delete_dob" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="view_moveout.php" method="POST">
            <input type="hidden" name="update_moveout_id" id="update_moveout_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Moveout Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="booking_id" class="form-label">Booking ID</label>
                        <input type="text" class="form-control" id="booking_id" name="booking_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="su_name" class="form-label">SU Name</label>
                        <input type="text" class="form-control" id="su_name" name="su_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="port_nass_ref" class="form-label">Port/Nass Ref</label>
                        <input type="text" class="form-control" id="port_nass_ref" name="port_nass_ref" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" required>
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" required>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <input type="text" class="form-control" id="gender" name="gender" required>
                    </div>
                    <div class="mb-3">
                        <label for="language" class="form-label">Language</label>
                        <input type="text" class="form-control" id="language" name="language" required>
                    </div>
                    <div class="mb-3">
                        <label for="flat_number" class="form-label">Flat Number</label>
                        <input type="text" class="form-control" id="flat_number" name="flat_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="su_makeup" class="form-label">SU Makeup</label>
                        <textarea class="form-control" id="su_makeup" name="su_makeup" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="move_out_option" class="form-label">Move Out Option</label>
                        <select class="form-select" id="move_out_option" name="move_out_option" required>
                            <option value="NTV">NTV</option>
                            <option value="Dispersal">Dispersal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="move_out_date" class="form-label">Move Out Date</label>
                        <input type="date" class="form-control" id="move_out_date" name="move_out_date" required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showDeleteModal(moveout_id) {
        document.getElementById('delete_moveout_id').value = moveout_id;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function showUpdateModal(moveout_id, booking_id, su_name, port_nass_ref, site_name, country, dob, gender, language, flat_number, su_makeup, move_out_option, move_out_date) {
        document.getElementById('update_moveout_id').value = moveout_id;
        document.getElementById('booking_id').value = booking_id;
        document.getElementById('su_name').value = su_name;
        document.getElementById('port_nass_ref').value = port_nass_ref;
        document.getElementById('site_name').value = site_name;
        document.getElementById('country').value = country;
        document.getElementById('dob').value = dob;
        document.getElementById('gender').value = gender;
        document.getElementById('language').value = language;
        document.getElementById('flat_number').value = flat_number;
        document.getElementById('su_makeup').value = su_makeup;
        document.getElementById('move_out_option').value = move_out_option;
        document.getElementById('move_out_date').value = move_out_date;
        var modal = new bootstrap.Modal(document.getElementById('updateModal'));
        modal.show();
    }

    function printTable() {
        var originalContents = document.body.innerHTML;
        var printContents = document.getElementById('printableArea').innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // Reload to restore functionality
    }

</script>
</body>
</html>
