<?php
session_start();

// Database connection
include '../Database/dbconfig.php';

$email_error = $password_error = ""; // Error message variables

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Capture and sanitize form data
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  // Validate input
  if (empty($email)) {
    $email_error = "Email is required";
  }
  if (empty($password)) {
    $password_error = "Password is required";
  }

  if (empty($email_error) && empty($password_error)) {
    // Prepare SQL query to fetch user data
    $sql = "SELECT userID, email, password FROM signup WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      // Fetch user data
      $user = $result->fetch_assoc();

      // Verify plain text password
      if ($password === $user['password']) {
        // Set session variables
        $_SESSION['user_logged_in'] = true;
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['user_email'] = $user['email'];

        // Redirect to home page
        header("Location: index.php");
        exit();
      } else {
        $password_error = "Incorrect password";
      }
    } else {
      $email_error = "No account found with that email";
    }
  }

  // Close statement and connection
  $stmt->close();
  $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <title>Login | StayEase</title>
  <style>
    body {
      background-image: url("../assets/img/beams-home@95.jpg");
      background-size: cover;
      background-position: center;
      font-family: "Poppins", sans-serif;
    }

    input[type="password"] {
      -webkit-appearance: none;
    }

    .loader {
      display: none;
      border: 2px solid transparent;
      border-top: 2px solid #fff;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    .otp-input {
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 4px;
      width: 40px;
      height: 40px;
      font-size: 18px;
    }

    #password-container,
    #submit-button {
      display: block;
    }

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
  </style>
  <script>
    function togglePassword(passwordFieldId, iconId) {
      const passwordField = document.getElementById(passwordFieldId);
      const icon = document.getElementById(iconId);

      if (passwordField.type === "password") {
        passwordField.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        passwordField.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }
  </script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen font-Nrj-fonts">
  <div class="bg-white p-8 rounded-lg shadow-lg w-96 border-[0.5px] border-gray-300">
    <div class="flex justify-center items-center space-x-1 mb-4">
      <div class="flex justify-center items-center space-x-1">
        <img src="../assets/img/stayease logo.svg" class="w-8 h-8" alt="" />
        <div class="h-[1.5px] w-7 bg-gray-500 rotate-90 rounded-full"></div>
      </div>
      <h2 class="text-2xl font-semibold text-center font-Nrj-fonts">Login</h2>
    </div>

    <div id="login-form">
      <form action="login.php" method="POST">
        <div class="mb-4">
          <label for="user-email" class="block text-sm font-medium text-gray-700 font-Nrj-fonts">Email</label>
          <div class="relative">
            <input type="email" id="user-email" name="email"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:outline-none focus:border-gray-500"
              placeholder="user@gmail.com" />
          </div>
          <!-- Display email error message -->
          <?php if (!empty($email_error)): ?>
            <p class="text-red-500 text-sm mt-2 font-Nrj-fonts">
              <?php echo $email_error; ?>
            </p>
          <?php endif; ?>
        </div>

        <div class="mb-4">
          <label for="password" class="block text-sm font-medium text-gray-700 font-Nrj-fonts">Password</label>
          <div class="relative">
            <input type="password" id="password" name="password"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:outline-none focus:border-gray-500"
              placeholder="Enter your password" required />
            <i id="password-icon" class="fas fa-eye absolute top-3 right-3 cursor-pointer opacity-80"
              onclick="togglePassword('password', 'password-icon')"></i>
          </div>
          <!-- Display password error message -->
          <?php if (!empty($password_error)): ?>
            <p class="text-red-500 text-sm mt-2 font-Nrj-fonts">
              <?php echo $password_error; ?>
            </p>
          <?php endif; ?>
          <a href="forgotpassword.php"
            class="mt-2 font-Nrj-fonts text-for text-sm hover:underline  block text-right">Forgot
            Password</a>
        </div>

        <button id="submit-button"
          class="w-full bg-blue-500 text-white font-semibold py-2 px-4 rounded-md hover:bg-blue-600 font-Nrj-fonts">
          Login
        </button>
      </form>
    </div>

    <div class="flex flex-row mt-4">
      <hr class="w-36 h-[2px] bg-gray-200 rounded-full mt-3" />
      <p class="font-Nrj-fonts font-medium text-gray-400 pl-2 pr-2">OR</p>
      <hr class="w-36 h-[2px] bg-gray-200 rounded-full mt-3" />
    </div>

    <p class="mt-4 text-center">
      <span class="text-black text-sm font-Nrj-fonts">Don't have an account?</span>
      <a href="signup.php" class="text-blue-500 hover:underline font-Nrj-fonts">Sign Up</a>
    </p>

    <div class="justify-center items-center flex mt-4">
      <a href="adminlogin.php" class="font-Nrj-fonts text-sm hover:text-for hover:underline">
        Are you Admin?
      </a>
    </div>
  </div>
</body>

</html>