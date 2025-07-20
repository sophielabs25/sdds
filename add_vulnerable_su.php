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

// Fetch SU data for dropdown
$site_name = $_SESSION['site_name'];
if ($site_name === 'level2') {
    $stmt = $conn->prepare("SELECT port_nass_ref, su_name, dob, flat_number, gender, site_name FROM sudata");
} else {
    $stmt = $conn->prepare("SELECT port_nass_ref, su_name, dob, flat_number, gender, site_name FROM sudata WHERE site_name = :site_name");
    $stmt->bindParam(':site_name', $site_name);
}
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $port_ref = $_POST['port_ref'];
    $su_name = $_POST['su_name'];
    $dob = $_POST['dob'];
    $flat_no = $_POST['flat_no'];
    $su_group = $_POST['su_group'];
    $gender = $_POST['gender'];
    $vulnerability = $_POST['vulnerability'];
    $action_taken = $_POST['action_taken'];
    $site_name = $_POST['site_name'];

    try {
        $sql = "INSERT INTO vulnerable_sus (
                    port_ref, su_name, dob, flat_no, su_group, gender, vulnerability, action_taken, site_name
                ) VALUES (
                    :port_ref, :su_name, :dob, :flat_no, :su_group, :gender, :vulnerability, :action_taken, :site_name
                )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':port_ref' => $port_ref,
            ':su_name' => $su_name,
            ':dob' => $dob,
            ':flat_no' => $flat_no,
            ':su_group' => $su_group,
            ':gender' => $gender,
            ':vulnerability' => $vulnerability,
            ':action_taken' => $action_taken,
            ':site_name' => $site_name,
        ]);

        $success_message = "Vulnerable SU added successfully!";
    } catch (PDOException $e) {
        $error_message = "Error adding Vulnerable SU: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vulnerable SU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Add Vulnerable SU</h2>

    <!-- Alerts -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Port Reference -->
        <div class="mb-3">
            <label for="port_ref" class="form-label">Port Reference</label>
            <select class="form-select select2" id="port_ref" name="port_ref" onchange="autoFillSUData()" required>
                <option value="">Select Port Reference</option>
                <?php foreach ($sus as $su): ?>
                    <option value="<?= $su['port_nass_ref'] ?>" 
                            data-su-name="<?= htmlspecialchars($su['su_name']) ?>" 
                            data-dob="<?= htmlspecialchars($su['dob']) ?>"
                            data-flat-no="<?= htmlspecialchars($su['flat_number']) ?>"
                            data-gender="<?= htmlspecialchars($su['gender']) ?>"
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

        <!-- DOB -->
        <div class="mb-3">
            <label for="dob" class="form-label">DOB</label>
            <input type="date" class="form-control" id="dob" name="dob" readonly>
        </div>

        <!-- Flat Number -->
        <div class="mb-3">
            <label for="flat_no" class="form-label">Flat No</label>
            <input type="text" class="form-control" id="flat_no" name="flat_no" readonly>
        </div>

        <!-- Group -->
        <div class="mb-3">
            <label for="su_group" class="form-label">Group</label>
            <input type="text" class="form-control" id="su_group" name="su_group" readonly>
        </div>

        <!-- Gender -->
        <div class="mb-3">
            <label for="gender" class="form-label">Gender</label>
            <input type="text" class="form-control" id="gender" name="gender" readonly>
        </div>

        <!-- Site Name -->
        <div class="mb-3">
            <label for="site_name" class="form-label">Site Name</label>
            <input type="text" class="form-control" id="site_name" name="site_name" readonly>
        </div>

        <!-- Vulnerability -->
        <div class="mb-3">
            <label for="vulnerability" class="form-label">Vulnerability</label>
            <textarea class="form-control" id="vulnerability" name="vulnerability" rows="3" required></textarea>
        </div>

        <!-- Action Taken -->
        <div class="mb-3">
            <label for="action_taken" class="form-label">Action Taken</label>
            <textarea class="form-control" id="action_taken" name="action_taken" rows="3" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Add Vulnerable SU</button>
    </form>
</div>

<script>
    // Auto-fill SU details based on selected Port/Nass Ref
    function autoFillSUData() {
        const selectedOption = document.querySelector("#port_ref").selectedOptions[0];
        const suName = selectedOption.getAttribute("data-su-name") || "";
        const dob = selectedOption.getAttribute("data-dob") || "";
        const flatNo = selectedOption.getAttribute("data-flat-no") || "";
        const gender = selectedOption.getAttribute("data-gender") || "";
        const siteName = selectedOption.getAttribute("data-site-name") || "";

        document.getElementById("su_name").value = suName;
        document.getElementById("dob").value = dob;
        document.getElementById("flat_no").value = flatNo;
        document.getElementById("gender").value = gender;
        document.getElementById("site_name").value = siteName;

        // Calculate group (Adult/Children) based on DOB
        const birthDate = new Date(dob);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        const group = age < 18 ? "Children" : "Adult";
        document.getElementById("su_group").value = group;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select Port Reference",
            allowClear: true,
            width: '100%'
        });
    });
</script>

</body>
</html>
