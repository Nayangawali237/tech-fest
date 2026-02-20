<?php
/**
 * registrations.php - Backend for Techfest 2.0 Public Registration
 * Processes form submissions and saves them to the AWS EC2 MariaDB.
 */

header('Content-Type: application/json');

// 1. Database Configuration
$host = 'localhost'; // MariaDB on EC2
$db_name = 'techfest_db';
$username = 'techfest_user';
$password = 'StrongPassword123';

try {
    // Establishing secure connection
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
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
$college     = strip_tags($_POST['college'] ?? '');

// Handle dynamic members calculation
$members = isset($_POST['member_names']) && is_array($_POST['member_names']) ? $_POST['member_names'] : [];
$participant_count = count($members) + 1; // Lead + Team Members
$total_fee = $participant_count * 199;    // 199 INR per head
$members_string = implode(', ', array_map('strip_tags', $members));

// 3. Handle File Upload (Transaction Proof)
// Admin Dashboard expects this file to be in the 'uploads/' directory
$proof_filename = ''; 
if (isset($_FILES['transaction_proof']) && $_FILES['transaction_proof']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    
    // Create directory if it doesn't exist on EC2
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['transaction_proof']['name'], PATHINFO_EXTENSION));
    $new_name = "proof_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
    $target_path = $upload_dir . $new_name;

    if (move_uploaded_file($_FILES['transaction_proof']['tmp_name'], $target_path)) {
        $proof_filename = $new_name;
    }
}

// 4. Basic Validation
if (empty($event_name) || empty($lead_name) || empty($lead_phone)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Transmission error: Required fields are missing.'
    ]);
    exit;
}

try {
    // 5. Prepare and Execute SQL
    // Note: payment_status defaults to 'Pending'
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
                transaction_proof,
                payment_status,
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
                :proof,
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
        ':proof'      => $proof_filename
    ]);

    // 6. Success Response
    echo json_encode([
        'status' => 'success', 
        'message' => 'Registration for ' . htmlspecialchars($event_name) . ' successful! Please wait for admin verification.',
        'fee_calculated' => $total_fee
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
?>
