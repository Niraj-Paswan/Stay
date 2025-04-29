<?php
// Start session to allow for any session data if needed.
session_start();

// Initialize variables for error and success messages.
$emailErrorMsg = $passwordErrorMsg = $serverMsg = "";

// Process the form submission only if the request method is POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form values.
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $new_password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Check if passwords match.
    if ($new_password !== $confirm_password) {
        $passwordErrorMsg = "Passwords do not match.";
    } else {
      include '../Database/dbconfig.php';

        // Check if the email exists in the "signup" table.
        $stmt = $conn->prepare("SELECT userID FROM signup WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Email exists, so update the password (plain text).
            $update_stmt = $conn->prepare("UPDATE signup SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $new_password, $email);

            if ($update_stmt->execute()) {
                $serverMsg = "Password updated successfully!";
            } else {
                $serverMsg = "Failed to update password. Please try again later.";
            }

            $update_stmt->close();
        } else {
            // Email is not registered.
            $serverMsg = "The email address is not registered.";
        }

        // Close the statements and the connection.
        $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="shortcut icon"
      href="../assets/img/stayease logo.svg"
      type="image/x-icon"
    />
    <!-- External CSS and Fonts -->
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../assets/css/styles.css" />

    <!-- Page Title -->
    <title>Forgot Password | StayEase</title>

    <style>
      body {
        background-image: url("../assets/img/beams-home@95.jpg");
        background-size: cover;
        background-position: center;
        font-family: "Poppins", sans-serif;
      }
      /* Hide password reset section by default */
      #password-section {
        display: none;
      }
      /* Password eye icon fixes */
      input[type="password"]::-ms-reveal,
      input[type="password"]::-ms-clear {
        display: none;
      }
      input[type="password"]::-webkit-credentials-auto-fill-button {
        display: none;
      }
      input[type="password"] {
        -webkit-appearance: none;
      }
      /* Spinner styles */
      .hidden {
        display: none;
      }
      .spinner {
        width: 1rem;
        height: 1rem;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }
      @keyframes spin {
        from {
          transform: rotate(0deg);
        }
        to {
          transform: rotate(360deg);
        }
      }
      /* Error message styling */
      .error-text {
        color: red;
        font-size: 0.875rem;
        margin-top: 0.25rem;
      }
      /* Server message styling */
      .server-msg {
        text-align: center;
        padding: 0.5rem;
        margin-bottom: 1rem;
        border-radius: 4px;
      }
      .server-msg.success {
        background-color: #d4edda;
        color: #155724;
      }
      .server-msg.error {
        background-color: #f8d7da;
        color: #721c24;
      }
    </style>

    <!-- Include EmailJS -->
    <script src="https://cdn.jsdelivr.net/npm/emailjs-com@3.2.0/dist/email.min.js"></script>
    <script>
      emailjs.init("Ei7nopPNhgExd9OLX");

      let emailVerified = false;

      function sendOTP() {
        const email = document.getElementById("email").value;
        const emailError = document.getElementById("email-error");
        const sendOtpButton = document.getElementById("send-otp-button");
        const spinner = document.getElementById("spinner");
        const buttonText = document.getElementById("button-text");

        // Clear previous email error message.
        emailError.textContent = "";

        // Validate email format.
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(email)) {
          emailError.textContent = "Invalid email format.";
          return;
        }

        buttonText.textContent = "Sending...";
        spinner.classList.remove("hidden");
        sendOtpButton.disabled = true;

        // Generate a 4-digit OTP and store it in sessionStorage (for demo purposes).
        const otp = Math.floor(1000 + Math.random() * 9000);
        sessionStorage.setItem("otp", otp);

        // Send the OTP via EmailJS. Adjust service/template IDs as needed.
        emailjs
          .send("service_huwqanr", "template_2zvq02l", {
            to_email: email,
            message: `Your OTP for password reset is ${otp}`,
          })
          .then(() => {
            // Optionally, clear any previous OTP errors.
            document.getElementById("otp-error").textContent = "";
            // Show the OTP input section.
            document.getElementById("otp-section").style.display = "block";
            sendOtpButton.style.display = "none";
            document.getElementById("email").readOnly = true;
          })
          .catch(() => {
            emailError.textContent = "Failed to send OTP. Please try again.";
          })
          .finally(() => {
            buttonText.textContent = "Send OTP";
            spinner.classList.add("hidden");
          });
      }

      function verifyOTP() {
        // Clear any previous OTP error.
        const otpError = document.getElementById("otp-error");
        otpError.textContent = "";

        // Collect the OTP digits from the input fields.
        const otpInputs = document.querySelectorAll(".otp-input");
        const enteredOTP = Array.from(otpInputs)
          .map((input) => input.value)
          .join("");
        const storedOTP = sessionStorage.getItem("otp");

        if (enteredOTP === storedOTP) {
          // OTP verified successfully.
          otpError.textContent = "";
          emailVerified = true;
          // Hide OTP section and display the password reset section.
          document.getElementById("otp-section").style.display = "none";
          document.getElementById("password-section").style.display = "block";
        } else {
          otpError.textContent = "Invalid OTP. Please try again.";
        }
      }

      function togglePasswordVisibility(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
          passwordInput.type = "password";
          icon.classList.replace("fa-eye-slash", "fa-eye");
        }
      }

      function validatePasswords() {
        const password = document.getElementById("password").value;
        const confirmPassword =
          document.getElementById("confirm-password").value;
        const passwordError = document.getElementById("password-error");

        if (password !== confirmPassword) {
          passwordError.textContent = "Passwords do not match.";
          return false;
        } else {
          passwordError.textContent = "";
          return true;
        }
      }

      // Auto-focus on next OTP input field.
      function nextInput(current) {
        if (current.value.length === 1) {
          const next = current.nextElementSibling;
          if (next && next.classList.contains("otp-input")) {
            next.focus();
          }
        }
      }
    </script>
  </head>
  <body class="bg-gray-100 flex justify-center items-center min-h-screen">
  <div class="p-4 absolute left-2 top-2">
        <button onclick="history.back()" class="flex items-center text-blue-600 font-medium hover:underline">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </button>
    </div>
    <div
class="bg-white p-8 rounded-md shadow-lg w-96 border-[0.5px] border-gray-300"
    >
    <div class="flex justify-center items-center space-x-1 mb-4">
        <div class="flex justify-center items-center space-x-1">
          <img src="../assets/img/stayease logo.svg" class="w-8 h-8" alt="" />
          <div class="h-[1.5px] w-7 bg-gray-500 rotate-90 rounded-full"></div>
        </div>
        <h2 class="text-2xl font-semibold text-center font-Nrj-fonts">Forgot Password</h2>
      </div>

      <!-- Display any server message from PHP if available -->
      <?php if (!empty($serverMsg)): ?>
      <div
        class="server-msg <?php echo (strpos($serverMsg, 'successfully') !== false) ? 'success' : 'error'; ?>"
      >
        <?php echo htmlspecialchars($serverMsg); ?>
      </div>
      <?php endif; ?>

      <!-- Forgot Password Form -->
      <!-- The form submits to the same page so the PHP code can process it -->
      <form method="post" action="" onsubmit="return validatePasswords()">
        <!-- Email Section -->
        <div class="mb-4">
          <label for="email" class="block text-sm font-medium text-gray-700"
            >Email</label
          >
          <input type="email" id="email" name="email" required class="w-full
          px-2 py-2 mt-2 border border-gray-300 rounded-lg focus:outline-none
          focus:ring-[0.5px] focus:ring-black" placeholder="Enter your email"
          <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') echo 'readonly'; ?>
          />
          <p id="email-error" class="error-text">
            <?php echo htmlspecialchars($emailErrorMsg); ?>
          </p>
        </div>

        <button
          type="button"
          id="send-otp-button"
          onclick="sendOTP()"
          class="w-full bg-blue-500 text-white py-2 rounded-md font-semibold hover:bg-blue-600 flex justify-center items-center"
        >
          <span id="button-text">Send OTP</span>
          <div id="spinner" class="hidden ml-2 spinner"></div>
        </button>

        <!-- OTP Section -->
        <div id="otp-section" style="display: none" class="mt-4">
          <label class="block text-sm font-medium text-gray-700"
            >Enter OTP</label
          >
          <div class="flex justify-center mt-2 space-x-2">
            <input
              type="text"
              maxlength="1"
              class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
              oninput="nextInput(this)"
            />
            <input
              type="text"
              maxlength="1"
              class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
              oninput="nextInput(this)"
            />
            <input
              type="text"
              maxlength="1"
              class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
              oninput="nextInput(this)"
            />
            <input
              type="text"
              maxlength="1"
              class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
              oninput="nextInput(this)"
            />
          </div>
          <p id="otp-error" class="error-text"></p>
          <button
            type="button"
            onclick="verifyOTP()"
            class="w-full bg-green-500 text-white py-2 mt-4 rounded-lg hover:bg-green-600"
          >
            Verify OTP
          </button>
        </div>

        <!-- Password Reset Section -->
        <div id="password-section">
          <div class="mt-6">
            <div class="mb-4">
              <label
                for="password"
                class="block text-sm font-medium text-gray-700"
                >New Password</label
              >
              <div class="relative">
                <input
                  type="password"
                  id="password"
                  name="password"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
                  placeholder="Enter your new password"
                  minlength="6"
                  required
                  oninput="validatePasswords()"
                />
                <i
                  id="password-icon"
                  class="fas fa-eye absolute top-3 right-3 cursor-pointer opacity-80"
                  onclick="togglePasswordVisibility('password', 'password-icon')"
                ></i>
              </div>
            </div>

            <div class="mb-4">
              <label
                for="confirm-password"
                class="block text-sm font-medium text-gray-700"
                >Confirm New Password</label
              >
              <div class="relative">
                <input
                  type="password"
                  id="confirm-password"
                  name="confirm_password"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
                  placeholder="Confirm your new password"
                  minlength="6"
                  required
                  oninput="validatePasswords()"
                />
                <i
                  id="confirm-password-icon"
                  class="fas fa-eye absolute top-3 right-3 cursor-pointer opacity-80"
                  onclick="togglePasswordVisibility('confirm-password', 'confirm-password-icon')"
                ></i>
              </div>
            </div>
            <p id="password-error" class="error-text">
              <?php echo htmlspecialchars($passwordErrorMsg); ?>
            </p>
          </div>

          <button
            id="submit-button"
            type="submit"
            class="w-full bg-blue-500 text-white font-semibold py-2 px-4 rounded-md hover:bg-blue-600"
          >
            Reset Password
          </button>
        </div>
      </form>
    </div>
  </body>
</html>
