<?php
// Database connection settings
include '../Database/dbconfig.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $query_id = rand(1000, 9999); // Generate a random 4-digit ID
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        die("All fields are required.");
    }

    // Prepare SQL query to insert data
    $stmt = $conn->prepare("INSERT INTO user_queries (query_id, name, email, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $query_id, $name, $email, $message);

    // HTML structure after form submission
    if ($stmt->execute()) {
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>StayEase | Success</title>
            <link rel="stylesheet" href="../assets/css/styles.css" />
            <link rel="shortcut icon" href="../assets/img/stayease_logo.svg" type="image/x-icon" />
            <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css" />
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
            <style>
                body {
                    background-image: url("../assets/img/beams-home@95.jpg");
                    background-size: cover;
                    background-position: center;
                }
            </style>
        </head>

        <body class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="bg-white shadow-md rounded-md p-8 w-[400px] text-center border-[1.5px] border-gray-300">
                <i class="fa-solid fa-circle-check text-green-600 text-4xl"></i>
                <h1 class="text-2xl font-bold text-gray-800 mt-4">Success!</h1>
                <p class="text-gray-600 mt-2">Your query has been submitted successfully.</p>
                <p class="text-gray-700 mt-1 font-semibold">Query ID: <span
                        class="text-blue-600"><?php echo $query_id; ?></span></p>
                <a href="../public/index.php"
                    class="mt-6 inline-block px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition duration-200">
                    Go to Home
                </a>
            </div>
        </body>

        </html>
        <?php
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>