<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';  // Replace with your MySQL username
$pass = '';      // Replace with your MySQL password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Get the booking_id from the URL
if (isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];

    // Fetch SU details
    $stmt = $conn->prepare("SELECT * FROM sudata WHERE booking_id = :booking_id");
    $stmt->bindParam(':booking_id', $booking_id);
    $stmt->execute();
    $su = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$su) {
        echo "<script>alert('SU record not found!'); window.location.href = 'manage_su.php';</script>";
        exit();
    }
} else {
    header('Location: manage_su.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-container, .print-container * {
                visibility: visible;
            }
            .print-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
            .print-button, .back-button {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5 print-container">
    <h2>SU Details</h2>
    <table class="table table-bordered">
        <tr>
            <th>Booking ID</th>
            <td><?= htmlspecialchars($su['booking_id']) ?></td>
        </tr>
        <tr>
            <th>Move In Date</th>
            <td><?= htmlspecialchars($su['move_in_date']) ?></td>
        </tr>
        <tr>
            <th>SU Name</th>
            <td><?= htmlspecialchars($su['su_name']) ?></td>
        </tr>
        <tr>
            <th>Port/Nass Ref</th>
            <td><?= htmlspecialchars($su['port_nass_ref']) ?></td>
        </tr>
        <tr>
            <th>Site Name</th>
            <td><?= htmlspecialchars($su['site_name']) ?></td>
        </tr>
        <tr>
            <th>Country</th>
            <td><?= htmlspecialchars($su['country']) ?></td>
        </tr>
        <tr>
            <th>Date of Birth</th>
            <td><?= htmlspecialchars($su['dob']) ?></td>
        </tr>
        <tr>
            <th>Gender</th>
            <td><?= htmlspecialchars($su['gender']) ?></td>
        </tr>
        <tr>
            <th>Language</th>
            <td><?= htmlspecialchars($su['language']) ?></td>
        </tr>
        <tr>
            <th>Flat Number</th>
            <td><?= htmlspecialchars($su['flat_number']) ?></td>
        </tr>
        <tr>
            <th>SU Makeup</th>
            <td><?= htmlspecialchars($su['su_makeup']) ?></td>
        </tr>
    </table>
    <div class="d-flex justify-content-between">
        <a href="manage_su.php" class="btn btn-secondary back-button">Back</a>
        <button class="btn btn-primary print-button" onclick="window.print()">Print</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

