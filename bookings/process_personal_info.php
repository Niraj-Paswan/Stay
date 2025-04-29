<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['userID']) || !isset($_SESSION['user_email'])) {
    die("Unauthorized: Please log in first.");
}

// Database connection
include '../Database/dbconfig.php';

// Fetch session data
$userID = $_SESSION['userID'];
$email_address = isset($_POST['email']) ? trim($_POST['email']) : $_SESSION['user_email'];

// Get form data
$full_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone_number = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$rent_start_date = isset($_POST['rentDate']) ? $_POST['rentDate'] : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

if (empty($full_name) || empty($phone_number) || empty($rent_start_date) || empty($gender)) {
    die("All fields are required.");
}

// Ensure gender is valid
if (!in_array($gender, ['male', 'female'])) {
    die("Invalid gender selected.");
}

// Ensure rent start date is in the future
if ($rent_start_date < date('Y-m-d')) {
    die("Rent start date must be a future date.");
}

// Handle file upload (ID proof)
$id_proof_path = null;
if (isset($_FILES['idProof']) && $_FILES['idProof']['error'] == 0) {
    $upload_dir = "userproofs/"; // Changed from "uploads/" to "userproofs/"

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
    }

    $file_extension = strtolower(pathinfo($_FILES['idProof']['name'], PATHINFO_EXTENSION));

    // Validate file type
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_extensions)) {
        die("Only JPG, JPEG, and PNG files are allowed.");
    }

    // Generate a unique file name
    $file_name = "id_proof_" . $userID . "." . $file_extension;
    $id_proof_path = $upload_dir . $file_name;

    // Move uploaded file to the directory
    if (!move_uploaded_file($_FILES['idProof']['tmp_name'], $id_proof_path)) {
        echo "<script>alert('File upload failed. Please try again.');</script>";
    }
}

// Insert into users table
$stmt = $conn->prepare("INSERT INTO users (userID, full_name, phone_number, rent_start_date, gender, email_address, id_proof) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssss", $userID, $full_name, $phone_number, $rent_start_date, $gender, $email_address, $id_proof_path);

if ($stmt->execute()) {
    header("Location: payment.php");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>