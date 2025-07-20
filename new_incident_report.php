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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_reporting = $_SESSION['name'];
    $incident_datetime = $_POST['incident_datetime'];
    $port_nass_ref = $_POST['port_nass_ref'];
    $su_name = $_POST['su_name'];
    $site_name = $_POST['site_name'];
    $telephone_number = $_POST['telephone_number'];
    $incident_type = implode(', ', $_POST['incident_type']); // Convert array to comma-separated string
    $incident_location = $_POST['incident_location'];
    $incident_description = $_POST['incident_description'];
    $action_taken = $_POST['action_taken'];
    $safeguarding_informed = $_POST['safeguarding_informed'];
    $safeguarding_cad_ref = $_POST['safeguarding_cad_ref'];
    $police_involved = $_POST['police_involved'];
    $police_cad_ref = $_POST['police_cad_ref'];
    $ambulance_involved = $_POST['ambulance_involved'];
    $ambulance_cad_ref = $_POST['ambulance_cad_ref'];
    $fire_service_involved = $_POST['fire_service_involved'];
    $fire_cad_ref = $_POST['fire_cad_ref'];
    $witness_port_nass_ref = $_POST['witness_port_nass_ref'];
    $witness_su_name = $_POST['witness_su_name'];
    $witness_non_sus = $_POST['witness_non_sus'];

    try {
        // Insert data into the incident_reports table
        $sql = "INSERT INTO incident_reports (
            person_reporting, incident_datetime, port_nass_ref, su_name, site_name, 
            telephone_number, incident_type, incident_location, incident_description, 
            action_taken, safeguarding_informed, safeguarding_cad_ref, police_involved, 
            police_cad_ref, ambulance_involved, ambulance_cad_ref, fire_service_involved, 
            fire_cad_ref, witness_port_nass_ref, witness_su_name, witness_non_sus
        ) VALUES (
            :person_reporting, :incident_datetime, :port_nass_ref, :su_name, :site_name, 
            :telephone_number, :incident_type, :incident_location, :incident_description, 
            :action_taken, :safeguarding_informed, :safeguarding_cad_ref, :police_involved, 
            :police_cad_ref, :ambulance_involved, :ambulance_cad_ref, :fire_service_involved, 
            :fire_cad_ref, :witness_port_nass_ref, :witness_su_name, :witness_non_sus
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':person_reporting' => $person_reporting,
            ':incident_datetime' => $incident_datetime,
            ':port_nass_ref' => $port_nass_ref,
            ':su_name' => $su_name,
            ':site_name' => $site_name,
            ':telephone_number' => $telephone_number,
            ':incident_type' => $incident_type,
            ':incident_location' => $incident_location,
            ':incident_description' => $incident_description,
            ':action_taken' => $action_taken,
            ':safeguarding_informed' => $safeguarding_informed,
            ':safeguarding_cad_ref' => $safeguarding_cad_ref,
            ':police_involved' => $police_involved,
            ':police_cad_ref' => $police_cad_ref,
            ':ambulance_involved' => $ambulance_involved,
            ':ambulance_cad_ref' => $ambulance_cad_ref,
            ':fire_service_involved' => $fire_service_involved,
            ':fire_cad_ref' => $fire_cad_ref,
            ':witness_port_nass_ref' => $witness_port_nass_ref,
            ':witness_su_name' => $witness_su_name,
            ':witness_non_sus' => $witness_non_sus,
        ]);

        // Success message
        echo "<script>alert('Incident report submitted successfully!');</script>";
    } catch (PDOException $e) {
        // Error message
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// Fetch SU data
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
    <title>New Incident Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>New Incident Report</h2>
    <form method="POST">
        <!-- Person Reporting -->
        <div class="mb-3">
            <label for="person_reporting" class="form-label">Person Reporting</label>
            <input type="text" class="form-control" id="person_reporting" name="person_reporting" value="<?= htmlspecialchars($_SESSION['name']) ?>" readonly>
        </div>

        <!-- Incident Date/Time -->
        <div class="mb-3">
            <label for="incident_datetime" class="form-label">Incident Date/Time</label>
            <input type="datetime-local" class="form-control" id="incident_datetime" name="incident_datetime" required>
        </div>

        <!-- Port/Nass Reference -->
        <div class="mb-3">
    <label for="port_nass_ref" class="form-label">Port/Nass Reference</label>
    <select class="form-select select2" id="port_nass_ref" name="port_nass_ref" onchange="autoFillSUData()" required>
        <option value="">Select Port/Nass Reference</option>
        <?php foreach ($sus as $su): ?>
            <option value="<?= $su['port_nass_ref'] ?>" 
                    data-su-name="<?= htmlspecialchars($su['su_name'] ?? 'Unknown') ?>" 
                    data-site-name="<?= htmlspecialchars($su['site_name'] ?? 'Unknown') ?>"> <!-- Default to 'Unknown' -->
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

        <!-- Telephone Number -->
        <div class="mb-3">
            <label for="telephone_number" class="form-label">Telephone Number</label>
            <input type="text" class="form-control" id="telephone_number" name="telephone_number" required>
        </div>

        <!-- Incident Type -->
        <div class="mb-3">
            <label for="incident_type" class="form-label">Incident Type</label>
            <div>
                <label><input type="checkbox" name="incident_type[]" value="Safeguarding"> Safeguarding</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Counter Terrorism"> Counter Terrorism</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Health"> Health</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Financial"> Financial</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Non-Compliance"> Non-Compliance</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Property Related"> Property Related</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Conflict"> Conflict</label><br>
                <label><input type="checkbox" name="incident_type[]" value="Other"> Other</label>
            </div>
        </div>

        <!-- Exact Location of Incident -->
        <div class="mb-3">
            <label for="incident_location" class="form-label">Exact Location of Incident</label>
            <input type="text" class="form-control" id="incident_location" name="incident_location" required>
        </div>

        <!-- Description of the Incident -->
        <div class="mb-3">
            <label for="incident_description" class="form-label">Description of the Incident</label>
            <textarea class="form-control" id="incident_description" name="incident_description" rows="4" required></textarea>
        </div>

        <!-- What action has been taken -->
        <div class="mb-3">
            <label for="action_taken" class="form-label">What action has been taken?</label>
            <textarea class="form-control" id="action_taken" name="action_taken" rows="4" required></textarea>
        </div>

        <!-- Reported Authorities -->
        <div class="mb-3">
            <label class="form-label">Reported Authorities</label>
            <div>
                <label>Safeguarding Informed:</label>
                <input type="radio" name="safeguarding_informed" value="Yes" onchange="toggleCADRef('safeguarding', true)"> Yes
                <input type="radio" name="safeguarding_informed" value="No" onchange="toggleCADRef('safeguarding', false)"> No
                <input type="text" id="safeguarding_cad_ref" name="safeguarding_cad_ref" class="form-control mt-2" placeholder="CAD Ref" readonly>
            </div>
            <div>
                <label>Police Involved:</label>
                <input type="radio" name="police_involved" value="Yes" onchange="toggleCADRef('police', true)"> Yes
                <input type="radio" name="police_involved" value="No" onchange="toggleCADRef('police', false)"> No
                <input type="text" id="police_cad_ref" name="police_cad_ref" class="form-control mt-2" placeholder="CAD Ref" readonly>
            </div>
            <div>
                <label>Ambulance Involved:</label>
                <input type="radio" name="ambulance_involved" value="Yes" onchange="toggleCADRef('ambulance', true)"> Yes
                <input type="radio" name="ambulance_involved" value="No" onchange="toggleCADRef('ambulance', false)"> No
                <input type="text" id="ambulance_cad_ref" name="ambulance_cad_ref" class="form-control mt-2" placeholder="CAD Ref" readonly>
            </div>
            <div>
                <label>Fire Service Involved:</label>
                <input type="radio" name="fire_service_involved" value="Yes" onchange="toggleCADRef('fire', true)"> Yes
                <input type="radio" name="fire_service_involved" value="No" onchange="toggleCADRef('fire', false)"> No
                <input type="text" id="fire_cad_ref" name="fire_cad_ref" class="form-control mt-2" placeholder="CAD Ref" readonly>
            </div>
        </div>

      <!-- Witness SU's Port/Nass Reference -->
<div class="mb-3">
    <label for="witness_port_nass_ref" class="form-label">Witness SU's Port/Nass Reference</label>
    <select class="form-select select2" id="witness_port_nass_ref" name="witness_port_nass_ref" onchange="autoFillWitnessSUData()" required>
        <option value="">Select Witness Port/Nass Reference</option>
        <?php foreach ($sus as $su): ?>
            <option value="<?= $su['port_nass_ref'] ?>" 
                    data-witness-su-name="<?= htmlspecialchars($su['su_name']) ?>">
                <?= htmlspecialchars($su['port_nass_ref']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Witness SU Name -->
<div class="mb-3">
    <label for="witness_su_name" class="form-label">Witness SU Name</label>
    <input type="text" class="form-control" id="witness_su_name" name="witness_su_name" readonly>
</div>


        <!-- Witness Non-SUs -->
        <div class="mb-3">
            <label for="witness_non_sus" class="form-label">Witness Non-SUs</label>
            <input type="text" class="form-control" id="witness_non_sus" name="witness_non_sus" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script>
    function autoFillSUData() {
        const selectedOption = document.querySelector("#port_nass_ref").selectedOptions[0];
        document.getElementById("su_name").value = selectedOption.getAttribute("data-su-name") || "";
        document.getElementById("site_name").value = selectedOption.getAttribute("data-site-name") || "";
    }

    function toggleCADRef(authority, isEditable) {
        const input = document.getElementById(`${authority}_cad_ref`);
        input.readOnly = !isEditable;
        if (!isEditable) input.value = '';
    }

    function autoFillWitnessSUData() {
    const selectedOption = document.querySelector("#witness_port_nass_ref").selectedOptions[0];
    document.getElementById("witness_su_name").value = selectedOption.getAttribute("data-witness-su-name") || "";
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
