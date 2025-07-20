<?php
session_start(); // Start the session to access session variables

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

$message = ''; // To store success/error messages
$modalType = ''; // To store modal type (success or error)

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
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

    // Check if the booking_id or port_nass_ref already exists in the database
    $check_sql = "SELECT COUNT(*) FROM sudata WHERE booking_id = :booking_id OR port_nass_ref = :port_nass_ref";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bindParam(':booking_id', $booking_id);
    $stmt_check->bindParam(':port_nass_ref', $port_nass_ref);
    $stmt_check->execute();
    $count = $stmt_check->fetchColumn();

    if ($count > 0) {
        // Show error if the booking_id or port_nass_ref already exists
        $message = "User already exists. Please go to the View tab and search with Booking ID or Port/Nass Ref.";
        $modalType = 'error';
    } else {
        // Insert into the database if the booking_id and port_nass_ref do not exist
        try {
            $sql = "INSERT INTO sudata (booking_id, move_in_date, su_name, port_nass_ref, site_name, country, dob, gender, language, flat_number, su_makeup)
                    VALUES (:booking_id, :move_in_date, :su_name, :port_nass_ref, :site_name, :country, :dob, :gender, :language, :flat_number, :su_makeup)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->bindParam(':move_in_date', $move_in_date);
            $stmt->bindParam(':su_name', $su_name);
            $stmt->bindParam(':port_nass_ref', $port_nass_ref);
            $stmt->bindParam(':site_name', $site_name); // Automatically set from session
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':language', $language);
            $stmt->bindParam(':flat_number', $flat_number);
            $stmt->bindParam(':su_makeup', $su_makeup);

            $stmt->execute();
            $message = "New SU added successfully!";
            $modalType = 'success';
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $modalType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New SU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


        <!-- Include the navigation bar -->
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <h2>Add New SU</h2>
        <form action="add_su.php" method="POST">
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
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
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

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Add SU</button>
        </form>
    </div>

    <!-- Bootstrap Modal for Success/Error Messages -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel"><?php echo ($modalType == 'success') ? 'Success' : 'Error'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo $message; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // If there is a message, trigger the modal
        <?php if (!empty($message)) { ?>
            var messageModal = new bootstrap.Modal(document.getElementById('messageModal'), {});
            messageModal.show();
        <?php } ?>
    </script>
</body>
</html>
