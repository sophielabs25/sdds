<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Fetch Properties
$site_name = $_SESSION['site_name'];
if ($site_name === 'level2') {
    $stmt_properties = $conn->prepare("SELECT site_name, flat_number FROM properties");
} else {
    $stmt_properties = $conn->prepare("SELECT site_name, flat_number FROM properties WHERE site_name = ?");
    $stmt_properties->execute([$site_name]);
}
$properties = $stmt_properties->fetchAll(PDO::FETCH_ASSOC);

// Handle Room Addition
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $site_name = $_POST['site_name'];
    $flat_number = $_POST['flat_number'];
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $total_beds = $_POST['total_beds'];
    $available_beds = $_POST['available_beds'];
    $room_size = $_POST['room_size'];
    $room_status = $_POST['room_status'];

    $stmt = $conn->prepare("INSERT INTO rooms (site_name, flat_number, room_number, room_type, total_beds, available_beds, room_size, room_status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$site_name, $flat_number, $room_number, $room_type, $total_beds, $available_beds, $room_size, $room_status]);

    echo "<script>alert('Room Added Successfully'); window.location.href='manage_rooms.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Add Room</h2>
    <form method="POST">
        <select name="flat_number">
            <?php foreach ($properties as $property): ?>
                <option value="<?= htmlspecialchars($property['flat_number']) ?>">
                    <?= htmlspecialchars($property['flat_number']) ?> (<?= htmlspecialchars($property['site_name']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="room_number" required placeholder="Room Number">
        <button type="submit">Add Room</button>
    </form>
</div>
</body>
</html>
