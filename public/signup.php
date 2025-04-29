<?php
// Database connection settings -- update these with your credentials
include '../Database/dbconfig.php';

if (isset($_POST["submit"])) {
  // Get form data and escape it to prevent SQL injection
  $email = $conn->real_escape_string($_POST['email']);
  $password = $conn->real_escape_string($_POST['password']);

  // Check if the email already exists in the database
  $checkEmailQuery = "SELECT * FROM `signup` WHERE `email` = '$email'";
  $result = $conn->query($checkEmailQuery);

  if ($result->num_rows > 0) {
    // Email exists, display error message
    echo "<script>alert('Email is already registered. Please use a different email.');</script>";
  } else {
    // SQL query to insert data
    $sql = "INSERT INTO `signup` (`email`, `password`) VALUES ('$email', '$password')";

    // Execute query and handle the result
    if ($conn->query($sql) === TRUE) {
      // Redirect to login page after successful signup
      echo "<script>alert('Signup successful. Redirecting you to the Login Page.'); window.location.href = 'login.php';</script>";
    } else {
      echo "<script>alert('Failed: " . $sql . "<br />Error: " . $conn->error . "');</script>";
    }
  }

  // Close the connection
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <!-- External CSS and Fonts -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Page Title -->
  <title>Sign Up | StayEase</title>

  <style>
    body {
      background-image: url("../assets/img/beams-home@95.jpg");
      background-size: cover;
      background-position: center;
      font-family: "Poppins", sans-serif;
    }

    /* Hide password section by default */
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

    /* Modal animation */
    .modal-enter {
      opacity: 0;
      transform: scale(0.95);
    }

    .modal-enter-active {
      opacity: 1;
      transform: scale(1);
      transition: opacity 300ms, transform 300ms;
    }

    .modal-exit {
      opacity: 1;
      transform: scale(1);
    }

    .modal-exit-active {
      opacity: 0;
      transform: scale(0.95);
      transition: opacity 300ms, transform 300ms;
    }

    /* Email highlight style */
    .email-highlight {
      font-weight: 600;
      color: #3b82f6;
    }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/emailjs-com@3.2.0/dist/email.min.js"></script>
  <script>
    emailjs.init("Ei7nopPNhgExd9OLX");

    let emailVerified = false;

    function showModal() {
      const modal = document.getElementById('success-modal');
      const modalContent = document.getElementById('modal-content');

      // Show the modal
      modal.classList.remove('hidden');

      // Add a small delay before adding the animation classes
      setTimeout(() => {
        modalContent.classList.remove('modal-enter');
        modalContent.classList.add('modal-enter-active');
      }, 10);
    }

    function closeModal() {
      const modal = document.getElementById('success-modal');
      const modalContent = document.getElementById('modal-content');

      // Start exit animation
      modalContent.classList.remove('modal-enter-active');
      modalContent.classList.add('modal-exit-active');

      // Hide the modal after animation completes
      setTimeout(() => {
        modal.classList.add('hidden');
        modalContent.classList.remove('modal-exit-active');
        modalContent.classList.add('modal-enter');
      }, 300);
    }

    function sendOTP() {
      const email = document.getElementById("email").value;
      const emailError = document.getElementById("email-error");
      const sendOtpButton = document.getElementById("send-otp-button");
      const spinner = document.getElementById("spinner");
      const buttonText = document.getElementById("button-text");

      emailError.textContent = "";

      const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      if (!emailPattern.test(email)) {
        emailError.textContent = "Invalid email format.";
        return;
      }

      buttonText.textContent = "Sending...";
      spinner.classList.remove("hidden");
      sendOtpButton.disabled = true;

      const otp = Math.floor(1000 + Math.random() * 9000);
      sessionStorage.setItem("otp", otp);

      emailjs
        .send("service_huwqanr", "template_2zvq02l", {
          to_email: email,
          message: `Your OTP is ${otp}`,
        })
        .then(() => {
          // Update modal message with the user's email
          document.getElementById('modal-message').innerHTML =
            `We've sent a 4-digit OTP to <span class="email-highlight">${email}</span>. Please check your inbox and enter the code to verify.`;

          // Show the modal
          showModal();
          document.getElementById("otp-section").style.display = "block";
          sendOtpButton.style.display = "none";
          document.getElementById("email").readOnly = true;
        })
        .catch(() => {
          emailError.textContent = "Failed to send OTP. Try again.";
        })
        .finally(() => {
          buttonText.textContent = "Send OTP";
          spinner.classList.add("hidden");
          sendOtpButton.disabled = false;
        });
    }

    function verifyOTP() {
      const otpInputs = document.querySelectorAll(".otp-input");
      const enteredOTP = Array.from(otpInputs)
        .map((input) => input.value)
        .join("");
      const storedOTP = sessionStorage.getItem("otp");
      const email = document.getElementById("email").value;

      if (enteredOTP === storedOTP) {
        // Show success modal instead of alert
        document.getElementById('modal-title').textContent = "Email Verified Successfully!";
        document.getElementById('modal-message').innerHTML =
          `Your email <span class="email-highlight">${email}</span> has been verified. Please set your password to continue.`;
        document.getElementById('modal-icon').innerHTML = '<svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
        document.getElementById('modal-icon-bg').classList.replace('bg-red-100', 'bg-green-100');
        showModal();

        emailVerified = true;
        document.getElementById("otp-section").style.display = "none";
        document.getElementById("password-section").style.display = "block";
      } else {
        // Show error modal instead of alert
        document.getElementById('modal-title').textContent = "Invalid OTP";
        document.getElementById('modal-message').textContent = "The OTP you entered is incorrect. Please try again.";
        document.getElementById('modal-icon').innerHTML = '<svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        document.getElementById('modal-icon-bg').classList.replace('bg-green-100', 'bg-red-100');
        showModal();
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

<body class="bg-gray-100 flex justify-center items-center min-h-screen font-Nrj-fonts">
  <div class="bg-white p-8 rounded-md shadow-lg w-96 border-[0.5px] border-gray-300">
    <div class="flex justify-center items-center space-x-1 mb-4">
      <div class="flex justify-center items-center space-x-1">
        <img src="../assets/img/stayease logo.svg" class="w-8 h-8" alt="" />
        <div class="h-[1.5px] w-7 bg-gray-500 rotate-90 rounded-full"></div>
      </div>
      <h2 class="text-2xl font-semibold text-center font-Nrj-fonts">Sign Up</h2>
    </div>

    <!-- Change the form action to the current file (signup.php) -->
    <form method="post" action="signup.php" onsubmit="return validatePasswords()">
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="email" name="email" required
          class="w-full px-2 py-2 mt-2 border border-gray-300 rounded-md focus:outline-none focus:ring-[0.5px] focus:ring-black"
          placeholder="Enter your email" />
        <p id="email-error" class="text-red-500 text-xs mt-2"></p>
      </div>

      <button type="button" id="send-otp-button" onclick="sendOTP()"
        class="w-full bg-blue-500 text-white py-2 rounded-lg font-semibold hover:bg-blue-600 flex justify-center items-center">
        <span id="button-text">Send OTP</span>
        <div id="spinner" class="hidden ml-2 spinner"></div>
      </button>

      <div class="flex flex-row mt-4">
        <hr class="w-36 h-[2px] bg-gray-200 rounded-full mt-3" />
        <p class="font-Nrj-fonts font-medium text-gray-400 pl-2 pr-2">OR</p>
        <hr class="w-36 h-[2px] bg-gray-200 rounded-full mt-3" />
      </div>

      <p class="mt-4 text-center">
        <span class="text-black text-sm font-Nrj-fonts">Already have an account?</span>
        <a href="login.php" class="text-blue-500 hover:underline font-Nrj-fonts">Log in</a>
      </p>

      <div id="otp-section" style="display: none" class="mt-4">
        <label class="block text-sm font-medium text-gray-700">Enter OTP</label>
        <div class="flex justify-center mt-2 space-x-2">
          <input type="text" maxlength="1"
            class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
            oninput="nextInput(this)" />
          <input type="text" maxlength="1"
            class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
            oninput="nextInput(this)" />
          <input type="text" maxlength="1"
            class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
            oninput="nextInput(this)" />
          <input type="text" maxlength="1"
            class="otp-input w-12 h-12 text-xl text-center border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
            oninput="nextInput(this)" />
        </div>

        <button type="button" onclick="verifyOTP()"
          class="w-full bg-green-500 text-white py-2 mt-4 rounded-lg hover:bg-green-600">
          Verify OTP
        </button>
      </div>

      <div id="password-section">
        <div class="mt-6">
          <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
                placeholder="Enter your password" minlength="6" required oninput="validatePasswords()" />
              <i id="password-icon" class="fas fa-eye absolute top-3 right-3 cursor-pointer opacity-80"
                onclick="togglePasswordVisibility('password', 'password-icon')"></i>
            </div>
          </div>

          <div class="mb-4">
            <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <div class="relative">
              <input type="password" id="confirm-password" name="confirm_password"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-[0.5px] focus:ring-black"
                placeholder="Confirm your password" minlength="6" required oninput="validatePasswords()" />
              <i id="confirm-password-icon" class="fas fa-eye absolute top-3 right-3 cursor-pointer opacity-80"
                onclick="togglePasswordVisibility('confirm-password', 'confirm-password-icon')"></i>
            </div>
            <p id="password-error" class="text-red-500 text-xs mt-2"></p>
          </div>
        </div>

        <button type="submit" id="submit" name="submit"
          class="w-full bg-blue-500 text-white py-2 rounded-lg font-semibold hover:bg-blue-600">
          Sign Up
        </button>
      </div>
    </form>
  </div>

  <!-- Success Modal -->
  <div id="success-modal"
    class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden flex items-center justify-center">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

    <!-- Modal Content -->
    <div id="modal-content" class="relative bg-white rounded-lg shadow-xl max-w-md mx-auto p-6 modal-enter">
      <div class="text-center">
        <!-- Success Icon -->
        <div id="modal-icon-bg"
          class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
          <div id="modal-icon">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
        </div>

        <!-- Modal Title -->
        <h3 id="modal-title" class="text-lg font-medium text-gray-900 mb-2">OTP Sent Successfully!</h3>

        <!-- Modal Message -->
        <p id="modal-message" class="text-sm text-gray-500 mb-5">
          We've sent a 4-digit OTP to your email address. Please check your inbox and enter the code to verify.
        </p>

        <!-- Continue Button -->
        <button type="button" onclick="closeModal()"
          class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-500 text-base font-medium text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm transition duration-200">
          Continue
        </button>
      </div>
    </div>
  </div>
</body>

</html>