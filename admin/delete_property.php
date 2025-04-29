<?php
// Database connection
include '../Database/dbconfig.php';

$message = "";
$messageType = ""; // "success" or "error"

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the property
    $sql = "DELETE FROM properties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Property deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting property!";
        $messageType = "error";
    }
    $stmt->close();
} else {
    $message = "Invalid property ID!";
    $messageType = "error";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Delete</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Apply Poppins font */
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Custom animation for the popup */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <?php if ($messageType === "success"): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-5 rounded-md shadow-md animate-fadeInUp">
            <div class="flex items-center">
                <span class="text-green-700">
                    <i class="fa-solid fa-check-circle fa-3x"></i>
                </span>
                <div class="ml-4">
                    <h2 class="text-2xl font-semibold">Success!</h2>
                    <p class="text-lg"><?= htmlspecialchars($message) ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-5 rounded-3xl shadow-lg animate-fadeInUp">
            <div class="flex items-center">
                <span class="text-red-700">
                    <i class="fa-solid fa-times-circle fa-3x"></i>
                </span>
                <div class="ml-4">
                    <h2 class="text-2xl font-semibold">Error!</h2>
                    <p class="text-lg"><?= htmlspecialchars($message) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <script>
        // After 2.5 seconds, redirect to dashboard.php
        setTimeout(function () {
            window.location.href = "main.php";
        }, 2500);
    </script>
</body>

</html>