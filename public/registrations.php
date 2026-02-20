<?php
/**
 * Techfest 2.0 - Registration Handler
 * This script processes incoming registration POST requests and saves them to the database.
 */

header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$db_name = 'techfest_db';
$username = 'techfest_user';
$password = 'StrongPassword123';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Set error mode to exception to catch any database issues
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get form data with null coalescing for safety
$event_name = $_POST['event_name'] ?? '';
$category = $_POST['category'] ?? 'General';
$team_name = $_POST['team_name'] ?? 'Solo';
$lead_name = $_POST['lead_name'] ?? '';
$lead_phone = $_POST['lead_phone'] ?? '';
$lead_email = $_POST['lead_email'] ?? '';
$college = $_POST['college'] ?? '';

// Handle dynamic members calculation
// Expecting 'member_names' to be an array from the frontend
$members = isset($_POST['member_names']) && is_array($_POST['member_names']) ? $_POST['member_names'] : [];
$participant_count = count($members) + 1; // Lead student + additional members
$total_fee = $participant_count * 199;    // Fee calculation: 199 INR per head
$members_string = implode(', ', $members);

// Basic Server-side Validation
if (empty($event_name) || empty($lead_name) || empty($lead_phone)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Missing required fields. Please ensure Event Name, Name, and Phone are provided.'
    ]);
    exit;
}

try {
    // Prepare the SQL statement
    // Note: Ensure your 'registrations' table matches these column names
    $sql = "INSERT INTO registrations (
                event_name, 
                category, 
                team_name, 
                lead_name, 
                lead_phone, 
                lead_email, 
                college, 
                additional_members, 
                total_fee,
                created_at
            ) VALUES (
                :event_name, 
                :category, 
                :team_name, 
                :lead_name, 
                :lead_phone, 
                :lead_email, 
                :college, 
                :members, 
                :fee,
                NOW()
            )";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute with mapped parameters
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

    // Send successful response to the frontend AJAX call
    echo json_encode([
        'status' => 'success', 
        'message' => 'Registration for ' . htmlspecialchars($event_name) . ' saved successfully!',
        'total_payable' => $total_fee
    ]);

} catch (PDOException $e) {
    // Check for specific errors like duplicate entries if you have unique constraints
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
