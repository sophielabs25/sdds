<?php 
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Get the site name from the session
$site_name = $_SESSION['site_name'];

// Database connection
$host = 'localhost'; // Your host
$dbname = 'user_database'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Filters
$filters = [];
$filter_sql = "1=1";

if (!empty($_GET['filter_booking_id'])) {
    $filters['filter_booking_id'] = $_GET['filter_booking_id'];
    $filter_sql .= " AND booking_id LIKE :filter_booking_id";
}
if (!empty($_GET['filter_su_name'])) {
    $filters['filter_su_name'] = $_GET['filter_su_name'];
    $filter_sql .= " AND su_name LIKE :filter_su_name";
}
if (!empty($_GET['filter_port_nass_ref'])) {
    $filters['filter_port_nass_ref'] = $_GET['filter_port_nass_ref'];
    $filter_sql .= " AND port_nass_ref LIKE :filter_port_nass_ref";
}
if (!empty($_GET['filter_gender'])) {
    // Use EQUAL instead of LIKE for gender
    $filters['filter_gender'] = $_GET['filter_gender'];
    $filter_sql .= " AND gender = :filter_gender"; 
}
if (!empty($_GET['filter_age'])) {
    $age_filter = $_GET['filter_age'];
    if ($age_filter === 'Minor') {
        $filter_sql .= " AND TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18";
    } elseif ($age_filter === 'Adult') {
        $filter_sql .= " AND TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 18";
    }
}
if (!empty($_GET['filter_flat_number'])) {
    $filters['filter_flat_number'] = $_GET['filter_flat_number'];
    $filter_sql .= " AND flat_number LIKE :filter_flat_number";
}

// Fetch SU data
if ($site_name === 'level2' || $site_name === 'Superadmin') {
    $sql = "SELECT * FROM sudata WHERE $filter_sql";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM sudata WHERE site_name = :site_name AND $filter_sql";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':site_name', $site_name);
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM sudata WHERE $filter_sql LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);

// Bind parameters carefully
foreach ($filters as $key => $value) {
    if ($key === 'filter_booking_id' || $key === 'filter_su_name' || $key === 'filter_port_nass_ref' || $key === 'filter_flat_number'|| $key === 'filter_age') {
        // For these text fields, we want partial match
        $stmt->bindValue(":$key", "%$value%");
    } elseif ($key === 'filter_gender') {
        // For gender, we do an exact match
        $stmt->bindValue(":$key", $value);
    }
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total records
$total_stmt_sql = "SELECT COUNT(*) FROM sudata WHERE $filter_sql";
$total_stmt = $conn->prepare($total_stmt_sql);

// Same binding logic for total count
foreach ($filters as $key => $value) {
    if ($key === 'filter_booking_id' || $key === 'filter_su_name' || $key === 'filter_port_nass_ref' || $key === 'filter_flat_number') {
        $total_stmt->bindValue(":$key", "%$value%");
    } elseif ($key === 'filter_gender') {
        $total_stmt->bindValue(":$key", $value);
    }
}

$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$stmt->execute();
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print, nav {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Include the navigation bar -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>
        <?php if ($site_name === 'level2'): ?>
            SU Records Across All Sites
        <?php else: ?>
            SU Records for <?= htmlspecialchars($site_name) ?> Site
        <?php endif; ?>
    </h2>

    <!-- Filter Form -->
    <form method="GET" class="row g-2 align-items-center no-print mb-3">
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_booking_id" placeholder="Booking ID" value="<?= htmlspecialchars($_GET['filter_booking_id'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_su_name" placeholder="SU Name" value="<?= htmlspecialchars($_GET['filter_su_name'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_port_nass_ref" placeholder="Port/Nass Ref" value="<?= htmlspecialchars($_GET['filter_port_nass_ref'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="filter_gender">
                <option value="">Gender</option>
                <option value="Male" <?= (isset($_GET['filter_gender']) && $_GET['filter_gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (isset($_GET['filter_gender']) && $_GET['filter_gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="filter_age">
                <option value="">Age</option>
                <option value="Minor" <?= (isset($_GET['filter_age']) && $_GET['filter_age'] === 'Minor') ? 'selected' : '' ?>>Minor</option>
                <option value="Adult" <?= (isset($_GET['filter_age']) && $_GET['filter_age'] === 'Adult') ? 'selected' : '' ?>>Adult</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control form-control-sm" name="filter_flat_number" placeholder="Flat Number" value="<?= htmlspecialchars($_GET['filter_flat_number'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="sudata.php" class="btn btn-secondary btn-sm ms-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="printTable()">Print</button>
        </div>
    </form>

    <!-- Table -->
    <div id="printableArea">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Move In Date</th>
                    <th>SU Name</th>
                    <th>Port/Nass Ref</th>
                    <th>Site Name</th>
                    <th>Country</th>
                    <th>DOB</th>
                    <th>Gender</th>
                    <th>Language</th>
                    <th>Flat Number</th>
                    <th>SU Makeup</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sus as $su): ?>
                    <tr>
                        <td><?= htmlspecialchars($su['booking_id']) ?></td>
                        <td><?= htmlspecialchars($su['move_in_date']) ?></td>
                        <td><?= htmlspecialchars($su['su_name']) ?></td>
                        <td><?= htmlspecialchars($su['port_nass_ref']) ?></td>
                        <td><?= htmlspecialchars($su['site_name']) ?></td>
                        <td><?= htmlspecialchars($su['country']) ?></td>
                        <td><?= htmlspecialchars($su['dob']) ?></td>
                        <td><?= htmlspecialchars($su['gender']) ?></td>
                        <td><?= htmlspecialchars($su['language']) ?></td>
                        <td><?= htmlspecialchars($su['flat_number']) ?></td>
                        <td><?= htmlspecialchars($su['su_makeup']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

         <!-- Render Pagination -->
         <div class="pagination-control">
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-secondary btn-sm <?= $page == 1 ? 'disabled' : '' ?>">First</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-secondary btn-sm <?= $page == 1 ? 'disabled' : '' ?>">Previous</a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn btn-primary btn-sm <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-secondary btn-sm <?= $page == $total_pages ? 'disabled' : '' ?>">Next</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="btn btn-secondary btn-sm <?= $page == $total_pages ? 'disabled' : '' ?>">Last</a>
    </div>
</div>

    </div>
</div>

<script>
    function printTable() {
        var originalContents = document.body.innerHTML;
        var printContents = document.getElementById('printableArea').innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // Reload to restore scripts and styles
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
