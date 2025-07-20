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

// Get vulnerable SU ID from the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch SU details
    $stmt = $conn->prepare("SELECT * FROM vulnerable_sus WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $su = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$su) {
        echo "<script>alert('Vulnerable SU not found!'); window.location.href = 'manage_vulnerable_sus.php';</script>";
        exit();
    }
} else {
    header('Location: manage_vulnerable_sus.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Vulnerable SU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Vulnerable SU Details</h2>

    <!-- Vulnerable SU Details -->
    <div id="printableArea">
        <table class="table table-bordered">
            <tr>
                <th>Port Reference</th>
                <td><?= htmlspecialchars($su['port_ref']) ?></td>
            </tr>
            <tr>
                <th>SU Name</th>
                <td><?= htmlspecialchars($su['su_name']) ?></td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td><?= htmlspecialchars($su['dob']) ?></td>
            </tr>
            <tr>
                <th>Flat Number</th>
                <td><?= htmlspecialchars($su['flat_no']) ?></td>
            </tr>
            <tr>
                <th>Group</th>
                <td><?= htmlspecialchars($su['su_group']) ?></td>
            </tr>
            <tr>
                <th>Gender</th>
                <td><?= htmlspecialchars($su['gender']) ?></td>
            </tr>
            <tr>
                <th>Site Name</th>
                <td><?= htmlspecialchars($su['site_name']) ?></td>
            </tr>
            <tr>
                <th>Vulnerability</th>
                <td><?= htmlspecialchars($su['vulnerability']) ?></td>
            </tr>
            <tr>
                <th>Action Taken</th>
                <td><?= htmlspecialchars($su['action_taken']) ?></td>
            </tr>
            <tr>
                <th>Created At</th>
                <td><?= htmlspecialchars($su['created_at']) ?></td>
            </tr>
        </table>
    </div>

    <!-- Buttons -->
    <div class="no-print">
        <a href="manage_vulnerable_sus.php" class="btn btn-secondary">Back</a>
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
