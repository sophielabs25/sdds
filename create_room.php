<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = $_POST['room_number'];
    $room_name = $_POST['room_name'];
    $property_name = $_POST['property_name'];
    $site_name = $_POST['site_name'];
    $building_name = $_POST['building_name'];
    $floor_number = $_POST['floor_number'];
    $flat_number = $_POST['flat_number'];
    $room_type = $_POST['room_type'];
    $room_size = $_POST['room_size'];
    $occupancy_limit = $_POST['occupancy_limit'];
    $current_occupancy = $_POST['current_occupancy'];
    $availability_status = $_POST['availability_status'];
    $furnished_status = $_POST['furnished_status'];
    $rental_price = $_POST['rental_price'];
    $deposit_required = $_POST['deposit_required'];
    $deposit_amount = $_POST['deposit_amount'];
    $number_of_beds = $_POST['number_of_beds'];
    $bathroom_type = $_POST['bathroom_type'];
    $amenities = $_POST['amenities'];
    $room_condition = $_POST['room_condition'];
    $heating_cooling = $_POST['heating_cooling'];
    $internet_access = $_POST['internet_access'];
    $electricity_meter_number = $_POST['electricity_meter_number'];
    $water_meter_number = $_POST['water_meter_number'];
    $parking_availability = $_POST['parking_availability'];
    $smoke_detector_installed = $_POST['smoke_detector_installed'];
    $fire_extinguisher_present = $_POST['fire_extinguisher_present'];
    $last_maintenance_date = $_POST['last_maintenance_date'];
    $next_maintenance_due = $_POST['next_maintenance_due'];
    $room_status_notes = $_POST['room_status_notes'];

    try {
        $stmt = $conn->prepare("INSERT INTO rooms (
            room_number, room_name, property_name, site_name, building_name, floor_number, flat_number, 
            room_type, room_size, occupancy_limit, current_occupancy, availability_status, furnished_status, 
            rental_price, deposit_required, deposit_amount, number_of_beds, bathroom_type, amenities, 
            room_condition, heating_cooling, internet_access, electricity_meter_number, water_meter_number, 
            parking_availability, smoke_detector_installed, fire_extinguisher_present, last_maintenance_date, 
            next_maintenance_due, room_status_notes
        ) VALUES (
            :room_number, :room_name, :property_name, :site_name, :building_name, :floor_number, :flat_number, 
            :room_type, :room_size, :occupancy_limit, :current_occupancy, :availability_status, :furnished_status, 
            :rental_price, :deposit_required, :deposit_amount, :number_of_beds, :bathroom_type, :amenities, 
            :room_condition, :heating_cooling, :internet_access, :electricity_meter_number, :water_meter_number, 
            :parking_availability, :smoke_detector_installed, :fire_extinguisher_present, :last_maintenance_date, 
            :next_maintenance_due, :room_status_notes
        )");
        $stmt->execute([
            ':room_number' => $room_number,
            ':room_name' => $room_name,
            ':property_name' => $property_name,
            ':site_name' => $site_name,
            ':building_name' => $building_name,
            ':floor_number' => $floor_number,
            ':flat_number' => $flat_number,
            ':room_type' => $room_type,
            ':room_size' => $room_size,
            ':occupancy_limit' => $occupancy_limit,
            ':current_occupancy' => $current_occupancy,
            ':availability_status' => $availability_status,
            ':furnished_status' => $furnished_status,
            ':rental_price' => $rental_price,
            ':deposit_required' => $deposit_required,
            ':deposit_amount' => $deposit_amount,
            ':number_of_beds' => $number_of_beds,
            ':bathroom_type' => $bathroom_type,
            ':amenities' => $amenities,
            ':room_condition' => $room_condition,
            ':heating_cooling' => $heating_cooling,
            ':internet_access' => $internet_access,
            ':electricity_meter_number' => $electricity_meter_number,
            ':water_meter_number' => $water_meter_number,
            ':parking_availability' => $parking_availability,
            ':smoke_detector_installed' => $smoke_detector_installed,
            ':fire_extinguisher_present' => $fire_extinguisher_present,
            ':last_maintenance_date' => $last_maintenance_date,
            ':next_maintenance_due' => $next_maintenance_due,
            ':room_status_notes' => $room_status_notes
        ]);
        echo "<script>alert('Room added successfully!'); window.location.href='manage_rooms.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error adding room: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Room</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Add Room</h2>
        <form method="POST">
            <input type="text" name="room_number" placeholder="Room Number" required class="form-control mt-2">
            <input type="text" name="room_name" placeholder="Room Name" class="form-control mt-2">
            <input type="text" name="property_name" placeholder="Property Name" required class="form-control mt-2">
            <input type="text" name="site_name" placeholder="Site Name" required class="form-control mt-2">
            <input type="text" name="building_name" placeholder="Building Name" class="form-control mt-2">
            <input type="number" name="floor_number" placeholder="Floor Number" class="form-control mt-2">
            <input type="text" name="flat_number" placeholder="Flat Number" class="form-control mt-2">
            <select name="room_type" class="form-select mt-2">
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Family">Family</option>
                <option value="Dormitory">Dormitory</option>
                <option value="Studio">Studio</option>
            </select>
            <input type="number" name="occupancy_limit" placeholder="Occupancy Limit" required class="form-control mt-2">
            <input type="text" name="availability_status" placeholder="Availability Status" required class="form-control mt-2">
            <select name="furnished_status" class="form-select mt-2">
                <option value="Yes">Furnished</option>
                <option value="No">Not Furnished</option>
            </select>
            <input type="number" name="rental_price" placeholder="Rental Price" class="form-control mt-2">
            <input type="number" name="deposit_amount" placeholder="Deposit Amount" class="form-control mt-2">
            <input type="number" name="number_of_beds" placeholder="Number of Beds" class="form-control mt-2">
            <textarea name="amenities" placeholder="Amenities" class="form-control mt-2"></textarea>
            <button type="submit" class="btn btn-primary mt-3">Add Room</button>
        </form>
    </div>
</body>
</html>
