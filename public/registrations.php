<?php
/**
 * registrations.php - Backend for Sanjivani Techfest 2.0
 * Verified and Synced with MariaDB [techfest_db] schema.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Database Configuration
$host     = 'localhost';
$db_name  = 'techfest_db';
$username = 'techfest_user';
$password = 'StrongPassword123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database Connection Failed'
    ]);
    exit;
}

// 2. Handle Input (Supports JSON Fetch from Frontend)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Extracting keys from the frontend 'registrationData' object
$event_name = strip_tags($input['eventName'] ?? '');
$lead_name  = strip_tags($input['leadName'] ?? '');
$lead_phone = strip_tags($input['leadPhone'] ?? '');
// Defaults to DB default if empty
$team_name  = !empty($input['teamName']) ? strip_tags($input['teamName']) : 'Solo Participation';
$members    = $input['members'] ?? [];

// 3. Calculation & Formatting
// total_fee is decimal(10,2) in DB
$participant_count = count($members) + 1;
$total_fee = (float)($participant_count * 199.00);

// additional_members is text in DB
$members_string = !empty($members) ? implode(', ', array_map('strip_tags', $members)) : null;

// 4. Validation (Fields marked 'NO' for Null in DB)
if (empty($event_name) || empty($lead_name) || empty($lead_phone)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Mandatory fields missing: Event Name, Lead Name, or Phone.'
    ]);
    exit;
}

try {
    // 5. Execute SQL
    // We omit 'id' (auto_increment), 'payment_status' (default: Pending),
    // and 'registration_date' (default: current_timestamp) to use DB defaults.
    $sql = "INSERT INTO registrations (
                event_name,
                team_name,
                lead_name,
                lead_phone,
                additional_members,
                total_fee
            ) VALUES (
                :event_name,
                :team_name,
                :lead_name,
                :lead_phone,
                :additional_members,
                :total_fee
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':event_name'         => $event_name,
        ':team_name'          => $team_name,
        ':lead_name'          => $lead_name,
        ':lead_phone'         => $lead_phone,
        ':additional_members' => $members_string,
        ':total_fee'          => $total_fee
    ]);

    // 6. Response
    echo json_encode([
        'status'  => 'success',
        'message' => 'Registration successful for ' . htmlspecialchars($lead_name),
        'details' => [
            'event' => $event_name,
            'fee'   => number_format($total_fee, 2),
            'id'    => $pdo->lastInsertId()
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
?>
