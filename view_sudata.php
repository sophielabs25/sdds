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
$dbname = 'user_database'; // Replace with your database name
$user = 'root'; // Replace with your MySQL username
$pass = ''; // Replace with your MySQL password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch all SUs grouped by booking_id for the current site
$sql = "SELECT * FROM sudata WHERE site_name = :site_name ORDER BY booking_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':site_name', $site_name);
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group SUs by booking_id
$grouped_sus = [];
foreach ($sus as $su) {
    $grouped_sus[$su['booking_id']][] = $su;
}
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
        <h2>Current Bookings for <?= htmlspecialchars($site_name); ?> Site</h2>
        <div class="accordion" id="bookingAccordion">
            <?php foreach ($grouped_sus as $booking_id => $su_group): ?>
                <!-- Booking Header -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $booking_id; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $booking_id; ?>" aria-expanded="false" aria-controls="collapse<?= $booking_id; ?>">
                            <strong>Booking ID:</strong> <?= htmlspecialchars($booking_id); ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $booking_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $booking_id; ?>" data-bs-parent="#bookingAccordion">
                        <div class="accordion-body">
                            <!-- Subtable for SUs under this booking -->
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>First Name</th>
                                        <th>Port/Nass Ref</th>
                                        <th>Country</th>
                                        <th>DOB</th>
                                        <th>Gender</th>
                                        <th>Flat Number</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($su_group as $su): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($su['su_name']); ?></td>
                                            <td><?= htmlspecialchars($su['port_nass_ref']); ?></td>
                                            <td><?= htmlspecialchars($su['country']); ?></td>
                                            <td><?= htmlspecialchars($su['dob']); ?></td>
                                            <td><?= htmlspecialchars($su['gender']); ?></td>
                                            <td><?= htmlspecialchars($su['flat_number']); ?></td>
                                            <td><button class="btn btn-primary btn-sm">View</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
