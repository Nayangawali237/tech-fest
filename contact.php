<?php
/**
 * contact.php - Backend for Techfest 2.0 Contact Form
 * Database: techfest_db
 * Table: queries (columns: id, name, email, subject, message, created_at)
 */

// Ensure clean JSON output by preventing stray whitespace or warnings
ob_start();
header('Content-Type: application/json');

// 1. Database Configuration (Default for XAMPP/WAMP)
$host = 'localhost';
$db_name = 'techfest_db';
$username = 'techfest_user';
$password = 'StrongPassword123';

try {
    // 2. Establish Database Connection using PDO
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Process the POST Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Sanitize and validate inputs
        $name    = isset($_POST['name'])    ? strip_tags(trim($_POST['name'])) : '';
        $email   = isset($_POST['email'])   ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
        $subject = isset($_POST['subject']) ? strip_tags(trim($_POST['subject'])) : '';
        $message = isset($_POST['message']) ? strip_tags(trim($_POST['message'])) : '';

        // Validation Check
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            ob_clean();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Transmission failed: All fields are required.'
            ]);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Transmission failed: Invalid email address format.'
            ]);
            exit;
        }

        // 4. Prepare and Execute SQL Statement
        // Columns: name, email, subject, message (id and created_at are automatic)
        $sql = "INSERT INTO queries (name, email, subject, message) 
                VALUES (:name, :email, :subject, :message)";
        
        $stmt = $conn->prepare($sql);

        // Bind parameters to prevent SQL Injection
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode([
                'status' => 'success', 
                'message' => 'Data successfully synced with the master server.'
            ]);
        } else {
            ob_clean();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Database execution failed.'
            ]);
        }

    } else {
        ob_clean();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Protocol error: Invalid request method.'
        ]);
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => 'Core connection error: ' . $e->getMessage()
    ]);
}
?>
