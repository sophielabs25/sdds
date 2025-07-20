<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database Connection
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Get Property ID
if (!isset($_GET['property_id'])) {
    header('Location: manage_properties.php');
    exit();
}
$property_id = $_GET['property_id'];

// Fetch Property Name
$property_stmt = $conn->prepare("SELECT property_name FROM properties WHERE id = :id");
$property_stmt->execute([':id' => $property_id]);
$property = $property_stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    die("Property not found.");
}

// Handle Room Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $total_beds = $_POST['total_beds'];
    $room_size = $_POST['room_size'];
    $room_status = $_POST['room_status'];
    $bed_type = $_POST['bed_type'];

    $sql = "INSERT INTO rooms (property_id, room_number, room_type, total_beds, room_size, room_status, bed_type) 
            VALUES (:property_id, :room_number, :room_type, :total_beds, :room_size, :room_status, :bed_type)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':property_id' => $property_id,
        ':room_number' => $room_number,
        ':room_type' => $room_type,
        ':total_beds' => $total_beds,
        ':room_size' => $room_size,
        ':room_status' => $room_status,
        ':bed_type' => $bed_type
    ]);

    header("Location: manage_rooms.php?property_id=$property_id");
    exit();
}

// Handle Room Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room_id'])) {
    $delete_sql = "DELETE FROM rooms WHERE id = :room_id";
    $stmt = $conn->prepare($delete_sql);
    $stmt->execute([':room_id' => $_POST['delete_room_id']]);
    header("Location: manage_rooms.php?property_id=$property_id");
    exit();
}

// Handle Room Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_id'])) {
    $update_sql = "UPDATE rooms SET 
        room_number = :room_number, 
        room_type = :room_type, 
        total_beds = :total_beds, 
        room_size = :room_size, 
        room_status = :room_status, 
        bed_type = :bed_type 
        WHERE id = :room_id";
    $stmt = $conn->prepare($update_sql);
    $stmt->execute([
        ':room_id' => $_POST['update_room_id'],
        ':room_number' => $_POST['room_number'],
        ':room_type' => $_POST['room_type'],
        ':total_beds' => $_POST['total_beds'],
        ':room_size' => $_POST['room_size'],
        ':room_status' => $_POST['room_status'],
        ':bed_type' => $_POST['bed_type']
    ]);
    header("Location: manage_rooms.php?property_id=$property_id");
    exit();
}

// Fetch All Rooms
$sql = "SELECT * FROM rooms WHERE property_id = :property_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':property_id' => $property_id]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Rooms for <?= htmlspecialchars($property['property_name']) ?></h2>
    <a href="manage_properties.php" class="btn btn-secondary mb-3">Back to Properties</a>

    <!-- Add Room Form -->
    <form method="POST" class="mb-4">
        <h4>Add Room</h4>
        <div class="row">
            <div class="col-md-2">
                <input type="text" class="form-control" name="room_number" placeholder="Room Number" required>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="room_type" required>
                    <option value="Single">Single</option>
                    <option value="Double">Double</option>
                    <option value="Dormitory">Dormitory</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="total_beds" placeholder="Total Beds" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="room_size" placeholder="Size (sq. ft)" required>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="room_status" required>
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="bed_type" required>
                    <option value="Single">Single</option>
                    <option value="Double">Double</option>
                    <option value="Queen">Queen</option>
                    <option value="King">King</option>
                </select>
            </div>
        </div>
        <button type="submit" name="add_room" class="btn btn-primary mt-3">Add Room</button>
    </form>

    <!-- Rooms Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Room Number</th>
                <th>Type</th>
                <th>Total Beds</th>
                <th>Size (sq. ft)</th>
                <th>Status</th>
                <th>Bed Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                    <td><?= htmlspecialchars($room['room_type']) ?></td>
                    <td><?= htmlspecialchars($room['total_beds']) ?></td>
                    <td><?= htmlspecialchars($room['room_size']) ?></td>
                    <td><?= htmlspecialchars($room['room_status']) ?></td>
                    <td><?= htmlspecialchars($room['bed_type']) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editRoom(<?= htmlspecialchars(json_encode($room)) ?>)">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_room_id" value="<?= $room['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function editRoom(room) {
        document.getElementById('edit_room_id').value = room.id;
        document.getElementById('edit_room_number').value = room.room_number;
        document.getElementById('edit_room_type').value = room.room_type;
        document.getElementById('edit_total_beds').value = room.total_beds;
        document.getElementById('edit_room_size').value = room.room_size;
        document.getElementById('edit_room_status').value = room.room_status;
        new bootstrap.Modal(document.getElementById('editRoomModal')).show();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
