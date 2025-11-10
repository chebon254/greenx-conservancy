<?php
/**
 * Contact Form Processor using Resend API
 * Sends form submissions to maryokeloconservancy@gmail.com
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response headers
header('Content-Type: application/json');

// Function to load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    return true;
}

// Load environment variables
$envPath = __DIR__ . '/.env';
if (!loadEnv($envPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Configuration file not found. Please contact the administrator.'
    ]);
    exit;
}

// Get environment variables
$resendApiKey = getenv('RESEND_API_KEY') ?: $_ENV['RESEND_API_KEY'] ?? '';
$recipientEmail = getenv('RECIPIENT_EMAIL') ?: $_ENV['RECIPIENT_EMAIL'] ?? 'maryokeloconservancy@gmail.com';
$fromEmail = getenv('FROM_EMAIL') ?: $_ENV['FROM_EMAIL'] ?? 'onboarding@resend.dev';

// Check if API key is set
if (empty($resendApiKey) || $resendApiKey === 'your_resend_api_key_here') {
    echo json_encode([
        'success' => false,
        'message' => 'Email service not configured. Please contact the administrator.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get and sanitize form data
$firstName = isset($_POST['fname']) ? htmlspecialchars(trim($_POST['fname'])) : '';
$lastName = isset($_POST['lname']) ? htmlspecialchars(trim($_POST['lname'])) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
$message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

// Validate required fields
$errors = [];

if (empty($firstName)) {
    $errors[] = 'First name is required.';
}

if (empty($lastName)) {
    $errors[] = 'Last name is required.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}

// Return errors if validation fails
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fix the following errors:',
        'errors' => $errors
    ]);
    exit;
}

// Build email content
$fullName = $firstName . ' ' . $lastName;
$emailSubject = "New Contact Form Submission from " . $fullName;

$htmlContent = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 5px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #555; }
        .value { margin-top: 5px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Submission</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Name:</div>
                <div class='value'>" . htmlspecialchars($fullName) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
            </div>
            <div class='field'>
                <div class='label'>Phone:</div>
                <div class='value'><a href='tel:" . htmlspecialchars($phone) . "'>" . htmlspecialchars($phone) . "</a></div>
            </div>
            <div class='field'>
                <div class='label'>Message:</div>
                <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from the contact form on Mary Okelo Conservancy website.</p>
            <p>Submitted on: " . date('F j, Y, g:i a') . "</p>
        </div>
    </div>
</body>
</html>
";

// Plain text version for email clients that don't support HTML
$textContent = "New Contact Form Submission\n\n";
$textContent .= "Name: " . $fullName . "\n";
$textContent .= "Email: " . $email . "\n";
$textContent .= "Phone: " . $phone . "\n";
$textContent .= "Message:\n" . $message . "\n\n";
$textContent .= "---\n";
$textContent .= "Submitted on: " . date('F j, Y, g:i a');

// Prepare data for Resend API
$data = [
    'from' => $fromEmail,
    'to' => [$recipientEmail],
    'reply_to' => $email,
    'subject' => $emailSubject,
    'html' => $htmlContent,
    'text' => $textContent
];

// Send email via Resend API
$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $resendApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($curlError) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email. Please try again later.',
        'error' => 'Network error occurred.'
    ]);
    exit;
}

// Parse Resend API response
$responseData = json_decode($response, true);

// Check if email was sent successfully
if ($httpCode === 200 || $httpCode === 201) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
} else {
    $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown error';

    echo json_encode([
        'success' => false,
        'message' => 'Failed to send your message. Please try again later or contact us directly.',
        'error' => $errorMessage
    ]);
}
