<?php
session_start(); // Start the session to access session variables

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

// Handle delete action
if (isset($_POST['delete_su'])) {
    $booking_id = $_POST['booking_id'];
    $delete_sql = "DELETE FROM sudata WHERE booking_id = :booking_id";
    $stmt_delete = $conn->prepare($delete_sql);
    $stmt_delete->bindParam(':booking_id', $booking_id);
    $stmt_delete->execute();
    header("Location: view_su.php"); // Refresh the page after deletion
    exit();
}

// Handle move out action
if (isset($_POST['move_out_su'])) {
    $booking_id = $_POST['booking_id'];

    // Retrieve the SU record from sudata
    $select_sql = "SELECT * FROM sudata WHERE booking_id = :booking_id";
    $stmt_select = $conn->prepare($select_sql);
    $stmt_select->bindParam(':booking_id', $booking_id);
    $stmt_select->execute();
    $su = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if ($su) {
        // Insert the record into moveout table with move_out_date
        $move_out_sql = "INSERT INTO moveout (booking_id, su_name, port_nass_ref, site_name, country, dob, gender, language, flat_number, su_makeup, move_out_date)
                         VALUES (:booking_id, :su_name, :port_nass_ref, :site_name, :country, :dob, :gender, :language, :flat_number, :su_makeup, :move_out_date)";
        
        $stmt_move_out = $conn->prepare($move_out_sql);
        $stmt_move_out->bindParam(':booking_id', $su['booking_id']);
        $stmt_move_out->bindParam(':su_name', $su['su_name']);
        $stmt_move_out->bindParam(':port_nass_ref', $su['port_nass_ref']);
        $stmt_move_out->bindParam(':site_name', $su['site_name']);
        $stmt_move_out->bindParam(':country', $su['country']);
        $stmt_move_out->bindParam(':dob', $su['dob']);
        $stmt_move_out->bindParam(':gender', $su['gender']);
        $stmt_move_out->bindParam(':language', $su['language']);
        $stmt_move_out->bindParam(':flat_number', $su['flat_number']);
        $stmt_move_out->bindParam(':su_makeup', $su['su_makeup']);
        $stmt_move_out->bindParam(':move_out_date', date('Y-m-d')); // Set the current date for move out
        
        $stmt_move_out->execute();

        // Delete the SU record from sudata
        $delete_sql = "DELETE FROM sudata WHERE booking_id = :booking_id";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bindParam(':booking_id', $booking_id);
        $stmt_delete->execute();
    }

    header("Location: view_su.php"); // Refresh the page after moving out
    exit();
}

// Fetch all SU records
$su_sql = "SELECT * FROM sudata";
$stmt_su = $conn->prepare($su_sql);
$stmt_su->execute();
$su_records = $stmt_su->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View SU Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>SU Data</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>SU Name</th>
                    <th>Port/Nass Ref</th>
                    <th>Site Name</th>
                    <th>Country</th>
                    <th>DOB</th>
                    <th>Gender</th>
                    <th>Language</th>
                    <th>Flat Number</th>
                    <th>SU Makeup</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($su_records as $su) { ?>
                <tr>
                    <td><?= htmlspecialchars($su['booking_id']) ?></td>
                    <td><?= htmlspecialchars($su['su_name']) ?></td>
                    <td><?= htmlspecialchars($su['port_nass_ref']) ?></td>
                    <td><?= htmlspecialchars($su['site_name']) ?></td>
                    <td><?= htmlspecialchars($su['country']) ?></td>
                    <td><?= htmlspecialchars($su['dob']) ?></td>
                    <td><?= htmlspecialchars($su['gender']) ?></td>
                    <td><?= htmlspecialchars($su['language']) ?></td>
                    <td><?= htmlspecialchars($su['flat_number']) ?></td>
                    <td><?= htmlspecialchars($su['su_makeup']) ?></td>
                    <td>
                        <form action="view_su.php" method="POST" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?= $su['booking_id'] ?>">
                            <button type="submit" name="delete_su" class="btn btn-danger btn-sm">Delete</button>
                        </form>

                        <form action="view_su.php" method="POST" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?= $su['booking_id'] ?>">
                            <button type="button" class="btn btn-warning btn-sm" onclick="confirmMoveOut(<?= $su['booking_id'] ?>)">Move Out</button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for Move Out Confirmation -->
    <div class="modal fade" id="moveOutModal" tabindex="-1" aria-labelledby="moveOutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveOutModalLabel">Confirm Move Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to move out this SU? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form id="moveOutForm" action="view_su.php" method="POST">
                        <input type="hidden" id="moveOutBookingId" name="booking_id">
                        <button type="submit" name="move_out_su" class="btn btn-warning">Yes, Move Out</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function confirmMoveOut(booking_id) {
            // Show the move out confirmation modal
            var moveOutModal = new bootstrap.Modal(document.getElementById('moveOutModal'), {});
            document.getElementById('moveOutBookingId').value = booking_id;
            moveOutModal.show();
        }
    </script>
</body>
</html>
