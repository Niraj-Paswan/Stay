<?php
session_start();

// Database connection
include '../Database/dbconfig.php';

// Initialize error variables
$admin_id_error = "";
$password_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = trim($_POST["adminid"]);
    $password = trim($_POST["password"]);

    // Validate inputs
    if (!empty($admin_id) && !empty($password)) {
        // Fetch admin credentials from the database
        $sql = "SELECT * FROM admin_info WHERE adminid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();

            // Verify password (If hashed, use password_verify)
            if ($password === $admin["password"]) {
                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_id"] = $admin["adminid"];
                header("Location: ../admin/main.php"); // Redirect to admin dashboard
                exit();
            } else {
                $password_error = "Invalid password!";
            }
        } else {
            $admin_id_error = "Invalid Admin ID!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StayEase | Admin Login</title>

    <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
    <link href="../assets/css/styles.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />

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

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none;
        }

        .error {
            color: red;
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen font-Nrj-fonts">
    <div class="p-4 absolute left-2 top-2">
        <button onclick="history.back()" class="flex items-center text-blue-600 font-medium hover:underline">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back
        </button>
    </div>
    <div class="bg-white p-8 rounded-lg shadow-md border border-gray-300 w-96">
        <div class="flex justify-center items-center space-x-1 mb-4">
            <div class="flex justify-center items-center space-x-1">
                <img src="../assets/img/stayease logo.svg" class="w-8 h-8" alt="StayEase Logo" />
                <div class="h-[1.5px] w-7 bg-gray-500 rotate-90 rounded-full"></div>
            </div>
            <h2 class="text-2xl font-semibold text-center font-Nrj-fonts">Admin Login</h2>
        </div>

        <!-- Login Form -->
        <form action="adminlogin.php" method="POST">
            <!-- Admin ID Input -->
            <div class="mb-2">
                <label for="admin-id" class="block text-sm font-medium text-gray-700 font-Nrj-fonts">Admin ID</label>
                <div class="relative">
                    <input type="text" id="admin-id" name="adminid"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:outline-none focus:border-gray-500"
                        required />
                </div>
                <?php if (!empty($admin_id_error)) { ?>
                    <p class="error"><?php echo $admin_id_error; ?></p>
                <?php } ?>
            </div>

            <!-- Password Input -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 font-Nrj-fonts">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:outline-none focus:border-gray-500"
                        placeholder="Enter your password" required />
                    <i id="password-icon" class="fas fa-eye absolute top-3 right-3 text-gray-700 cursor-pointer"
                        onclick="togglePassword('password', 'password-icon')"></i>
                </div>
                <?php if (!empty($password_error)) { ?>
                    <p class="error"><?php echo $password_error; ?></p>
                <?php } ?>
            </div>

            <!-- Login Button -->
            <button type="submit"
                class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600 transition-colors font-semibold">Login</button>
        </form>
    </div>

    <!-- Password Toggle Script -->
    <script>
        function togglePassword(passwordFieldId, iconId) {
            const passwordField = document.getElementById(passwordFieldId);
            const icon = document.getElementById(iconId);

            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
</body>

</html>