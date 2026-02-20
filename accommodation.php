<?php
/**
 * accommodation.php - Backend for Techfest 2.0 Stay Arrangements
 * Database: techfest_db
 * Table: stay_requests 
 * (Expected columns: id, full_name, college_name, gender, number_of_nights, check_in_date, phone_number, special_requirements, created_at)
 */

ob_start();
header('Content-Type: application/json');

// 1. Database Configuration (Using credentials from register.php)
$host = 'localhost';
$db_name = 'techfest_db';
$username = 'techfest_user';
$password = 'StrongPassword123';

try {
    // 2. Establish Database Connection
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Process the POST Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Sanitize and validate inputs
        $full_name      = isset($_POST['full_name']) ? strip_tags(trim($_POST['full_name'])) : '';
        $college_name   = isset($_POST['college_name']) ? strip_tags(trim($_POST['college_name'])) : '';
        $gender         = isset($_POST['gender']) ? strip_tags(trim($_POST['gender'])) : '';
        $nights         = isset($_POST['number_of_nights']) ? (int)$_POST['number_of_nights'] : 0;
        $check_in       = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
        $phone          = isset($_POST['phone_number']) ? strip_tags(trim($_POST['phone_number'])) : '';
        $requirements   = isset($_POST['special_requirements']) ? strip_tags(trim($_POST['special_requirements'])) : '';

        // Basic Validation
        if (empty($full_name) || empty($college_name) || empty($gender) || empty($nights) || empty($check_in) || empty($phone)) {
            ob_clean();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Submission failed: All mandatory fields must be completed.'
            ]);
            exit;
        }

        // 4. Prepare SQL Statement
        // Ensure you have created a table named 'stay_requests'
        $sql = "INSERT INTO `stay_requests` (
                    full_name, 
                    college_name, 
                    gender, 
                    number_of_nights, 
                    check_in_date, 
                    phone_number, 
                    special_requirements,
                    created_at
                ) VALUES (
                    :full_name, 
                    :college_name, 
                    :gender, 
                    :nights, 
                    :check_in, 
                    :phone, 
                    :requirements,
                    NOW()
                )";
        
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':college_name', $college_name);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':nights', $nights, PDO::PARAM_INT);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':requirements', $requirements);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode([
                'status' => 'success', 
                'message' => 'Stay request received! Our hospitality team will contact you shortly via WhatsApp.'
            ]);
        } else {
            ob_clean();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Critical database error. Please try again later.'
            ]);
        }

    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
