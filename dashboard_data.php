<?php
session_start();

// Check user role or site_name
$site_name = $_SESSION['site_name'] ?? '';
$isAllAccess = ($site_name === 'level2' || $site_name === 'Superadmin');

// Database connection
$host = 'localhost';
$dbname = 'user_database';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Database Error: " . $e->getMessage()]));
}

// 1) SU Data (sudata)
if ($isAllAccess) {
    $sql_su = "SELECT 
                 COUNT(*) AS total_sus,
                 SUM(CASE WHEN gender='Male' THEN 1 ELSE 0 END) AS males,
                 SUM(CASE WHEN gender='Female' THEN 1 ELSE 0 END) AS females,
                 SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 1 ELSE 0 END) AS minors
               FROM sudata";
    $stmt = $conn->prepare($sql_su);
} else {
    $sql_su = "SELECT 
                 COUNT(*) AS total_sus,
                 SUM(CASE WHEN gender='Male' THEN 1 ELSE 0 END) AS males,
                 SUM(CASE WHEN gender='Female' THEN 1 ELSE 0 END) AS females,
                 SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 1 ELSE 0 END) AS minors
               FROM sudata
               WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_su);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$suData = $stmt->fetch(PDO::FETCH_ASSOC);
$total_sus = (int)($suData['total_sus'] ?? 0);
$males = (int)($suData['males'] ?? 0);
$females = (int)($suData['females'] ?? 0);
$minors = (int)($suData['minors'] ?? 0);
$adults = $total_sus - $minors;

if ($isAllAccess) {
    $sql_incidents = "SELECT 
                        COUNT(*) AS total_incidents,
                        SUM(CASE WHEN status='Open' THEN 1 ELSE 0 END) AS open_incidents,
                        SUM(CASE WHEN status='Close' THEN 1 ELSE 0 END) AS closed_incidents
                      FROM incident_reports";
    $stmt = $conn->prepare($sql_incidents);
} else {
    $sql_incidents = "SELECT 
                        COUNT(*) AS total_incidents,
                        SUM(CASE WHEN status='Open' THEN 1 ELSE 0 END) AS open_incidents,
                        SUM(CASE WHEN status='Close' THEN 1 ELSE 0 END) AS closed_incidents
                      FROM incident_reports
                      WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_incidents);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$incidents = $stmt->fetch(PDO::FETCH_ASSOC);
$total_incidents = (int)($incidents['total_incidents'] ?? 0);
$open_incidents = (int)($incidents['open_incidents'] ?? 0);
$closed_incidents = (int)($incidents['closed_incidents'] ?? 0);

// 3) Complaints
if ($isAllAccess) {
    $sql_complaints = "SELECT 
                         COUNT(*) AS total_complaints,
                         SUM(CASE WHEN status='Open' THEN 1 ELSE 0 END) AS open_complaints,
                         SUM(CASE WHEN status='Close' THEN 1 ELSE 0 END) AS closed_complaints
                       FROM complaints";
    $stmt = $conn->prepare($sql_complaints);
} else {
    $sql_complaints = "SELECT 
                         COUNT(*) AS total_complaints,
                         SUM(CASE WHEN status='Open' THEN 1 ELSE 0 END) AS open_complaints,
                         SUM(CASE WHEN status='Close' THEN 1 ELSE 0 END) AS closed_complaints
                       FROM complaints
                       WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_complaints);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$complaints = $stmt->fetch(PDO::FETCH_ASSOC);
$total_complaints = (int)($complaints['total_complaints'] ?? 0);
$open_complaints = (int)($complaints['open_complaints'] ?? 0);
$closed_complaints = (int)($complaints['closed_complaints'] ?? 0);

// 4) Safeguarding (referrals + vulnerable_sus)
if ($isAllAccess) {
    $sql_referrals = "SELECT COUNT(*) AS total_referrals FROM safeguarding_referrals";
    $stmt = $conn->prepare($sql_referrals);
} else {
    $sql_referrals = "SELECT COUNT(*) AS total_referrals FROM safeguarding_referrals
                      WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_referrals);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$referrals_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_referrals = (int)($referrals_data['total_referrals'] ?? 0);

if ($isAllAccess) {
    $sql_vul = "SELECT COUNT(*) AS total_vulnerable FROM vulnerable_sus";
    $stmt = $conn->prepare($sql_vul);
} else {
    $sql_vul = "SELECT COUNT(*) AS total_vulnerable FROM vulnerable_sus
                WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_vul);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$vul_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_vulnerable = (int)($vul_data['total_vulnerable'] ?? 0);

// 5) Moveout
if ($isAllAccess) {
    $sql_moveout = "SELECT COUNT(*) AS total_moveout FROM moveout";
    $stmt = $conn->prepare($sql_moveout);
} else {
    $sql_moveout = "SELECT COUNT(*) AS total_moveout FROM moveout
                    WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_moveout);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$moveout_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_moveout = (int)($moveout_data['total_moveout'] ?? 0);

// 6) Properties, Rooms, Beds, property_images, documents, etc. (same pattern)...

// Example for documents
if ($isAllAccess) {
    $sql_docs = "SELECT COUNT(*) AS total_docs FROM documents";
    $stmt = $conn->prepare($sql_docs);
} else {
    $sql_docs = "SELECT COUNT(*) AS total_docs FROM documents WHERE site_name = :site_name";
    $stmt = $conn->prepare($sql_docs);
    $stmt->bindValue(':site_name', $site_name);
}
$stmt->execute();
$docs_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_docs = (int)($docs_data['total_docs'] ?? 0);

// Return JSON
header('Content-Type: application/json');
echo json_encode([
    'total_sus' => $total_sus,
    'males' => $males,
    'females' => $females,
    'minors' => $minors,
    'adults' => $adults,

    'total_incidents' => $total_incidents,
    'open_incidents' => $open_incidents,
    'closed_incidents' => $closed_incidents,

    'total_complaints' => $total_complaints,
    'open_complaints' => $open_complaints,
    'closed_complaints' => $closed_complaints,

    'total_referrals' => $total_referrals,
    'total_vulnerable' => $total_vulnerable,

    'total_moveout' => $total_moveout,
    'total_docs' => $total_docs,

    // Add more as needed...
]);
