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
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

// Validate and sanitize POST inputs
$type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';
$site = isset($_POST['site']) ? htmlspecialchars($_POST['site']) : 'all';

$site_condition = ($site !== 'all') ? "WHERE site_name = :site" : "";

switch ($type) {
    case 'complaints':
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_complaints,
            SUM(CASE WHEN status = 'Close' THEN 1 ELSE 0 END) as closed_complaints
            FROM complaints $site_condition";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return list-group style
        echo '<ul class="list-group">';
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Total Complaints';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Open';
        echo '<span class="badge bg-danger rounded-pill">' . htmlspecialchars($data['open_complaints']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Closed';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['closed_complaints']) . '</span>';
        echo '</li>';
        echo '</ul>';
        break;

    case 'incidents':
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_incidents,
            SUM(CASE WHEN status = 'Close' THEN 1 ELSE 0 END) as closed_incidents
            FROM incident_reports $site_condition";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<ul class="list-group">';
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Total Incidents';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Open';
        echo '<span class="badge bg-danger rounded-pill">' . htmlspecialchars($data['open_incidents']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Closed';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['closed_incidents']) . '</span>';
        echo '</li>';
        echo '</ul>';
        break;

    case 'safeguarding':
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_cases,
            SUM(CASE WHEN status = 'Close' THEN 1 ELSE 0 END) as closed_cases
            FROM safeguarding_referrals $site_condition";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<ul class="list-group">';
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Total Referrals';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Open';
        echo '<span class="badge bg-danger rounded-pill">' . htmlspecialchars($data['open_cases']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Closed';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['closed_cases']) . '</span>';
        echo '</li>';
        echo '</ul>';
        break;

    case 'properties':
        $query = "SELECT 
            COUNT(DISTINCT flat_number) as total_flats,
            COUNT(DISTINCT site_name) as total_sites
            FROM sudata $site_condition";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<ul class="list-group">';
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Total Flats';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total_flats']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Total Sites';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total_sites']) . '</span>';
        echo '</li>';
        echo '</ul>';
        break;

    case 'serviceusers':
        $query = "SELECT 
            COUNT(*) as total_sus,
            COUNT(DISTINCT country) as total_countries
            FROM sudata $site_condition";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        echo '<ul class="list-group">';
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Total Service Users';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total_sus']) . '</span>';
        echo '</li>';

        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
        echo 'Countries Represented';
        echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($data['total_countries']) . '</span>';
        echo '</li>';
        echo '</ul>';
        break;

    case 'agebreakdown':
        // Already returns a list-group approach
        $query = "SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 1 THEN 'Under 1'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 1 AND 12 THEN '1 to 12'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 13 AND 17 THEN '13 to 17'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 24 THEN '18 to 24'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 25 AND 54 THEN '25 to 54'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 55 AND 64 THEN '55 to 64'
                ELSE '65+' 
            END AS age_group,
            COUNT(*) AS total_people
            FROM sudata";
        if ($site !== 'all') {
            $query .= " WHERE site_name = :site";
        }
        $query .= " GROUP BY age_group
                    ORDER BY FIELD(age_group, 'Under 1', '1 to 12', '13 to 17', '18 to 24', '25 to 54', '55 to 64', '65+')";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $output = '<ul class="list-group">';
        foreach ($results as $row) {
            $output .= '<li class="list-group-item d-flex justify-content-between align-items-center">';
            $output .= htmlspecialchars($row['age_group']);
            $output .= '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($row['total_people']) . '</span>';
            $output .= '</li>';
        }
        $output .= '</ul>';
        echo $output;
        break;

    case 'familymakeup':
        $query = "SELECT 
            su_makeup,
            COUNT(*) as total
            FROM sudata
            $site_condition
            GROUP BY su_makeup
            ORDER BY CASE 
                WHEN su_makeup = 'SM' THEN 1
                WHEN su_makeup = 'SF' THEN 2
                WHEN su_makeup = 'Fam x2' THEN 3
                WHEN su_makeup = 'Fam x3' THEN 4
                WHEN su_makeup = 'Fam x4' THEN 5
                WHEN su_makeup = 'Fam x5' THEN 6
                WHEN su_makeup = 'Fam x6' THEN 7
                ELSE 8
            END";
        $stmt = $conn->prepare($query);
        if ($site !== 'all') {
            $stmt->bindParam(':site', $site, PDO::PARAM_STR);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return as list-group approach:
        echo '<ul class="list-group">';
        foreach ($results as $row) {
            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
            echo htmlspecialchars($row['su_makeup']);
            echo '<span class="badge bg-primary rounded-pill">' . htmlspecialchars($row['total']) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
        break;

    default:
        echo "Invalid request type.";
        break;
}


$conn = null;
?>
