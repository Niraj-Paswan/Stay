<?php

session_start(); // Start session
include '../Database/dbconfig.php';

// Check if the property ID is passed
if (isset($_GET['id'])) {
  $property_id = intval($_GET['id']); // Change from pid to id

  // Secure integer conversion
  $sql = "SELECT id, property_name, property_location, property_price, property_description, 
                 latitude, longitude, main_image, kitchen_img, washroom_img, gallery_img, 
                 property_type, bathrooms, bedrooms, area 
          FROM properties WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $property_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $properties = [];
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $properties[] = [
        "pid" => $row["id"],
        "property_name" => $row["property_name"],
        "location" => $row["property_location"],
        "coordinates" => [(float) $row["longitude"], (float) $row["latitude"]],
        "price" => $row["property_price"],
        "des" => $row["property_description"],
        "image" => $row["main_image"],
        "kitchen" => $row["kitchen_img"],
        "washroom" => $row["washroom_img"],
        "bedroomimg" => $row["gallery_img"],
        "type" => $row["property_type"],
        "bathrooms" => $row["bathrooms"] ?? "N/A",
        "bedrooms" => $row["bedrooms"] ?? "N/A",
        "area" => $row["area"] ?? "N/A"
      ];
      // Set session variables
      $_SESSION['property_id'] = $row["id"];
      $_SESSION['property_price'] = $row["property_price"];
      $_SESSION['property_name'] = $row["property_name"];
    }
  } else {
    echo "Property not found!";
    exit;
  }
} else {
  echo "No property selected!";
  exit;
}
// Fetch occupants from payments table
$occupants_sql = "SELECT u.full_name, u.email_address, u.phone_number , u.gender , u.rent_start_date
                  FROM payments p 
                  JOIN users u ON p.userID = u.userID 
                  WHERE p.property_id = ?";
$stmt = $conn->prepare($occupants_sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$occupants_result = $stmt->get_result();

$occupants = [];
while ($occupant = $occupants_result->fetch_assoc()) {
  $occupants[] = $occupant;
}

// Fetch property details including sharable status
$sql = "SELECT id, property_name, property_location, property_price, is_sharable FROM properties WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

// Check how many people have booked this property from the payments table
$sql = "SELECT COUNT(*) as occupant_count FROM payments WHERE property_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$occupantData = $result->fetch_assoc();
$occupant_count = $occupantData['occupant_count'];

// Determine status
$status = "Vacant";
$status_color = "text-green-600"; // Default green dot

if ($occupant_count == 1) {
  if ($property['is_sharable'] == 1) {
    $status = "Partially Booked";
    $status_color = "text-yellow-600"; // Yellow dot
  } else {
    $status = "Fully Booked"; // Since it's not sharable, one booking fills it
    $status_color = "text-red-600"; // Red dot
  }
} elseif ($occupant_count == 2) {
  $status = "Fully Booked";
  $status_color = "text-red-600"; // Red dot
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="../assets/css/styles.css" rel="stylesheet" />
  <title>StayEase | Property Listing</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
    rel="stylesheet" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.2/css/all.css" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>
    /* Rotate the second icon when it has the rotate-180 class */
    .rotate-180 {
      transform: rotate(180deg);
      transition: transform 0.3s ease-in-out;
    }

    /* Fixed position for the first icon */
    .icon-fixed {
      transform: rotate(0deg);
    }

    .carousel-container {
      overflow: hidden;
      position: relative;
    }

    .carousel-track {
      display: flex;
      gap: 1.5rem;
      transition: transform 0.3s ease-in-out;
    }

    .gradient-shadow {
      position: absolute;
      top: 0;
      width: 50px;
      height: 100%;
      pointer-events: none;
    }

    .left-shadow {
      left: 0;
      background: linear-gradient(to right, rgba(255, 255, 255, 0.5), transparent);
    }

    .right-shadow {
      right: 0;
      background: linear-gradient(to left, rgba(255, 255, 255, 0.5), transparent);
    }

    /* Custom Styles for hiding and showing elements */
  </style>
</head>

<body>
  <?php if (!empty($properties)): ?>
    <a href="listingview.php?pid=<?= $properties[0]['pid'] ?>"></a>
  <?php endif; ?>

  <div class="h-full w-full bg-white font-Nrj-fonts">
    <div class="container mx-auto p-4">
      <!-- Main Content Section -->
      <div class="flex flex-wrap lg:flex-nowrap gap-6">
        <!-- Left Section -->
        <div class="flex-1" id="property_gallery">
          <!-- Property Image Gallery -->
          <div class="bg-white p-4 border-[1.5px] border-gray-300 rounded-lg h-[100%]" id="image_gallery">
            <!-- Main Image -->
            <?php foreach ($properties as $property): ?>
              <div class="relative w-full h-80 rounded-lg overflow-hidden" id="main_image_container">
                <img src="<?= $property['image'] ?>" alt="Room Image" class="w-full h-full object-cover" name="main_image"
                  id="main_image" />
              </div>
              <!-- Thumbnails -->
              <div class="grid grid-cols-3 gap-4 mt-4" id="thumbnails">
                <?php
                $images = [
                  ['src' => $property['kitchen'], 'title' => 'Kitchen'],
                  ['src' => $property['bedroomimg'], 'title' => 'Bedroom'],
                  ['src' => $property['washroom'], 'title' => 'Washroom']
                ];

                foreach ($images as $index => $img):
                  ?>
                  <div class="relative w-full h-44 rounded-lg overflow-hidden border-[1.5px] border-gray-300 group">
                    <img src="<?= $img['src'] ?>" alt="Thumbnail" class="w-full h-full object-cover" />
                    <div class="absolute bottom-0 w-full bg-gray-200 p-2 text-center text-sm font-medium">
                      <?= $img['title'] ?>
                    </div>
                    <button
                      class="zoom-btn absolute top-2 right-2 bg-white p-3 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center h-10 w-10"
                      data-index="<?= $index ?>">
                      <i class="fa-regular fa-search-plus text-gray-700 text-base"></i>
                    </button>

                  </div>
                <?php endforeach; ?>
              </div>

              <!-- Modal for zoomed image with carousel -->
              <div id="imageModal"
                class="fixed inset-0 bg-black bg-opacity-0 flex items-center justify-center z-50 pointer-events-none opacity-0 transition-all duration-300">
                <div class="relative max-w-4xl max-h-[90vh] w-full mx-4">
                  <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                    <div class="relative">
                      <!-- Carousel Buttons -->
                      <button id="prevBtn"
                        class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white rounded-full shadow-lg hidden h-9 w-9 flex items-center justify-center">
                        <i class="fa-solid fa-chevron-left text-gray-800 text-base leading-none"></i>
                      </button>

                      <img id="zoomedImage" src="/placeholder.svg" alt="Zoomed image"
                        class="w-full object-contain max-h-[80vh] transition-opacity duration-300 rounded-lg">

                      <button id="nextBtn"
                        class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white rounded-full shadow-lg hidden h-9 w-9 flex items-center justify-center">
                        <i class="fa-solid fa-chevron-right text-gray-800 text-base leading-none"></i>
                      </button>



                      <!-- Close Button -->
                      <button id="closeModal" class="absolute top-4 right-4 bg-white p-2 rounded-full shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                    <div class="p-4 border-t">
                      <h3 id="imageTitle" class="text-xl font-semibold text-center"></h3>
                    </div>
                  </div>
                </div>
              </div>

              <script>
                document.addEventListener('DOMContentLoaded', function () {
                  const modal = document.getElementById('imageModal');
                  const zoomedImage = document.getElementById('zoomedImage');
                  const imageTitle = document.getElementById('imageTitle');
                  const closeModal = document.getElementById('closeModal');
                  const zoomButtons = document.querySelectorAll('.zoom-btn');
                  const prevBtn = document.getElementById('prevBtn');
                  const nextBtn = document.getElementById('nextBtn');

                  let currentIndex = 0;
                  const images = [
                    { src: "<?= $property['kitchen'] ?>", title: "Kitchen" },
                    { src: "<?= $property['bedroomimg'] ?>", title: "Bedroom" },
                    { src: "<?= $property['washroom'] ?>", title: "Washroom" }
                  ];

                  function openModal(index) {
                    currentIndex = index;
                    updateImage();

                    modal.classList.remove('opacity-0', 'pointer-events-none');
                    modal.classList.add('opacity-100', 'pointer-events-auto');
                    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.75)';

                    // Show/hide navigation buttons
                    prevBtn.style.display = currentIndex > 0 ? 'block' : 'none';
                    nextBtn.style.display = currentIndex < images.length - 1 ? 'block' : 'none';
                  }

                  function updateImage() {
                    zoomedImage.classList.add('opacity-0');
                    setTimeout(() => {
                      zoomedImage.src = images[currentIndex].src;
                      imageTitle.textContent = images[currentIndex].title;
                      zoomedImage.classList.remove('opacity-0');
                    }, 200);
                  }

                  function closeImageModal() {
                    modal.classList.add('opacity-0', 'pointer-events-none');
                    modal.classList.remove('opacity-100', 'pointer-events-auto');

                    setTimeout(() => {
                      modal.style.backgroundColor = 'rgba(0, 0, 0, 0)';
                    }, 300);
                  }

                  zoomButtons.forEach(button => {
                    button.addEventListener('click', function () {
                      openModal(parseInt(this.getAttribute('data-index')));
                    });
                  });

                  closeModal.addEventListener('click', closeImageModal);

                  modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                      closeImageModal();
                    }
                  });

                  document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modal.classList.contains('opacity-0')) {
                      closeImageModal();
                    } else if (e.key === 'ArrowRight' && currentIndex < images.length - 1) {
                      currentIndex++;
                      updateImage();
                      prevBtn.style.display = 'block';
                      nextBtn.style.display = currentIndex === images.length - 1 ? 'none' : 'block';
                    } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                      currentIndex--;
                      updateImage();
                      nextBtn.style.display = 'block';
                      prevBtn.style.display = currentIndex === 0 ? 'none' : 'block';
                    }
                  });

                  prevBtn.addEventListener('click', function () {
                    if (currentIndex > 0) {
                      currentIndex--;
                      updateImage();
                      nextBtn.style.display = 'block';
                      prevBtn.style.display = currentIndex === 0 ? 'none' : 'block';
                    }
                  });

                  nextBtn.addEventListener('click', function () {
                    if (currentIndex < images.length - 1) {
                      currentIndex++;
                      updateImage();
                      prevBtn.style.display = 'block';
                      nextBtn.style.display = currentIndex === images.length - 1 ? 'none' : 'block';
                    }
                  });
                });
              </script>


              <!-- Property Title and Location -->
              <h2 class="text-2xl font-semibold text-gray-800 mt-6 flex flex-row justify-between"
                id="property_title_container">
                <span name="property_title" id="property_title"> <?= $property['property_name'] ?></span>
              </h2>
              <div class="flex flex-row items-center justify-start mr-12" id="property_location_container">
                <p class="text-gray-600 font-medium text-sm">
                  <i class="fa-regular fa-location-dot text-sm mr-1"></i>
                  <span name="property_location" id="property_location"><?= $property['location'] ?></span>
                </p>
              </div>
              <div class="flex flex-row justify-between  mt-4">
                <!-- Type Section -->
                <div
                  class="flex items-center gap-2 border-[1.5px] border-gray-400 bg-white rounded-md px-4 py-2 shadow-sm">
                  <i class="fa-regular fa-house text-for text-sm"></i>
                  <span class="text-black text-sm font-medium">Type</span>
                  <span class="text-gray-500">|</span>
                  <span class="text-sm font-medium text-for"><?= $property['type'] ?></span>
                </div>
                <!-- Sharable Section -->
                <div
                  class="flex items-center gap-2 border-[1.5px] border-gray-400 bg-white rounded-md px-4 py-2 shadow-sm">
                  <i class="fa-regular fa-user-group text-for text-sm"></i>
                  <span class="text-black text-sm font-medium">Sharable</span>
                  <span class="text-gray-500">|</span>
                  <span class="text-sm font-medium text-for">Max 2 People</span>
                </div>

                <!-- Status Section -->
                <div
                  class="flex items-center gap-2 border-[1.5px] border-gray-400 bg-white rounded-md px-4 py-2 shadow-sm">
                  <i class="fa-regular fa-circle-check text-for text-sm"></i>
                  <span class="text-black text-sm font-medium ">Status</span>
                  <span class="text-gray-500">|</span>
                  <span class="text-sm font-medium text-for"><?= $status ?></span>
                  <i class="fa-solid fa-circle text-xs"
                    style="color: <?= $status_color == 'text-red-600' ? 'red' : ($status_color == 'text-yellow-600' ? 'yellow' : 'green') ?>;"></i>
                </div>
              </div>
              <!-- Occupants Details Section -->
              <div class="mt-4">
                <h2 class="text-[16px] font-medium text-black mb-1">Occupants Details</h2>
                <?php if (!empty($occupants)): ?>
                  <?php foreach ($occupants as $occupant): ?>
                    <!-- Determine Image Based on Gender -->
                    <?php
                    $imageSrc = "../assets/img/Male.png"; // Default to male
                    if (strtolower($occupant['gender']) === "female") {
                      $imageSrc = "../assets/img/Female.png";
                    }
                    ?>

                    <!-- Occupant Card -->
                    <div class="flex items-center gap-4 p-4 bg-white rounded-md">
                      <!-- Profile Picture -->
                      <img class="h-16 w-16 rounded-full border border-for " src="<?= $imageSrc ?>" alt="Profile Image">

                      <!-- User Info -->
                      <div>
                        <p class="text-lg font-semibold text-gray-900">
                          <?= htmlspecialchars($occupant['full_name']) ?>
                          <i class="fa-solid fa-badge-check ml-1 text-for text-sm"></i>
                        </p>

                        <!-- Email & Phone -->
                        <div class="flex flex-row gap-4 mt-1">
                          <p class="text-sm text-gray-600 flex items-center gap-2">
                            <i class="fa-regular fa-envelope text-for"></i>
                            <?= htmlspecialchars($occupant['email_address']) ?>
                          </p>

                          <span class="text-gray-500">|</span>
                          <p class="text-sm text-gray-600 flex items-center gap-2">
                            <i class="fa-regular fa-mars text-for"></i> <?= htmlspecialchars($occupant['gender']) ?>
                          </p>

                          <span class="text-gray-500">|</span>
                          <p class="text-sm text-gray-600 flex items-center gap-2">
                            <i class="fa-regular fa-calendar-circle-user text-for"></i>
                            <?= htmlspecialchars($occupant['rent_start_date']) ?>
                          </p>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-gray-500">No occupants yet.</p>
                <?php endif; ?>
              </div>




            </div>
          </div>

          <!-- Right Section -->
          <div class="w-full h-[100%] lg:w-1/3 bg-white p-4 rounded-lg border-[1.5px] border-gray-300 shadow-sm"
            data-property-id="1" data-property-name="Suddha Apartment" data-rent-price="15000" data-rent-duration="month"
            data-verified="1">
            <h2 class="text-lg font-semibold text-gray-800  flex flex-row justify-between mb-2">
              <?= $property['property_name'] ?>
              <div class="text-sm font-medium bg-gray-50 border-[1.5px] border-gray-300 rounded-md p-1 px-2  text-black"
                data-verified="1">
                <i class="fa-solid fa-badge-check mr-1 text-blue-600"></i>Verified
              </div>
            </h2>
            <h2 class="text-sm font-normal text-gray-500 mb-2">From <span
                class="text-black text-xl font-semibold">₹<?= number_format($property['price']) ?><span
                  class="text-sm font-normal text-gray-500"> /month</span></span>
            </h2>
            <!-- Action Buttons -->
            <form action="../bookings/shared.php" method="GET">
              <input type="hidden" name="id" value="<?= $property['pid'] ?>">
              <button class="w-full py-2 bg-blue-500 text-white font-medium rounded hover:bg-for mb-4"
                <?= ($status === "Fully Booked") ? 'disabled' : '' ?>>
                Continue
              </button>
            </form>
            <?php if ($status === "Fully Booked"): ?>
              <p class="text-red-600 text-sm font-medium mt-1 mb-4">❌ Sorry, this property is fully booked.</p>
            <?php endif; ?>
            <button class="w-full py-2 bg-gray-200 text-gray-700 font-medium rounded hover:bg-gray-300"
              data-action="go-back" onclick="window.history.back();">
              Go Back
            </button>
            <!-- Shortlisted Info -->

            <!-- Collapsible Section -->
            <div class="mt-6">
              <h3 class="text-xl font-semibold text-gray-800 mb-4">
                Why Choose This Property?
              </h3>
              <!-- Collapsible Items -->
              <div class="collapsible-item border-t border-gray-300">
                <button type="button" class="toggle-button w-full flex justify-between items-center py-4 text-left"
                  onclick="toggleContent('feature1', this.querySelector('.rotate-icon'))" data-feature-id="1">
                  <span class="flex items-center text-sm font-medium text-gray-800 gap-4">
                    <div
                      class="icon-wrapper w-9 h-9 bg-blue-200 bg-opacity-25 rounded-full flex justify-center items-center">
                      <i class="fa-regular fa-calendar-check text-[16px] text-for"></i>
                    </div>
                    Instant Booking
                  </span>
                  <i class="rotate-icon fa-solid fa-caret-down mr-4 text-sm text-gray-500"></i>
                </button>
                <div id="feature1" class="hidden px-4 py-2 text-gray-600 text-sm"
                  data-feature-description="Instant booking allows you to quickly book the property by paying the amount.">
                  Instant booking allows you to quickly book the property by
                  paying the amount.
                </div>
              </div>

              <div class="collapsible-item border-t border-gray-300">
                <button type="button" class="toggle-button w-full flex justify-between items-center py-4 text-left"
                  onclick="toggleContent('feature2', this.querySelector('.rotate-icon'))" data-feature-id="2">
                  <span class="flex items-center text-sm font-medium text-gray-800 gap-4">
                    <div
                      class="icon-wrapper w-9 h-9 bg-blue-200 bg-opacity-25 rounded-full flex justify-center items-center">
                      <i class="fa-regular fa-hand-holding-dollar text-[16px] text-for"></i>
                    </div>
                    Lowest Price Guaranteed
                  </span>
                  <i class="rotate-icon fa-solid fa-caret-down mr-4 text-sm text-gray-500"></i>
                </button>
                <div id="feature2" class="hidden px-4 py-2 text-gray-600 text-sm"
                  data-feature-description="We ensure you get the lowest price for your stay.">
                  We ensure you get the lowest price for your stay.
                </div>
              </div>

              <div class="collapsible-item border-t border-gray-300">
                <button type="button" class="toggle-button w-full flex justify-between items-center py-4 text-left"
                  onclick="toggleContent('feature3', this.querySelector('.rotate-icon'))" data-feature-id="3">
                  <span class="flex items-center text-sm font-medium text-gray-800 gap-4">
                    <div
                      class="icon-wrapper w-9 h-9 bg-blue-200 bg-opacity-25 rounded-full flex justify-center items-center">
                      <i class="fa-regular fa-circle-check text-[16px] text-for"></i>
                    </div>
                    Verified Listings
                  </span>
                  <i class="rotate-icon fa-solid fa-caret-down mr-4 text-sm text-gray-500"></i>
                </button>
                <div id="feature3" class="hidden px-4 py-2 text-gray-600 text-sm"
                  data-feature-description="Our listings are thoroughly verified to ensure your safety and comfort.">
                  Our listings are thoroughly verified to ensure your safety and
                  comfort.
                </div>
              </div>

              <div class="collapsible-item border-t border-gray-300">
                <button type="button" class="toggle-button w-full flex justify-between items-center py-4 text-left"
                  onclick="toggleContent('feature4', this.querySelector('.rotate-icon'))" data-feature-id="4">
                  <span class="flex items-center text-sm font-medium text-gray-800 gap-4">
                    <div
                      class="icon-wrapper w-9 h-9 bg-blue-200 bg-opacity-25 rounded-full flex justify-center items-center">
                      <i class="fa-regular fa-tag text-[16px] text-for"></i>
                    </div>
                    Instant Discounts
                  </span>
                  <i class="rotate-icon fa-solid fa-caret-down mr-4 text-sm text-gray-500"></i>
                </button>
                <div id="feature4" class="hidden px-4 py-2 text-gray-600 text-sm"
                  data-feature-description="Enjoy instant discounts on your Booking, save more with every deal, no waiting required!">
                  Enjoy instant discounts on your Booking, save more with every
                  deal, no waiting required!
                </div>
              </div>

              <div class="collapsible-item border-t border-gray-300">
                <button type="button" class="toggle-button w-full flex justify-between items-center py-4 text-left"
                  onclick="toggleContent('feature5', this.querySelector('.rotate-icon'))" data-feature-id="5">
                  <span class="flex items-center text-sm font-medium text-gray-800 gap-4">
                    <div
                      class="icon-wrapper w-9 h-9 bg-blue-200 bg-opacity-25 rounded-full flex justify-center items-center">
                      <i class="fa-regular fa-eye-slash text-[16px] text-for"></i>
                    </div>
                    No hidden charges
                  </span>
                  <i class="rotate-icon fa-solid fa-caret-down mr-4 text-sm text-gray-500"></i>
                </button>
                <div id="feature5" class="hidden px-4 py-2 text-gray-600 text-sm"
                  data-feature-description="Negotiate prices effortlessly to get the best deal that suits your budget!">
                  Transparent pricing with no unexpected fees or extra costs.
                </div>
              </div>

              <!-- Promo Codes Section -->
              <!-- Promo Codes Section -->
              <div class="mt-1 p-5 rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
                <h2 class="text-[16px] font-semibold mb-1">
                  🎉 Exclusive Promo Codes - Save More!
                </h2>
                <p class="text-xs text-gray-200">Use these promo codes to get discounts on booking</p>

                <div class="mt-3 space-y-2">
                  <!-- Promo Code 1 -->
                  <div class="flex justify-between items-center text-white font-normal text-sm">
                    <div class="flex items-center gap-2">
                      <i class="fa-solid fa-tag text-green-500"></i>
                      <strong class="font-medium">SAVE10</strong> - Get 10% off
                    </div>
                    <button class="copy-btn text-white hover:text-gray-300" data-code="SAVE10">
                      <i class="fa-regular fa-copy"></i>
                    </button>
                  </div>

                  <!-- Promo Code 2 -->
                  <div class="flex justify-between items-center text-white font-normal text-sm">
                    <div class="flex items-center gap-2">
                      <i class="fa-solid fa-tag text-green-500"></i>
                      <strong class="font-medium">FLAT500</strong> - Flat ₹500 off
                    </div>
                    <button class="copy-btn text-white hover:text-gray-300" data-code="FLAT500">
                      <i class="fa-regular fa-copy"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- About Property Section -->
        <div class="sm:w-full lg:w-[100%] bg-white border border-gray-300 rounded-lg mt-4" data-property-id="1">
          <!-- Section Title -->
          <div class="py-3 px-4 border-b border-gray-300">
            <h2 class="font-semibold text-[16px] text-gray-800">
              About The Property
            </h2>
          </div>
          <div class="p-4">
            <!-- Property Description -->
            <p class="text-sm font-normal text-gray-700 mb-4 leading-relaxed"
              data-property-description="Experience modern rental room apartments situated in the serene state of Goa, offering a perfect blend of comfort, convenience, and style. These apartments feature essential amenities like spacious interiors, high-speed internet, and fully equipped kitchens, all designed to cater to your everyday needs. Located in prime areas close to beaches, markets, and major attractions, they provide a hassle-free living experience for both long and short-term stays.">
              <?= $property['des'] ?>
            </p>
            <!-- Key Features -->
            <div class="flex justify-start items-start gap-24 mb-2">
              <div class="flex items-center gap-2" data-bedrooms="2">
                <i class="fa-light fa-bed text-lg text-blue-500"></i>
                <p class="text-[16px] font-medium text-gray-800"><?= $property['bedrooms'] ?> Bedrooms</p>
              </div>
              <div class="flex items-center gap-2" data-bathrooms="1">
                <i class="fa-light fa-bath text-lg text-blue-500"></i>
                <p class="text-[16px] font-medium text-gray-800"><?= $property['bathrooms'] ?> Bathroom
              </div>
              <div class="flex items-center gap-2" data-size="1800">
                <i class="fa-light fa-arrows-maximize text-lg text-blue-500"></i>
                <p class="text-[16px] font-medium text-gray-800"><?= $property['area'] ?> Sq.ft</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Room Amenties -->
        <div class="sm:w-full lg:w-[100%] h-[50%] mt-4 bg-white border-[1.5px] border-gray-300 rounded-lg"
          data-room-id="101">
          <h2 class="text-lg lg:text-[16px] font-medium py-2 ml-4" data-section="room-includes">
            Room Includes
            <span class="ml-2 text-[14px] font-normal bg-green-600 text-white rounded-full px-3 py-1">Amenities</span>
          </h2>
          <p class="border-b border-gray-300"></p>

          <div class="flex flex-row justify-between py-2 text-gray-600 mr-12" data-bills-included="true">
            <h2 class="font-medium text-sm lg:text-[15px] py-2 ml-4" data-item="bills-included">
              Bills included
            </h2>
            <div class="flex justify-center items-center flex-row gap-2" data-item-id="water">
              <i class="fa-light fa-glass-water-droplet text-sm lg:text-xl"></i>
              <h2 class="text-sm" data-item-name="water">Water</h2>
            </div>

            <div class="flex justify-center items-center flex-row gap-2" data-item-id="electricity">
              <i class="fa-light fa-plug text-sm lg:text-xl"></i>
              <h2 class="text-sm" data-item-name="electricity">Electricity</h2>
            </div>

            <div class="flex justify-center items-center flex-row gap-2" data-item-id="fan">
              <i class="fa-light fa-fan-table text-sm lg:text-xl"></i>
              <h2 class="text-sm" data-item-name="fan">Fan</h2>
            </div>
          </div>
          <p class="border-b border-gray-300"></p>

          <div class="flex flex-row justify-between py-2 text-gray-600 mr-12" data-common-amenities="true">
            <h2 class="font-medium text-sm lg:text-[15px] py-2 ml-4" data-item="common-amenities">
              Common Amenities
            </h2>
            <div class="flex justify-center items-center flex-row gap-2 mr-0 lg:mr-12" data-item-id="parking">
              <i class="fa-light fa-car-garage text-sm lg:text-xl"></i>
              <h2 class="text-sm" data-item-name="parking">Parking</h2>
            </div>

            <div class="flex justify-center items-center flex-row gap-2" data-item-id="cctv">
              <i class="fa-light fa-camera-cctv text-sm lg:text-xl"></i>
              <h2 class="text-sm" data-item-name="cctv">CCTV</h2>
            </div>

            <div class="flex justify-center items-center flex-row gap-2" data-item-id="alarm">
              <i class="fa-light fa-siren-on text-sm lg:text-xl"></i>
              <h2 class="text-sm" data-item-name="alarm">Alarm</h2>
            </div>
          </div>
        </div>

        <!-- Payment Policy -->
        <div class="sm:w-full lg:w-[100%] h-[50%] bg-white border-[1.5px] border-gray-300 rounded-lg mt-4"
          data-policy-id="101">
          <!-- Payment Policy Title (Clickable to toggle dropdown) -->
          <div
            class="accordion-header flex flex-row justify-start items-center gap-2 w-full cursor-pointer py-3 px-4 hover:bg-gray-100"
            data-section="payment-policies">
            <h2 class="text-[16px] font-medium ml-2">Payment Policies</h2>
            <!-- Dropdown Icon -->
            <i class="fa-solid fa-caret-down ml-auto text-gray-500" id="dropdown-icon" data-toggle="true"></i>
          </div>
          <p class="border-b border-gray-300"></p>

          <!-- Dropdown Content (Initially hidden) -->
          <div class="accordion-content hidden" data-accordion-content="true">
            <!-- Booking Deposit Section -->
            <div class="flex flex-col justify-start items-start pt-1 border-b border-gray-300 hover:bg-gray-100"
              data-policy-id="deposit">
              <div class="flex flex-row justify-center items-center gap-2">
                <i class="fa-regular fa-circle-check text-green-600 text-xl ml-4" data-icon="checkmark"></i>
                <div class="flex flex-col justify-start items-start">
                  <h2 class="text-[16px] font-medium ml-2" data-policy-name="booking-deposit">
                    Booking Deposit
                  </h2>
                  <p class="text-sm font-light ml-2 text-gray-500 mb-2" data-policy-description="deposit">
                    This property requires a booking deposit of
                    <span class="font-medium text-gray-600" data-deposit-amount="25%">25%</span>
                    of the
                    <span class="font-medium text-gray-600">rent amount.</span>
                    and
                    <span class="font-medium text-gray-600">One Month Advance rent amount.</span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Mode of Payment Section -->
            <div class="flex flex-col justify-start items-start pt-1 border-b border-gray-300 hover:bg-gray-100"
              data-policy-id="mode-of-payment">
              <div class="flex flex-row justify-center items-center gap-2">
                <i class="fa-regular fa-circle-check text-green-600 text-xl ml-4" data-icon="checkmark"></i>
                <div class="flex flex-col justify-start items-start">
                  <h2 class="text-[16px] font-medium ml-2" data-policy-name="mode-of-payment">
                    Mode of Payment
                  </h2>
                  <p class="text-sm font-light ml-2 text-gray-500 mb-2" data-policy-description="mode">
                    Payment via
                    <span class="font-medium text-gray-600">easy transaction</span>
                    modes.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cancellation Policy -->
        <div class="sm:w-full lg:w-[100%] h-[50%] bg-white border-[1.5px] border-gray-300 rounded-lg mt-4"
          data-policy-id="cancellation">
          <!-- Cancellation Policy Title (Clickable to toggle dropdown) -->
          <div
            class="accordion-header-cancellation flex flex-row justify-start items-center gap-2 w-full cursor-pointer py-3 px-4 hover:bg-gray-100"
            data-section="cancellation-policies">
            <h2 class="text-[16px] font-medium ml-2">Cancellation Policies</h2>
            <!-- Dropdown Icon -->
            <i class="fa-solid fa-caret-down ml-auto text-gray-500" id="dropdown-icon-cancellation"
              data-toggle="true"></i>
          </div>
          <p class="border-b border-gray-300"></p>

          <!-- Dropdown Content (Initially hidden) -->
          <div class="accordion-content-cancellation hidden" data-accordion-content="true">
            <!-- Refund Section -->
            <div class="flex flex-col justify-start items-start pt-1 border-b border-gray-300 hover:bg-gray-100"
              data-policy-id="refund">
              <div class="flex flex-row justify-center items-center gap-2">
                <i class="fa-regular fa-circle-check text-green-600 text-xl ml-4" data-icon="checkmark"></i>
                <div class="flex flex-col justify-start items-start">
                  <h2 class="text-[16px] font-medium ml-2" data-policy-name="refund">
                    Refund
                  </h2>
                  <p class="text-sm font-light ml-2 text-gray-500 mb-2" data-policy-description="refund">
                    The remaining
                    <span class="font-medium text-gray-600" data-refund-amount="2%">2% will be charged</span>
                    and the remaining amount will be refunded.
                  </p>
                </div>
              </div>
            </div>

            <!-- Cancellation Payment Section -->
            <div class="flex flex-col justify-start items-start pt-1 hover:bg-gray-100"
              data-policy-id="cancellation-payment">
              <div class="flex flex-row justify-center items-center gap-2">
                <i class="fa-regular fa-circle-check text-green-600 text-xl ml-4" data-icon="checkmark"></i>
                <div class="flex flex-col justify-start items-start">
                  <h2 class="text-[16px] font-medium ml-2" data-policy-name="cancellation-payment">
                    Cancellation Payment
                  </h2>
                  <p class="text-sm font-light ml-2 text-gray-500 mb-2" data-policy-description="payment">
                    Payment will be transferred to the
                    <span class="font-medium text-gray-600" data-payment-duration="2-days">account within 2 days</span>
                    to the original account.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Location Section -->
        <div class="sm:w-full lg:w-[100%] h-[50%] bg-white border-[1.5px] border-gray-300 rounded-lg mt-4"
          data-section="location">
          <!-- Section Title -->
          <h2 class="font-medium text-[16px] py-3 ml-4" data-title="locations-and-nearby">
            Locations and Nearby
          </h2>
          <p class="border-b border-gray-300"></p>

          <!-- Embedded Map (iframe) -->
          <!-- Embedded Map (iframe) -->
          <!-- In your listing view page -->
          <iframe id="mapFrame" class="w-full h-[500px] border-gray-300 rounded-md p-4" src=""></iframe>

          <script>
            // Assuming you have the coordinates from PHP (from your $properties array)
            var latitude = <?= $properties[0]['coordinates'][1] ?>;
            var longitude = <?= $properties[0]['coordinates'][0] ?>;

            // Build the URL with query parameters
            var mapUrl = "map_index.php?latitude=" + latitude + "&longitude=" + longitude;

            // Set the iframe's src to load the dynamic map
            document.getElementById("mapFrame").src = mapUrl;
          </script>


        </div>
      </div>
    </div>


    <?php
    include '../Database/dbconfig.php';


    $sql = "SELECT id, property_name, property_location, property_price, latitude, longitude, main_image, kitchen_img, gallery_img, washroom_img, property_type, bathrooms, bedrooms, area, is_sharable FROM properties";
    $result = $conn->query($sql);

    $properties = [];
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $properties[] = [
          "pid" => $row["id"],
          "property_name" => $row["property_name"],
          "location" => $row["property_location"],
          "coordinates" => [(float) $row["longitude"], (float) $row["latitude"]],
          "price" => $row["property_price"],
          "image" => $row["main_image"],
          "washroom" => $row["washroom_img"],
          "gallery" => $row["gallery_img"],
          "kitchen" => $row["kitchen_img"],
          "type" => $row["property_type"],
          "bathrooms" => $row["bathrooms"] ?? "N/A",
          "bedrooms" => $row["bedrooms"] ?? "N/A",
          "area" => $row["area"] ?? "N/A",
          "is_sharable" => isset($row["is_sharable"]) ? $row["is_sharable"] : 0
        ];
      }
    }

    ?>
    <h1 class="text-center font-Nrj-fonts font-medium text-2xl sm:text-3xl lg:text-4xl mt-12">
      Spotted for You
      <span class="font-Nrj-fonts text-for">Perfect Properties</span>
    </h1>

    <div class="bg-white flex justify-center items-center mt-12 font-Nrj-fonts px-4">
      <div class="relative w-full max-w-6xl">
        <!-- Gradient Shadows (optional) -->
        <div class="gradient-shadow left-shadow"></div>
        <div class="gradient-shadow right-shadow"></div>

        <div class="carousel-container overflow-x-auto">
          <div id="carousel" class="carousel-track flex gap-4 transition-transform duration-300 ease-in-out">
            <?php foreach ($properties as $property): ?>
              <div class="bg-white rounded-xl shadow-md border border-gray-300 w-64 sm:w-72 h-auto flex-shrink-0">
                <div class="relative">
                  <img class="w-full h-40 object-cover rounded-t-xl" src="<?= $property['image'] ?>" alt="Property Image" />
                </div>

                <div class="p-4">
                  <h3 class="text-lg font-semibold"><?= $property['property_name'] ?></h3>
                  <div class="flex items-center text-gray-600 text-sm mt-1 mb-1">
                    <i class="fa-regular fa-location-dot mr-1"></i>
                    <?= $property['location'] ?>
                  </div>

                  <!-- Price & Action Button -->
                  <form action="listingview.php" method="GET">
                    <input type="hidden" name="id" value="<?= $property['pid'] ?>">
                    <input type="hidden" name="property_name" value="<?= $property['property_name'] ?>">
                    <input type="hidden" name="location" value="<?= $property['location'] ?>">
                    <input type="hidden" name="price" value="<?= $property['price'] ?>">
                    <input type="hidden" name="image" value="<?= $property['image'] ?>">
                    <input type="hidden" name="type" value="<?= $property['type'] ?>">
                    <input type="hidden" name="bathrooms" value="<?= $property['bathrooms'] ?>">
                    <input type="hidden" name="bedrooms" value="<?= $property['bedrooms'] ?>">
                    <input type="hidden" name="area" value="<?= $property['area'] ?>">
                    <input type="hidden" name="userID" value="<?= $_SESSION['userID'] ?>">
                    <input type="hidden" name="user_email" value="<?= $_SESSION['user_email'] ?>">

                    <!-- Price -->
                    <h2 class="text-sm font-normal text-gray-500">
                      <span class="text-black text-xl font-semibold">
                        ₹<?= number_format($property['price']) ?>
                        <span class="text-sm font-normal text-gray-500"> /month</span>
                      </span>
                    </h2>

                    <button type="submit"
                      class="w-full py-2 px-4 mt-2 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 shadow-sm transition duration-200 ease-in-out">
                      View Details
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Navigation Buttons (visible on all devices) -->
        <button onclick="moveSlide(-1)"
          class="absolute left-2 sm:left-[-2rem] top-1/2 transform -translate-y-1/2 bg-gray-50 hover:bg-gray-100 text-for border border-gray-300 p-2 rounded-full w-10 h-10 sm:w-12 sm:h-12 shadow-lg">
          <i class="fa-solid fa-chevron-left text-xs sm:text-sm"></i>
        </button>
        <button onclick="moveSlide(1)"
          class="absolute right-2 sm:right-[-2rem] top-1/2 transform -translate-y-1/2 bg-gray-50 hover:bg-gray-100 text-for border border-gray-300 p-2 rounded-full w-10 h-10 sm:w-12 sm:h-12 shadow-lg">
          <i class="fa-solid fa-chevron-right text-xs sm:text-sm"></i>
        </button>
      </div>
    </div>

    <script>
      let currentIndex = 0;
      const track = document.getElementById("carousel");
      const cards = document.querySelectorAll(".carousel-track > div");
      // Adjust the gap based on Tailwind's gap (using 1rem = 16px here)
      const gap = 16;
      const cardWidth = cards[0].offsetWidth + gap;

      function moveSlide(direction) {
        const maxIndex = cards.length - 1;
        currentIndex = Math.max(0, Math.min(maxIndex, currentIndex + direction));
        track.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
      }
    </script>


    <footer class="bg-black font-Nrj-fonts mt-6">
      <div class="mx-auto w-full max-w-screen-xl p-4 py-6 lg:py-8">
        <div class="md:flex md:justify-between">
          <div class="mb-6 md:mb-0 ml-14 md:ml-0 lg:ml-0">
            <a href="#" id="brands" class="font-Nrj-fonts font-semibold text-md flex items-center ml-2">
              <img class="max-w-10 max-h-44 ml-5" src="../assets/img/stayease logo.svg" alt="" />
              <p class="font-Nrj-fonts ml-2 text-xl text-white">StayEase</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-3">
            <div>
              <h2 class="mb-6 text-sm font-semibold text-for uppercase dark:text-white">
                Resources
              </h2>
              <ul class="text-white dark:text-gray-400 font-medium">
                <li class="mb-4">
                  <a href="#" class="hover:underline">StayEase</a>
                </li>
                <li>
                  <a href="#" class="hover:underline">Blog</a>
                </li>
              </ul>
            </div>
            <div>
              <h2 class="mb-6 text-sm font-semibold text-for uppercase dark:text-white">
                Follow us
              </h2>
              <ul class="text-white dark:text-gray-400 font-medium">
                <li class="mb-4">
                  <a href="https://github.com/Niraj-Paswan" class="hover:underline">Github</a>
                </li>
                <li>
                  <a href="https://discord.gg" class="hover:underline">Discord</a>
                </li>
              </ul>
            </div>
            <div>
              <h2 class="mb-6 text-sm font-semibold text-for uppercase dark:text-white">
                Legal
              </h2>
              <ul class="text-white dark:text-gray-400 font-medium">
                <li class="mb-4">
                  <a href="#" class="hover:underline">Privacy Policy</a>
                </li>
                <li>
                  <a href="#" class="hover:underline">Terms &amp; Conditions</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <hr class="my-6 border-gray-200 sm:mx-auto dark:border-gray-700 lg:my-8" />
        <div class="sm:flex sm:items-center sm:justify-between">
          <span class="text-sm text-white sm:text-center dark:text-gray-400">© 2025 <a href="#"
              class="hover:underline">StayEase™</a>. All
            Rights Reserved.
          </span>
          <div class="flex mt-4 sm:justify-center sm:mt-0">
            <a href="#" class="text-white hover:text-for dark:hover:text-white">
              <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                viewBox="0 0 8 19">
                <path fill-rule="evenodd"
                  d="M6.135 3H8V0H6.135a4.147 4.147 0 0 0-4.142 4.142V6H0v3h2v9.938h3V9h2.021l.592-3H5V3.591A.6.6 0 0 1 5.592 3h.543Z"
                  clip-rule="evenodd" />
              </svg>
              <span class="sr-only">Facebook page</span>
            </a>
            <a href="#" class="text-white hover:text-for dark:hover:text-white ms-5">
              <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                viewBox="0 0 20 17">
                <path fill-rule="evenodd"
                  d="M20 1.892a8.178 8.178 0 0 1-2.355.635 4.074 4.074 0 0 0 1.8-2.235 8.344 8.344 0 0 1-2.605.98A4.13 4.13 0 0 0 13.85 0a4.068 4.068 0 0 0-4.1 4.038 4 4 0 0 0 .105.919A11.705 11.705 0 0 1 1.4.734a4.006 4.006 0 0 0 1.268 5.392 4.165 4.165 0 0 1-1.859-.5v.05A4.057 4.057 0 0 0 4.1 9.635a4.19 4.19 0 0 1-1.856.07 4.108 4.108 0 0 0 3.831 2.807A8.36 8.36 0 0 1 0 14.184 11.732 11.732 0 0 0 6.291 16 11.502 11.502 0 0 0 17.964 4.5c0-.177 0-.35-.012-.523A8.143 8.143 0 0 0 20 1.892Z"
                  clip-rule="evenodd" />
              </svg>
              <span class="sr-only">Twitter page</span>
            </a>
            <a href="https://github.com/Niraj-Paswan/stay" class="text-white hover:text-for dark:hover:text-white ms-5">
              <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M10 .333A9.911 9.911 0 0 0 6.866 19.65c.5.092.678-.215.678-.477 0-.237-.01-1.017-.014-1.845-2.757.6-3.338-1.169-3.338-1.169a2.627 2.627 0 0 0-1.1-1.451c-.9-.615.07-.6.07-.6a2.084 2.084 0 0 1 1.518 1.021 2.11 2.11 0 0 0 2.884.823c.044-.503.268-.973.63-1.325-2.2-.25-4.516-1.1-4.516-4.9A3.832 3.832 0 0 1 4.7 7.068a3.56 3.56 0 0 1 .095-2.623s.832-.266 2.726 1.016a9.409 9.409 0 0 1 4.962 0c1.89-1.282 2.717-1.016 2.717-1.016.366.83.402 1.768.1 2.623a3.827 3.827 0 0 1 1.02 2.659c0 3.807-2.319 4.644-4.525 4.889a2.366 2.366 0 0 1 .673 1.834c0 1.326-.012 2.394-.012 2.72 0 .263.18.572.681.475A9.911 9.911 0 0 0 10 .333Z"
                  clip-rule="evenodd" />
              </svg>
              <span class="sr-only">GitHub account</span>
            </a>
          </div>
        </div>
      </div>
    </footer>
  </body>

  <!-- JavaScript to toggle collapsible sections -->
  <script>
    // Toggles the visibility of collapsible sections and rotates the caret icon
    function toggleContent(id, icon) {
      const content = document.getElementById(id);
      content.classList.toggle("hidden");

      // Rotate the second icon (animate the rotation)
      icon.classList.toggle("rotate-180");
    }
    // First section's JavaScript (Booking Policies)
    const accordionHeader = document.querySelector(".accordion-header");
    const accordionContent = document.querySelector(".accordion-content");
    const dropdownIcon = document.getElementById("dropdown-icon");

    accordionHeader.addEventListener("click", () => {
      accordionContent.classList.toggle("hidden");
      // Toggle dropdown icon (caret up/down)
      if (accordionContent.classList.contains("hidden")) {
        dropdownIcon.classList.remove("fa-caret-up");
        dropdownIcon.classList.add("fa-caret-down");
      } else {
        dropdownIcon.classList.remove("fa-caret-down");
        dropdownIcon.classList.add("fa-caret-up");
      }
    });

    // Second section's JavaScript (Cancellation Policies)
    const accordionHeaderCancellation = document.querySelector(
      ".accordion-header-cancellation"
    );
    const accordionContentCancellation = document.querySelector(
      ".accordion-content-cancellation"
    );
    const dropdownIconCancellation = document.getElementById(
      "dropdown-icon-cancellation"
    );

    accordionHeaderCancellation.addEventListener("click", () => {
      accordionContentCancellation.classList.toggle("hidden");
      // Toggle dropdown icon (caret up/down)
      if (accordionContentCancellation.classList.contains("hidden")) {
        dropdownIconCancellation.classList.remove("fa-caret-up");
        dropdownIconCancellation.classList.add("fa-caret-down");
      } else {
        dropdownIconCancellation.classList.remove("fa-caret-down");
        dropdownIconCancellation.classList.add("fa-caret-up");
      }
    });
    document.querySelectorAll(".copy-btn").forEach((button) => {
      button.addEventListener("click", function () {
        const promoCode = this.getAttribute("data-code");
        navigator.clipboard.writeText(promoCode).then(() => {
          this.innerHTML = '<i class="fa-solid fa-check text-green-300"></i>';
          setTimeout(() => {
            this.innerHTML = '<i class="fa-regular fa-copy"></i>';
          }, 1500);
        });
      });
    });
  </script>

  </html>
<?php endforeach; ?>