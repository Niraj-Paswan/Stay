<?php
session_start();

// Ensure all required session variables exist
if (!isset($_SESSION['userID'], $_SESSION['property_id'], $_SESSION['total_payable'])) {
    die("Error: Payment details missing.");
}

include '../Database/dbconfig.php';

// Get user ID from session
$userID = $_SESSION['userID'] ?? null;

if (!$userID) {
    die("Error: User not logged in.");
}

$query = "SELECT u.full_name, u.email_address, u.phone_number, u.rent_start_date, 
                 p.transaction_id, p.payment_id, p.payment_date, p.payment_time, 
                 p.payment_amount, p.original_rent, p.payment_status, 
                 p.payment_method, p.booking_type, p.discount_amount,  
                 pr.property_name, pr.property_location, pr.property_price, 
                 pr.property_description, pr.latitude, pr.longitude, 
                 pr.main_image, pr.property_type, pr.bedrooms, pr.bathrooms, pr.area 
          FROM users u
          JOIN payments p ON u.userID = p.userID
          JOIN properties pr ON p.property_id = pr.id
          WHERE u.userID = ? 
          ORDER BY p.payment_date DESC LIMIT 1";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$paymentData = $result->fetch_assoc();

if (!$paymentData) {
    echo "<p style='color: red; text-align: center;'>No payment details found for this user.</p>";
    exit;
}

// Fetch booking type correctly
$bookingType = $paymentData['booking_type'] ?? 'N/A';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Receipt | StayEase</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.0/css/all.css" />
</head>
<style>
    body {
        background-image: url("../assets/img/beams-home@95.jpg");
        background-size: cover;
        background-position: center;
    }
</style>

<body class="bg-gray-100 flex items-center justify-center min-h-screen font-Nrj-fonts p-4 sm:p-8">
    <div class="w-[40%] bg-white p-6 sm:p-8 rounded-md border border-gray-300 shadow-lg font-Nrj-fonts">
        <div class="text-center">
            <h2 class="text-2xl font-semibold text-gray-800 mt-4">Payment Successful</h2>
            <p class="text-gray-500 mt-2">Thank you for your payment. Here is your receipt.</p>
        </div>
        <div class="mt-6 bg-gray-50 p-4 rounded-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">Payment Details</h3>
            <hr class="border-gray-300 my-2" />
            <div class="flex justify-between text-gray-600 text-sm">
                <span>Transaction ID:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['transaction_id']) ?></span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Date:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['payment_date']) ?></span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Payment Method:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['payment_method']) ?></span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Original Rent Amount:</span>
                <span class="font-medium text-gray-800">₹<?= htmlspecialchars($paymentData['original_rent']) ?>
                    INR</span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Discount Applied:</span>
                <span class="font-medium text-red-500">- ₹<?= htmlspecialchars($paymentData['discount_amount']) ?>
                    INR</span>
            </div>

            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Total Paid Amount :</span>
                <span class="font-semibold text-green-600">₹<?= htmlspecialchars($paymentData['payment_amount']) ?>
                    INR</span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Booking Type:</span>
                <span class="font-medium"><?= htmlspecialchars($bookingType) ?></span>
            </div>

            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Payment Status:</span>
                <span class="font-medium text-green-600"><?= htmlspecialchars($paymentData['payment_status']) ?></span>
            </div>
        </div>
        <div class="mt-6 bg-gray-50 p-4 rounded-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">Customer Details</h3>
            <hr class="border-gray-300 my-2" />
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Customer Name:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['full_name']) ?></span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Customer Email:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['email_address']) ?></span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Customer Phone No:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['phone_number']) ?></span>
            </div>
        </div>
        <div class="mt-6 bg-gray-50 p-4  rounded-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700">Booking Details</h3>
            <div
                class="flex flex-col md:flex-row w-full bg-white border border-gray-300 rounded-md overflow-hidden mt-2">
                <!-- Image Section (Left) -->
                <div class="w-full md:w-1/3">
                    <img id="property-image" class="w-full h-44 md:h-44 object-cover"
                        src="<?= htmlspecialchars($paymentData['main_image']) ?>" alt="Property Image" />
                </div>

                <!-- Info Section (Right) -->
                <div class="w-full md:w-2/3 p-4 flex flex-col justify-center ml-8">
                    <h5 class="text-lg font-semibold text-gray-900">
                        <?= htmlspecialchars($paymentData['property_name']) ?>
                    </h5>
                    <p class="text-sm text-gray-600 flex items-center mt-1">
                        <i class="fa-solid fa-location-dot mr-1 text-blue-500"></i>
                        <?= htmlspecialchars($paymentData['property_location']) ?>
                    </p>
                    <div class="text-gray-600 text-sm mt-2 ">
                        <p class="py-1"><i class="fa-regular fa-bed text-blue-500 mr-1"></i>
                            <?= $paymentData['bedrooms'] ?> Bedrooms
                        </p>
                        <p class="py-1"><i class="fa-regular fa-bath text-blue-500 mr-1"></i>
                            <?= $paymentData['bathrooms'] ?> Bath
                        </p>
                        <p class="py-1"><i class="fa-regular fa-arrows-maximize text-blue-500 mr-1"></i>
                            <?= $paymentData['area'] ?>
                            Sq.ft</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-between text-gray-600 text-sm mt-2">
                <span>Rent Start Date:</span>
                <span class="font-medium"><?= htmlspecialchars($paymentData['rent_start_date']) ?></span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Rent Occurrence:</span>
                <span class="font-medium">Monthly</span>
            </div>
            <div class="flex justify-between text-gray-600 text-sm mt-1">
                <span>Next Rent Due:</span>
                <span class="font-medium">
                    <?php
                    $rentStartDate = new DateTime($paymentData['rent_start_date']);
                    $nextRentDueDate = $rentStartDate->modify('+1 month');
                    echo htmlspecialchars($nextRentDueDate->format('Y-m-d'));
                    ?>
                </span>
            </div>
        </div>
        <div class="mt-6 text-center flex justify-center gap-4">
            <button onclick="window.print()"
                class="py-2 px-4 rounded-md text-white font-medium bg-black hover:bg-opacity-80 transition">
                <i class="fa-solid fa-print mr-1"></i> Print
            </button>
            <a href="../public/index.php"
                class="py-2 px-4 rounded-md text-white font-medium bg-blue-600 hover:bg-blue-800 transition flex items-center">
                <i class="fa-solid fa-home mr-1"></i> Go to Home
            </a>
        </div>
    </div>
</body>
<!-- Load the correct EmailJS library -->
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

<script>
    // Initialize EmailJS with your Public Key
    emailjs.init("Ei7nopPNhgExd9OLX");

    function sendBookingConfirmation() {
        // Fetch user and booking details from PHP
        const userEmail = "<?= $paymentData['email_address'] ?>";
        const userName = "<?= $paymentData['full_name'] ?>";
        const transactionID = "<?= $paymentData['transaction_id'] ?>";
        const propertyName = "<?= $paymentData['property_name'] ?>";
        const propertyLocation = "<?= $paymentData['property_location'] ?>";
        const paymentAmount = "₹<?= $paymentData['payment_amount'] ?>";
        const rentStartDate = "<?= $paymentData['rent_start_date'] ?>";
        const nextRentDue = "<?= $nextRentDueDate->format('Y-m-d') ?>";
        const paymentStatus = "<?= $paymentData['payment_status'] ?>";

        // Send email via EmailJS
        emailjs.send("service_huwqanr", "template_4w94avg", {
            to_email: userEmail,        // Recipient email
            to_name: userName,          // Recipient name
            transaction_id: transactionID,
            property_name: propertyName,
            property_location: propertyLocation,
            payment_amount: paymentAmount,
            rent_start_date: rentStartDate,
            next_rent_due: nextRentDue,
            payment_status: paymentStatus,
        })
            .then((response) => {
                console.log("SUCCESS!", response);
                alert("Booking confirmation email sent successfully!");
            })
            .catch((error) => {
                console.error("FAILED...", error);
                alert("Error sending email: " + error.text);
            });
    }

    // Trigger email when the page loads
    window.onload = function () {
        sendBookingConfirmation();
    };
</script>

</html>