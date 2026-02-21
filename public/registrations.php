<?php
/**
 * registrations.php - Backend for Techfest 2.0 Public Registration
 * Processes form submissions and saves them to the AWS EC2 MariaDB.
 * Integrated with the modern registrations.html UI.
 */
header('Content-Type: application/json');

// 1. Database Configuration
// Adjust these if your EC2 environment uses different credentials
$host     = 'localhost'; 
$db_name  = 'techfest_db';
$username = 'techfest_user';
$password = 'StrongPassword123';

try {
    // Establishing secure connection using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Cloud Database Connection Failed: ' . $e->getMessage()
    ]);
    exit;
}

// 2. Extract and Sanitize POST Data
$event_name  = strip_tags($_POST['event_name'] ?? '');
$category    = strip_tags($_POST['category'] ?? 'General');
$team_name   = strip_tags($_POST['team_name'] ?? 'Solo');
$lead_name   = strip_tags($_POST['lead_name'] ?? '');
$lead_phone  = strip_tags($_POST['lead_phone'] ?? '');
$lead_email  = filter_var($_POST['lead_email'] ?? '', FILTER_SANITIZE_EMAIL);
$college     = strip_tags($_POST['college'] ?? 'Sanjivani College of Engineering');
$utr         = strtoupper(trim(strip_tags($_POST['utr'] ?? '')));

// 3. Dynamic Fee Calculation Logic
// Base fee is 199 INR per participant.
$members = isset($_POST['member_names']) && is_array($_POST['member_names']) ? $_POST['member_names'] : [];
$participant_count = count($members) + 1; // 1 (Lead) + N (Additional Members)
$total_fee = $participant_count * 199;
$members_string = implode(', ', array_map('strip_tags', $members));

// 4. Basic Validation
if (empty($event_name) || empty($lead_name) || empty($lead_phone)) {
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Validation error: Event, Name, and Phone are required.'
    ]);
    exit;
}

if (empty($utr)) {
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Payment error: Transaction UTR is required to secure your slot.'
    ]);
    exit;
}

try {
    // 5. Prepare and Execute SQL Insertion
    // Note: 'payment_status' defaults to 'Pending' for admin review.
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
                utr,
                payment_status,
                registration_date
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
                :utr,
                'Pending',
                NOW()
            )";
    
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
        ':fee'        => $total_fee,
        ':utr'        => $utr
    ]);

    // 6. Success Response
    echo json_encode([
        'status'         => 'success', 
        'message'        => 'Registration for ' . htmlspecialchars($event_name) . ' submitted! UTR: ' . htmlspecialchars($utr) . ' is now under verification.',
        'fee_calculated' => $total_fee,
        'team_size'      => $participant_count
    ]);

} catch (PDOException $e) {
    // Handle Duplicate UTR if you have a UNIQUE constraint on that column
    if ($e->getCode() == 23000) {
        echo json_encode([
            'status'  => 'error', 
            'message' => 'This UTR has already been used for another registration.'
        ]);
    } else {
        echo json_encode([
            'status'  => 'error', 
            'message' => 'Database operation failed: ' . $e->getMessage()
        ]);
    }
}
?>
