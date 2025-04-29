<?php
session_start();
require('../fpdf/fpdf.php'); // Include FPDF library

// Database connection
include '../Database/dbconfig.php';

// Fetch user details
$userID = $_SESSION['userID'] ?? null;
if (!$userID) {
    die("User not logged in.");
}

$sql = "SELECT u.full_name, u.phone_number, u.gender, u.email_address, u.rent_start_date,
               p.transaction_id, p.payment_date, p.payment_amount, 
               p.security_deposit, p.original_rent, p.payment_status, p.payment_method 
        FROM users u 
        LEFT JOIN payments p ON u.userID = p.userID 
        WHERE u.userID = ? 
        ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

/// Get page width manually
$pageWidth = $pdf->GetX() + 210; // A4 width = 210mm
$logoWidth = 40; // Set logo width
$logoX = ($pageWidth - $logoWidth) / 2; // Calculate center

// Add Logo (Replace with correct image path)
$pdf->Image('C:/xampp/htdocs/stay/assets/img/stayeaselogo.png', $logoX, 10, $logoWidth);

// Reduce space after logo
$pdf->Ln(45);


// StayEase Branding
$pdf->Cell(190, 10, 'StayEase', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(190, 8, 'Simplifying Your Home Rent Needs', 0, 1, 'C');
$pdf->Ln(10);

// User Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Name:', 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, $user['full_name'] ?? 'N/A', 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Email:', 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, $user['email_address'] ?? 'N/A', 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Phone:', 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, $user['phone_number'] ?? 'N/A', 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Gender:', 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, $user['gender'] ?? 'N/A', 0, 1);

$pdf->Ln(5);

// Transaction Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(40, 10, 'Transaction ID', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Rent Start', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Rent', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Deposit', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Date', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Method', 1, 0, 'C', true);
$pdf->Ln();

// Table Data
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(40, 10, $user['transaction_id'] ?? 'N/A', 1);
$pdf->Cell(30, 10, $user['rent_start_date'] ?? 'N/A', 1);
$pdf->Cell(30, 10, '₹' . ($user['original_rent'] ?? '0'), 1);
$pdf->Cell(30, 10, '₹' . ($user['security_deposit'] ?? '0'), 1);
$pdf->Cell(30, 10, $user['payment_date'] ?? 'N/A', 1);
$pdf->Cell(30, 10, $user['payment_method'] ?? 'N/A', 1);
$pdf->Ln();

// Payment Status
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Payment Status:', 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, ucfirst($user['payment_status']) ?? 'N/A', 0, 1);

// Footer: Customer Support
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(190, 8, 'Customer Support', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(190, 8, 'support@stayease.com', 0, 1, 'C');
$pdf->Cell(190, 8, '+91 98765 43210', 0, 1, 'C');

// Output PDF (force download)
$pdf->Output('D', 'User_Payment_Details.pdf');
?>