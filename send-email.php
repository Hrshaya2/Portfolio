<?php
/**
 * Contact Form Email Handler
 * 
 * This script handles contact form submissions and sends emails.
 * Make sure to update the $to_email variable with your actual email address.
 */

// ===================================
// CONFIGURATION - UPDATE THESE VALUES
// ===================================
$to_email = "harshalakshitha001@gmail.com";  // <-- Replace with your email address
$site_name = "Portfolio Contact Form";

// ===================================
// CORS Headers (for AJAX requests)
// ===================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===================================
// Only accept POST requests
// ===================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST request.'
    ]);
    exit();
}

// ===================================
// Get and sanitize form data
// ===================================
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : 'No Subject';
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

// ===================================
// Validation
// ===================================
$errors = [];

// Check required fields
if (empty($name)) {
    $errors[] = 'Name is required.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
}

// Check for spam (honeypot - add a hidden field in your form if needed)
if (isset($_POST['website']) && !empty($_POST['website'])) {
    // This is likely a bot
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Spam detected.'
    ]);
    exit();
}

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors)
    ]);
    exit();
}

// ===================================
// Prepare email
// ===================================
$email_subject = "[$site_name] $subject";

$email_body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0a192f; color: #64ffda; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #0a192f; }
        .value { margin-top: 5px; }
        .footer { padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Message</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>From:</div>
                <div class='value'>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
            </div>
            <div class='field'>
                <div class='label'>Subject:</div>
                <div class='value'>" . htmlspecialchars($subject) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Message:</div>
                <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from your portfolio contact form.</p>
            <p>Sent on: " . date('F j, Y \a\t g:i A') . "</p>
        </div>
    </div>
</body>
</html>
";

// Plain text version for email clients that don't support HTML
$email_body_plain = "
New Contact Form Message
========================

From: $name
Email: $email
Subject: $subject

Message:
$message

------------------------
Sent on: " . date('F j, Y \a\t g:i A') . "
";

// ===================================
// Email headers
// ===================================
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $site_name . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion()
];

// ===================================
// Send email
// ===================================
$mail_sent = @mail($to_email, $email_subject, $email_body, implode("\r\n", $headers));

if ($mail_sent) {
    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent successfully. I\'ll get back to you soon!'
    ]);
    
    // Optional: Log successful submissions
    log_submission($name, $email, $subject, 'SUCCESS');
} else {
    // Error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error sending your message. Please try again later or contact me directly at ' . $to_email
    ]);
    
    // Log failed submissions
    log_submission($name, $email, $subject, 'FAILED');
}

// ===================================
// Helper Functions
// ===================================

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Log form submissions (optional)
 */
function log_submission($name, $email, $subject, $status) {
    $log_file = __DIR__ . '/logs/contact_log.txt';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_entry = date('Y-m-d H:i:s') . " | $status | Name: $name | Email: $email | Subject: $subject\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>
