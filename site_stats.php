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

// Get distinct site names for dropdown
$site_query = "SELECT DISTINCT site_name FROM sites";
$site_result = $conn->query($site_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Site Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h2>Site Statistics Dashboard</h2>
    
    <!-- Site Selection Dropdown -->
    <div class="form-group mb-4">
        <label for="siteSelect">Select Site:</label>
        <select class="form-control" id="siteSelect" onchange="updateStats()">
            <option value="all">All Sites</option>
            <?php while ($row = $site_result->fetch(PDO::FETCH_ASSOC)) { ?>
                <option value="<?php echo htmlspecialchars($row['site_name']); ?>">
                    <?php echo htmlspecialchars($row['site_name']); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="row">
        <!-- Complaints Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Complaints</h5>
                    <div id="complaintsStats"></div>
                </div>
            </div>
        </div>

        <!-- Incident Reports Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Incident Reports</h5>
                    <div id="incidentStats"></div>
                </div>
            </div>
        </div>

        <!-- Safeguarding Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Safeguarding Referrals</h5>
                    <div id="safeguardingStats"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Service Users Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Service Users</h5>
                    <div id="suStats"></div>
                </div>
            </div>
        </div>

        <!-- Age Breakdown Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Age Breakdown</h5>
                    <div id="ageBreakdownStats"></div>
                </div>
            </div>
        </div>

        <!-- Family Makeup Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Service User Family Makeup</h5>
                    <div id="familyMakeupStats"></div>
                </div>
            </div>
        </div>

        <!-- Properties Stats -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Properties</h5>
                    <div id="propertyStats"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStats() {
    var site = $('#siteSelect').val();

    // Update Complaints Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'complaints', site: site },
        success: function(data) {
            $('#complaintsStats').html(data);
        }
    });

    // Update Incident Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'incidents', site: site },
        success: function(data) {
            $('#incidentStats').html(data);
        }
    });

    // Update Safeguarding Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'safeguarding', site: site },
        success: function(data) {
            $('#safeguardingStats').html(data);
        }
    });

    // Update Property Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'properties', site: site },
        success: function(data) {
            $('#propertyStats').html(data);
        }
    });

    // Update Service Users Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'serviceusers', site: site },
        success: function(data) {
            $('#suStats').html(data);
        }
    });

    // Update Age Breakdown Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'agebreakdown', site: site },
        success: function(data) {
            $('#ageBreakdownStats').html(data);
        }
    });

    // Update Family Makeup Stats
    $.ajax({
        url: 'get_stats.php',
        type: 'POST',
        data: { type: 'familymakeup', site: site },
        success: function(data) {
            $('#familyMakeupStats').html(data);
        }
    });
}

// Load initial stats
$(document).ready(function() {
    updateStats();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>