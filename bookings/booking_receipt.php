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
                 p.payment_amount, p.original_rent, p.payment_status, p.security_deposit,
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
  <title>StayEase | Booking Receipt</title>
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.0/css/all.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    body {
      background: url("../assets/img/beams-home@95.jpg");
      background-size: cover;
      background-position: center;
      font-family: "Poppins", sans-serif;
    }

    .animate-fade-in {
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center p-8 bg-white font-Nrj-fonts">
  <!-- Back Button -->
  <div class="back-button absolute top-2 left-2 ml-4">
    <a href="../public/index.php">
      <button class="flex items-center text-black font-medium hover:underline">
        <i class="fa-regular fa-arrow-left-from-bracket mr-2"></i> Back
      </button>
    </a>
  </div>
  <div
    class="w-full max-w-3xl mx-auto bg-white shadow-sm rounded-lg overflow-hidden border border-gray-300 animate-fade-in"
    id="booking-receipt">
    <!-- Header -->
    <div class="bg-for p-6 text-white relative overflow-hidden">
      <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-20 rounded-full -translate-y-1/2 translate-x-1/2">
      </div>
      <div class="absolute bottom-0 left-0 w-40 h-40 bg-white opacity-20 rounded-full translate-y-1/2 -translate-x-1/2">
      </div>

      <div class="relative z-10">
        <h1 class="text-2xl font-semibold">Booking Confirmation</h1>
        <p class="text-gray-100 mt-1 text-sm">
          Thank you for your payment. Here is your receipt
        </p>
      </div>
    </div>

    <!-- Booking Reference -->
    <div class="bg-gray-100 px-10 py-4 flex justify-between items-center border-b border-gray-200">
      <div>
        <p class="text-sm text-gray-500">Transaction ID</p>
        <p class="font-semibold text-gray-900"><?= htmlspecialchars($paymentData['transaction_id']) ?></p>
      </div>
      <div class="text-right">
        <p class="text-sm text-gray-500">Issued On</p>
        <p class="font-semibold text-gray-900"><?= htmlspecialchars($paymentData['payment_date']) ?></p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="p-6 px-12 space-y-6">
      <!-- Customer Information -->
      <div>
        <h2 class="text-lg font-semibold mb-3">Customer Information</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Name</p>
            <p class="font-semibold"><?= htmlspecialchars($paymentData['full_name']) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="font-semibold"><?= htmlspecialchars($paymentData['email_address']) ?></p>
          </div>
        </div>
      </div>

      <hr class="border-gray-300" />

      <!-- Booking Details -->
      <div>
        <h2 class="text-xl font-semibold mb-3">Booking Details</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Booking Type</p>
            <p class="font-semibold"><?= htmlspecialchars($bookingType) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Rent start Date</p>
            <p class="font-semibold"><?= htmlspecialchars($paymentData['rent_start_date']) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Rent Occurrence</p>
            <p class="font-semibold">Monthly</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Next rent Due</p>
            <p class="font-semibold"><?php
            $rentStartDate = new DateTime($paymentData['rent_start_date']);
            $nextRentDueDate = $rentStartDate->modify('+1 month');
            echo htmlspecialchars($nextRentDueDate->format('Y-m-d'));
            ?></p>
          </div>
        </div>
      </div>
      <hr class="border-gray-300" />
      <!-- Property Details Section -->

      <div>
        <h2 class="text-xl font-semibold mb-3">Property Details</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Property Name</p>
            <p class="font-semibold"><?= htmlspecialchars($paymentData['property_name']) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Property Location</p>
            <p class="font-semibold"><?= htmlspecialchars($paymentData['property_location']) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Monthly rent</p>
            <p class="font-semibold">₹<?= number_format(htmlspecialchars($paymentData['property_price'])) ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">No:of Bedrooms</p>
            <p class="font-semibold"><?= $paymentData['bedrooms'] ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">No: of Bathroom</p>
            <p class="font-semibold"><?= $paymentData['bathrooms'] ?></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Area</p>
            <p class="font-semibold"><?= $paymentData['area'] ?> sq.ft</p>
          </div>
        </div>
      </div>

      <hr class="border-gray-300" />

      <!-- Payment Information -->
      <div>
        <h2 class="text-xl font-semibold mb-3">Payment Information</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Payment Status</p>
            <p
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-semibold 
        <?= ($paymentData['payment_status'] === 'successful') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
              <?= htmlspecialchars($paymentData['payment_status']) ?>
            </p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Payment Method</p>
            <p class="font-semibold"><?= htmlspecialchars($paymentData['payment_method']) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Rent Amount</p>
            <p class="font-semibold">₹<?= number_format($paymentData['original_rent'], 2) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Security Deposit Paid</p>
            <p class="font-semibold">₹<?= number_format($paymentData['security_deposit'], 2) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Discount Applied</p>
            <p class="font-semibold text-green-600">- ₹<?= number_format($paymentData['discount_amount'], 2) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Total Paid Amount</p>
            <p class="font-semibold">₹<?= number_format($paymentData['payment_amount'], 2) ?></p>
          </div>
        </div>
      </div>


      <hr class="border-gray-300" />

      <!-- Booking Verification Section -->
      <div class="p-6 bg-white border border-gray-200 rounded-xl max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-900 text-center">
          Booking Verification
          <span class="block text-sm text-gray-600 font-medium">For Landlords Only</span>
        </h2>

        <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-8">
          <!-- Verification Instructions -->
          <div class="flex-1">
            <p class="text-sm text-gray-700 leading-relaxed">
              Landlords can verify this booking by scanning the QR code.
              Please ensure the following:
            </p>
            <ul class="text-sm text-gray-800 mt-4 space-y-3 list-disc list-inside">
              <li>Match the tenant's details with booking records.</li>
              <li>Check the <strong>Transaction ID</strong> for validity.</li>
              <li>Confirm the <strong>rent amount & due date</strong>.</li>
              <li>
                Ensure the payment status is marked as
                <strong class="text-green-600">"Successful"</strong>.
              </li>
              <li>Verify the tenant’s <strong>Identity Proof</strong> if required.</li>
            </ul>
          </div>

          <!-- QR Code Section -->
          <?php
          $qr_data = "Payment Date: " . htmlspecialchars($paymentData['payment_date']) . "\n" .
            "Full Name: " . htmlspecialchars($paymentData['full_name']) . "\n" .
            "Email: " . htmlspecialchars($paymentData['email_address']) . "\n" .
            "Booking Type: " . htmlspecialchars($bookingType) . "\n" .
            "Rent Start Date: " . htmlspecialchars($paymentData['rent_start_date']) . "\n" .
            "Property Name: " . htmlspecialchars($paymentData['property_name']) . "\n" .
            "Location: " . htmlspecialchars($paymentData['property_location']) . "\n" .
            "Property Price: ₹" . number_format(htmlspecialchars($paymentData['property_price'])) . "\n" .
            "Payment Status: " . htmlspecialchars($paymentData['payment_status']) . "\n" .
            "Payment Method: " . htmlspecialchars($paymentData['payment_method']) . "\n" .
            "Original Rent: ₹" . number_format($paymentData['original_rent'], 2) . "\n" .
            "Security Deposit: ₹" . number_format($paymentData['security_deposit'], 2) . "\n" .
            "Discount: ₹" . number_format($paymentData['discount_amount'], 2) . "\n" .
            "Total Paid: ₹" . number_format($paymentData['payment_amount'], 2);
          ?>

          <div class="flex flex-col items-center">
            <div class="bg-white p-4 border border-gray-300 rounded-lg shadow-sm">
              <img
                src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($qr_data); ?>"
                alt="QR Code" />
            </div>
            <p class="text-xs text-gray-500 mt-2">
              Scan to verify booking details.
            </p>
          </div>
        </div>
      </div>
    </div>


    <!-- Footer -->
    <div class="bg-gray-50 px-6 py-4 flex flex-col justify-between items-center gap-4 border-t border-gray-200">
      <div class="flex space-x-3">
        <button onclick="printReceipt()"
          class="px-4 py-2 bg-white text-black border border-gray-300 hover:bg-gray-50 rounded-lg flex items-center gap-2">
          <i class="fa-regular fa-print mr-1"></i> Print
        </button>
        <button onclick="downloadPDF()"
          class="px-4 py-2 bg-for hover:bg-opacity-80 text-white rounded-lg flex items-center gap-2">
          <i class="fa-regular fa-arrow-down-to-bracket mr-1"></i>
          Download PDF
        </button>
      </div>
      <p class="text-sm text-gray-500 text-center sm:text-left">
        If you have any questions, please contact our support team at
        support@stayease.com
      </p>
    </div>
  </div>

  <script>
    function printReceipt() {
      window.print();
    }

    function downloadPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      html2canvas(document.getElementById("booking-receipt")).then(
        (canvas) => {
          const imgData = canvas.toDataURL("image/png");
          const imgWidth = 210;
          const imgHeight = (canvas.height * imgWidth) / canvas.width;
          doc.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
          doc.save("Booking_Receipt.pdf");
        }
      );
    }
  </script>
  <!-- EmailJS Script -->
  <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

  <!-- Modal Structure -->
  <div id="emailSentModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm text-center">
      <i class="fa-solid fa-envelope-circle-check text-green-500 text-4xl"></i>
      <h2 class="text-xl font-semibold mt-3">Email Sent!</h2>
      <p class="text-gray-600 mt-2">A confirmation email has been sent to your registered email.</p>
      <p class="text-gray-600 mt-2 text-sm">Please check your inbox!</p>
      <button onclick="closeModal()" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-for transition">
        Okay
      </button>
    </div>
  </div>

  <!-- Email Sending Script -->
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
          openModal(); // Open the success modal
        })
        .catch((error) => {
          console.error("FAILED...", error);
          alert("Error sending email: " + error.text);
        });
    }

    // Function to open modal
    function openModal() {
      document.getElementById("emailSentModal").classList.remove("hidden");
    }

    // Function to close modal
    function closeModal() {
      document.getElementById("emailSentModal").classList.add("hidden");
    }

    // Trigger email when the page loads
    window.onload = function () {
      sendBookingConfirmation();
    };
  </script>

  </script>
</body>

</html>