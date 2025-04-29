<?php
session_start();
// Database connection
include '../Database/dbconfig.php';

// Check if the property ID is passed
if (isset($_GET['id'])) {
    $property_id = intval($_GET['id']); // Ensure it's an integer

    // Fetch data from properties table
    $query = "SELECT property_price, is_sharable FROM properties WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $stmt->bind_result($property_price, $is_sharable);
    $stmt->fetch();
    $stmt->close();

    // Ensure price is a valid number (since it's stored as varchar)
    $property_price = is_numeric($property_price) ? floatval($property_price) : 5000;
} else {
    // Default value if no ID is provided
    $property_price = 15000;
    $is_sharable = true;
}

// On click of Confirm Booking button
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $property_id = $_POST['property_id'];
    $booking_option = $_POST['booking_option'];
    $total_amount = floatval($_POST['total_amount']); // Ensure numeric
    $discount_amount = floatval($_POST['discount_amount']); // Ensure numeric
    $actual_rent = floatval($_POST['actual_rent']); // Ensure numeric

    // ✅ Calculate security deposit correctly as 25% of actual rent
    $security_deposit = $actual_rent * 0.25;

    // ✅ Calculate the final payable amount
    $total_payable = $total_amount + $security_deposit - $discount_amount;

    // ✅ Store values in session
    $_SESSION['property_id'] = $property_id;
    $_SESSION['booking_option'] = $booking_option;
    $_SESSION['total_amount'] = $total_amount;
    $_SESSION['discount_amount'] = $discount_amount;
    $_SESSION['security_deposit'] = $security_deposit;
    $_SESSION['total_payable'] = $total_payable;
    $_SESSION['actual_rent'] = $actual_rent;

    header("Location: personal_info.php?id=$property_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StayEase | Room Booking Options</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet" />
    <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css" />
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url("../assets/img/beams-home@95.jpg");
            background-size: cover;
            background-position: center;
        }

        .option-card {
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .option-card:hover:not(.disabled) {
            background-color: #f1f5f9;
        }

        .option-card.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .option-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .check-circle {
            display: none;
            background-color: #3b82f6;
            color: white;
            border-radius: 9999px;
            width: 20px;
            height: 20px;
            align-items: center;
            justify-content: center;
            margin-left: auto;
        }

        .option-card.selected .check-circle {
            display: flex;
        }
    </style>
</head>

<body class="min-h-screen flex justify-center items-center p-6">
    <div class="absolute left-6 top-6">
        <button onclick="history.back()" class="flex items-center text-blue-600 font-medium hover:underline">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-lg max-w-xl w-full border-[1.5px] border-gray-300 animate-fade-in">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 text-center">
                Choose Your Room Booking Option
            </h2>
        </div>

        <!-- Booking Options -->
        <form method="POST" class="p-6">
            <input type="hidden" name="property_id" value="<?= $property_id ?? 1; ?>">
            <input type="hidden" name="total_amount" id="total_amount">
            <input type="hidden" name="discount_amount" id="discount_amount">
            <input type="hidden" name="security_deposit" id="security_deposit">
            <input type="hidden" name="actual_rent" id="actual_rent">
            <input type="hidden" name="booking_option" id="booking_option" value="solely">

            <!-- Sole Booking -->
            <div id="sole-option" class="option-card relative bg-gray-50 p-4 rounded-md mb-4 cursor-pointer selected">
                <span
                    class="absolute top-0 right-0 bg-amber-100 text-amber-900 text-xs px-2 py-1 rounded-md border border-amber-200">
                    recommended
                </span>

                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 px-4 py-2.5 rounded-full">
                        <i class="fa-regular fa-user text-blue-700"></i>
                    </div>
                    <div>
                        <span class="block text-lg font-medium text-gray-900">Book Solely</span>
                        <p class="text-gray-700 text-sm mt-1">
                            Pay full rent & full security deposit.
                        </p>
                    </div>
                    <div class="check-circle">
                        <i class="fa-solid fa-check text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Shared Booking -->
            <div id="share-option"
                class="option-card relative bg-gray-50 p-4 rounded-md mb-4 cursor-pointer <?= $is_sharable ? '' : 'disabled' ?>">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 px-3 py-2.5 rounded-full">
                        <i class="fa-regular fa-user-group text-blue-700"></i>
                    </div>
                    <div>
                        <span class="block text-lg font-medium text-gray-900">Share Room</span>
                        <p class="text-gray-700 text-sm mt-1">
                            Split rent & security deposit 50-50.
                        </p>
                    </div>
                    <div class="check-circle">
                        <i class="fa-solid fa-check text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Promo Code Section -->
            <div class="mt-6">
                <label class="block text-gray-700 font-medium mb-2">Apply Promo Code:</label>
                <div class="flex">
                    <input type="text" id="promo-code"
                        class="w-full p-2 px-4 border border-gray-300 rounded-l-md shadow-sm focus:ring-blue-500 focus:outline-none focus:border-gray-500"
                        placeholder="Enter promo code" />
                    <button type="button" id="apply-code"
                        class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700 transition">
                        Apply
                    </button>
                </div>
                <div id="promo-message" class="hidden flex items-center gap-1 mt-2 text-sm"></div>
            </div>

            <!-- Rent Summary -->
            <div class="bg-blue-50 border-[1.5px] border-gray-300 p-5 rounded-lg mt-6 text-sm">
                <div class="flex justify-between items-center mb-2">
                    <p class="font-medium">Room Rent:</p>
                    <p class="font-semibold">₹<span id="rent"><?= $property_price ?></span></p>
                </div>

                <div class="flex justify-between items-center mb-2">
                    <p class="font-medium">Security Deposit (25% of Rent):</p>
                    <p class="font-semibold">₹<span id="deposit"><?= round($property_price * 0.25) ?></span></p>
                </div>

                <div class="flex justify-between items-center mb-2">
                    <p class="font-medium">Discount:</p>
                    <p class="font-semibold text-green-600">
                        -₹<span id="discount">0</span>
                    </p>
                </div>

                <div class="border-t-2 border-gray-300 mt-3 pt-3 flex justify-between items-center">
                    <p class="text-gray-800 font-semibold text-lg">Total Payable:</p>
                    <p class="text-blue-700 font-semibold text-xl">₹<span
                            id="total"><?= $property_price + round($property_price * 0.25) ?></span></p>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="confirm_booking"
                class="w-full mt-6 bg-blue-600 text-white py-2 rounded-lg text-lg font-medium hover:bg-blue-700 transition">
                Confirm Booking
            </button>
        </form>
    </div>

    <!-- JavaScript for Real-Time Calculation -->
    <script>
        // Base Rent and Security Deposit Percentage
        const fullRent = <?php echo $property_price; ?>;
        const securityPercentage = 0.25;
        const isSharable = <?php echo $is_sharable ? 'true' : 'false'; ?>;

        // Elements
        const rentEl = document.getElementById("rent");
        const depositEl = document.getElementById("deposit");
        const totalEl = document.getElementById("total");
        const discountEl = document.getElementById("discount");
        const totalAmountInput = document.getElementById("total_amount");
        const discountAmountInput = document.getElementById("discount_amount");
        const securityDepositInput = document.getElementById("security_deposit");
        const actualRentInput = document.getElementById("actual_rent");
        const bookingOptionInput = document.getElementById("booking_option");
        const soleOption = document.getElementById("sole-option");
        const shareOption = document.getElementById("share-option");
        const promoMessage = document.getElementById("promo-message");

        let discountAmount = 0;
        let currentOption = "solely";

        // Set initial values
        function calculateInitialValues() {
            rentEl.textContent = fullRent.toLocaleString();
            depositEl.textContent = Math.round(fullRent * securityPercentage).toLocaleString();
            totalEl.textContent = Math.round(fullRent + fullRent * securityPercentage).toLocaleString();
            totalAmountInput.value = Math.round(fullRent + fullRent * securityPercentage);
            securityDepositInput.value = Math.round(fullRent * securityPercentage);
            discountAmountInput.value = 0;
            actualRentInput.value = fullRent;
        }
        calculateInitialValues();

        // Function to update amounts dynamically
        function updateAmount(option) {
            if (option === "shared" && !isSharable) return;

            currentOption = option;
            bookingOptionInput.value = option;

            const rent = option === "solely" ? fullRent : Math.round(fullRent / 2);
            const deposit = Math.round(rent * securityPercentage);

            rentEl.textContent = rent.toLocaleString();
            depositEl.textContent = deposit.toLocaleString();
            totalEl.textContent = Math.max(0, rent + deposit - discountAmount).toLocaleString();

            totalAmountInput.value = Math.max(0, rent + deposit - discountAmount);
            securityDepositInput.value = deposit;
            actualRentInput.value = rent;

            // Update UI
            soleOption.classList.toggle("selected", option === "solely");
            shareOption.classList.toggle("selected", option === "shared");
        }

        // Booking Selection
        soleOption.addEventListener("click", () => updateAmount("solely"));

        if (isSharable) {
            shareOption.addEventListener("click", () => updateAmount("shared"));
        }

        // Promo Code Logic
        const promoCodes = {
            SAVE10: 10, // 10% discount
            FLAT500: 500 // Flat ₹500 discount
        };

        document.getElementById("apply-code").addEventListener("click", () => {
            const promoInput = document.getElementById("promo-code").value.trim().toUpperCase();

            if (promoCodes[promoInput]) {
                let currentTotal = parseInt(rentEl.textContent.replace(/,/g, '')) + parseInt(depositEl.textContent.replace(/,/g, ''));

                if (promoInput.startsWith("SAVE")) {
                    discountAmount = Math.round((parseInt(promoCodes[promoInput]) / 100) * currentTotal);
                } else {
                    discountAmount = promoCodes[promoInput];
                }

                discountEl.textContent = discountAmount.toLocaleString();
                promoMessage.innerHTML = `<i class="fa-solid fa-badge-check text-green-600 mr-1"></i> Promo code applied! You saved ₹${discountAmount.toLocaleString()}.`;
                promoMessage.classList.remove("hidden", "text-red-600");
                promoMessage.classList.add("text-green-600");

                discountAmountInput.value = discountAmount;
            } else {
                discountAmount = 0;
                discountEl.textContent = "0";
                promoMessage.innerHTML = `<i class="fa-solid fa-xmark text-red-600 mr-1"></i> Invalid promo code.`;
                promoMessage.classList.remove("hidden", "text-green-600");
                promoMessage.classList.add("text-red-600");

                discountAmountInput.value = 0;
            }

            const rent = parseInt(rentEl.textContent.replace(/,/g, ''));
            const deposit = parseInt(depositEl.textContent.replace(/,/g, ''));
            const total = Math.max(0, rent + deposit - discountAmount);

            totalEl.textContent = total.toLocaleString();
            totalAmountInput.value = total;
        });

        // Initialize form
        updateAmount("solely");
    </script>
</body>

</html>