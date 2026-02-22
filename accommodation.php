<?php
/**
 * accommodation.php - Backend for Techfest 2.0 Stay Arrangements
 * Database: techfest_db
 * Table: stay_requests 
 * Schema: id, full_name, college_name, gender, number_of_nights, check_in_date, phone_number, special_requirements, submitted_at
 */

// Error reporting helps identify why entries aren't saving
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
header('Content-Type: application/json');

// 1. Database Configuration
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
        
        // Extract and Sanitize Inputs
        $full_name      = isset($_POST['full_name']) ? strip_tags(trim($_POST['full_name'])) : '';
        $college_name   = isset($_POST['college_name']) ? strip_tags(trim($_POST['college_name'])) : '';
        $gender         = isset($_POST['gender']) ? strip_tags(trim($_POST['gender'])) : '';
        $nights         = isset($_POST['number_of_nights']) ? (int)$_POST['number_of_nights'] : 0;
        $check_in       = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
        $phone          = isset($_POST['phone_number']) ? strip_tags(trim($_POST['phone_number'])) : '';
        $requirements   = isset($_POST['special_requirements']) ? strip_tags(trim($_POST['special_requirements'])) : '';

        // Server-side validation
        $errors = [];
        if (empty($full_name)) $errors[] = "Full Name is required.";
        if (empty($college_name)) $errors[] = "College Name is required.";
        if (empty($gender)) $errors[] = "Gender is required.";
        if ($nights <= 0) $errors[] = "Minimum 1 night required.";
        if (empty($check_in)) $errors[] = "Check-in date is required.";
        if (empty($phone)) $errors[] = "Phone number is required.";
        if (strlen($requirements) < 6) $errors[] = "Payment UTR is required in the requirements box.";

        if (!empty($errors)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
            exit;
        }

        // 4. Prepare SQL Statement
        // Column 'submitted_at' matches your MariaDB terminal output exactly
        $sql = "INSERT INTO `stay_requests` (
                    full_name, 
                    college_name, 
                    gender, 
                    number_of_nights, 
                    check_in_date, 
                    phone_number, 
                    special_requirements,
                    submitted_at
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
                'message' => 'Stay request received! Our hospitality team will verify your UTR and contact you shortly.'
            ]);
        } else {
            $err = $stmt->errorInfo();
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $err[2]]);
        }

    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method.']);
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()]);
}
?>
