<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['site_name'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Get the site name from the session
$site_name = $_SESSION['site_name'];

// Database connection
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Handle delete or moveout actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $booking_id = $_POST['booking_id'];

        // Delete action
        if ($_POST['action'] == 'delete') {
            $delete_sql = "DELETE FROM sudata WHERE booking_id = :booking_id AND site_name = :site_name";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->bindParam(':site_name', $site_name);
            $stmt->execute();
            echo "<script>alert('SU has been deleted successfully.');</script>";
        }

        // Move out action
        if ($_POST['action'] == 'moveout') {
            $moveout_sql = "INSERT INTO moveout (booking_id, su_name, port_nass_ref, site_name, country, dob, gender, language, flat_number, su_makeup, moveout_date)
                            SELECT booking_id, su_name, port_nass_ref, site_name, country, dob, gender, language, flat_number, su_makeup, CURDATE() 
                            FROM sudata WHERE booking_id = :booking_id AND site_name = :site_name";
            $stmt = $conn->prepare($moveout_sql);
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->bindParam(':site_name', $site_name);
            $stmt->execute();

            // Now delete the SU from sudata after moving
            $delete_sql = "DELETE FROM sudata WHERE booking_id = :booking_id AND site_name = :site_name";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->bindParam(':site_name', $site_name);
            $stmt->execute();
            echo "<script>alert('SU has been moved out successfully.');</script>";
        }
    }
}

// Fetch the SU data for the logged-in user's site
$sql = "SELECT * FROM sudata WHERE site_name = :site_name";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':site_name', $site_name);
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage SU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Include the navigation bar -->
<?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2>Manage SU Records for <?= htmlspecialchars($site_name); ?> Site</h2>
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
                <?php foreach ($sus as $su) : ?>
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
                        <?php if ($site_name === $su['site_name'] || $site_name === 'level2' || $site_name === 'Superadmin'): ?>
                            <button class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $su['booking_id'] ?>)">Delete</button>
                            <button class="btn btn-warning btn-sm" onclick="showMoveOutModal(<?= $su['booking_id'] ?>)">Move Out</button>
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
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Do you want to delete the record?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="deleteBookingId" name="booking_id">
                        <button type="submit" name="action" value="delete" class="btn btn-danger">Delete</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Move Out Confirmation Modal -->
    <div class="modal fade" id="moveOutModal" tabindex="-1" aria-labelledby="moveOutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveOutModalLabel">Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The SU will be moved out. Are you sure you want to proceed?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="moveOutBookingId" name="booking_id">
                        <button type="submit" name="action" value="moveout" class="btn btn-warning">Move Out</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Function to trigger the Delete modal and set booking ID
        function confirmDelete(bookingId) {
            document.getElementById('deleteBookingId').value = bookingId;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Function to trigger the Move Out modal and set booking ID
        function confirmMoveOut(bookingId) {
            document.getElementById('moveOutBookingId').value = bookingId;
            var moveOutModal = new bootstrap.Modal(document.getElementById('moveOutModal'));
            moveOutModal.show();
        }
    </script>
</body>
</html>
