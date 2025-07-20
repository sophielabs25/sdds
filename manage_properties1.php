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

// Fetch Sites for filter dropdown
$stmt = $conn->prepare("SELECT * FROM sites");
$stmt->execute();
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- PRG for Update and Delete ---
// Handle Update Property
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_property_id'])) {
    $update_id = $_POST['update_property_id'];
    $flat_number = $_POST['flat_number'];
    $property_name = $_POST['property_name'];
    $address = $_POST['address'];
    $property_type = $_POST['property_type'];
    $total_floors = $_POST['total_floors'];
    $total_units = $_POST['total_units'];
    // New fields
    $unit_type = $_POST['unit_type'];
    $floor_level = $_POST['floor_level'];
    $bedspaces_ppar = $_POST['bedspaces_ppar'];
    $kitchen_lounge_type = $_POST['kitchen_lounge_type'];
    $kitchen_lounge_size = $_POST['kitchen_lounge_size'];
    $bathroom_type = $_POST['bathroom_type'];
    $bathroom_size = $_POST['bathroom_size'];

    try {
        $stmt = $conn->prepare("UPDATE properties SET 
                                flat_number = :flat_number, 
                                property_name = :property_name, 
                                address = :address, 
                                property_type = :property_type, 
                                total_floors = :total_floors, 
                                total_units = :total_units,
                                unit_type = :unit_type,
                                floor_level = :floor_level,
                                bedspaces_ppar = :bedspaces_ppar,
                                kitchen_lounge_type = :kitchen_lounge_type,
                                kitchen_lounge_size = :kitchen_lounge_size,
                                bathroom_type = :bathroom_type,
                                bathroom_size = :bathroom_size
                                WHERE id = :id");
        $stmt->execute([
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
            ':bathroom_size' => $bathroom_size,
            ':id' => $update_id
        ]);
        header("Location: manage_properties.php?success=" . urlencode("Property updated successfully!"));
        exit();
    } catch (PDOException $e) {
        header("Location: manage_properties.php?error=" . urlencode("Error updating property: " . $e->getMessage()));
        exit();
    }
}

// Handle Property Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property_id'])) {
    $delete_id = $_POST['delete_property_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM properties WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        header("Location: manage_properties.php?success=" . urlencode("Property deleted successfully!"));
        exit();
    } catch (PDOException $e) {
        header("Location: manage_properties.php?error=" . urlencode("Error deleting property: " . $e->getMessage()));
        exit();
    }
}

// --- Filters ---
$filters = [];
$filter_sql = "1=1"; // Default condition

if (!empty($_GET['site_id'])) {
    $filters['site_id'] = $_GET['site_id'];
    $filter_sql .= " AND properties.site_id = :site_id";
}

if (!empty($_GET['flat_number'])) {
    $filters['flat_number'] = '%' . $_GET['flat_number'] . '%';
    $filter_sql .= " AND properties.flat_number LIKE :flat_number";
}

if (!empty($_GET['property_name'])) {
    $filters['property_name'] = '%' . $_GET['property_name'] . '%';
    $filter_sql .= " AND properties.property_name LIKE :property_name";
}

if (!empty($_GET['property_type'])) {
    $filters['property_type'] = $_GET['property_type'];
    $filter_sql .= " AND properties.property_type = :property_type";
}

// New filter fields
if (!empty($_GET['unit_type'])) {
    $filters['unit_type'] = '%' . $_GET['unit_type'] . '%';
    $filter_sql .= " AND properties.unit_type LIKE :unit_type";
}

if (!empty($_GET['floor_level'])) {
    $filters['floor_level'] = $_GET['floor_level'];
    $filter_sql .= " AND properties.floor_level = :floor_level";
}

if (!empty($_GET['bedspaces_ppar'])) {
    $filters['bedspaces_ppar'] = $_GET['bedspaces_ppar'];
    $filter_sql .= " AND properties.bedspaces_ppar = :bedspaces_ppar";
}

if (!empty($_GET['kitchen_lounge_type'])) {
    $filters['kitchen_lounge_type'] = $_GET['kitchen_lounge_type'];
    $filter_sql .= " AND properties.kitchen_lounge_type = :kitchen_lounge_type";
}

if (!empty($_GET['kitchen_lounge_size'])) {
    $filters['kitchen_lounge_size'] = $_GET['kitchen_lounge_size'];
    $filter_sql .= " AND properties.kitchen_lounge_size = :kitchen_lounge_size";
}

if (!empty($_GET['bathroom_type'])) {
    $filters['bathroom_type'] = $_GET['bathroom_type'];
    $filter_sql .= " AND properties.bathroom_type = :bathroom_type";
}

if (!empty($_GET['bathroom_size'])) {
    $filters['bathroom_size'] = $_GET['bathroom_size'];
    $filter_sql .= " AND properties.bathroom_size = :bathroom_size";
}

if (!empty($_GET['current_bedspaces'])) {
    // Here we use a subquery to match the calculated current bedspaces
    $filters['current_bedspaces'] = $_GET['current_bedspaces'];
    $filter_sql .= " AND (
      SELECT COALESCE(SUM(total_beds * CASE WHEN bed_type IN ('Double','Queen','King') THEN 2 ELSE 1 END), 0)
      FROM rooms
      WHERE property_id = properties.id
    ) = :current_bedspaces";
}

// --- Pagination ---
// Set rows per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the base SQL query (joined with sites for the site name)
$sql_base = "SELECT properties.*, sites.site_name,
        (
          SELECT COALESCE(SUM(total_beds * CASE WHEN bed_type IN ('Double','Queen','King') THEN 2 ELSE 1 END), 0)
          FROM rooms
          WHERE property_id = properties.id
        ) AS current_bedspaces
        FROM properties 
        JOIN sites ON properties.site_id = sites.id 
        WHERE $filter_sql";

// Get total count of records
$count_sql = "SELECT COUNT(*) FROM properties JOIN sites ON properties.site_id = sites.id WHERE $filter_sql";
$count_stmt = $conn->prepare($count_sql);
foreach ($filters as $key => $value) {
    $count_stmt->bindValue(":$key", $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Append LIMIT clause to the main query
$sql = $sql_base . " LIMIT :offset, :limit";
$stmt = $conn->prepare($sql);
foreach ($filters as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Properties</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      @media print {
         .no-print, nav { display: none; }
      }
      .pagination-control a {
         margin-right: 5px;
      }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Properties</h2>
    
    <!-- Display success/error messages -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php elseif(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <!-- Advanced Inline Filter Form -->
    <form method="GET" class="d-flex flex-wrap align-items-end mb-3">
        <div class="me-2">
            <label class="form-label mb-0">Site</label>
            <select class="form-select form-select-sm" name="site_id">
                <option value="">All</option>
                <?php foreach ($sites as $site): ?>
                    <option value="<?= $site['id'] ?>" <?= (isset($_GET['site_id']) && $_GET['site_id'] == $site['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($site['site_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Flat #</label>
            <input type="text" class="form-control form-control-sm" name="flat_number" placeholder="Flat Number" value="<?= $_GET['flat_number'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Property Name</label>
            <input type="text" class="form-control form-control-sm" name="property_name" placeholder="Property Name" value="<?= $_GET['property_name'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Type</label>
            <select class="form-select form-select-sm" name="property_type">
                <option value="">All</option>
                <option value="House" <?= (isset($_GET['property_type']) && $_GET['property_type'] == 'House') ? 'selected' : '' ?>>House</option>
                <option value="Apartment" <?= (isset($_GET['property_type']) && $_GET['property_type'] == 'Apartment') ? 'selected' : '' ?>>Apartment</option>
                <option value="Studio" <?= (isset($_GET['property_type']) && $_GET['property_type'] == 'Studio') ? 'selected' : '' ?>>Studio</option>
                <option value="Dormitory" <?= (isset($_GET['property_type']) && $_GET['property_type'] == 'Dormitory') ? 'selected' : '' ?>>Dormitory</option>
                <option value="Commercial" <?= (isset($_GET['property_type']) && $_GET['property_type'] == 'Commercial') ? 'selected' : '' ?>>Commercial</option>
            </select>
        </div>
        <!-- New filter fields -->
        <div class="me-2">
            <label class="form-label mb-0">Unit Type</label>
            <input type="text" class="form-control form-control-sm" name="unit_type" placeholder="Unit Type" value="<?= $_GET['unit_type'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Floor Level</label>
            <input type="number" class="form-control form-control-sm" name="floor_level" placeholder="Floor Level" value="<?= $_GET['floor_level'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Bedspaces (PPAR)</label>
            <input type="number" class="form-control form-control-sm" name="bedspaces_ppar" placeholder="Bedspaces" value="<?= $_GET['bedspaces_ppar'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Current Bedspaces</label>
            <input type="number" class="form-control form-control-sm" name="current_bedspaces" placeholder="Bedspaces" value="<?= $_GET['current_bedspaces'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Kitchen Type</label>
            <select class="form-select form-select-sm" name="kitchen_lounge_type">
                <option value="">All</option>
                <option value="Shared" <?= (isset($_GET['kitchen_lounge_type']) && $_GET['kitchen_lounge_type'] == 'Shared') ? 'selected' : '' ?>>Shared</option>
                <option value="Private" <?= (isset($_GET['kitchen_lounge_type']) && $_GET['kitchen_lounge_type'] == 'Private') ? 'selected' : '' ?>>Private</option>
            </select>
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Kitchen Size (sqm)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" name="kitchen_lounge_size" placeholder="Kitchen Size" value="<?= $_GET['kitchen_lounge_size'] ?? '' ?>">
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Bathroom Type</label>
            <select class="form-select form-select-sm" name="bathroom_type">
                <option value="">All</option>
                <option value="Shared" <?= (isset($_GET['bathroom_type']) && $_GET['bathroom_type'] == 'Shared') ? 'selected' : '' ?>>Shared</option>
                <option value="Private" <?= (isset($_GET['bathroom_type']) && $_GET['bathroom_type'] == 'Private') ? 'selected' : '' ?>>Private</option>
            </select>
        </div>
        <div class="me-2">
            <label class="form-label mb-0">Bathroom Size (sqm)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" name="bathroom_size" placeholder="Bathroom Size" value="<?= $_GET['bathroom_size'] ?? '' ?>">
        </div>
        <div class="d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm me-2">Filter</button>
            <a href="manage_properties.php" class="btn btn-secondary btn-sm me-2">Clear</a>
            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">Print</button>
        </div>
    </form>

    <!-- Properties Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Site</th>
                <th>Flat Number</th>
                <th>Unit Type</th>
                <th>Floor Level</th>
                <th>Bedspaces (PPAR)</th>
                <th>Current Bedspaces</th>
                <th>Kitchen/Lounge Type</th>
                <th>Kitchen Size (sqm)</th>
                <th>Bathroom Type</th>
                <th>Bathroom Size (sqm)</th>
                <th class="no-print">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($properties as $property): ?>
                <tr>
                    <td><?= htmlspecialchars($property['site_name']) ?></td>
                    <td><?= htmlspecialchars($property['flat_number']) ?></td>
                    <td><?= htmlspecialchars($property['unit_type']) ?></td>
                    <td><?= htmlspecialchars($property['floor_level']) ?></td>
                    <td><?= htmlspecialchars($property['bedspaces_ppar']) ?></td>
                    <td><?= htmlspecialchars($property['current_bedspaces']) ?></td>
                    <td><?= htmlspecialchars($property['kitchen_lounge_type']) ?></td>
                    <td><?= htmlspecialchars($property['kitchen_lounge_size']) ?></td>
                    <td><?= htmlspecialchars($property['bathroom_type']) ?></td>
                    <td><?= htmlspecialchars($property['bathroom_size']) ?></td>
                    <td class="no-print">
                        <div class="d-flex gap-2">
                            <a href="view_property.php?id=<?= $property['id'] ?>" class="btn btn-info btn-sm">View</a>
                            <button type="button" class="btn btn-warning btn-sm" onclick='showUpdateModal(<?= json_encode($property) ?>)'>Update</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="showDeleteModal(<?= $property['id'] ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination Controls -->
    <div class="pagination-control">
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-secondary btn-sm <?= ($page == 1) ? 'disabled' : '' ?>">First</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-secondary btn-sm <?= ($page == 1) ? 'disabled' : '' ?>">Previous</a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn btn-primary btn-sm <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-secondary btn-sm <?= ($page == $total_pages) ? 'disabled' : '' ?>">Next</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="btn btn-secondary btn-sm <?= ($page == $total_pages) ? 'disabled' : '' ?>">Last</a>
    </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="manage_properties.php">
      <input type="hidden" name="update_property_id" id="update_property_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateModalLabel">Update Property</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Update form fields -->
          <div class="mb-3">
            <label class="form-label">Flat Number</label>
            <input type="text" class="form-control" name="flat_number" id="flat_number" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Property Name</label>
            <input type="text" class="form-control" name="property_name" id="property_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address" id="address" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Property Type</label>
            <select class="form-select" name="property_type" id="property_type" required>
              <option value="House">House</option>
              <option value="Apartment">Apartment</option>
              <option value="Studio">Studio</option>
              <option value="Dormitory">Dormitory</option>
              <option value="Commercial">Commercial</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Total Floors</label>
            <input type="number" class="form-control" name="total_floors" id="total_floors" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Total Units</label>
            <input type="number" class="form-control" name="total_units" id="total_units" required>
          </div>
          <!-- New Fields -->
          <div class="mb-3">
            <label class="form-label">Unit Type</label>
            <input type="text" class="form-control" name="unit_type" id="unit_type" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Floor Level</label>
            <input type="number" class="form-control" name="floor_level" id="floor_level" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Bedspaces (PPAR)</label>
            <input type="number" class="form-control" name="bedspaces_ppar" id="bedspaces_ppar" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Kitchen/Lounge Type</label>
            <select class="form-select" name="kitchen_lounge_type" id="kitchen_lounge_type" required>
              <option value="Shared">Shared</option>
              <option value="Private">Private</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Kitchen/Lounge Size (sqm)</label>
            <input type="number" step="0.01" class="form-control" name="kitchen_lounge_size" id="kitchen_lounge_size" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Bathroom Type</label>
            <select class="form-select" name="bathroom_type" id="bathroom_type" required>
              <option value="Shared">Shared</option>
              <option value="Private">Private</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Bathroom Size (sqm)</label>
            <input type="number" step="0.01" class="form-control" name="bathroom_size" id="bathroom_size" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="manage_properties.php">
      <input type="hidden" name="delete_property_id" id="delete_property_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel">Delete Property</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this property? This action is irreversible.</p>
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
    function showUpdateModal(property) {
        document.getElementById('update_property_id').value = property.id;
        document.getElementById('flat_number').value = property.flat_number;
        document.getElementById('property_name').value = property.property_name;
        document.getElementById('address').value = property.address;
        document.getElementById('property_type').value = property.property_type;
        document.getElementById('total_floors').value = property.total_floors;
        document.getElementById('total_units').value = property.total_units;
        // New fields:
        document.getElementById('unit_type').value = property.unit_type;
        document.getElementById('floor_level').value = property.floor_level;
        document.getElementById('bedspaces_ppar').value = property.bedspaces_ppar;
        document.getElementById('kitchen_lounge_type').value = property.kitchen_lounge_type;
        document.getElementById('kitchen_lounge_size').value = property.kitchen_lounge_size;
        document.getElementById('bathroom_type').value = property.bathroom_type;
        document.getElementById('bathroom_size').value = property.bathroom_size;
        new bootstrap.Modal(document.getElementById('updateModal')).show();
    }

    function showDeleteModal(propertyId) {
        document.getElementById('delete_property_id').value = propertyId;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
