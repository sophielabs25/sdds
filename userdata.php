<?php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$dbname = 'user_database';
$user = 'root';  // Replace with your MySQL username
$pass = '';      // Replace with your MySQL password

// Database connection
$conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the site_name of the logged-in user from the session
$site_name = $_SESSION['site_name'];

// Fetch 5 records from the users table filtered by site_name
$stmt = $conn->prepare('SELECT name, ref_number, site_name, dob, gender, country, contact_number FROM users WHERE site_name = :site_name LIMIT 5');
$stmt->execute(['site_name' => $site_name]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <!-- Include the navigation bar -->
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2>Records for <?= htmlspecialchars($_SESSION['site_name']) ?> Site</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Ref Number</th>
                    <th>Site Name</th>
                    <th>DOB</th>
                    <th>Gender</th>
                    <th>Country</th>
                    <th>Contact Number</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['ref_number']) ?></td>
                    <td><?= htmlspecialchars($user['site_name']) ?></td>
                    <td><?= htmlspecialchars($user['dob']) ?></td>
                    <td><?= htmlspecialchars($user['gender']) ?></td>
                    <td><?= htmlspecialchars($user['country']) ?></td>
                    <td><?= htmlspecialchars($user['contact_number']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
