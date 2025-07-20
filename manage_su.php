<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['site_name'])) {
    // Redirect to login page if not logged in
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
}

// Initialize message variables
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle record update
    if (isset($_POST['booking_id'])) {
        $booking_id = $_POST['booking_id'];
        $move_in_date = $_POST['move_in_date'];
        $su_name = $_POST['su_name'];
        $port_nass_ref = $_POST['port_nass_ref'];
        $country = $_POST['country'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $language = $_POST['language'];
        $flat_number = $_POST['flat_number'];
        $su_makeup = $_POST['su_makeup'];

        try {
            // Update query
            $update_sql = "UPDATE sudata SET 
                            move_in_date = :move_in_date,
                            su_name = :su_name,
                            port_nass_ref = :port_nass_ref,
                            country = :country,
                            dob = :dob,
                            gender = :gender,
                            language = :language,
                            flat_number = :flat_number,
                            su_makeup = :su_makeup
                            WHERE booking_id = :booking_id";
            
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->execute([
                ':move_in_date' => $move_in_date,
                ':su_name' => $su_name,
                ':port_nass_ref' => $port_nass_ref,
                ':country' => $country,
                ':dob' => $dob,
                ':gender' => $gender,
                ':language' => $language,
                ':flat_number' => $flat_number,
                ':su_makeup' => $su_makeup,
                ':booking_id' => $booking_id
            ]);

            $success_message = "SU record updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating record: " . $e->getMessage();
        }
    }

    // Handle deletion of SU
    if (isset($_POST['delete_id']) && isset($_POST['delete_dob'])) {
        try {
            $delete_id = $_POST['delete_id'];
            $delete_dob = $_POST['delete_dob'];

            $check_dob_sql = "SELECT * FROM sudata WHERE booking_id = :booking_id AND dob = :dob";
            $stmt_check_dob = $conn->prepare($check_dob_sql);
            $stmt_check_dob->bindParam(':booking_id', $delete_id);
            $stmt_check_dob->bindParam(':dob', $delete_dob);
            $stmt_check_dob->execute();

            if ($stmt_check_dob->rowCount() > 0) {
                $delete_sql = "DELETE FROM sudata WHERE booking_id = :booking_id";
                $stmt_delete = $conn->prepare($delete_sql);
                $stmt_delete->bindParam(':booking_id', $delete_id);
                $stmt_delete->execute();
                $success_message = "SU record successfully deleted!";
            } else {
                $error_message = "DOB does not match. Cannot delete the record.";
            }
        } catch (PDOException $e) {
            $error_message = "Error deleting record: " . $e->getMessage();
        }
    }

    // Handle move out of SU
    if (isset($_POST['move_out_id'])) {
        try {
            $move_out_id = $_POST['move_out_id'];
            $move_out_option = $_POST['move_out_option'];
            $move_out_date = $_POST['move_out_date'];

            $su_sql = "SELECT * FROM sudata WHERE booking_id = :booking_id";
            $stmt_su = $conn->prepare($su_sql);
            $stmt_su->bindParam(':booking_id', $move_out_id);
            $stmt_su->execute();
            $su_data = $stmt_su->fetch(PDO::FETCH_ASSOC);

            if ($su_data) {
                $move_out_sql = "INSERT INTO moveout (booking_id, move_in_date, su_name, port_nass_ref, site_name, country, dob, gender, language, flat_number, su_makeup, move_out_option, move_out_date)
                                VALUES (:booking_id, :move_in_date, :su_name, :port_nass_ref, :site_name, :country, :dob, :gender, :language, :flat_number, :su_makeup, :move_out_option, :move_out_date)";
                $stmt_move_out = $conn->prepare($move_out_sql);
                $stmt_move_out->execute([
                    ':booking_id' => $su_data['booking_id'],
                    ':move_in_date' => $su_data['move_in_date'],
                    ':su_name' => $su_data['su_name'],
                    ':port_nass_ref' => $su_data['port_nass_ref'],
                    ':site_name' => $su_data['site_name'],
                    ':country' => $su_data['country'],
                    ':dob' => $su_data['dob'],
                    ':gender' => $su_data['gender'],
                    ':language' => $su_data['language'],
                    ':flat_number' => $su_data['flat_number'],
                    ':su_makeup' => $su_data['su_makeup'],
                    ':move_out_option' => $move_out_option,
                    ':move_out_date' => $move_out_date,
                ]);

                $delete_su_sql = "DELETE FROM sudata WHERE booking_id = :booking_id";
                $stmt_delete_su = $conn->prepare($delete_su_sql);
                $stmt_delete_su->bindParam(':booking_id', $move_out_id);
                $stmt_delete_su->execute();
                $success_message = "SU record successfully moved out!";
            } 
        } catch (PDOException $e) {
            $error_message = "Error moving out record: " . $e->getMessage();
        }
    }

    // Redirect to prevent form resubmission
    header("Location: manage_su.php?success=" . urlencode($success_message) . "&error=" . urlencode($error_message));
    exit();
}

// Filters
$filters = [];
$filter_sql = "1=1";

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
if (!empty($_GET['filter_gender'])) {
    // Use EQUAL instead of LIKE for gender
    $filters['filter_gender'] = $_GET['filter_gender'];
    $filter_sql .= " AND gender = :filter_gender"; 
}
if (!empty($_GET['filter_flat_number'])) {
    $filters['filter_flat_number'] = $_GET['filter_flat_number'];
    $filter_sql .= " AND flat_number LIKE :filter_flat_number";
}

// Fetch SU data
if ($site_name === 'level2' || $site_name === 'Superadmin') {
    $sql = "SELECT * FROM sudata WHERE $filter_sql";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM sudata WHERE site_name = :site_name AND $filter_sql";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':site_name', $site_name);
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM sudata WHERE $filter_sql LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);

// Bind parameters carefully
foreach ($filters as $key => $value) {
    if ($key === 'filter_booking_id' || $key === 'filter_su_name' || $key === 'filter_port_nass_ref' || $key === 'filter_flat_number') {
        // For these text fields, we want partial match
        $stmt->bindValue(":$key", "%$value%");
    } elseif ($key === 'filter_gender') {
        // For gender, we do an exact match
        $stmt->bindValue(":$key", $value);
    }
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total records
$total_stmt_sql = "SELECT COUNT(*) FROM sudata WHERE $filter_sql";
$total_stmt = $conn->prepare($total_stmt_sql);

// Same binding logic for total count
foreach ($filters as $key => $value) {
    if ($key === 'filter_booking_id' || $key === 'filter_su_name' || $key === 'filter_port_nass_ref' || $key === 'filter_flat_number') {
        $total_stmt->bindValue(":$key", "%$value%");
    } elseif ($key === 'filter_gender') {
        $total_stmt->bindValue(":$key", $value);
    }
}

$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage SUs</title>
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

<!-- Bootstrap Alerts -->
<?php
if (isset($_GET['success']) && !empty($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> ' . htmlspecialchars($_GET['success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}

if (isset($_GET['error']) && !empty($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> ' . htmlspecialchars($_GET['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}
?>

<div class="container mt-5">
    <h2>Manage SUs</h2>

    <!-- Filter Form -->
    <form method="GET" class="row g-2 align-items-center no-print mb-3">
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
            <select class="form-select form-select-sm" name="filter_gender">
                <option value="">Gender</option>
                <option value="Male" <?= (isset($_GET['filter_gender']) && $_GET['filter_gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (isset($_GET['filter_gender']) && $_GET['filter_gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_flat_number" placeholder="Flat Number" value="<?= htmlspecialchars($_GET['filter_flat_number'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="manage_su.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="printTable()">Print</button>
        </div>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Move In Date</th>
                <th>SU Name</th>
                <th>Port/Nass Ref</th>
                <th>Site Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Flat Number</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sus as $su): ?>
                <tr>
                    <td><?= htmlspecialchars($su['booking_id']) ?></td>
                    <td><?= htmlspecialchars($su['move_in_date']) ?></td>
                    <td><?= htmlspecialchars($su['su_name']) ?></td>
                    <td><?= htmlspecialchars($su['port_nass_ref']) ?></td>
                    <td><?= htmlspecialchars($su['site_name']) ?></td>
                    <td><?= htmlspecialchars($su['dob']) ?></td>
                    <td><?= htmlspecialchars($su['gender']) ?></td>
                    <td><?= htmlspecialchars($su['flat_number']) ?></td>
                    <td>
                        <a href="view_su_detail.php?booking_id=<?= $su['booking_id'] ?>" class="btn btn-info btn-sm">View</a>
                        <?php if (strtolower($site_name) === 'superadmin' || strtolower($site_name) === 'level2' || strtolower($site_name) === strtolower($su['site_name'])): ?>
    <button class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $su['booking_id'] ?>)">Delete</button>
    <button class="btn btn-warning btn-sm" onclick="showMoveOutModal(<?= $su['booking_id'] ?>)">MoveOut</button>
    <button class="btn btn-primary btn-sm" onclick="showUpdateModal(
        '<?= $su['booking_id'] ?>',
        '<?= $su['move_in_date'] ?>',
        '<?= $su['su_name'] ?>',
        '<?= $su['port_nass_ref'] ?>',
        '<?= $su['country'] ?>',
        '<?= $su['dob'] ?>',
        '<?= $su['gender'] ?>',
        '<?= $su['language'] ?>',
        '<?= $su['flat_number'] ?>',
        '<?= $su['su_makeup'] ?>'
    )">Update</button>
<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
        <!-- Render Pagination -->
        <div class="pagination-control">
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-secondary btn-sm <?= $page == 1 ? 'disabled' : '' ?>">First</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-secondary btn-sm <?= $page == 1 ? 'disabled' : '' ?>">Previous</a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn btn-primary btn-sm <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-secondary btn-sm <?= $page == $total_pages ? 'disabled' : '' ?>">Next</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="btn btn-secondary btn-sm <?= $page == $total_pages ? 'disabled' : '' ?>">Last</a>
    </div>

</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="manage_su.php" method="POST">
            <input type="hidden" name="delete_id" id="delete_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete SU Record</h5>
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

<!-- Move Out Modal -->
<div class="modal fade" id="moveOutModal" tabindex="-1" aria-labelledby="moveOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="manage_su.php" method="POST">
            <input type="hidden" name="move_out_id" id="move_out_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveOutModalLabel">Move Out SU</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>The SU will move out. Please select an option and provide the move-out date:</p>
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
                    <button type="submit" class="btn btn-primary">Confirm Move Out</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
       <form action="manage_su.php" method="POST">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="updateModalLabel">Update SU Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <!-- Booking ID -->
            <div class="mb-3">
                <label for="booking_id" class="form-label">Booking ID</label>
                <input type="text" class="form-control" id="booking_id" name="booking_id" required>
            </div>
            <!-- Move In Date -->
            <div class="mb-3">
                <label for="move_in_date" class="form-label">Move In Date</label>
                <input type="date" class="form-control" id="move_in_date" name="move_in_date" required>
            </div>
            <!-- SU Name -->
            <div class="mb-3">
                <label for="su_name" class="form-label">SU Name</label>
                <input type="text" class="form-control" id="su_name" name="su_name" required>
            </div>
            <!-- Port/Nass Ref -->
            <div class="mb-3">
                <label for="port_nass_ref" class="form-label">Port/Nass Ref</label>
                <input type="text" class="form-control" id="port_nass_ref" name="port_nass_ref" required>
            </div>
            <!-- Country -->
            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <input type="text" class="form-control" id="country" name="country" required>
            </div>
            <!-- DOB -->
            <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" class="form-control" id="dob" name="dob" required>
            </div>
            <!-- Gender -->
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-select" id="gender" name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <!-- Language -->
            <div class="mb-3">
                <label for="language" class="form-label">Language</label>
                <input type="text" class="form-control" id="language" name="language" required>
            </div>
            <!-- Flat Number -->
            <div class="mb-3">
                <label for="flat_number" class="form-label">Flat Number</label>
                <input type="text" class="form-control" id="flat_number" name="flat_number" required>
            </div>
            <!-- SU Makeup -->
            <div class="mb-3">
                <label for="su_makeup" class="form-label">SU Makeup</label>
                <textarea class="form-control" id="su_makeup" name="su_makeup" rows="3" required></textarea>
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

<script>
    function printTable() {
        var originalContents = document.body.innerHTML;
        var printContents = document.querySelector('.container').innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    function showDeleteModal(booking_id) {
        document.getElementById('delete_id').value = booking_id;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function showMoveOutModal(booking_id) {
        document.getElementById('move_out_id').value = booking_id;
        var modal = new bootstrap.Modal(document.getElementById('moveOutModal'));
        modal.show();
    }

    function showUpdateModal(
    booking_id,
    move_in_date,
    su_name,
    port_nass_ref,
    country,
    dob,
    gender,
    language,
    flat_number,
    su_makeup
) {
    document.getElementById('booking_id').value = booking_id; // Prefill Booking ID
    document.getElementById('move_in_date').value = move_in_date;
    document.getElementById('su_name').value = su_name;
    document.getElementById('port_nass_ref').value = port_nass_ref;
    document.getElementById('country').value = country;
    document.getElementById('dob').value = dob;
    document.getElementById('gender').value = gender;
    document.getElementById('language').value = language;
    document.getElementById('flat_number').value = flat_number;
    document.getElementById('su_makeup').value = su_makeup;

    var modal = new bootstrap.Modal(document.getElementById('updateModal'));
    modal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
