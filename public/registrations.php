<?php
header('Content-Type: application/json');

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
$event_name  = strip_tags($_POST['event_name'] ?? '');
$category    = strip_tags($_POST['category'] ?? 'General');
$team_name   = strip_tags($_POST['team_name'] ?? 'Solo');
$lead_name   = strip_tags($_POST['lead_name'] ?? '');
$lead_phone  = strip_tags($_POST['lead_phone'] ?? '');
$lead_email  = filter_var($_POST['lead_email'] ?? '', FILTER_SANITIZE_EMAIL);
$college     = strip_tags($_POST['college'] ?? 'Sanjivani College of Engineering');
$utr         = strip_tags($_POST['utr'] ?? '');

// Handle dynamic members calculation
$members = isset($_POST['member_names']) && is_array($_POST['member_names']) ? $_POST['member_names'] : [];$participant_count = count($members) + 1; // Lead + Team Members
$total_fee = $participant_count * 199;    // 199 INR per head
$members_string = implode(', ', array_map('strip_tags', $members));

// 3. Basic Validation
if (empty($event_name) || empty($lead_name) || empty($lead_phone)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Transmission error: Required fields are missing.'
    ]);
    exit;
}

try {
    // 4. Prepare and Execute SQL
    // Removed transaction_proof from columns and values
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
                :college, :members,
                :fee,
                :utr,
                'Pending',
                NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':event_name' => $event_name,
        ':category'   => $category,
        ':team_name'  =>  $team_name,
        ':lead_name'  => $lead_name,
        ':lead_phone' => $lead_phone,
        ':lead_email' => $lead_email,
        ':college'    => $college,
        ':members'    => $members_string,
        ':fee'        => $total_fee,
        ':utr'        => $utr
    ]);

    // 5. Success Response
    echo json_encode([
        'status' => 'success',
        'message' => 'Registration for ' . htmlspecialchars($event_name) . ' successful! UTR ' . htmlspecialchars($utr) . ' submitted for verification.',
        'fee_calculated' => $total_fee
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
?>
