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

// Fetch incident report details
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM incident_reports WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo "<script>alert('Incident report not found!'); window.location.href='manage_incident_reports.php';</script>";
        exit();
    }
} else {
    header('Location: manage_incident_reports.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $incident_datetime = $_POST['incident_datetime'];
    $telephone_number = $_POST['telephone_number'];
    $incident_type = implode(', ', $_POST['incident_type']);
    $incident_location = $_POST['incident_location'];
    $incident_description = $_POST['incident_description'];
    $action_taken = $_POST['action_taken'];
    $witness_port_nass_ref = $_POST['witness_port_nass_ref']; // Single value
    $witness_su_name = $_POST['witness_su_name']; // Single value
    $witness_non_sus = $_POST['witness_non_sus'];

    try {
        $sql = "UPDATE incident_reports SET 
                    incident_datetime = :incident_datetime,
                    telephone_number = :telephone_number,
                    incident_type = :incident_type,
                    incident_location = :incident_location,
                    incident_description = :incident_description,
                    action_taken = :action_taken,
                    witness_port_nass_ref = :witness_port_nass_ref,
                    witness_su_name = :witness_su_name,
                    witness_non_sus = :witness_non_sus
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':incident_datetime' => $incident_datetime,
            ':telephone_number' => $telephone_number,
            ':incident_type' => $incident_type,
            ':incident_location' => $incident_location,
            ':incident_description' => $incident_description,
            ':action_taken' => $action_taken,
            ':witness_port_nass_ref' => $witness_port_nass_ref,
            ':witness_su_name' => $witness_su_name,
            ':witness_non_sus' => $witness_non_sus,
            ':id' => $id
        ]);

        echo "<script>alert('Incident report updated successfully!'); window.location.href='manage_incident_reports.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error updating report: " . $e->getMessage() . "');</script>";
    }
}


// Fetch SU data for dropdowns
$stmt_su = $conn->prepare("SELECT port_nass_ref, su_name FROM sudata");
$stmt_su->execute();
$sus = $stmt_su->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Incident Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Update Incident Report</h2>
    <form method="POST">
    <input type="hidden" name="id" value="<?= htmlspecialchars($report['id']) ?>">

    <!-- Incident Date/Time -->
    <div class="mb-3">
        <label for="incident_datetime" class="form-label">Incident Date/Time</label>
        <input type="datetime-local" class="form-control" id="incident_datetime" name="incident_datetime" value="<?= htmlspecialchars($report['incident_datetime']) ?>" required>
    </div>

    <!-- Telephone Number -->
    <div class="mb-3">
        <label for="telephone_number" class="form-label">Telephone Number</label>
        <input type="text" class="form-control" id="telephone_number" name="telephone_number" value="<?= htmlspecialchars($report['telephone_number']) ?>" required>
    </div>

    <!-- Incident Type -->
    <div class="mb-3">
        <label for="incident_type" class="form-label">Incident Type</label>
        <div>
            <?php
            $incident_types = ['Safeguarding', 'Counter Terrorism', 'Health', 'Financial', 'Non-Compliance', 'Property Related', 'Conflict', 'Other'];
            $selected_types = explode(', ', $report['incident_type']);
            foreach ($incident_types as $type):
            ?>
                <label>
                    <input type="checkbox" name="incident_type[]" value="<?= $type ?>" <?= in_array($type, $selected_types) ? 'checked' : '' ?>>
                    <?= $type ?>
                </label><br>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Exact Location of Incident -->
    <div class="mb-3">
        <label for="incident_location" class="form-label">Exact Location of Incident</label>
        <input type="text" class="form-control" id="incident_location" name="incident_location" value="<?= htmlspecialchars($report['incident_location']) ?>" required>
    </div>

    <!-- Description of the Incident -->
    <div class="mb-3">
        <label for="incident_description" class="form-label">Description of the Incident</label>
        <textarea class="form-control" id="incident_description" name="incident_description" rows="4" required><?= htmlspecialchars($report['incident_description']) ?></textarea>
    </div>

    <!-- What action has been taken -->
    <div class="mb-3">
        <label for="action_taken" class="form-label">What action has been taken?</label>
        <textarea class="form-control" id="action_taken" name="action_taken" rows="4" required><?= htmlspecialchars($report['action_taken']) ?></textarea>
    </div>

    <!-- Witness SU Port/Nass Reference -->
    <div class="mb-3">
        <label for="witness_port_nass_ref" class="form-label">Witness SU Port/Nass Reference</label>
        <input type="text" class="form-control" id="witness_port_nass_ref" name="witness_port_nass_ref" 
               value="<?= htmlspecialchars($report['witness_port_nass_ref'] ?? '') ?>" required>
    </div>

    <!-- Witness SU Name -->
    <div class="mb-3">
        <label for="witness_su_name" class="form-label">Witness SU Name</label>
        <input type="text" class="form-control" id="witness_su_name" name="witness_su_name" 
               value="<?= htmlspecialchars($report['witness_su_name'] ?? '') ?>" required>
    </div>

    <!-- Witness Non-SUs -->
    <div class="mb-3">
        <label for="witness_non_sus" class="form-label">Witness Non-SUs</label>
        <textarea class="form-control" id="witness_non_sus" name="witness_non_sus" rows="3"><?= htmlspecialchars($report['witness_non_sus']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Update</button>
    <a href="manage_incident_reports.php" class="btn btn-secondary">Cancel</a>
</form>

</div>

<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select Options",
            allowClear: true,
            width: '100%'
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</body>
</html>
