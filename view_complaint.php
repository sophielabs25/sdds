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
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch complaint details
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM complaints WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        echo "<script>alert('Complaint not found!'); window.location.href='manage_complaints.php';</script>";
        exit();
    }
} else {
    header('Location: manage_complaints.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printableTable, #printableTable * {
                visibility: visible;
            }
            #printableTable {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 20px; /* Add margin to all sides */
                padding: 20px; /* Add padding to make it more readable */
              }
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">

    <div id="printableTable">
    <h2>Complaint Details</h2>
        <table class="table table-bordered">
            <tr><th>ID</th><td><?= htmlspecialchars($complaint['id']) ?></td></tr>
            <tr><th>Port/Nass Reference</th><td><?= htmlspecialchars($complaint['port_nass_ref']) ?></td></tr>
            <tr><th>SU Name</th><td><?= htmlspecialchars($complaint['su_name']) ?></td></tr>
            <tr><th>Site Name</th><td><?= htmlspecialchars($complaint['site_name']) ?></td></tr>
            <tr><th>Source of Complaint</th><td><?= htmlspecialchars($complaint['source_of_complaint']) ?></td></tr>
            <tr><th>Client Ref Number</th><td><?= htmlspecialchars($complaint['client_ref_number']) ?></td></tr>
            <tr><th>Officer Leading</th><td><?= htmlspecialchars($complaint['officer_leading']) ?></td></tr>
            <tr><th>Issue Type</th><td><?= htmlspecialchars($complaint['issue_type']) ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($complaint['status']) ?></td></tr>
            <tr><th>Notes</th><td><?= htmlspecialchars($complaint['notes']) ?></td></tr>
            <tr><th>Date Received</th><td><?= htmlspecialchars($complaint['date_received']) ?></td></tr>
            <tr><th>Date Responded</th><td><?= htmlspecialchars($complaint['date_responded']) ?></td></tr>
            <tr><th>Deadline to Respond</th><td><?= htmlspecialchars($complaint['deadline_to_respond']) ?></td></tr>
        </table>
    </div>
    <a href="manage_complaints.php" class="btn btn-secondary">Back</a>
    <button onclick="window.print();" class="btn btn-primary">Print</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
