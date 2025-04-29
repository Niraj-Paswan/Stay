<?php
// Get the latitude and longitude from URL parameters
$latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : 0;
$longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>Location | With Amenities</title>
  <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
    rel="stylesheet" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css" />
  <link href="https://api.mapbox.com/mapbox-gl-js/v3.9.1/mapbox-gl.css" rel="stylesheet" />
  <script src="https://api.mapbox.com/mapbox-gl-js/v3.9.1/mapbox-gl.js"></script>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }



    /* Close Button */
    .mapboxgl-popup-close-button {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      background-color: white;
      border-radius: 50%;
      width: 1.5rem;
      height: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: box-shadow 0.2s ease;
      border: none;
    }

    .mapboxgl-popup-close-button:hover {
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Ensure the map container fills its area */
    #map {
      width: 100%;
      height: 100%;
    }

    /* Tab styles and other UI elements (unchanged) */
    .tab {
      transition: all 0.3s ease;
      border-bottom: 2px solid transparent;
    }

    .tab.active {
      border-bottom: 2px solid #3b82f6;
      color: #3b82f6;
    }

    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    .map-style-selector {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }

    .map-style-selector.open {
      max-height: 300px;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    .animate-fade-in {
      animation: fadeIn 0.3s ease-in-out;
    }

    .place-item {
      transition: all 0.2s ease;
    }

    .place-item:hover {
      transform: translateY(-2px);
    }

    .badge {
      font-size: 0.75rem;
      padding: 0.125rem 0.5rem;
      border-radius: 9999px;
    }
  </style>
</head>

<body class="m-0 p-0 h-screen overflow-hidden bg-gray-50">
  <div class="flex flex-col md:flex-row h-full">
    <!-- Sidebar -->
    <div id="sidebar" class="w-full md:w-80 bg-white shadow-lg z-10 flex flex-col h-full">
      <!-- Header -->
      <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold flex items-center gap-2 text-gray-800">
          <i class="fa-solid fa-map-pin text-blue-500"></i>
          Nearby Amenities
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Explore places around your location
        </p>
      </div>
      <!-- Tabs -->
      <div class="flex border-b border-gray-200 pr-4">
        <button class="tab active flex-1 py-3 px-2 text-sm font-medium flex flex-col items-center gap-1"
          data-tab="restaurants">
          <i class="fas fa-utensils text-red-500"></i>
          <span>Dining</span>
        </button>
        <button class="tab flex-1 py-3 px-2 text-sm font-medium flex flex-col items-center gap-1" data-tab="hospitals">
          <i class="fas fa-house-medical text-black"></i>
          <span>Health</span>
        </button>
        <button class="tab flex-1 py-3 px-2 text-sm font-medium flex flex-col items-center gap-1" data-tab="railways">
          <i class="fas fa-train-subway text-green-600"></i>
          <span>Transit</span>
        </button>
        <button class="tab flex-1 py-3 px-2 text-sm font-medium flex flex-col items-center gap-1"
          data-tab="supermarkets">
          <i class="fas fa-cart-shopping text-yellow-600"></i>
          <span>Shop</span>
        </button>
        <button class="tab flex-1 py-3 px-2 text-sm font-medium flex flex-col items-center gap-1" data-tab="colleges">
          <i class="fas fa-graduation-cap text-purple-600"></i>
          <span>Colleges</span>
        </button>
      </div>
      <!-- Tab Content -->
      <div class="flex-1 overflow-hidden">
        <!-- Restaurants Tab -->
        <div class="tab-content active h-full flex flex-col" id="restaurants-content">
          <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="bg-red-500 h-8 w-8 p-1 rounded-full text-white flex items-center justify-center">
                <i class="fa-regular fa-utensils text-xs"></i>
              </div>
              <h2 class="font-medium text-gray-700">Restaurants</h2>
            </div>
            <span class="badge bg-gray-100 text-gray-600" id="restaurants-count">0 Results</span>
          </div>
          <!-- Scrollable area -->
          <div class="px-4 pb-4 flex-1 overflow-y-auto custom-scrollbar w-full">
            <ul class="space-y-3" id="restaurants-list"></ul>
          </div>
        </div>

        <!-- Hospitals Tab -->
        <div class="tab-content hidden h-full flex flex-col" id="hospitals-content">
          <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="bg-black h-8 w-8 p-1 rounded-full text-white flex items-center justify-center">
                <i class="fa-regular fa-house-medical text-xs"></i>
              </div>
              <h2 class="font-medium text-gray-700">Hospitals</h2>
            </div>
            <span class="badge bg-gray-100 text-gray-600" id="hospitals-count">0 Results</span>
          </div>
          <div class="px-4 pb-4 flex-1 overflow-y-auto custom-scrollbar w-full">
            <ul class="space-y-3" id="hospitals-list"></ul>
          </div>
        </div>

        <!-- Railways Tab -->
        <div class="tab-content hidden h-full flex flex-col" id="railways-content">
          <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="bg-green-600 h-8 w-8 p-1 rounded-full text-white flex items-center justify-center">
                <i class="fa-regular fa-train-subway text-xs"></i>
              </div>
              <h2 class="font-medium text-gray-700">Railways</h2>
            </div>
            <span class="badge bg-gray-100 text-gray-600" id="railways-count">0 Results</span>
          </div>
          <div class="px-4 pb-4 flex-1 overflow-y-auto custom-scrollbar w-full">
            <ul class="space-y-3" id="railways-list"></ul>
          </div>
        </div>

        <!-- Supermarkets Tab -->
        <div class="tab-content hidden h-full flex flex-col" id="supermarkets-content">
          <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="bg-yellow-500 h-8 w-8 p-1 rounded-full text-white flex items-center justify-center">
                <i class="fa-regular fa-cart-shopping text-xs"></i>
              </div>
              <h2 class="font-medium text-gray-700">Supermarkets</h2>
            </div>
            <span class="badge bg-gray-100 text-gray-600" id="supermarkets-count">0 Results</span>
          </div>
          <div class="px-4 pb-4 flex-1 overflow-y-auto custom-scrollbar w-full">
            <ul class="space-y-3" id="supermarkets-list"></ul>
          </div>
        </div>

        <!-- Colleges Tab -->
        <div class="tab-content hidden h-full flex flex-col" id="colleges-content">
          <div class="p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="bg-purple-600 h-8 w-8 p-1 rounded-full text-white flex items-center justify-center">
                <i class="fa-regular fa-graduation-cap text-xs"></i>
              </div>
              <h2 class="font-medium text-gray-700">Colleges</h2>
            </div>
            <span class="badge bg-gray-100 text-gray-600" id="colleges-count">0 Results</span>
          </div>
          <div class="px-4 pb-4 flex-1 overflow-y-auto custom-scrollbar w-full">
            <ul class="space-y-3" id="colleges-list"></ul>
          </div>
        </div>
      </div>
    </div>
    <!-- Map Container -->
    <div class="flex-1 relative">
      <div id="map"></div>
      <!-- Map Style Control -->
      <div class="absolute top-4 left-4 z-10 bg-white rounded-lg shadow-lg overflow-hidden w-48">
        <div id="style-toggle" class="p-3 flex items-center justify-between cursor-pointer hover:bg-gray-50">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-layer-group text-blue-500"></i>
            <span class="font-medium text-sm">Map Style</span>
          </div>
          <i class="fa-solid fa-chevron-down text-gray-400 transition-transform" id="style-chevron"></i>
        </div>
        <div class="map-style-selector border-t border-gray-100" id="style-selector">
          <div class="p-2 space-y-1">
            <div class="style-option flex items-center p-2 rounded-md cursor-pointer hover:bg-gray-100 active-style"
              data-style="Streets">
              <input type="radio" name="map-style" id="style-streets" class="mr-2" checked>
              <label for="style-streets" class="text-sm cursor-pointer">Streets</label>
            </div>
            <div class="style-option flex items-center p-2 rounded-md cursor-pointer hover:bg-gray-100"
              data-style="Satellite">
              <input type="radio" name="map-style" id="style-satellite" class="mr-2">
              <label for="style-satellite" class="text-sm cursor-pointer">Satellite</label>
            </div>
            <div class="style-option flex items-center p-2 rounded-md cursor-pointer hover:bg-gray-100"
              data-style="Light">
              <input type="radio" name="map-style" id="style-light" class="mr-2">
              <label for="style-light" class="text-sm cursor-pointer">Light</label>
            </div>
            <div class="style-option flex items-center p-2 rounded-md cursor-pointer hover:bg-gray-100"
              data-style="Dark">
              <input type="radio" name="map-style" id="style-dark" class="mr-2">
              <label for="style-dark" class="text-sm cursor-pointer">Dark</label>
            </div>
            <div class="style-option flex items-center p-2 rounded-md cursor-pointer hover:bg-gray-100"
              data-style="Navigation">
              <input type="radio" name="map-style" id="style-navigation" class="mr-2">
              <label for="style-navigation" class="text-sm cursor-pointer">Navigation</label>
            </div>
          </div>
        </div>
      </div>
      <!-- Map Controls -->
      <div class="absolute bottom-4 right-4 z-10 flex flex-col gap-2">
        <button id="fullscreen-btn"
          class="bg-white p-2 rounded-full shadow-lg hover:bg-gray-50 transition-colors hidden">
          <i class="fa-solid fa-expand text-gray-700"></i>
        </button>
        <button id="center-btn" class="bg-white p-2 rounded-full shadow-lg hover:bg-gray-50 transition-colors hidden">
          <i class="fa-solid fa-location-crosshairs text-gray-700"></i>
        </button>
      </div>
    </div>
  </div>
  <script>
    // Haversine Formula to calculate distance between two points on Earth
    function calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Earth's radius in km
      const φ1 = (lat1 * Math.PI) / 180;
      const φ2 = (lat2 * Math.PI) / 180;
      const Δφ = ((lat2 - lat1) * Math.PI) / 180;
      const Δλ = ((lon2 - lon1) * Math.PI) / 180;
      const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    // Tab functionality
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        // Remove active class from all tabs and content
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        // Add active class to clicked tab and show corresponding content
        tab.classList.add('active');
        const tabId = tab.getAttribute('data-tab');
        document.getElementById(`${tabId}-content`).classList.remove('hidden');
        document.getElementById(`${tabId}-content`).classList.add('active');
      });
    });

    // Map style selector toggle
    const styleToggle = document.getElementById('style-toggle');
    const styleSelector = document.getElementById('style-selector');
    const styleChevron = document.getElementById('style-chevron');
    styleToggle.addEventListener('click', () => {
      styleSelector.classList.toggle('open');
      styleChevron.style.transform = styleSelector.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0)';
    });

    // Fullscreen toggle
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    fullscreenBtn.addEventListener('click', () => {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        fullscreenBtn.innerHTML = '<i class="fa-solid fa-compress text-gray-700"></i>';
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
          fullscreenBtn.innerHTML = '<i class="fa-solid fa-expand text-gray-700"></i>';
        }
      }
    });

    // Mapbox access token
    mapboxgl.accessToken = "pk.eyJ1IjoidHlwcm9qZWN0IiwiYSI6ImNtNTZxdWx6bjEwamUyaXMyc2poczd4OHAifQ.tuR-aGDXJdcOWzsmYz4hnw";

    // Define centerCoordinates using dynamic PHP values
    let centerCoordinates = {
      lat: <?= $latitude ?>,
      lng: <?= $longitude ?>
    };
    console.log("Center Coordinates:", centerCoordinates);

    // Initialize the Map using centerCoordinates
    const map = new mapboxgl.Map({
      container: "map",
      style: "mapbox://styles/mapbox/streets-v12",
      center: [centerCoordinates.lng, centerCoordinates.lat],
      zoom: 12,
    });

    // Add Map Controls
    map.addControl(new mapboxgl.NavigationControl(), "top-right");
    map.addControl(new mapboxgl.FullscreenControl(), "top-right");
    map.addControl(
      new mapboxgl.GeolocateControl({
        positionOptions: { enableHighAccuracy: true },
        trackUserLocation: true,
        showUserHeading: true,
      }),
      "top-right"
    );
    map.addControl(new mapboxgl.ScaleControl(), "bottom-right");

    // Map style options
    const layers = {
      Streets: "mapbox://styles/mapbox/streets-v12",
      Satellite: "mapbox://styles/mapbox/satellite-v9",
      Light: "mapbox://styles/mapbox/light-v11",
      Dark: "mapbox://styles/mapbox/dark-v11",
      Navigation: "mapbox://styles/mapbox/navigation-day-v1",
    };

    // Style option click handlers
    document.querySelectorAll('.style-option').forEach(option => {
      option.addEventListener('click', () => {
        const style = option.getAttribute('data-style');
        map.setStyle(layers[style]);

        // Update active style
        document.querySelectorAll('.style-option').forEach(opt => {
          opt.classList.remove('active-style');
          opt.querySelector('input').checked = false;
        });
        option.classList.add('active-style');
        option.querySelector('input').checked = true;
      });
    });

    // Center button click handler
    document.getElementById('center-btn').addEventListener('click', () => {
      map.flyTo({
        center: [centerCoordinates.lng, centerCoordinates.lat],
        zoom: 12,
        essential: true
      });
    });

    // Build Overpass query using centerCoordinates including colleges/universities
    const query = ` 
      [out:json];
      (
        node["amenity"="restaurant"](around:10000, ${centerCoordinates.lat}, ${centerCoordinates.lng});
        node["amenity"="hospital"](around:10000, ${centerCoordinates.lat}, ${centerCoordinates.lng});
        node["railway"="station"](around:10000, ${centerCoordinates.lat}, ${centerCoordinates.lng});
        node["shop"="supermarket"](around:10000, ${centerCoordinates.lat}, ${centerCoordinates.lng});
        node["amenity"="college"](around:10000, ${centerCoordinates.lat}, ${centerCoordinates.lng});
        node["amenity"="university"](around:10000, ${centerCoordinates.lat}, ${centerCoordinates.lng});
      );
      out body;
    `;
    const overpassUrl = "https://overpass-api.de/api/interpreter?data=" + encodeURIComponent(query);
    console.log("Overpass URL:", overpassUrl);

    // Store all markers for later reference
    const allMarkers = [];

    // Helper function to create a custom marker element with an icon based on category
    function createCustomMarker(category, color) {
      let iconHTML = "";
      // Set category-specific icons (using FontAwesome classes)
      switch (category) {
        case "Restaurant":
          iconHTML = '<i class="fa-regular fa-utensils text-white text-xs"></i>';
          break;
        case "Hospital":
          iconHTML = '<i class="fa-regular fa-house-medical text-white text-xs"></i>';
          break;
        case "Railway":
          iconHTML = '<i class="fa-regular fa-train-subway text-white text-xs"></i>';
          break;
        case "Supermarket":
          iconHTML = '<i class="fa-regular fa-cart-shopping text-white text-xs"></i>';
          break;
        case "College":
          iconHTML = '<i class="fa-regular fa-graduation-cap text-white text-xs"></i>';
          break;
        default:
          iconHTML = '<i class="fa-regular fa-map-marker-alt text-white text-xs"></i>';
      }
      const markerEl = document.createElement("div");
      markerEl.className = "custom-marker";
      markerEl.innerHTML = iconHTML;
      // Style the marker element (bigger size and background color)
      markerEl.style.backgroundColor = color;
      markerEl.style.width = "30px";
      markerEl.style.height = "30px";
      markerEl.style.display = "flex";
      markerEl.style.alignItems = "center";
      markerEl.style.justifyContent = "center";
      markerEl.style.borderRadius = "50%";
      markerEl.style.fontSize = "16px";
      markerEl.style.cursor = "pointer";
      return markerEl;
    }

    // Fetch nearby amenities from Overpass API
    fetch(overpassUrl)
      .then(response => response.json())
      .then(data => {
        console.log("Overpass API Data:", data);
        const restaurantList = [];
        const hospitalList = [];
        const railwayList = [];
        const supermarketList = [];
        const collegeList = [];
        data.elements.forEach(place => {
          const placeName = place.tags.name || "Unnamed Location";
          const placeType = place.tags.amenity || place.tags.railway || place.tags.shop;
          const placeLat = place.lat;
          const placeLng = place.lon;
          const distance = calculateDistance(centerCoordinates.lat, centerCoordinates.lng, placeLat, placeLng).toFixed(2);
          if (placeType === "restaurant" && restaurantList.length < 5) {
            restaurantList.push({ name: placeName, lat: placeLat, lng: placeLng, distance: distance });
          } else if (placeType === "hospital" && hospitalList.length < 5) {
            hospitalList.push({ name: placeName, lat: placeLat, lng: placeLng, distance: distance });
          } else if (placeType === "station" && railwayList.length < 5) {
            railwayList.push({ name: placeName, lat: placeLat, lng: placeLng, distance: distance });
          } else if (placeType === "supermarket" && supermarketList.length < 5) {
            supermarketList.push({ name: placeName, lat: placeLat, lng: placeLng, distance: distance });
          } else if ((placeType === "college" || placeType === "university") && collegeList.length < 5) {
            collegeList.push({ name: placeName, lat: placeLat, lng: placeLng, distance: distance });
          }
        });
        // Update sidebar result counts
        document.getElementById('restaurants-count').textContent = `${restaurantList.length} Results`;
        document.getElementById('hospitals-count').textContent = `${hospitalList.length} Results`;
        document.getElementById('railways-count').textContent = `${railwayList.length} Results`;
        document.getElementById('supermarkets-count').textContent = `${supermarketList.length} Results`;
        document.getElementById('colleges-count').textContent = `${collegeList.length} Results`;

        // Function to create a place card (for sidebar)
        function createPlaceCard(place) {
          return `
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 place-item animate-fade-in transition-transform duration-200 ease-in-out hover:shadow-md cursor-pointer">
              <div class="font-medium text-gray-800">${place.name}</div>
              <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                <i class="fa-solid fa-location-arrow text-xs"></i>
                ${place.distance} km away
              </div>
            </div>
          `;
        }

        // Function to populate each category in the sidebar
        function populateCategory(list, listId, category, color) {
          const container = document.getElementById(listId);
          if (list.length === 0) {
            container.innerHTML = `
              <div class="text-center py-8 text-gray-400">
                <i class="fa-solid fa-map-pin text-gray-300 text-3xl mb-2"></i>
                <p>No ${category.toLowerCase()} found nearby</p>
              </div>
            `;
            return;
          }
          list.forEach(place => {
            const listItem = document.createElement('li');
            listItem.innerHTML = createPlaceCard(place);
            container.appendChild(listItem);
            // Create custom marker element based on category
            const markerEl = createCustomMarker(category, color);
            const marker = new mapboxgl.Marker({ element: markerEl })
              .setLngLat([place.lng, place.lat])
              .setPopup(
                new mapboxgl.Popup({ offset: 25 }).setHTML(
                  `<div class="custom-popup font-Nrj-fonts">
  <!-- Close Button -->
  <button class="mapboxgl-popup-close-button">
    <i class="fa-solid fa-xmark text-sm"></i>
  </button>
    <h3 class="text-lg font-semibold text-gray-800 ">${place.name}</h3>
  <p class="text-sm text-gray-600 mb-3">${category}</p>
  <!-- Distance -->
  <div class="flex items-center gap-2 text-sm text-gray-500">
    <i class="fa-solid fa-ruler text-xs"></i>
    <span>Distance: ${place.distance} km</span>
  </div>
</div>`
                )
              )
              .addTo(map);
            allMarkers.push(marker);
            listItem.addEventListener('click', () => {
              map.flyTo({ center: [place.lng, place.lat], zoom: 14, essential: true });
              marker.togglePopup();
            });
          });
        }

        // Add a marker for the center location
        const centerMarker = new mapboxgl.Marker({ color: "#3b82f6" })
          .setLngLat([centerCoordinates.lng, centerCoordinates.lat])
          .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(
            `<div class="p-4">
              <h3 class="text-xl font-semibold text-gray-800">Center Location</h3>
              <p class="text-sm text-gray-600 mt-2">Your reference point</p>
            </div>`
          ))
          .addTo(map);
        allMarkers.push(centerMarker);

        // Populate sidebar categories with fetched results
        populateCategory(restaurantList, "restaurants-list", "Restaurant", "#ef4444");
        populateCategory(hospitalList, "hospitals-list", "Hospital", "#000000");
        populateCategory(railwayList, "railways-list", "Railway", "#22c55e");
        populateCategory(supermarketList, "supermarkets-list", "Supermarket", "#f59e0b");
        populateCategory(collegeList, "colleges-list", "College", "#8b5cf6");
      })
      .catch(error => {
        console.error("Error fetching Overpass data:", error);
        const errorMessage = `
          <div class="text-center py-8 text-red-500">
            <i class="fa-solid fa-triangle-exclamation text-3xl mb-2"></i>
            <p>Error loading data. Please try again.</p>
          </div>
        `;
        document.getElementById('restaurants-list').innerHTML = errorMessage;
        document.getElementById('hospitals-list').innerHTML = errorMessage;
        document.getElementById('railways-list').innerHTML = errorMessage;
        document.getElementById('supermarkets-list').innerHTML = errorMessage;
        document.getElementById('colleges-list').innerHTML = errorMessage;
      });
  </script>
</body>

</html>