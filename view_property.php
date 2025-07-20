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

// Check if Property ID is Provided
if (!isset($_GET['id'])) {
    header('Location: manage_properties.php');
    exit();
}
$property_id = $_GET['id'];

// --- Room Management Processing ---
// Process Add Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
  $room_number = $_POST['room_number'] ?? '';
  $room_type = $_POST['room_type'] ?? '';
  $total_beds = $_POST['total_beds'] ?? 0;
  // Allow decimal values for room size by setting step="any"
  $room_size = $_POST['room_size'] ?? 0;
  $room_status = $_POST['room_status'] ?? 'Available';
  $bed_type = $_POST['bed_type'] ?? 'Single';
  try {
      $stmtAdd = $conn->prepare("INSERT INTO rooms (property_id, room_number, room_type, total_beds, room_size, room_status, bed_type)
                                  VALUES (:property_id, :room_number, :room_type, :total_beds, :room_size, :room_status, :bed_type)");
      $stmtAdd->execute([
          ':property_id' => $property_id,
          ':room_number' => $room_number,
          ':room_type' => $room_type,
          ':total_beds' => $total_beds,
          ':room_size' => $room_size,
          ':room_status' => $room_status,
          ':bed_type' => $bed_type
      ]);
      header("Location: view_property.php?id=" . $property_id . "&room_success=" . urlencode("Room added successfully!"));
      exit();
  } catch (PDOException $e) {
      header("Location: view_property.php?id=" . $property_id . "&room_error=" . urlencode("Error adding room: " . $e->getMessage()));
      exit();
  }
}

// Process Update Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_id'])) {
  $room_id = $_POST['update_room_id'];
  $room_number = $_POST['update_room_number'] ?? '';
  $room_type = $_POST['update_room_type'] ?? '';
  $total_beds = $_POST['update_total_beds'] ?? 0;
  // Use step="any" for decimal room size
  $room_size = $_POST['update_room_size'] ?? 0;
  $room_status = $_POST['update_room_status'] ?? 'Available';
  $bed_type = $_POST['update_bed_type'] ?? 'Single';
  try {
      $stmtUpdate = $conn->prepare("UPDATE rooms SET 
          room_number = :room_number, 
          room_type = :room_type, 
          total_beds = :total_beds, 
          room_size = :room_size, 
          room_status = :room_status, 
          bed_type = :bed_type 
          WHERE id = :room_id");
      $stmtUpdate->execute([
          ':room_number' => $room_number,
          ':room_type' => $room_type,
          ':total_beds' => $total_beds,
          ':room_size' => $room_size,
          ':room_status' => $room_status,
          ':bed_type' => $bed_type,
          ':room_id' => $room_id
      ]);
      header("Location: view_property.php?id=" . $property_id . "&room_success=" . urlencode("Room updated successfully!"));
      exit();
  } catch (PDOException $e) {
      header("Location: view_property.php?id=" . $property_id . "&room_error=" . urlencode("Error updating room: " . $e->getMessage()));
      exit();
  }
}

// Process Delete Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room_id'])) {
  $room_id = $_POST['delete_room_id'];
  try {
      $stmtDel = $conn->prepare("DELETE FROM rooms WHERE id = :room_id");
      $stmtDel->execute([':room_id' => $room_id]);
      header("Location: view_property.php?id=" . $property_id . "&room_success=" . urlencode("Room deleted successfully!"));
      exit();
  } catch (PDOException $e) {
      header("Location: view_property.php?id=" . $property_id . "&room_error=" . urlencode("Error deleting room: " . $e->getMessage()));
      exit();
  }
}

// Fetch property details along with site name
$stmt = $conn->prepare("
    SELECT p.*, s.site_name 
    FROM properties p
    JOIN sites s ON p.site_id = s.id 
    WHERE p.id = :id
");
$stmt->execute([':id' => $property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    echo "<script>alert('Property not found!'); window.location.href='manage_properties.php';</script>";
    exit();
}

// Fetch images from property_images table
$stmt_images = $conn->prepare("
    SELECT image_path 
    FROM property_images 
    WHERE property_id = :property_id
");
$stmt_images->execute([':property_id' => $property_id]);
$images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

// Fetch SU's allocated to this flat
$stmt_su = $conn->prepare("
    SELECT * 
    FROM sudata 
    WHERE flat_number = :flat_number 
      AND site_name = :site_name
");
$stmt_su->execute([
    ':flat_number' => $property['flat_number'],
    ':site_name'   => $property['site_name']
]);
$sus = $stmt_su->fetchAll(PDO::FETCH_ASSOC);

// Gather all port_nass_ref for SU’s in this flat
$portNassRefs = array_column($sus, 'port_nass_ref');

// If we have SU’s, we can fetch the relevant records from the other tables
$incidentReports       = [];
$complaints           = [];
$safeguardingReferrals = [];
$vulnerableRecords     = [];

if (count($portNassRefs) > 0) {
    // Build an IN clause with placeholders
    $inClause = rtrim(str_repeat('?,', count($portNassRefs)), ',');

    // 1) Incident Reports
    $sqlIncident = "SELECT * FROM incident_reports WHERE port_nass_ref IN ($inClause)";
    $stmtIncident = $conn->prepare($sqlIncident);
    $stmtIncident->execute($portNassRefs);
    $incidentReports = $stmtIncident->fetchAll(PDO::FETCH_ASSOC);

    // 2) Complaints
    $sqlComplaints = "SELECT * FROM complaints WHERE port_nass_ref IN ($inClause)";
    $stmtComplaints = $conn->prepare($sqlComplaints);
    $stmtComplaints->execute($portNassRefs);
    $complaints = $stmtComplaints->fetchAll(PDO::FETCH_ASSOC);

    // 3) Safeguarding Referrals
    $sqlSafeguarding = "SELECT * FROM safeguarding_referrals WHERE port_nass_ref IN ($inClause)";
    $stmtSafeguarding = $conn->prepare($sqlSafeguarding);
    $stmtSafeguarding->execute($portNassRefs);
    $safeguardingReferrals = $stmtSafeguarding->fetchAll(PDO::FETCH_ASSOC);

    // 4) Vulnerable
    $sqlVulnerable = "SELECT * FROM vulnerable_sus WHERE port_ref IN ($inClause)";
    $stmtVulnerable = $conn->prepare($sqlVulnerable);
    $stmtVulnerable->execute($portNassRefs);
    $vulnerableRecords = $stmtVulnerable->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Rooms for this property
$stmt_rooms = $conn->prepare("
    SELECT * 
    FROM rooms 
    WHERE property_id = :property_id
");
$stmt_rooms->execute([':property_id' => $property_id]);
$rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

// Calculate current bedspaces from rooms
$current_bedspaces = 0;
foreach ($rooms as $room) {
    // Determine multiplier based on bed_type.
    $multiplier = 1;
    if (in_array($room['bed_type'], ["Double", "Queen", "King"])) {
        $multiplier = 2;
    }
    $current_bedspaces += $room['total_beds'] * $multiplier;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Property</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .property-images img {
      width: 100%;
      max-height: 250px;
      object-fit: cover;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>
  
  <div class="container mt-5">
    <h2>View Property Details</h2>

     <!-- Display any room management messages -->
     <?php if(isset($_GET['room_success'])): ?>
          <div class="alert alert-success"><?= htmlspecialchars($_GET['room_success']) ?></div>
        <?php elseif(isset($_GET['room_error'])): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($_GET['room_error']) ?></div>
        <?php endif; ?>
    

    <!-- Nav Tabs (Now 8 total tabs) -->
    <ul class="nav nav-tabs" id="propertyTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="prop-tab" data-bs-toggle="tab" data-bs-target="#prop" type="button" role="tab" aria-controls="prop" aria-selected="true">Property</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#gallery" type="button" role="tab" aria-controls="gallery" aria-selected="false">Gallery</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="su-tab" data-bs-toggle="tab" data-bs-target="#su" type="button" role="tab" aria-controls="su" aria-selected="false">SU's</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms" type="button" role="tab" aria-controls="rooms" aria-selected="false">Manage Rooms</button>
      </li>
      <!-- New Tabs -->
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="incident-tab" data-bs-toggle="tab" data-bs-target="#incident" type="button" role="tab" aria-controls="incident" aria-selected="false">Incident Reports</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="complaints-tab" data-bs-toggle="tab" data-bs-target="#complaints" type="button" role="tab" aria-controls="complaints" aria-selected="false">Complaints</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="safeguarding-tab" data-bs-toggle="tab" data-bs-target="#safeguarding" type="button" role="tab" aria-controls="safeguarding" aria-selected="false">Safeguarding Referral</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="vulnerable-tab" data-bs-toggle="tab" data-bs-target="#vulnerable" type="button" role="tab" aria-controls="vulnerable" aria-selected="false">Vulnerable</button>
      </li>
    </ul>

    <div class="tab-content" id="propertyTabContent">
      <!-- Property Tab -->
      <div class="tab-pane fade show active p-3" id="prop" role="tabpanel" aria-labelledby="prop-tab">
        <table class="table table-bordered table-striped">
          <tbody>
            <tr>
              <th>Site</th>
              <td><?= htmlspecialchars($property['site_name']) ?></td>
            </tr>
            <tr>
              <th>Flat Number</th>
              <td><?= htmlspecialchars($property['flat_number']) ?></td>
            </tr>
            <tr>
              <th>Property Name</th>
              <td><?= htmlspecialchars($property['property_name']) ?></td>
            </tr>
            <tr>
              <th>Address</th>
              <td><?= htmlspecialchars($property['address']) ?></td>
            </tr>
            <tr>
              <th>Property Type</th>
              <td><?= htmlspecialchars($property['property_type']) ?></td>
            </tr>
            <tr>
              <th>Total Floors</th>
              <td><?= htmlspecialchars($property['total_floors']) ?></td>
            </tr>
            <tr>
              <th>Total Units</th>
              <td><?= htmlspecialchars($property['total_units']) ?></td>
            </tr>
            <tr>
              <th>Unit Type</th>
              <td><?= htmlspecialchars($property['unit_type']) ?></td>
            </tr>
            <tr>
              <th>Floor Level</th>
              <td><?= htmlspecialchars($property['floor_level']) ?></td>
            </tr>
            <tr>
              <th>Bedspaces (PPAR)</th>
              <td><?= htmlspecialchars($property['bedspaces_ppar']) ?></td>
            </tr>
            <tr>
              <th>Kitchen/Lounge Type</th>
              <td><?= htmlspecialchars($property['kitchen_lounge_type']) ?></td>
            </tr>
            <tr>
              <th>Kitchen/Lounge Size (sqm)</th>
              <td><?= htmlspecialchars($property['kitchen_lounge_size']) ?></td>
            </tr>
            <tr>
              <th>Bathroom Type</th>
              <td><?= htmlspecialchars($property['bathroom_type']) ?></td>
            </tr>
            <tr>
              <th>Bathroom Size (sqm)</th>
              <td><?= htmlspecialchars($property['bathroom_size']) ?></td>
            </tr>
            <tr>
              <th>Current Bedspaces</th>
              <td><?= htmlspecialchars($current_bedspaces) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Gallery Tab -->
      <div class="tab-pane fade p-3" id="gallery" role="tabpanel" aria-labelledby="gallery-tab">
        <?php if (count($images) > 0): ?>
          <div class="row">
            <?php foreach ($images as $img): ?>
              <div class="col-md-4 mb-3">
                <img src="<?= htmlspecialchars($img['image_path']) ?>" class="img-fluid rounded" alt="Property Image">
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>No images uploaded for this property.</p>
        <?php endif; ?>
      </div>
      
      <!-- SU's Tab -->
      <div class="tab-pane fade p-3" id="su" role="tabpanel" aria-labelledby="su-tab">
        <?php if (count($sus) > 0): ?>
          <table class="table table-bordered">
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
        <?php else: ?>
          <p>No SU's allocated to this flat.</p>
        <?php endif; ?>
      </div>
      
      <!-- Manage Rooms Tab -->
      <!-- Manage Rooms Tab -->
      <div class="tab-pane fade p-3" id="rooms" role="tabpanel" aria-labelledby="rooms-tab">
        <h4>Manage Rooms</h4>
        <!-- Add Room Form -->
        <form method="POST" class="mb-4">
            <input type="hidden" name="property_id" value="<?= $property_id; ?>">
            <div class="row g-3">
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
                    <!-- Allow decimals with step="any" -->
                    <input type="number" step="any" class="form-control" name="room_size" placeholder="Room Size (sq.ft)" required>
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
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Room Type</th>
                    <th>Total Beds</th>
                    <th>Size (sq.ft)</th>
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
                        <td class="no-print">
                            <button type="button" class="btn btn-warning btn-sm" onclick='showRoomUpdateModal(<?= json_encode($room) ?>)'>Edit</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="showRoomDeleteModal(<?= $room['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>

      <!-- New Tab: Incident Reports -->
      <div class="tab-pane fade p-3" id="incident" role="tabpanel" aria-labelledby="incident-tab">
        <h4>Incident Reports</h4>
        <?php if (count($incidentReports) > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>Port/Nass Ref</th>
                <th>Incident Date/Time</th>
                <th>Incident Type</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($incidentReports as $ir): ?>
                <tr>
                  <td><?= htmlspecialchars($ir['id']) ?></td>
                  <td><?= htmlspecialchars($ir['port_nass_ref']) ?></td>
                  <td><?= htmlspecialchars($ir['incident_datetime']) ?></td>
                  <td><?= htmlspecialchars($ir['incident_type']) ?></td>
                  <td>
                    <!-- Example: link to a view page for the incident -->
                    <a href="view_incident_report.php?id=<?= $ir['id'] ?>" class="btn btn-info btn-sm">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No incident reports found for SU's in this flat.</p>
        <?php endif; ?>
      </div>

      <!-- New Tab: Complaints -->
      <div class="tab-pane fade p-3" id="complaints" role="tabpanel" aria-labelledby="complaints-tab">
        <h4>Complaints</h4>
        <?php if (count($complaints) > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>Port/Nass Ref</th>
                <th>Source of Complaint</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($complaints as $c): ?>
                <tr>
                  <td><?= htmlspecialchars($c['id']) ?></td>
                  <td><?= htmlspecialchars($c['port_nass_ref']) ?></td>
                  <td><?= htmlspecialchars($c['source_of_complaint']) ?></td>
                  <td><?= htmlspecialchars($c['status']) ?></td>
                  <td>
                    <!-- Example: link to a view page for the complaint -->
                    <a href="view_complaint.php?id=<?= $c['id'] ?>" class="btn btn-info btn-sm">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No complaints found for SU's in this flat.</p>
        <?php endif; ?>
      </div>

      <!-- New Tab: Safeguarding Referral -->
      <div class="tab-pane fade p-3" id="safeguarding" role="tabpanel" aria-labelledby="safeguarding-tab">
        <h4>Safeguarding Referrals</h4>
        <?php if (count($safeguardingReferrals) > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>Port/Nass Ref</th>
                <th>Referral Council</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($safeguardingReferrals as $sr): ?>
                <tr>
                  <td><?= htmlspecialchars($sr['id']) ?></td>
                  <td><?= htmlspecialchars($sr['port_nass_ref']) ?></td>
                  <td><?= htmlspecialchars($sr['referral_council']) ?></td>
                  <td><?= htmlspecialchars($sr['status']) ?></td>
                  <td>
                    <!-- Example: link to a view page for the safeguarding referral -->
                    <a href="view_safeguarding_referral.php?id=<?= $sr['id'] ?>" class="btn btn-info btn-sm">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No safeguarding referrals found for SU's in this flat.</p>
        <?php endif; ?>
      </div>

      <!-- New Tab: Vulnerable -->
      <div class="tab-pane fade p-3" id="vulnerable" role="tabpanel" aria-labelledby="vulnerable-tab">
        <h4>Vulnerable SU Records</h4>
        <?php if (count($vulnerableRecords) > 0): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>Port/Nass Ref</th>
                <th>SU Name</th>
                <th>Group</th>
                <th>Action Taken</th>
                <th>Vulnerability</th>
                <!-- ... other columns if you have them ... -->
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vulnerableRecords as $vr): ?>
                <tr>
                  <td><?= htmlspecialchars($vr['id']) ?></td>
                  <td><?= htmlspecialchars($vr['port_nass_ref']) ?></td>
                  <td><?= htmlspecialchars($vr['su_name']) ?></td>
                  <td><?= htmlspecialchars($vr['su_group']) ?></td>
                  <td><?= htmlspecialchars($vr['action_taken']) ?></td>
                  <td><?= htmlspecialchars($vr['vulnerability']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No vulnerable SU records found for SU's in this flat.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Room Update Modal -->
  <div class="modal fade" id="roomUpdateModal" tabindex="-1" aria-labelledby="roomUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="view_property.php?id=<?= $property_id ?>">
        <input type="hidden" name="update_room_id" id="update_room_id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="roomUpdateModalLabel">Update Room</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Room Number</label>
              <input type="text" class="form-control" name="update_room_number" id="update_room_number" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Room Type</label>
              <select class="form-select" name="update_room_type" id="update_room_type" required>
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Dormitory">Dormitory</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Total Beds</label>
              <input type="number" class="form-control" name="update_total_beds" id="update_total_beds" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Room Size (sq.ft)</label>
              <input type="number" step="any" class="form-control" name="update_room_size" id="update_room_size" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Room Status</label>
              <select class="form-select" name="update_room_status" id="update_room_status" required>
                <option value="Available">Available</option>
                <option value="Occupied">Occupied</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Bed Type</label>
              <select class="form-select" name="update_bed_type" id="update_bed_type" required>
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Queen">Queen</option>
                <option value="King">King</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Room Delete Modal -->
  <div class="modal fade" id="roomDeleteModal" tabindex="-1" aria-labelledby="roomDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="view_property.php?id=<?= $property_id ?>">
        <input type="hidden" name="delete_room_id" id="delete_room_id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="roomDeleteModalLabel">Delete Room</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this room?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Delete</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <script>
    function showRoomUpdateModal(room) {
        document.getElementById('update_room_id').value = room.id;
        document.getElementById('update_room_number').value = room.room_number;
        document.getElementById('update_room_type').value = room.room_type;
        document.getElementById('update_total_beds').value = room.total_beds;
        document.getElementById('update_room_size').value = room.room_size;
        document.getElementById('update_room_status').value = room.room_status;
        document.getElementById('update_bed_type').value = room.bed_type;
        new bootstrap.Modal(document.getElementById('roomUpdateModal')).show();
    }
  
    function showRoomDeleteModal(roomId) {
        document.getElementById('delete_room_id').value = roomId;
        new bootstrap.Modal(document.getElementById('roomDeleteModal')).show();
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
