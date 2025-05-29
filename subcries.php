<?php
require_once 'includes/config.php';
require_once 'includes/db_config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $response = ['success' => false, 'message' => 'This email is already subscribed.'];
            } else {
                // Insert new subscriber
                $stmt = $pdo->prepare("INSERT INTO subscribers (email) VALUES (?)");
                $stmt->execute([$email]);
                
                $response = ['success' => true, 'message' => 'Thank you for subscribing!'];
                
                // In a real app, you would send a confirmation email here
            }
        } catch(PDOException $e) {
            error_log("Subscription error: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not a POST request, redirect to home
header("Location: index.php");
exit;