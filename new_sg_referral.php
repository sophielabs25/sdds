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

// Fetch SU records based on user role
$site_name = $_SESSION['site_name'];
$username = $_SESSION['username'];
$query = "SELECT port_nass_ref, su_name FROM sudata";

if ($site_name !== 'Superadmin') {
    $query .= " WHERE site_name = :site_name";
}
$stmt = $conn->prepare($query);

if ($site_name !== 'Superadmin') {
    $stmt->bindParam(':site_name', $site_name);
}
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referral_council = $_POST['referral_council'];
    $site_name = $_SESSION['site_name']; // Logged-in user's site
    $port_nass_ref = $_POST['port_nass_ref'];
    $su_name = $_POST['su_name'];
    $officer_leading = $_SESSION['name']; // Logged-in user
    $referral_type = $_POST['referral_type'];
    $status = $_POST['status'];
    $date_referred = $_POST['date_referred'];
    $method_of_referral = $_POST['method_of_referral'];
    $acknowledgement_received = $_POST['acknowledgement_received'];
    $response_received_from_la = $_POST['response_received_from_la'];
    $la_leading_officer = $_POST['la_leading_officer'];
    $referral_notes = $_POST['referral_notes'];

    try {
        $sql = "INSERT INTO safeguarding_referrals (
                    referral_council, site_name, port_nass_ref, su_name, officer_leading,
                    referral_type, status, date_referred, method_of_referral,
                    acknowledgement_received, response_received_from_la,
                    la_leading_officer, referral_notes
                ) VALUES (
                    :referral_council, :site_name, :port_nass_ref, :su_name, :officer_leading,
                    :referral_type, :status, :date_referred, :method_of_referral,
                    :acknowledgement_received, :response_received_from_la,
                    :la_leading_officer, :referral_notes
                )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':referral_council' => $referral_council,
            ':site_name' => $site_name,
            ':port_nass_ref' => $port_nass_ref,
            ':su_name' => $su_name,
            ':officer_leading' => $officer_leading,
            ':referral_type' => $referral_type,
            ':status' => $status,
            ':date_referred' => $date_referred,
            ':method_of_referral' => $method_of_referral,
            ':acknowledgement_received' => $acknowledgement_received,
            ':response_received_from_la' => $response_received_from_la,
            ':la_leading_officer' => $la_leading_officer,
            ':referral_notes' => $referral_notes,
        ]);

        echo "<script>alert('Safeguarding referral created successfully!');</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Safeguarding Referral</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Create Safeguarding Referral</h2>
    <form method="POST">
        <!-- Referral Council -->
        <div class="mb-3">
            <label for="referral_council" class="form-label">Referral Council</label>
            <input type="text" class="form-control" id="referral_council" name="referral_council" required>
        </div>

        <!-- Site Name -->
        <div class="mb-3">
            <label for="site_name" class="form-label">Site Name</label>
            <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($site_name) ?>" readonly>
        </div>

        <!-- Port/Nass Reference -->
        <div class="mb-3">
            <label for="port_nass_ref" class="form-label">Port/Nass Reference</label>
            <select class="form-select select2" id="port_nass_ref" name="port_nass_ref" onchange="autoFillSUData()" required>
                <option value="">Select Port/Nass Reference</option>
                <?php foreach ($sus as $su): ?>
                    <option value="<?= htmlspecialchars($su['port_nass_ref']) ?>" 
                            data-su-name="<?= htmlspecialchars($su['su_name']) ?>">
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

        <!-- Officer Leading -->
        <div class="mb-3">
            <label for="officer_leading" class="form-label">Officer Leading</label>
            <input type="text" class="form-control" id="officer_leading" name="officer_leading" value="<?= htmlspecialchars($_SESSION['name']) ?>" readonly>
        </div>

        <!-- Referral Type -->
        <div class="mb-3">
            <label for="referral_type" class="form-label">Referral Type</label>
            <input type="text" class="form-control" id="referral_type" name="referral_type" required>
        </div>

        <!-- Status -->
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Open">Open</option>
                <option value="Close">Close</option>
            </select>
        </div>

        <!-- Date Referred -->
        <div class="mb-3">
            <label for="date_referred" class="form-label">Date Referred</label>
            <input type="date" class="form-control" id="date_referred" name="date_referred" required>
        </div>

        <!-- Method of Referral -->
        <div class="mb-3">
            <label for="method_of_referral" class="form-label">Method of Referral</label>
            <input type="text" class="form-control" id="method_of_referral" name="method_of_referral" required>
        </div>

        <!-- Acknowledgement Received -->
        <div class="mb-3">
            <label for="acknowledgement_received" class="form-label">Acknowledgement Received</label>
            <select class="form-select" id="acknowledgement_received" name="acknowledgement_received" required>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
        </div>

        <!-- Response Received from LA -->
        <div class="mb-3">
            <label for="response_received_from_la" class="form-label">Response Received from LA</label>
            <select class="form-select" id="response_received_from_la" name="response_received_from_la" required>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
        </div>

        <!-- LA Leading Officer -->
        <div class="mb-3">
            <label for="la_leading_officer" class="form-label">LA Leading Officer</label>
            <input type="text" class="form-control" id="la_leading_officer" name="la_leading_officer">
        </div>

        <!-- Referral Notes -->
        <div class="mb-3">
            <label for="referral_notes" class="form-label">Referral Notes or Action Taken</label>
            <textarea class="form-control" id="referral_notes" name="referral_notes" rows="4"></textarea>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script>
    function autoFillSUData() {
        const selectedOption = document.querySelector("#port_nass_ref").selectedOptions[0];
        document.getElementById("su_name").value = selectedOption.getAttribute("data-su-name") || "";
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('.select2').select2({
            placeholder: "Select Port/Nass Reference",
            width: '100%'
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
