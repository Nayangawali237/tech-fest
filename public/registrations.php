<?php
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$db_name = 'techfest_db';
$username = 'root';
$password = ''; // Default for XAMPP is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

// Get form data
// Mapping event_name to the database column to fix the Column Not Found error
$event_name = $_POST['event_name'] ?? '';
$category = $_POST['category'] ?? 'General'; // From the registration form
$team_name = $_POST['team_name'] ?? 'Solo';
$lead_name = $_POST['lead_name'] ?? '';
$lead_phone = $_POST['lead_phone'] ?? '';
$lead_email = $_POST['lead_email'] ?? '';
$college = $_POST['college'] ?? '';

// Handle dynamic members and calculation
$members = isset($_POST['member_names']) ? $_POST['member_names'] : [];
$participant_count = count($members) + 1; // Lead + Team Members
$total_fee = $participant_count * 199; // Every participant pays 199
$members_string = implode(', ', $members);

// Basic Validation
if (empty($event_name) || empty($lead_name) || empty($lead_phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

try {
    // SQL matches your database screenshot + the additional fields we added
    $sql = "INSERT INTO registrations (event_name, category, team_name, lead_name, lead_phone, lead_email, college, additional_members, total_fee) 
            VALUES (:event_name, :category, :team_name, :lead_name, :lead_phone, :lead_email, :college, :members, :fee)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':event_name' => $event_name,
        ':category'   => $category,
        ':team_name'  => $team_name,
        ':lead_name'  => $lead_name,
        ':lead_phone' => $lead_phone,
        ':lead_email' => $lead_email,
        ':college'    => $college,
        ':members'    => $members_string,
        ':fee'        => $total_fee
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Registration saved successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
