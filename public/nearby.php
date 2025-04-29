<?php
include '../Database/dbconfig.php';

$sql = "SELECT id, property_name, property_location, property_price, latitude, longitude, main_image,kitchen_img,gallery_img,washroom_img, property_type, bathrooms, bedrooms, area, is_sharable FROM properties";
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

session_start();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StayEase | Nearby</title>
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://api.mapbox.com/mapbox-gl-js/v3.9.1/mapbox-gl.css" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>
    .scrollbar-hidden {
      scrollbar-width: none;
      /* For Firefox */
      -ms-overflow-style: none;
      /* For Internet Explorer and Edge */
    }

    .scrollbar-hidden::-webkit-scrollbar {
      display: none;
      /* For Chrome, Safari, and Opera */
    }

    /* Custom styles for hover card */
    .hover-card {
      width: 300px;
      background: white;
      border-radius: 8px;
      border: 1px solid #ccc;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      position: relative;
    }

    .hover-card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
    }

    .hover-card i {
      position: absolute;
      top: 10px;
      right: 10px;
      background: white;
      padding: 5px;
      border-radius: 50%;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      cursor: pointer;
    }

    .hover-card .content {
      padding: 16px;
    }

    .hover-card .content h3 {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .hover-card .content h2 {
      margin-top: 8px;
      font-size: 16px;
      font-weight: 500;
      color: #555;
    }

    .hover-card .content h2 span {
      color: #080bae;
      font-size: 20px;
      font-weight: 700;
    }

    /* Remove extra white space and background in the popup */
    .mapboxgl-popup-content {
      padding: 0 !important;
      /* Remove padding */
      background-color: transparent !important;
      /* Remove the white background */
      border: none !important;
      /* Remove b/rder */
    }

    .mapboxgl-popup-tip {
      display: none !important;
      /* Remove the popup arrow */
    }
  </style>
  <script src="https://api.mapbox.com/mapbox-gl-js/v3.9.1/mapbox-gl.js"></script>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>

<body class="h-screen font-Nrj-fonts">
  <nav class="w-full h-16 bg-white border-b border-gray-300 px-6 flex items-center gap-6  ">
    <!-- Logo -->
    <a href="index.php" id="brands" class="font-Nrj-fonts font-semibold text-md flex items-center ml-2">
      <img class="w-7 h-7 ml-2" src="../assets/img/stayease logo.svg" alt="" />
      <p class="font-Nrj-fonts ml-2 text-lg text-black">StayEase</p>
    </a>

    <!-- Location Dropdown -->
    <div class="relative flex items-center bg-gray-50 border-[1.5px] border-gray-300 rounded-full px-2 py-2">
      <p class="font-medium text-sm px-2 py-1">
        Your Location
        <i class="fa-solid fa-location-dot text-sm text-red-600 mr-1"></i>:
        <!-- Actual Location -->
        <span id="user-location" class="text-sm font-semibold">Loading...</span>
      </p>
    </div>

    <!-- Search Input Container -->
    <div class="relative flex items-center w-full max-w-lg">
      <!-- Input Field Wrapper -->
      <div
        class="flex items-center w-full border-[1.5px] border-gray-300 bg-gray-50 px-3 py-0.5 rounded-full shadow-sm">
        <!-- Input Field -->
        <input type="text" id="search-input" placeholder="Search Nearby Properties by City or State"
          class="w-full bg-transparent text-gray-700 placeholder-gray-500 focus:outline-none text-sm p-3">

        <!-- Clear Button (Hidden by default, shows when input has text) -->
        <button id="clear-btn" onclick="document.getElementById('search-input').value=''; filterProperties();"
          class="text-gray-700 hover:text-red-500 bg-gray-50 hover:bg-gray-200 rounded-full w-12 h-10 flex  transition hidden">
          <i class="fa-solid fa-xmark "></i>
        </button>

        <!-- Search Button -->
        <button onclick="filterProperties()"
          class="bg-blue-500 text-white w-9 h-8 flex items-center justify-center rounded-full ml-2 hover:bg-blue-600 transition">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </div>
    </div>

    <!-- Get in Touch Button -->
    <button class="bg-blue-500 text-white px-4 py-3 rounded-full text-sm font-medium hover:bg-blue-600 transition">
      Contact Us
    </button>

  </nav>
  <script>
    // Toggle Clear Button Visibility
    document.getElementById('search-input').addEventListener('input', function () {
      toggleClearButton();
    });

    function toggleClearButton() {
      const clearBtn = document.getElementById('clear-btn');
      const searchInput = document.getElementById('search-input').value;
      clearBtn.style.display = searchInput.length > 0 ? 'block' : 'none';
    }
  </script>



  <div class="grid grid-cols-[60%_40%] h-full">
    <!-- Left Section: Vertically Scrollable without Visible Scrollbar -->
    <div class="overflow-y-auto scrollbar-hidden px-10 py-0">

      <div class="flex flex-row justify-between items-center mb-4 mt-4">
        <div class="flex  items-center space-x-4">
          <nav class="flex items-center text-gray-600 text-[16px]">
            <a href="index.php" class="hover:text-blue-500 transition hover:underline">Home</a>
            <span class="mx-3 text-gray-400">/</span>
            <p class="text-[16px] text-black font-medium mr-8">Properties</p>
          </nav>
        </div>
      </div>

      <div id="properties-list">
        <?php foreach ($properties as $property): ?>
          <div
            class="property-item flex flex-row border-[1.5px] border-gray-300 rounded-lg overflow-hidden bg-white mb-8 shadow-sm transition">

            <!-- Image Section -->
            <!-- Image Carousel Section -->
            <div class="relative w-80 overflow-hidden">
              <div class="carousel relative">
                <?php
                $images = [$property['image'], $property['kitchen'], $property['gallery'], $property['washroom']];
                foreach ($images as $index => $img):
                  ?>
                  <div
                    class="carousel-item absolute inset-0 transition-opacity duration-500 <?= $index === 0 ? 'opacity-100' : 'opacity-0' ?>">
                    <img class="w-full h-60 object-cover" src="<?= $img ?>" alt="Property Image" />
                  </div>
                  <div
                    class="absolute top-0 left-0 bg-white text-sm font-medium px-2 py-1 text-black rounded-tl-lg rounded-br-lg flex items-center gap-2 ">
                    <i class="fa-solid fa-badge-check mr-1 text-blue-600"></i> Verified
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- Navigation Arrows -->
              <button
                class=" flex justify-center items-center absolute left-2 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full h-7 w-7 bg-opacity-70 prev "
                onclick="prevSlide(this)">
                <i class="fa-solid fa-chevron-left text-gray-600 text-xs"></i>
              </button>
              <button
                class=" flex justify-center items-center absolute right-2 top-1/2 transform -translate-y-1/2 bg-white p-2 rounded-full h-7 w-7 bg-opacity-70 next"
                onclick="nextSlide(this)">
                <i class="fa-solid fa-chevron-right text-gray-600 text-xs"></i>
              </button>

              <!-- Dots Indicator -->
              <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-2">
                <?php foreach ($images as $index => $img): ?>
                  <span class="dot w-2 h-2 rounded-full bg-white opacity-50" data-index="<?= $index ?>"></span>
                <?php endforeach; ?>
              </div>
            </div>



            <!-- Property Info Section -->
            <div class="flex flex-col flex-1 px-6 py-5 space-y-4">

              <!-- Name & Location -->
              <div>
                <h2 class="text-2xl font-semibold text-gray-800"><?= $property['property_name'] ?></h2>
                <div class="flex items-center text-gray-600 text-sm mt-1">
                  <i class="fa-regular fa-location-dot mr-1"></i>
                  <?= $property['location'] ?>
                </div>
              </div>

              <!-- Badges -->
              <div class="flex flex-wrap gap-3">
                <div
                  class="badge flex items-center border border-gray-300 bg-white px-3 py-1 rounded-full text-gray-700 text-sm font-medium hover:shadow-sm">
                  <i class="fa-regular fa-home text-for mr-2"></i>
                  <span><?= $property['type'] ?></span>
                </div>

                <!-- Sharable or Sole Booking -->
                <div class="badge flex items-center gap-2 border border-gray-300 bg-white rounded-full px-3 py-1 ">
                  <i
                    class="<?= ($property['bedrooms'] == 2 && isset($property['is_sharable']) && $property['is_sharable'] == 1) ? 'fa-regular fa-user-group' : 'fa-regular fa-user' ?> text-for text-sm"></i>
                  <span class="text-gray-700 text-sm font-medium">
                    <?= ($property['bedrooms'] == 2 && isset($property['is_sharable']) && $property['is_sharable'] == 1) ? 'Sharable' : 'Sole Booking' ?>
                  </span>
                </div>

                <div
                  class="badge flex items-center border border-gray-300 bg-white px-3 py-1 rounded-full text-gray-700 text-sm font-medium hover:shadow-sm">
                  <i class="fa-regular fa-bolt text-for mr-2"></i>
                  <span>Quick Booking</span>
                </div>

                <div
                  class="badge flex items-center border border-gray-300 bg-white px-3 py-1 rounded-full text-gray-700 text-sm font-medium hover:shadow-sm">
                  <i class="fa-light fa-bed text-for"></i>
                  <p class="text-sm font-medium text-gray-800 ml-2">
                    <?= $property['bedrooms'] ?> Bedrooms
                  </p>
                </div>

                <div
                  class="badge flex items-center border border-gray-300 bg-white px-3 py-1 rounded-full text-gray-700 text-sm font-medium hover:shadow-sm">
                  <i class="fa-light fa-bath text-for"></i>
                  <p class="text-sm font-medium text-gray-800 ml-2">
                    <?= $property['bathrooms'] ?> Bathrooms
                  </p>
                </div>

                <div
                  class="badge flex items-center border border-gray-300 bg-white px-3 py-1 rounded-full text-gray-700 text-sm font-medium hover:shadow-sm">
                  <i class="fa-light fa-arrows-maximize text-for"></i>
                  <p class="text-sm font-medium text-gray-800 ml-2">
                    <?= $property['area'] ?> Sq.ft
                  </p>
                </div>
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

                <div class="flex flex-row justify-between items-center">
                  <!-- Price -->
                  <h2 class="text-sm font-normal text-gray-500">From <span
                      class="text-black text-xl font-semibold">₹<?= number_format($property['price']) ?><span
                        class="text-sm font-normal text-gray-500"> /month</span></span>
                  </h2>

                  <button type="submit"
                    class="w-[45%] py-2 px-4 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 shadow-sm">
                    View Details
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right Section: Fixed with Map -->
    <div class="bg-transparent p-4 h-full relative">
      <div id="map" class="h-full rounded-md border border-gray-300"></div>
    </div>
  </div>

  <script>
    function nextSlide(btn) {
      let carousel = btn.closest('.property-item').querySelector('.carousel');
      let items = carousel.querySelectorAll('.carousel-item');
      let dots = btn.closest('.property-item').querySelectorAll('.dot');
      let activeIndex = [...items].findIndex(item => item.classList.contains('opacity-100'));
      items[activeIndex].classList.replace('opacity-100', 'opacity-0');
      dots[activeIndex].classList.replace('opacity-100', 'opacity-50');
      let nextIndex = (activeIndex + 1) % items.length;
      items[nextIndex].classList.replace('opacity-0', 'opacity-100');
      dots[nextIndex].classList.replace('opacity-50', 'opacity-100');
    }

    function prevSlide(btn) {
      let carousel = btn.closest('.property-item').querySelector('.carousel');
      let items = carousel.querySelectorAll('.carousel-item');
      let dots = btn.closest('.property-item').querySelectorAll('.dot');
      let activeIndex = [...items].findIndex(item => item.classList.contains('opacity-100'));
      items[activeIndex].classList.replace('opacity-100', 'opacity-0');
      dots[activeIndex].classList.replace('opacity-100', 'opacity-50');
      let prevIndex = (activeIndex - 1 + items.length) % items.length;
      items[prevIndex].classList.replace('opacity-0', 'opacity-100');
      dots[prevIndex].classList.replace('opacity-50', 'opacity-100');
    }
    function filterProperties() {
      const searchInput = document.getElementById('search-input').value.toLowerCase();
      const propertyItems = document.querySelectorAll('.property-item');

      propertyItems.forEach((item) => {
        const location = item.querySelector('.fa-location-dot').nextSibling.textContent.toLowerCase();
        if (location.includes(searchInput)) {
          item.style.display = 'flex';
        } else {
          item.style.display = 'none';
        }
      });
    }
    // Initialize Mapbox map
    mapboxgl.accessToken =
      "pk.eyJ1IjoidHlwcm9qZWN0IiwiYSI6ImNtNTZxdWx6bjEwamUyaXMyc2poczd4OHAifQ.tuR-aGDXJdcOWzsmYz4hnw";

    let map = new mapboxgl.Map({
      container: "map", // The ID of the container where the map will be displayed
      style: "mapbox://styles/mapbox/streets-v11", // Default "streets" style
      center: [73.8245, 15.5916], // Coordinates of Mapusa, Goa (default location)
      zoom: 10, // Default zoom level
    });

    // Add navigation controls (zoom and rotation)
    map.addControl(new mapboxgl.NavigationControl());

    // Add Geolocate Control to the map (default "Find Current Location" button)
    const geolocate = new mapboxgl.GeolocateControl({
      positionOptions: {
        enableHighAccuracy: true,
      },
      trackUserLocation: true, // This will keep the location centered
    });

    map.addControl(geolocate);

    // Function to reverse geocode the latitude and longitude to human-readable address
    function reverseGeocode(lat, lon) {
      const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lon},${lat}.json?access_token=${mapboxgl.accessToken}`;

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          const place = data.features[0]?.place_name || "Unknown Location";
          const userLocationElement = document.getElementById("user-location");
          userLocationElement.textContent = place;
        })
        .catch((error) => {
          console.error("Error fetching geocoding data:", error);
          const userLocationElement = document.getElementById("user-location");
          userLocationElement.textContent = "Unable to fetch location";
        });
    }

    // Array of markers fetched from the database
    const markers = <?php echo json_encode($properties); ?>;

    // Function to calculate distance between two coordinates
    function getDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Radius of the Earth in kilometers
      const dLat = ((lat2 - lat1) * Math.PI) / 180;
      const dLon = ((lon1 - lon2) * Math.PI) / 180;
      const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c; // Distance in kilometers
    }

    // Function to find the nearest marker
    function findNearestMarker(userLat, userLon) {
      let nearestMarker = null;
      let minDistance = Infinity; // Initialize with a large value

      markers.forEach((marker) => {
        const [lon, lat] = marker.coordinates;
        const distance = getDistance(userLat, userLon, lat, lon);
        if (distance < minDistance) {
          nearestMarker = marker;
          minDistance = distance;
        }
      });

      return { nearestMarker, minDistance };
    }

    // Add the markers to the map with hover cards
    markers.forEach((marker) => {
      const popupContent = `
    <div class="hover-card">
      <img src="${marker.image}" alt="Property" />
      <i class="fa-regular fa-xmark close-popup" style="cursor:pointer;"></i>
      <div class="content font-Nrj-fonts">
        <h3 class="font-bold text-lg">${marker.property_name}</h3> 
        <h1 class="font-normal text-sm">${marker.location}</h1> 
        <h2>From <span>₹${marker.price}</span> /Month</h2>
        <p>Distance: ${getDistance(15.5916, 73.8245, marker.coordinates[1], marker.coordinates[0]).toFixed(2)} km</p>
      </div>
    </div>
  `;

      const popup = new mapboxgl.Popup({
        offset: 25,
        closeButton: false,
      }).setHTML(popupContent);

      // Create marker with popup
      new mapboxgl.Marker({ color: "blue" })
        .setLngLat(marker.coordinates)
        .setPopup(popup)
        .addTo(map);

      // Attach event listener to the close icon after popup opens
      popup.on('open', () => {
        const popupElement = popup.getElement();
        const closeBtn = popupElement.querySelector('.close-popup');
        if (closeBtn) {
          closeBtn.addEventListener('click', () => {
            popup.remove();
          });
        }
      });
    });


    // Geolocate event listener
    geolocate.on("geolocate", (event) => {
      const { latitude, longitude } = event.coords;
      reverseGeocode(latitude, longitude);

      // Update map center to user's location
      map.setCenter([longitude, latitude]);

      // Find the nearest marker and open its popup
      const { nearestMarker, minDistance } = findNearestMarker(latitude, longitude);
      if (nearestMarker) {
        const popup = new mapboxgl.Popup({
          offset: 25,
          closeButton: false,
        }).setHTML(
          `<div class="hover-card">
            <img src="${nearestMarker.image}" alt="Property" />
            <i class="fa-regular fa-xmark"></i>
            <div class="content font-Nrj-fonts">
              <h3>${nearestMarker.property_name}</h3>
              <h2>From <span>₹${nearestMarker.price}</span> /Month</h2>
              <p>Distance: ${minDistance.toFixed(2)} km</p>
            </div>
          </div>`
        );

        popup.setLngLat(nearestMarker.coordinates).addTo(map);
      }

      // Update distances for all properties
      markers.forEach((marker, index) => {
        const distance = getDistance(latitude, longitude, marker.coordinates[1], marker.coordinates[0]);
        document.querySelectorAll('.property-distance')[index].textContent = `Distance: ${distance.toFixed(2)} km`;
      });
    });

  </script>
</body>

</html>
</script>
</body>

</html>