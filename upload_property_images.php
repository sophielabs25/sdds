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
    die("Database Connection Error: " . $e->getMessage());
}

$success_message = $error_message = "";

// Fetch Properties
$stmt = $conn->prepare("SELECT * FROM properties");
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['property_image'])) {
    $property_id = $_POST['property_id'];

    $target_dir = "uploads/properties/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["property_image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (move_uploaded_file($_FILES["property_image"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO property_images (property_id, image_path) VALUES (:property_id, :image_path)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':property_id' => $property_id,
            ':image_path' => $target_file
        ]);

        $success_message = "Image uploaded successfully!";
    } else {
        $error_message = "Error uploading file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Property Image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Upload Property Image</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Select Property</label>
            <select class="form-select" name="property_id" required>
                <option value="">Select Property</option>
                <?php foreach ($properties as $property): ?>
                    <option value="<?= $property['id'] ?>"><?= htmlspecialchars($property['property_name']) ?> (Flat: <?= $property['flat_number'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Image</label>
            <input type="file" class="form-control" name="property_image" required>
        </div>

        <button type="submit" class="btn btn-primary">Upload Image</button>
    </form>
</div>

</body>
</html>
