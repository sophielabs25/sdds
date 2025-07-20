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

$success_message = $error_message = "";

// Fetch Available Sites
$stmt = $conn->prepare("SELECT * FROM sites");
$stmt->execute();
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve default values for each site from the properties table.
// This query picks one property per site (for example, the first property inserted) to serve as default.
$stmt_defaults = $conn->prepare("
    SELECT p.site_id, p.property_type, p.total_floors, p.total_units, p.address, p.property_name 
    FROM properties p
    INNER JOIN (
        SELECT site_id, MIN(id) AS min_id 
        FROM properties 
        GROUP BY site_id
    ) p2 ON p.site_id = p2.site_id AND p.id = p2.min_id
");
$stmt_defaults->execute();
$defaultsData = $stmt_defaults->fetchAll(PDO::FETCH_ASSOC);
$siteDefaults = [];
foreach ($defaultsData as $row) {
    $siteDefaults[$row['site_id']] = [
        'property_type' => $row['property_type'],
        'total_floors'  => $row['total_floors'],
        'total_units'   => $row['total_units'],
        'address'       => $row['address'],
        'property_name' => $row['property_name']
    ];
}

// Handle Form Submission using POST/Redirect/GET pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_id = $_POST['site_id'] ?? '';
    $flat_number = $_POST['flat_number'] ?? '';
    $property_name = $_POST['property_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $property_type = $_POST['property_type'] ?? '';
    $total_floors = $_POST['total_floors'] ?? 0;
    $total_units = $_POST['total_units'] ?? 0;
    
    // New fields
    $unit_type = $_POST['unit_type'] ?? '';
    $floor_level = $_POST['floor_level'] ?? 0;
    $bedspaces_ppar = $_POST['bedspaces_ppar'] ?? 0;
    $kitchen_lounge_type = $_POST['kitchen_lounge_type'] ?? '';
    $kitchen_lounge_size = $_POST['kitchen_lounge_size'] ?? 0;
    $bathroom_type = $_POST['bathroom_type'] ?? '';
    $bathroom_size = $_POST['bathroom_size'] ?? 0;

    if (empty($site_id) || empty($flat_number) || empty($property_name) || empty($address) || empty($property_type) ||
        $total_floors <= 0 || $total_units <= 0 || empty($unit_type) || $floor_level <= 0 || $bedspaces_ppar <= 0 ||
        empty($kitchen_lounge_type) || $kitchen_lounge_size <= 0 || empty($bathroom_type) || $bathroom_size <= 0) {
        $error_message = "All fields are required.";
        header("Location: add_property.php?error=" . urlencode($error_message));
        exit();
    } else {
        try {
            $conn->beginTransaction();
            // Insert Property
            $sql = "INSERT INTO properties (site_id, flat_number, property_name, address, property_type, total_floors, total_units, unit_type, floor_level, bedspaces_ppar, kitchen_lounge_type, kitchen_lounge_size, bathroom_type, bathroom_size)
                    VALUES (:site_id, :flat_number, :property_name, :address, :property_type, :total_floors, :total_units, :unit_type, :floor_level, :bedspaces_ppar, :kitchen_lounge_type, :kitchen_lounge_size, :bathroom_type, :bathroom_size)";
            $stmt_insert = $conn->prepare($sql);
            $stmt_insert->execute([
                ':site_id' => $site_id,
                ':flat_number' => $flat_number,
                ':property_name' => $property_name,
                ':address' => $address,
                ':property_type' => $property_type,
                ':total_floors' => $total_floors,
                ':total_units' => $total_units,
                ':unit_type' => $unit_type,
                ':floor_level' => $floor_level,
                ':bedspaces_ppar' => $bedspaces_ppar,
                ':kitchen_lounge_type' => $kitchen_lounge_type,
                ':kitchen_lounge_size' => $kitchen_lounge_size,
                ':bathroom_type' => $bathroom_type,
                ':bathroom_size' => $bathroom_size
            ]);
            $property_id = $conn->lastInsertId();

            // Insert Rooms if provided
            if (!empty($_POST['room_numbers'])) {
                foreach ($_POST['room_numbers'] as $index => $room_number) {
                    $room_type = $_POST['room_types'][$index] ?? 'Single';
                    $total_beds = $_POST['total_beds'][$index] ?? 1;
                    $room_size = $_POST['room_sizes'][$index] ?? 0;
                    $room_status = $_POST['room_statuses'][$index] ?? 'Available';
                    $bed_type = $_POST['bed_types'][$index] ?? 'Single';

                    $room_sql = "INSERT INTO rooms (property_id, room_number, room_type, total_beds, room_size, room_status, bed_type) 
                                 VALUES (:property_id, :room_number, :room_type, :total_beds, :room_size, :room_status, :bed_type)";
                    $room_stmt = $conn->prepare($room_sql);
                    $room_stmt->execute([
                        ':property_id' => $property_id,
                        ':room_number' => $room_number,
                        ':room_type' => $room_type,
                        ':total_beds' => $total_beds,
                        ':room_size' => $room_size,
                        ':room_status' => $room_status,
                        ':bed_type' => $bed_type
                    ]);
                }
            }

            // Handle Image Uploads
            if (!empty($_FILES['property_images']['name'][0])) {
                $target_dir = "uploads/properties/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
                    $image_name = basename($_FILES['property_images']['name'][$key]);
                    $target_file = $target_dir . $image_name;
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $img_sql = "INSERT INTO property_images (property_id, image_path) VALUES (:property_id, :image_path)";
                        $img_stmt = $conn->prepare($img_sql);
                        $img_stmt->execute([
                            ':property_id' => $property_id,
                            ':image_path' => $target_file
                        ]);
                    }
                }
            }

            $conn->commit();
            header("Location: add_property.php?success=" . urlencode("Property added successfully!"));
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            header("Location: add_property.php?error=" . urlencode("Error adding property: " . $e->getMessage()));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Property</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    // Pass PHP default values to JavaScript
    const siteDefaults = <?= json_encode($siteDefaults) ?>;
    document.addEventListener('DOMContentLoaded', function() {
      const siteSelect = document.querySelector('select[name="site_id"]');
      siteSelect.addEventListener('change', function() {
          let siteId = this.value;
          if (siteDefaults[siteId]) {
              document.querySelector('select[name="property_type"]').value = siteDefaults[siteId].property_type;
              document.querySelector('input[name="total_floors"]').value = siteDefaults[siteId].total_floors;
              document.querySelector('input[name="total_units"]').value = siteDefaults[siteId].total_units;
              document.querySelector('textarea[name="address"]').value = siteDefaults[siteId].address;
              document.querySelector('input[name="property_name"]').value = siteDefaults[siteId].property_name;
          } else {
              document.querySelector('select[name="property_type"]').value = "";
              document.querySelector('input[name="total_floors"]').value = "";
              document.querySelector('input[name="total_units"]').value = "";
              document.querySelector('textarea[name="address"]').value = "";
              document.querySelector('input[name="property_name"]').value = "";
          }
      });
    });

    // Function to add a new room fieldset
    function addRoomField() {
      let roomContainer = document.getElementById('roomContainer');
      let roomDiv = document.createElement('div');
      roomDiv.classList.add('mb-3', 'room-field');
      roomDiv.innerHTML = `
          <label>Room Number</label>
          <input type="text" class="form-control" name="room_numbers[]" required>
  
          <label>Room Type</label>
          <select class="form-select" name="room_types[]" required>
              <option value="Single">Single</option>
              <option value="Double">Double</option>
              <option value="Dormitory">Dormitory</option>
          </select>
  
          <label>Total Beds</label>
          <input type="number" class="form-control" name="total_beds[]" required>
  
          <label>Room Size (sqm)</label>
          <input type="number" class="form-control" name="room_sizes[]" required>
  
          <label>Room Status</label>
          <select class="form-select" name="room_statuses[]" required>
              <option value="Available">Available</option>
              <option value="Occupied">Occupied</option>
          </select>
  
          <label>Bed Type</label>
          <select class="form-select" name="bed_types[]" required>
              <option value="Single">Single</option>
              <option value="Double">Double</option>
              <option value="Queen">Queen</option>
              <option value="King">King</option>
          </select>
  
          <button type="button" class="btn btn-danger btn-sm mt-2" onclick="this.parentElement.remove()">Remove Room</button>
          <hr>
      `;
      roomContainer.appendChild(roomDiv);
    }
  </script>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
  <h2>Add Property</h2>
  <?php
      if (isset($_GET['success']) && !empty($_GET['success'])) {
          echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
      }
      if (isset($_GET['error']) && !empty($_GET['error'])) {
          echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
      }
  ?>
  <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
          <label class="form-label">Site</label>
          <select class="form-select" name="site_id" required>
              <option value="">Select Site</option>
              <?php foreach ($sites as $site) : ?>
                  <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['site_name']) ?></option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="mb-3">
          <label class="form-label">Property/Block Name</label>
          <input type="text" id="property_name" class="form-control" name="property_name" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Address</label>
          <textarea class="form-control" name="address" required></textarea>
      </div>

      <div class="mb-3">
          <label class="form-label">Property Type</label>
          <select class="form-select" name="property_type" required>
              <option value="">Select Property Type</option>
              <option value="House">House</option>
              <option value="Apartment">Apartment</option>
              <option value="Studio">Studio</option>
              <option value="Dormitory">Dormitory</option>
              <option value="Commercial">Commercial</option>
          </select>
      </div>

      <div class="mb-3">
          <label class="form-label">Total Floors</label>
          <input type="number" class="form-control" name="total_floors" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Total Units</label>
          <input type="number" class="form-control" name="total_units" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Flat Number</label>
          <input type="text" class="form-control" name="flat_number" required>
      </div>

      <!-- New Fields -->
      <div class="mb-3">
          <label class="form-label">Unit Type</label>
          <input type="text" class="form-control" name="unit_type" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Floor Level</label>
          <input type="number" class="form-control" name="floor_level" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Bedspaces According to PPAR</label>
          <input type="number" class="form-control" name="bedspaces_ppar" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Kitchen / Lounge Type</label>
          <select class="form-select" name="kitchen_lounge_type" required>
              <option value="">Select</option>
              <option value="Shared">Shared</option>
              <option value="Private">Private</option>
          </select>
      </div>

      <div class="mb-3">
          <label class="form-label">Kitchen / Lounge Size (sqm)</label>
          <input type="number" step="0.01" class="form-control" name="kitchen_lounge_size" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Bathroom (Shared/Private)</label>
          <select class="form-select" name="bathroom_type" required>
              <option value="">Select</option>
              <option value="Shared">Shared</option>
              <option value="Private">Private</option>
          </select>
      </div>

      <div class="mb-3">
          <label class="form-label">Bathroom Size (sqm)</label>
          <input type="number" step="0.01" class="form-control" name="bathroom_size" required>
      </div>

      <div class="mb-3">
          <label class="form-label">Upload Property Images</label>
          <input type="file" class="form-control" name="property_images[]" multiple accept="image/*">
      </div>

      <h4>Rooms</h4>
      <div id="roomContainer"></div>
      <button type="button" class="btn btn-secondary" onclick="addRoomField()">+ Add Room</button>

      <button type="submit" class="btn btn-primary mt-3">Add Property</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
