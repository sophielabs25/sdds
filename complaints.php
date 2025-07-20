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
    $error_message = "Error connecting to the database: " . $e->getMessage();
}

// Initialize success and error messages
$success_message = null;
$error_message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $port_nass_ref = $_POST['port_nass_ref'];
        $su_name = $_POST['su_name'];
        $site_name = $_POST['site_name'];
        $source_of_complaint = $_POST['source_of_complaint'];
        $client_ref_number = $_POST['client_ref_number'];
        $officer_leading = $_SESSION['name']; // Logged-in user's name
        $issue_type = $_POST['issue_type'];
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        $date_received = $_POST['date_received'];
        $date_responded = $_POST['date_responded'];
        $deadline_to_respond = $_POST['deadline_to_respond'];

        $sql = "INSERT INTO complaints (
                    port_nass_ref, su_name, site_name, source_of_complaint,
                    client_ref_number, officer_leading, issue_type, status,
                    notes, date_received, date_responded, deadline_to_respond
                ) VALUES (
                    :port_nass_ref, :su_name, :site_name, :source_of_complaint,
                    :client_ref_number, :officer_leading, :issue_type, :status,
                    :notes, :date_received, :date_responded, :deadline_to_respond
                )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':port_nass_ref' => $port_nass_ref,
            ':su_name' => $su_name,
            ':site_name' => $site_name,
            ':source_of_complaint' => $source_of_complaint,
            ':client_ref_number' => $client_ref_number,
            ':officer_leading' => $officer_leading,
            ':issue_type' => $issue_type,
            ':status' => $status,
            ':notes' => $notes,
            ':date_received' => $date_received,
            ':date_responded' => $date_responded,
            ':deadline_to_respond' => $deadline_to_respond
        ]);

        $success_message = "Complaint added successfully!";
    } catch (PDOException $e) {
        $error_message = "Error adding complaint: " . $e->getMessage();
    }
}

// Fetch SU records based on user permissions
$site_name = $_SESSION['site_name'];
if ($site_name === 'Superadmin' || $site_name === 'level2') {
    $stmt = $conn->prepare("SELECT port_nass_ref, su_name, site_name FROM sudata");
} else {
    $stmt = $conn->prepare("SELECT port_nass_ref, su_name, site_name FROM sudata WHERE site_name = :site_name");
    $stmt->bindParam(':site_name', $site_name);
}
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Add Complaint</h2>
    <form action="complaints.php" method="POST">
        <!-- Port/Nass Reference -->
        <div class="mb-3">
            <label for="port_nass_ref" class="form-label">Port/Nass Reference</label>
            <select class="form-select select2" id="port_nass_ref" name="port_nass_ref" onchange="autoFillSUData()" required>
                <option value="">Select Port/Nass Reference</option>
                <?php foreach ($sus as $su): ?>
                    <option value="<?= $su['port_nass_ref'] ?>" 
                            data-su-name="<?= htmlspecialchars($su['su_name']) ?>" 
                            data-site-name="<?= htmlspecialchars($su['site_name']) ?>">
                        <?= htmlspecialchars($su['port_nass_ref']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- SU Name -->
        <div class="mb-3">
            <label for="su_name" class="form-label">SU Name</label>
            <input type="text" class="form-control" id="su_name" name="su_name" readonly>
        </div>

        <!-- Site Name -->
        <div class="mb-3">
            <label for="site_name" class="form-label">Site Name</label>
            <input type="text" class="form-control" id="site_name" name="site_name" readonly>
        </div>

        <!-- Source of Complaint -->
        <div class="mb-3">
            <label for="source_of_complaint" class="form-label">Source of Complaint</label>
            <input type="text" class="form-control" id="source_of_complaint" name="source_of_complaint" required>
        </div>

        <!-- Client Ref Number -->
        <div class="mb-3">
            <label for="client_ref_number" class="form-label">Client Ref Number</label>
            <input type="text" class="form-control" id="client_ref_number" name="client_ref_number">
        </div>

        <!-- Officer Leading -->
        <div class="mb-3">
            <label for="officer_leading" class="form-label">Officer Leading</label>
            <input type="text" class="form-control" id="officer_leading" name="officer_leading" 
                   value="<?= htmlspecialchars($_SESSION['name']) ?>" readonly>
        </div>

        <!-- Issue Type -->
        <div class="mb-3">
            <label for="issue_type" class="form-label">Issue Type</label>
            <input type="text" class="form-control" id="issue_type" name="issue_type" required>
        </div>

        <!-- Status -->
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Open">Open</option>
                <option value="Close">Close</option>
            </select>
        </div>

        <!-- Notes -->
        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
        </div>

        <!-- Date Received -->
        <div class="mb-3">
            <label for="date_received" class="form-label">Date Received</label>
            <input type="date" class="form-control" id="date_received" name="date_received" required>
        </div>

        <!-- Date Responded -->
        <div class="mb-3">
            <label for="date_responded" class="form-label">Date Responded</label>
            <input type="date" class="form-control" id="date_responded" name="date_responded">
        </div>

        <!-- Deadline to Respond -->
        <div class="mb-3">
            <label for="deadline_to_respond" class="form-label">Deadline to Respond</label>
            <input type="date" class="form-control" id="deadline_to_respond" name="deadline_to_respond">
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="successMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($success_message)): ?>
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        document.getElementById('successMessage').textContent = "<?= $success_message ?>";
        successModal.show();
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        document.getElementById('errorMessage').textContent = "<?= $error_message ?>";
        errorModal.show();
    <?php endif; ?>
});

function autoFillSUData() {
    const selectedOption = document.querySelector("#port_nass_ref").selectedOptions[0];
    document.getElementById("su_name").value = selectedOption.getAttribute("data-su-name") || "";
    document.getElementById("site_name").value = selectedOption.getAttribute("data-site-name") || "";
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select Port/Nass Reference",
            allowClear: true,
            width: '100%'
        });
    });
</script>

</body>
</html>
