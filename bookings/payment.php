<?php
session_start();

// Ensure session variables exist
$property_id = $_SESSION['property_id'] ?? null;
$total_amount = floatval($_SESSION['total_amount'] ?? 0);
$discount_amount = floatval($_SESSION['discount_amount'] ?? 0);
$security_deposit = floatval($_SESSION['security_deposit'] ?? 0); // Keep the session value
$total_payable = floatval($_SESSION['total_payable'] ?? 0);
$booking_option = $_SESSION['booking_option'] ?? '';
$actual_rent = floatval($_SESSION['actual_rent'] ?? 0);

// Debugging
error_log("Payment Page Session Data: " . print_r($_SESSION, true));

// Database connection
include '../Database/dbconfig.php';

// Validate session data
if (!isset($_SESSION['userID'], $_SESSION['property_id'])) {
    die("Error: Required session data is missing.");
}

$property_id = intval($_SESSION['property_id']);
$userID = intval($_SESSION['userID']);

// Fetch property details
$query = "
    SELECT p.property_price, 
           COALESCE(pay.security_deposit, NULL) AS db_security_deposit
    FROM properties p
    LEFT JOIN payments pay ON p.id = pay.property_id
    WHERE p.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    die("Error: Property details not found.");
}

// Ensure property price is numeric
$property_price = floatval($property['property_price']);

// ✅ **Fix: Use the correct security deposit**
if ($security_deposit == 0) {
    // If session security deposit is missing, use the database value or calculate
    $security_deposit = $property['db_security_deposit'] !== null
        ? floatval($property['db_security_deposit'])
        : ($property_price * 0.25);
}

// ✅ **Recalculate Total Payable Amount**
$total_payable = ($actual_rent + $security_deposit) - $discount_amount;

// ✅ **Store Values in Session for process_payment.php**
$_SESSION['property_price'] = $property_price;
$_SESSION['security_deposit'] = $security_deposit;
$_SESSION['discount_amount'] = $discount_amount;
$_SESSION['total_payable'] = $total_payable;
$_SESSION['booking_option'] = $booking_option;
$_SESSION['actual_rent'] = $actual_rent;

// Debugging: Check if security deposit is correct
error_log("Final Security Deposit: " . $_SESSION['security_deposit']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StayEase | Payment</title>
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            background-image: url("../assets/img/beams-home@95.jpg");
            background-size: cover;
            background-position: center;
        }

        .payment-btn {
            transition: all 0.2s ease;
        }

        .payment-btn:hover {
            transform: translateY(-1px);
        }

        .payment-btn:active {
            transform: translateY(1px);
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

        .active-option {
            border: 1px solid #2563eb;
            background-color: #e0f2fe;
        }

        /* Enhanced Modal Styles */
        #paymentSuccessModal {
            transition: opacity 0.5s ease;
        }

        #modalContent {
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen font-Nrj-fonts p-6">
    <div class="p-4 absolute left-2 top-2">
        <button onclick="history.back()" class="flex items-center text-blue-600 font-medium hover:underline">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </button>
    </div>
    <div class="w-full max-w-6xl animate-fade-in">
        <div class="bg-white rounded-md shadow-md overflow-hidden border-[1.5px] border-gray-300">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-0">
                <!-- Payment Details Section - 3/5 width on desktop -->
                <div class="md:col-span-3 p-6 md:p-8">
                    <div class="flex justify-center items-center space-x-4 mb-6">
                        <div class="flex items-center">
                            <img src="../assets/img/stayease logo.svg" class="w-8 h-8 mr-2" alt="StayEase Logo" />
                        </div>
                        <div class="h-6 w-px bg-gray-500"></div>
                        <img src="../assets/img/stripe logo.svg" class="w-24 h-10" alt="Stripe Logo" />
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Complete Your Payment</h2>
                        <p class="text-gray-500 text-sm">Secure payment processing by Stripe</p>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Accepted Payment Cards</h3>
                        <div class="flex space-x-4">
                            <div class="p-2 bg-gray-50 rounded-md border border-gray-300 merchant-logo">
                                <img src="../assets/img/visa-logo.svg" class="w-12 h-8" alt="Visa Logo" />
                            </div>
                            <div class="p-2 bg-gray-50 rounded-md border border-gray-300 merchant-logo">
                                <img src="../assets/img/mastercard-logo.svg" class="w-12 h-8" alt="MasterCard Logo" />
                            </div>
                            <div class="p-2 bg-gray-50 rounded-md border border-gray-300 merchant-logo">
                                <img src="../assets/img/american-express.svg" class="w-12 h-8"
                                    alt="American Express Logo" />
                            </div>
                            <div class="p-2 bg-gray-50 rounded-md border border-gray-300 merchant-logo">
                                <img src="../assets/img/rupay-logo.svg" class="w-12 h-8" alt="RuPay Logo" />
                            </div>
                        </div>
                    </div>

                    <form id="payment-form" action="process_payment.php" method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="card-name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cardholder Name
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <i class="fa-regular fa-user"></i>
                                    </span>
                                    <input type="text" id="card-name" name="card_name"
                                        class="card-input w-full pl-10 pr-3 py-3 rounded-lg border border-gray-300 focus:outline-none"
                                        placeholder="Full Name as per the Card" required />
                                </div>
                            </div>

                            <div>
                                <label for="card-element" class="block text-sm font-medium text-gray-700 mb-2">
                                    Card Details
                                </label>
                                <div id="card-element"
                                    class="card-input w-full p-4 rounded-lg border border-gray-300 bg-white">
                                    <!-- Stripe Card Element will be inserted here -->
                                </div>
                                <p id="card-error" class="text-red-500 text-sm mt-1 hidden">Please enter valid card
                                    details.</p>
                                <p id="payment-status" class="text-gray-500 text-sm mt-1 hidden"></p>
                            </div>

                            <button id="submit" type="submit"
                                class="payment-btn w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg flex items-center justify-center shadow-md transition-all duration-300">
                                <i class="fa-solid fa-credit-card mr-2"></i>
                                Pay Now ₹ <?= number_format($total_amount, 2); ?>
                            </button>
                        </div>
                    </form>

                    <p class="text-gray-600 text-xs mt-4 text-center">
                        By clicking on the "Pay Now" button, you agree to our
                        <a href="#" class="text-blue-600 hover:underline">Terms & Conditions</a> and
                        <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>.
                    </p>
                </div>

                <!-- Payment Summary Section - 2/5 width on desktop -->
                <div class="md:col-span-2 bg-white p-6 border border-gray-300">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking Summary</h2>

                    <div class="bg-blue-50 border border-gray-300 text-black p-4 rounded-md mb-6 text-sm">
                        <div class="flex justify-between items-center mb-1">
                            <p class="font-medium">Base Rent amount</p>
                            <p class="font-semibold">₹<span id="rent"><?= number_format($property_price, 2); ?></span>
                            </p>
                        </div>

                        <div class="flex justify-between items-center mb-1">
                            <p class="font-medium">Booking Type:</p>
                            <p class="font-semibold"><span
                                    id="booking-option"><?= htmlspecialchars($booking_option); ?></span> booking</p>
                        </div>

                        <div class="flex justify-between items-center mb-1">
                            <p class="font-medium">Monthly Room Rent:</p>
                            <p class="font-semibold">₹<span id="rent"><?= number_format($actual_rent, 2); ?></span></p>
                        </div>

                        <div class="flex justify-between items-center mb-1">
                            <p class="font-medium">Security Deposit (25% of Rent):</p>
                            <p class="font-semibold">₹<span
                                    id="deposit"><?= number_format($security_deposit, 2); ?></span></p>
                        </div>

                        <div class="flex justify-between items-center mb-1">
                            <p class="font-medium">Discount:</p>
                            <p class="font-semibold text-green-600">-₹<span
                                    id="discount"><?= number_format($discount_amount, 2); ?></span></p>
                        </div>

                        <div
                            class="border-t-2 border-gray-300 mt-2 pt-2 flex justify-between items-center text-lg font-semibold">
                            <p class="text-gray-800">Total Payable:</p>
                            <p class="text-blue-600">₹<span id="total"><?= number_format($total_amount, 2); ?></span>
                            </p>
                        </div>
                    </div>
                    <!-- Security & Support Section -->
                    <div class="mt-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-3">Security & Support</h2>
                        <div class="flex justify-center space-x-4 mb-4">
                            <img src="../assets/img/ssl-secure.png" class="w-24 h-24" alt="SSL Secure" />
                            <img src="../assets/img/pci-compliant.svg" class="w-24 h-24" alt="PCI Compliant" />
                        </div>
                        <p class="text-gray-600 text-sm mb-4 text-center">Your payment is encrypted and 100% secure.</p>

                        <div class="bg-blue-50 border border-gray-300 rounded-lg p-4 text-center">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Need Help?</h3>
                            <p class="text-gray-600 text-sm mb-2">Contact our support team.</p>
                            <a href="mailto:support@stayease.com"
                                class="text-blue-600 font-semibold hover:underline flex items-center justify-center">
                                <i class="fa-regular fa-envelope mr-2"></i>
                                support@stayease.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Payment Success Modal -->
    <div id="paymentSuccessModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-[400px] text-center relative" id="modalContent">
            <!-- Animated Check Icon -->
            <div class="flex justify-center items-center">
                <div class="w-16 h-16 flex items-center justify-center rounded-full bg-green-100">
                    <i class="fa-solid fa-circle-check text-green-500 text-4xl"></i>
                </div>
            </div>
            <h2 class="text-2xl font-bold mt-4 text-gray-800">Payment Successful!</h2>
            <p class="text-gray-600 mt-2">Thank you for your payment. Your transaction was successful.</p>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="mt-4">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-blue-500 border-solid"></div>
            </div>

            <!-- Confetti Canvas -->
            <canvas id="confettiCanvas" class="absolute inset-0 pointer-events-none"></canvas>

            <!-- Redirect Countdown Message -->
            <p id="redirectMsg" class="hidden text-sm text-gray-500 mt-2">Redirecting in <span
                    id="countdown">3</span>...</p>
        </div>
    </div>

    <script>
        // Initialize Stripe
        const stripe = Stripe("pk_test_51QdZIFIsZuG9rUaAUlU1SDR4OPVjRWrXaMJUTji3L5iYn7ZJGOKwfab9SolHF3H6OkAlX6uD8JicEs4eFMxJ153600sMKu54oZ");
        const elements = stripe.elements();
        const card = elements.create("card");
        card.mount("#card-element");

        // Payment Form Submission
        const form = document.getElementById("payment-form");
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            // Show processing message
            const paymentStatus = document.getElementById("payment-status");
            paymentStatus.textContent = "Processing Payment...";
            paymentStatus.classList.remove("hidden");

            // Simulate payment processing (2 seconds)
            setTimeout(() => {
                paymentStatus.textContent = "Payment Successful!";
                paymentStatus.classList.remove("text-gray-500");
                paymentStatus.classList.add("text-green-600");

                // Instead of immediate redirect, show enhanced modal after a brief delay
                setTimeout(() => {
                    showSuccessModal();
                }, 500);
            }, 2000);
        });

        // Function to display the enhanced success modal with animations
        function showSuccessModal() {
            const modal = document.getElementById('paymentSuccessModal');
            const modalContent = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            // Animate modal in
            setTimeout(() => {
                modalContent.style.transform = 'scale(1)';
                modalContent.style.opacity = '1';
            }, 50);

            // Hide the loading spinner after 1.5 seconds and show the redirect message with confetti
            setTimeout(() => {
                document.getElementById('loadingSpinner').classList.add('hidden');
                document.getElementById('redirectMsg').classList.remove('hidden');
                startConfetti();
            }, 1500);

            // Countdown Timer for Redirect (3 seconds)
            let count = 3;
            const countdownInterval = setInterval(() => {
                count--;
                document.getElementById('countdown').innerText = count;
                if (count === 0) {
                    clearInterval(countdownInterval);
                    window.location.href = "process_payment.php"; // Change to your actual next page if needed
                }
            }, 1000);
        }

        // Confetti Effect Function
        function startConfetti() {
            const canvas = document.getElementById("confettiCanvas");
            const ctx = canvas.getContext("2d");

            // Set canvas to full window size
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            let particles = [];
            for (let i = 0; i < 100; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 5 + 2,
                    d: Math.random() * 2 + 1
                });
            }

            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (let i = 0; i < particles.length; i++) {
                    ctx.beginPath();
                    ctx.arc(particles[i].x, particles[i].y, particles[i].r, 0, Math.PI * 2, false);
                    ctx.fillStyle = `hsl(${Math.random() * 360}, 100%, 50%)`;
                    ctx.fill();
                }
            }

            function update() {
                for (let i = 0; i < particles.length; i++) {
                    particles[i].y += particles[i].d;
                    if (particles[i].y > canvas.height) {
                        particles[i].y = 0;
                        particles[i].x = Math.random() * canvas.width;
                    }
                }
            }

            function loop() {
                draw();
                update();
                requestAnimationFrame(loop);
            }
            loop();
        }
    </script>
</body>

</html>