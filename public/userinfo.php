<?php
// Start session to access user data
session_start();

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
  header('Location: login.php');
  exit();
}

// Database connection
include '../Database/dbconfig.php';

// Process booking cancellation if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
  $bookingId = $_POST['booking_id'];
  $userId = $_SESSION['userID'];

  // Verify booking belongs to user
  $checkSql = "SELECT payment_id FROM payments WHERE payment_id = ? AND userID = ?";
  $checkStmt = $conn->prepare($checkSql);
  $checkStmt->bind_param("ii", $bookingId, $userId);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows > 0) {
    // Update booking status to cancelled
    $updateSql = "UPDATE payments SET payment_status = 'cancelled' WHERE payment_id = ? AND userID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ii", $bookingId, $userId);

    if ($updateStmt->execute()) {
      $cancellationSuccess = true;
      $successMessage = "Your booking has been cancelled successfully.";
    } else {
      $cancellationError = true;
      $errorMessage = "Failed to cancel booking. Please try again.";
    }
    $updateStmt->close();
  } else {
    $cancellationError = true;
    $errorMessage = "Invalid booking or unauthorized access.";
  }
  $checkStmt->close();
}

// Fetch user email
$userID = $_SESSION['userID'];
$sql = "SELECT email FROM signup WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

// Fetch user details including rent start date and property details
$userID = $_SESSION['userID'];
$sql = "SELECT 
            u.full_name, 
            u.phone_number, 
            u.gender, 
            u.email_address, 
            u.rent_start_date,
            p.payment_id, 
            p.transaction_id, 
            p.payment_date, 
            p.payment_amount, 
            p.property_id, 
            p.security_deposit, 
            p.original_rent, 
            p.payment_status, 
            p.payment_method,
            p.total_payable,
            p.booking_type,
            p.discount_amount,
            pr.property_name,
            pr.property_type,
            pr.property_location
        FROM users u 
        LEFT JOIN payments p ON u.userID = p.userID 
        LEFT JOIN properties pr ON p.property_id = pr.id
        WHERE u.userID = ? 
        ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();


// Determine display name
$displayName = !empty($user['full_name']) ? $user['full_name'] : $email;

// Function to get user initials for avatar
function getInitials($name)
{
  $words = explode(' ', $name);
  $initials = '';
  foreach ($words as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
  }
  return $initials;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile | StayEase</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon">
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.0/css/all.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1769ff', // This matches the "for" class in the original
          },
          fontFamily: {
            'poppins': ['Poppins', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <style>
    body {
      background-image: url("../assets/img/beams-home@95.jpg");
      background-size: cover;
      background-position: center;
      font-family: 'Poppins', sans-serif;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .tab-button.active {
      background-color: white;
      color: #3b82f6;
      font-weight: 600;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 50;
    }

    .modal-content {
      background-color: white;
      margin: 10% auto;
      max-width: 500px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body class="bg-white min-h-screen p-8 md:p-8">
  <!-- Back Button -->
  <button onclick="history.back()"
    class="mb-6 flex items-center text-blue-600 font-medium hover:bg-gray-100 p-2 rounded-md">
    <i class="fa-solid fa-arrow-left mr-2"></i> Back
  </button>

  <?php if (isset($cancellationSuccess) && $cancellationSuccess): ?>
    <div class="max-w-6xl mx-auto mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fa-solid fa-check text-green-500"></i>
        </div>
        <div class="ml-3">
          <p class="text-sm text-green-700"><?php echo $successMessage; ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (isset($cancellationError) && $cancellationError): ?>
    <div class="max-w-6xl mx-auto mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-700"><?php echo $errorMessage; ?></p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="max-w-6xl mx-auto bg-white p-8 border border-gray-300 rounded-lg">
    <!-- Profile header -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-4 mb-8">
      <div
        class="h-20 w-20 rounded-full bg-primary text-white flex items-center justify-center text-xl font-bold border-2 border-primary">
        <?php echo getInitials($displayName); ?>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-gray-800">
          Hey 👋 <?php echo htmlspecialchars($displayName); ?>
        </h1>
        <p class="text-gray-500">Welcome to your StayEase profile</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="grid grid-cols-2 max-w-md bg-gray-50 p-1 rounded-md border-[1.5px] border-gray-300">
        <button id="tab-personal" class="tab-button active py-2 px-4 rounded-sm text-sm font-medium transition-colors">
          Personal Information
        </button>
        <button id="tab-bookings" class="tab-button py-2 px-4 rounded-sm text-sm font-medium transition-colors">
          Your Bookings
        </button>
      </div>
    </div>

    <!-- Personal Information Tab -->
    <div id="content-personal" class="tab-content active space-y-6">
      <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold flex items-center">
            <i class="fa-regular fa-user mr-2"></i> Personal Information
          </h2>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-500">Email Address</label>
              <div class="p-3 bg-gray-50 rounded-md border-[1.5px] border-gray-300 text-gray-800 font-medium">
                <?php echo htmlspecialchars($email); ?>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-500">Phone Number</label>
              <div class="p-3 bg-gray-50 rounded-md border-[1.5px] border-gray-300 text-gray-800 font-medium">
                <?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-500">Gender</label>
              <div class="p-3  bg-gray-50 rounded-md border-[1.5px] border-gray-300 text-gray-800 font-medium">
                <?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bookings Tab -->
    <div id="content-bookings" class="tab-content space-y-6">
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-ticket-check-icon lucide-ticket-check">
            <path
              d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
            <path d="m9 12 2 2 4-4" />
          </svg>
          <h2 class="text-xl font-semibold">Recent Bookings</h2>
        </div>

        <div class="p-6">
          <?php if ($result->num_rows > 0) {
            // Reset the result pointer
            $result->data_seek(0);
            while ($booking = $result->fetch_assoc()) { ?>
              <div class="border rounded-lg overflow-hidden mb-6">
                <div class="bg-blue-50 p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                  <div>
                    <p class="text-sm text-gray-500">Transaction ID</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['transaction_id'] ?? 'N/A'); ?></p>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold 
                      <?php
                      if ($booking['payment_status'] == 'successful') {
                        echo 'bg-green-100 text-green-800';
                      } elseif ($booking['payment_status'] == 'cancelled') {
                        echo 'bg-red-100 text-red-800';
                      } else {
                        echo 'bg-yellow-100 text-yellow-800';
                      }
                      ?>">
                      <?php echo htmlspecialchars($booking['payment_status'] ?? 'N/A'); ?>
                    </span>

                    <?php if ($booking['payment_status'] == 'successful') { ?>
                      <button onclick="openCancelModal('<?php echo $booking['payment_id']; ?>')"
                        class="text-red-600 hover:text-red-800 text-sm font-medium bg-red-50 hover:bg-red-100 px-3 py-1 rounded-full transition-colors">
                        Cancel Booking
                      </button>
                    <?php } ?>
                  </div>
                </div>

                <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div>
                    <p class="text-sm text-gray-500">Property Name</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['property_name'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Property Location</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['property_location'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Property Location</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['property_type'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Rent Start Date</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['rent_start_date'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Booking Type</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['booking_type'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Payment Date</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['payment_date'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Payment Method</p>
                    <p class="font-medium"><?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Original Rent</p>
                    <p class="font-medium">₹<?php echo number_format($booking['original_rent'] ?? 0); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Security Deposit</p>
                    <p class="font-medium">₹<?php echo number_format($booking['security_deposit'] ?? 0); ?></p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Discount Amount</p>
                    <p class="font-medium text-green-600">-₹<?php echo number_format($booking['discount_amount'] ?? 0); ?>
                    </p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Total Paid</p>
                    <p class="font-semibold text-lg">₹<?php echo number_format($booking['total_payable'] ?? 0); ?></p>
                  </div>
                </div>
              </div>
            <?php }
          } else { ?>
            <div class="text-center py-8">
              <p class="text-red-500 font-medium">No Bookings Found</p>
            </div>
          <?php } ?>

          <div class="mt-6 flex justify-center">
            <button onclick="window.location.href='download.php'"
              class="w-full max-w-md bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-4 rounded-md flex items-center justify-center">
              <i class="fa-solid fa-arrow-down-to-bracket mr-2"></i> Download Booking Details
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cancel Booking Modal -->
  <div id="cancelModal" class="modal">
    <div class="modal-content w-full max-w-md mx-auto p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Cancel Booking</h3>
        <button onclick="closeCancelModal()" class="text-gray-500 hover:text-gray-700">
          <i class="fa-solid fa-xmark text-xl"></i>
        </button>
      </div>

      <p class="text-gray-600 mb-4">Are you sure you want to cancel this booking? This action cannot be undone.</p>

      <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fa-solid fa-triangle-exclamation text-yellow-400"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              Cancellation may be subject to our refund policy. Please contact customer support for refund information.
            </p>
          </div>
        </div>
      </div>

      <form id="cancelBookingForm" method="post" action="">
        <input type="hidden" id="booking_id" name="booking_id">
        <input type="hidden" name="cancel_booking" value="1">

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeCancelModal()"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md font-medium">
            No, Keep Booking
          </button>
          <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md font-medium">
            Yes, Cancel Booking
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function () {
      const tabButtons = document.querySelectorAll('.tab-button');
      const tabContents = document.querySelectorAll('.tab-content');

      tabButtons.forEach(button => {
        button.addEventListener('click', function () {
          // Remove active class from all buttons and contents
          tabButtons.forEach(btn => btn.classList.remove('active'));
          tabContents.forEach(content => content.classList.remove('active'));

          // Add active class to clicked button
          this.classList.add('active');

          // Get the content id based on the button id
          const contentId = 'content-' + this.id.split('-')[1];
          document.getElementById(contentId).classList.add('active');
        });
      });
    });

    // Cancel booking modal functions
    function openCancelModal(bookingId) {
      document.getElementById('booking_id').value = bookingId;
      document.getElementById('cancelModal').style.display = 'block';
    }

    function closeCancelModal() {
      document.getElementById('cancelModal').style.display = 'none';
    }
  </script>
</body>

</html>