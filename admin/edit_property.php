<?php
// Database connection
include '../Database/dbconfig.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form
    $id = $_POST['id'];
    $property_name = $_POST['property_name'];
    $property_location = $_POST['property_location'];
    $property_price = $_POST['property_price'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $property_type = $_POST['property_type'];
    $bathrooms = $_POST['bathrooms'];
    $bedrooms = $_POST['bedrooms'];
    $area = $_POST['area'];

    // Fetch existing images
    $sql = "SELECT main_image, kitchen_img, washroom_img, gallery_img FROM properties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existing_images = $stmt->get_result()->fetch_assoc();

    // Image upload handling
    $image_columns = ['main_image', 'kitchen_img', 'washroom_img', 'gallery_img'];
    $upload_dir = "../public/";
    $new_images = [];

    foreach ($image_columns as $column) {
        if (!empty($_FILES[$column]['name'])) {
            $image_name = time() . "_" . basename($_FILES[$column]['name']);
            $target_file = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES[$column]['tmp_name'], $target_file)) {
                // Delete old image if exists
                if (!empty($existing_images[$column]) && file_exists($upload_dir . $existing_images[$column])) {
                    unlink($upload_dir . $existing_images[$column]);
                }
                $new_images[$column] = $image_name;
            }
        } else {
            // No new file uploaded; keep the old image
            $new_images[$column] = $existing_images[$column];
        }
    }
    $is_sharable = isset($_POST['is_sharable']) ? 1 : 0;

    // Update query
    $sql = "UPDATE properties SET 
        property_name=?, property_location=?, property_price=?, 
        latitude=?, longitude=?, property_type=?, 
        bathrooms=?, bedrooms=?, area=?, is_sharable=?, 
        main_image=?, kitchen_img=?, washroom_img=?, gallery_img=? 
        WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssisssiiiissssi",
        $property_name,
        $property_location,
        $property_price,
        $latitude,
        $longitude,
        $property_type,
        $bathrooms,
        $bedrooms,
        $area,
        $is_sharable,
        $new_images['main_image'],
        $new_images['kitchen_img'],
        $new_images['washroom_img'],
        $new_images['gallery_img'],
        $id
    );

    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('Property updated successfully!', 'success');
                setTimeout(function() {
                    window.location.href='dashboard.php';
                }, 2000);
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('Error updating property: " . $conn->error . "', 'error');
            });
        </script>";
    }
}

// Fetch property details if ID is provided in URL
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid property ID.");
}

$sql = "SELECT * FROM properties WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    die("Property not found.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Property | StayEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Heroicons (for icons) -->
    <script src="https://unpkg.com/heroicons@2.0.18/dist/heroicons.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .notification.success {
            background-color: #10b981;
        }

        .notification.error {
            background-color: #ef4444;
        }

        .image-upload-container {
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .image-upload-container:hover {
            border-color: #10b981;
        }

        .drag-over {
            border-color: #10b981 !important;
            background-color: #d1fae5 !important;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .loading-overlay.show {
            visibility: visible;
            opacity: 1;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #10b981;
            border-radius: 50%;
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
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <div class="min-h-screen py-8 px-4 md:px-8">
        <div class="max-w-5xl mx-auto">
            <!-- Header with Back Button -->
            <div class="flex items-center mb-6">
                <a href="dashboard.php"
                    class="flex items-center text-gray-600 hover:text-emerald-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to Dashboard</span>
                </a>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-800 px-6 py-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-white text-center">Edit Property</h1>
                </div>

                <!-- Form Content -->
                <div class="p-6 md:p-8">
                    <form id="propertyForm" method="POST" enctype="multipart/form-data" class="space-y-8">
                        <input type="hidden" name="id" value="<?php echo $property['id']; ?>">

                        <!-- Basic Information Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-700" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Basic Information
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="property_name" class="block text-gray-700 font-medium mb-2">Property
                                        Name</label>
                                    <input type="text" id="property_name" name="property_name"
                                        value="<?php echo $property['property_name']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                                <div>
                                    <label for="property_location"
                                        class="block text-gray-700 font-medium mb-2">Location</label>
                                    <input type="text" id="property_location" name="property_location"
                                        value="<?php echo $property['property_location']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none  focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                                <div>
                                    <label for="property_price" class="block text-gray-700 font-medium mb-2">Price
                                    </label>
                                    <input type="number" id="property_price" name="property_price"
                                        value="<?php echo $property['property_price']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                                <div>
                                    <label for="property_type" class="block text-gray-700 font-medium mb-2">Property
                                        Type</label>
                                    <input type="text" id="property_type" name="property_type"
                                        value="<?php echo $property['property_type']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        <!-- Location Coordinates Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-700" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Map Coordinates
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="latitude" class="block text-gray-700 font-medium mb-2">Latitude</label>
                                    <input type="text" id="latitude" name="latitude"
                                        value="<?php echo $property['latitude']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                                <div>
                                    <label for="longitude"
                                        class="block text-gray-700 font-medium mb-2">Longitude</label>
                                    <input type="text" id="longitude" name="longitude"
                                        value="<?php echo $property['longitude']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        <!-- Property Images Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-700" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Property Images
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php
                                $images = [
                                    'main_image' => 'Main Image',
                                    'kitchen_img' => 'Kitchen Image',
                                    'washroom_img' => 'Washroom Image',
                                    'gallery_img' => 'Gallery Image'
                                ];
                                foreach ($images as $column => $label) {
                                    ?>
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 image-upload-container"
                                        data-field="<?php echo $column; ?>">
                                        <label class="block text-gray-700 font-medium mb-3"><?php echo $label; ?></label>
                                        <div class="flex flex-col sm:flex-row items-center gap-4">
                                            <!-- Current Image -->
                                            <div class="text-center">
                                                <span class="text-sm text-gray-600 block mb-1">Current</span>
                                                <div class="w-32 h-32 overflow-hidden rounded-md border border-gray-200">
                                                    <img src="../public/<?php echo $property[$column]; ?>"
                                                        alt="<?php echo $label; ?>" class="w-full h-full object-cover"
                                                        id="current-<?php echo $column; ?>">
                                                </div>
                                            </div>

                                            <!-- New Image Preview -->
                                            <div class="text-center">
                                                <span class="text-sm text-gray-600 block mb-1">New</span>
                                                <div
                                                    class="w-32 h-32 overflow-hidden rounded-md border border-gray-200 relative">
                                                    <img src="https://via.placeholder.com/150?text=No+Image"
                                                        alt="New <?php echo $label; ?>" class="w-full h-full object-cover"
                                                        id="preview-<?php echo $column; ?>">
                                                    <button type="button"
                                                        class="absolute top-1 right-1 bg-white rounded-full p-1 shadow-md hover:bg-gray-100 hidden clear-image-btn"
                                                        onclick="clearImage('<?php echo $column; ?>')">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload Area -->
                                        <div class="mt-4 border-2 border-dashed border-gray-300 rounded-md p-4 text-center cursor-pointer hover:border-blue-700 transition-colors upload-area"
                                            onclick="document.getElementById('file-<?php echo $column; ?>').click()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-gray-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            <p class="text-sm text-gray-500 mt-2">
                                                Drag & drop or click to upload
                                            </p>
                                            <input type="file" id="file-<?php echo $column; ?>"
                                                name="<?php echo $column; ?>" accept="image/*" class="hidden file-input"
                                                onchange="previewImage(this, '<?php echo $column; ?>')">
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        <!-- Property Features Section -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-700" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                Property Features
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="bathrooms"
                                        class="block text-gray-700 font-medium mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-blue-700"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 10H3m0 0v8a2 2 0 002 2h14a2 2 0 002-2v-8M3 10l1-3h16l1 3" />
                                        </svg>
                                        Bathrooms
                                    </label>
                                    <input type="number" id="bathrooms" name="bathrooms"
                                        value="<?php echo $property['bathrooms']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                                <div>
                                    <label for="bedrooms"
                                        class="block text-gray-700 font-medium mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-blue-700"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Bedrooms
                                    </label>
                                    <input type="number" id="bedrooms" name="bedrooms"
                                        value="<?php echo $property['bedrooms']; ?>" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                                <div>
                                    <label for="area" class="block text-gray-700 font-medium mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-blue-700"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
                                        </svg>
                                        Area (sqft)
                                    </label>
                                    <input type="number" id="area" name="area" value="<?php echo $property['area']; ?>"
                                        required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-[0.5px] focus:ring-black transition-colors">
                                </div>
                            </div>
                        </div>

                        <!-- Sharing Option -->
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="is_sharable" name="is_sharable" value="1" <?php echo ($property['is_sharable'] == 1 ? 'checked' : ''); ?>
                                class="w-5 h-5 text-emerald-600 border-gray-300 rounded focus:text-blue-700">
                            <label for="is_sharable" class="text-gray-700 font-medium">Allow Sharing</label>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-4">
                            <a href="dashboard.php"
                                class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                                Cancel
                            </a>
                            <button type="submit" id="submitBtn"
                                class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Update Property
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show notification function
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        // Image preview function
        function previewImage(input, fieldName) {
            const previewImg = document.getElementById('preview-' + fieldName);
            const clearBtn = previewImg.nextElementSibling;

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    clearBtn.classList.remove('hidden');
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        // Clear image function
        function clearImage(fieldName) {
            const fileInput = document.getElementById('file-' + fieldName);
            const previewImg = document.getElementById('preview-' + fieldName);
            const clearBtn = previewImg.nextElementSibling;

            fileInput.value = '';
            previewImg.src = 'https://via.placeholder.com/150?text=No+Image';
            clearBtn.classList.add('hidden');
        }

        // Form submission with loading overlay
        document.getElementById('propertyForm').addEventListener('submit', function () {
            document.getElementById('loadingOverlay').classList.add('show');
        });

        // Drag and drop functionality
        document.addEventListener('DOMContentLoaded', function () {
            const uploadContainers = document.querySelectorAll('.image-upload-container');

            uploadContainers.forEach(container => {
                const uploadArea = container.querySelector('.upload-area');
                const fileInput = container.querySelector('.file-input');
                const fieldName = container.dataset.field;

                // Drag over event
                uploadArea.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    uploadArea.classList.add('drag-over');
                });

                // Drag leave event
                uploadArea.addEventListener('dragleave', function () {
                    uploadArea.classList.remove('drag-over');
                });

                // Drop event
                uploadArea.addEventListener('drop', function (e) {
                    e.preventDefault();
                    uploadArea.classList.remove('drag-over');

                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        previewImage(fileInput, fieldName);
                    }
                });
            });

            // Add input validation
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                input.addEventListener('input', function () {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            });

            // Form validation before submit
            document.getElementById('propertyForm').addEventListener('submit', function (e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value) {
                        isValid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showNotification('Please fill in all required fields', 'error');
                    window.scrollTo(0, 0);
                }
            });

            // Input focus effects
            const inputs = document.querySelectorAll('input[type="text"], input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('focus', function () {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function () {
                    this.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>

</html>