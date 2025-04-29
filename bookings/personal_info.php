<?php
include '../Database/dbconfig.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
  die("Unauthorized: Please log in first.");
}

// Fetch the logged-in user's details
$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : null;
$email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : "Email not found.";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <title>StayEase | Personal Information</title>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Set min and max date for rentDate input
      const rentDateInput = document.getElementById("rentDate");
      if (rentDateInput) {
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 15);

        rentDateInput.min = today.toISOString().split("T")[0];
        rentDateInput.max = maxDate.toISOString().split("T")[0];
      }

      // Attach validation function on form submission
      const form = document.querySelector("form");
      if (form) {
        form.addEventListener("submit", validateForm);
      }
    });

    // Function to validate form
    function validateForm(event) {
      let valid = true;

      // Clear previous error messages
      document.querySelectorAll(".error-message").forEach((msg) => {
        msg.textContent = "";
      });

      // Form field values
      const formElements = {
        name: document.getElementById("name").value.trim(),
        phone: document.getElementById("phone").value.trim(),
        email: document.getElementById("email").value.trim(),
        file: document.getElementById("idProof").files[0],
        rentDate: document.getElementById("rentDate").value,
      };

      // Validation patterns
      const validations = {
        name: /^[a-zA-Z\s]{3,}$/, // At least 3 characters, only letters and spaces
        phone: /^[789]\d{9}$/, // Phone number starts with 7, 8, or 9 followed by 9 digits
        email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/, // Basic email validation
        image: /\.(png|jpe?g)$/i, // PNG or JPEG image file
      };

      // Validate name
      if (!validations.name.test(formElements.name)) {
        document.getElementById("name-error").textContent =
          "Name should be at least 3 characters long and contain only letters and spaces.";
        valid = false;
      }

      // Validate phone number
      if (!validations.phone.test(formElements.phone)) {
        document.getElementById("phone-error").textContent =
          "Phone number should be 10 digits and start with 7, 8, or 9.";
        valid = false;
      }

      // Validate email (if it's an editable field)
      if (document.getElementById("email") && !validations.email.test(formElements.email)) {
        document.getElementById("email-error").textContent =
          "Please enter a valid email address.";
        valid = false;
      }

      // Validate rent start date (must be a future date)
      const today = new Date().toISOString().split("T")[0];
      if (formElements.rentDate < today) {
        document.getElementById("rentDate-error").textContent =
          "Rent start date must be a future date.";
        valid = false;
      }

      // Validate file upload (ID proof)
      if (!formElements.file || !validations.image.test(formElements.file.name)) {
        document.getElementById("file-error").textContent =
          "Please upload an image file in PNG or JPEG format.";
        valid = false;
      }

      // If validation fails, prevent form submission
      if (!valid) {
        event.preventDefault();
      }
    }

    // Handle file selection
    function handleFileSelect(event) {
      event.preventDefault();
      const fileInput = document.getElementById("idProof");
      const file = event.target.files[0] || event.dataTransfer.files[0];

      if (file) {
        fileInput.files = event.target.files || event.dataTransfer.files;
        displayFileName(file);
      }
    }

    // Display selected file name
    function displayFileName(file) {
      const fileInfo = document.getElementById("file-info");
      fileInfo.textContent = file.name;

      const trashIcon = document.getElementById("trash-icon");
      trashIcon.style.display = "inline-block";
    }

    // Handle drag-and-drop events
    function handleDragOver(event) {
      event.preventDefault();
      event.stopPropagation();
      event.target.style.borderColor = "green";
    }

    function handleDragLeave(event) {
      event.preventDefault();
      event.stopPropagation();
      event.target.style.borderColor = "#ccc";
    }

    // Open file input on click
    function triggerFileInput() {
      document.getElementById("idProof").click();
    }

    // Cancel file selection
    function cancelFileSelection() {
      const fileInput = document.getElementById("idProof");
      fileInput.value = "";
      const fileInfo = document.getElementById("file-info");
      fileInfo.textContent = "";
      const trashIcon = document.getElementById("trash-icon");
      trashIcon.style.display = "none";
    }
  </script>

  <style>
    body {
      background-image: url("../assets/img/beams-home@95.jpg");
      background-size: cover;
      background-position: center;
    }

    .back-button {
      position: absolute;
      top: 16px;
      left: 16px;
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

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-8 font-Nrj-fonts">
  <!-- Back Button -->
  <div class="back-button">
    <button onclick="history.back()" class="flex items-center text-blue-600 font-medium hover:underline">
      <i class="fa-solid fa-arrow-left mr-2"></i> Back
    </button>
  </div>
  <div class="bg-white p-8 rounded-lg shadow-lg w-[550px] border-[0.5px] border-gray-300 animate-fade-in">
    <div class="flex justify-center items-center space-x-1 mb-4">
      <h2 class="text-xl font-semibold text-start text-gray-800">
        Enter Your Personal Information
      </h2>
    </div>

    <form action="process_personal_info.php" method="POST" enctype="multipart/form-data" onsubmit="validateForm(event)">
      <!-- Hidden field to store userID -->
      <input type="hidden" name="userID" value="<?php echo htmlspecialchars($userID); ?>">

      <div class="mb-4">
        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
        <input type="text" id="name" name="name"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-gray-300 focus:outline-none focus:border-gray-500"
          placeholder="Arun Verma" />
        <p id="name-error" class="text-red-500 text-xs error-message"></p>
      </div>

      <div class="mb-4">
        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
        <input type="text" id="phone" name="phone"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-gray-300 focus:outline-none focus:border-gray-500"
          placeholder="9856124732" />
        <p id="phone-error" class="text-red-500 text-xs error-message"></p>
      </div>

      <div class="mb-4">
        <label for="rentDate" class="block text-sm font-medium text-gray-700">Rent Start Date</label>
        <input type="date" id="rentDate" name="rentDate"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-0 focus:border-black focus:outline-none text-gray-400"
          min="" max="" />
        <p id="rentDate-error" class="text-red-500 text-xs error-message"></p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Gender</label>
        <div class="flex items-center space-x-4">
          <label class="inline-flex items-center">
            <input type="radio" name="gender" value="male" class="form-radio" />
            <span class="ml-2">Male</span>
          </label>
          <label class="inline-flex items-center">
            <input type="radio" name="gender" value="female" class="form-radio" />
            <span class="ml-2">Female</span>
          </label>
        </div>
      </div>

      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
          class="mt-1 block w-full px-3 py-2 border-[1.5px] border-gray-400 rounded-md shadow-sm 
     bg-gray-100 focus:outline-none cursor-default" readonly />

        <p id="email-error" class="text-red-500 text-xs error-message"></p>
      </div>
      <p class="text-xs text-gray-500 mt-1 mb-2"><span class="text-red-500 text-xs">note* </span>This email is
        automatically fetched and cannot be edited.</p>

      <div class="mb-4">
        <label for="idProof" class="block text-sm font-medium text-gray-700">Upload ID Proof</label>
        <div id="drop-area"
          class="border-2 border-dashed border-gray-300 p-5 text-center cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-md mt-2 group text-gray-500 hover:text-black hover:border-gray-400"
          ondrop="handleFileSelect(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)"
          onclick="triggerFileInput()">
          <i class="fa-regular fa-arrow-up-from-bracket mr-2 text-gray-500 group-hover:text-black"></i>
          Click to Select File
        </div>
        <input type="file" id="idProof" name="idProof" accept=".png, .jpeg, .jpg" class="hidden"
          onchange="handleFileSelect(event)" />
        <div class="flex flex-row justify-between items-center mt-2">
          <div id="file-info" class="mt-2 text-sm text-gray-600"></div>
          <i id="trash-icon"
            class="fa-solid fa-trash hidden text-red-400 text-sm cursor-pointer relative hover:text-red-600"
            onclick="cancelFileSelection()"></i>
        </div>
        <p id="file-error" class="text-red-500 text-xs error-message"></p>
      </div>
      <!-- <div class="summary">
        <h3>Stored Session Variables:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
      </div> -->
      <button type="submit"
        class="w-full bg-blue-600 text-white font-semibold py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
        Submit
      </button>
    </form>
  </div>
</body>

</html>