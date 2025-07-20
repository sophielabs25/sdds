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
    die("Error: " . $e->getMessage());
}

// Fetch referral details
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM safeguarding_referrals WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $referral = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$referral) {
        echo "<script>alert('Safeguarding referral not found!'); window.location.href = 'manage_safeguarding_referrals.php';</script>";
        exit();
    }
} else {
    header('Location: manage_safeguarding_referrals.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Safeguarding Referral</title>
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
    <h2>Safeguarding Referral Details</h2>
    <div id="printableArea">
        <table class="table table-bordered">
            <tr>
                <th>ID</th>
                <td><?= htmlspecialchars($referral['id']) ?></td>
            </tr>
            <tr>
                <th>Referral Council</th>
                <td><?= htmlspecialchars($referral['referral_council']) ?></td>
            </tr>
            <tr>
                <th>Site Name</th>
                <td><?= htmlspecialchars($referral['site_name']) ?></td>
            </tr>
            <tr>
                <th>Port/Nass Reference</th>
                <td><?= htmlspecialchars($referral['port_nass_ref']) ?></td>
            </tr>
            <tr>
                <th>SU Name</th>
                <td><?= htmlspecialchars($referral['su_name']) ?></td>
            </tr>
            <tr>
                <th>Officer Leading</th>
                <td><?= htmlspecialchars($referral['officer_leading']) ?></td>
            </tr>
            <tr>
                <th>Referral Type</th>
                <td><?= htmlspecialchars($referral['referral_type']) ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><?= htmlspecialchars($referral['status']) ?></td>
            </tr>
            <tr>
                <th>Date Referred</th>
                <td><?= htmlspecialchars($referral['date_referred']) ?></td>
            </tr>
            <tr>
                <th>Method of Referral</th>
                <td><?= htmlspecialchars($referral['method_of_referral']) ?></td>
            </tr>
            <tr>
                <th>Acknowledgement Received</th>
                <td><?= htmlspecialchars($referral['acknowledgement_received']) ?></td>
            </tr>
            <tr>
                <th>Response Received from LA</th>
                <td><?= htmlspecialchars($referral['response_received_from_la']) ?></td>
            </tr>
            <tr>
                <th>LA Leading Officer</th>
                <td><?= htmlspecialchars($referral['la_leading_officer']) ?></td>
            </tr>
            <tr>
                <th>Referral Notes / Actions Taken</th>
                <td><?= htmlspecialchars($referral['referral_notes']) ?></td>
            </tr>
        </table>
    </div>
    <div class="no-print">
        <a href="manage_safeguarding_referrals.php" class="btn btn-secondary">Back</a>
        <button class="btn btn-primary" onclick="window.print();">Print</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
