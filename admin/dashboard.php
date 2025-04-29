<?php
// Database connection
include '../Database/dbconfig.php';

// Get filter values if set
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build SQL query with filters
$sql = "SELECT p.id, p.property_name, p.property_location, p.property_price, 
               p.latitude, p.longitude, p.main_image, p.property_type, 
               p.bathrooms, p.bedrooms, p.area, p.is_sharable, 
               (SELECT COUNT(*) FROM payments WHERE property_id = p.id) AS occupant_count 
        FROM properties p
        WHERE 1=1";

if (!empty($filter_type)) {
  $sql .= " AND p.property_type = '$filter_type'";
}

if (!empty($search_query)) {
  $sql .= " AND (p.property_name LIKE '%$search_query%' OR p.property_location LIKE '%$search_query%')";
}

$result = $conn->query($sql);

$properties = [];
$property_types = [];
$total_vacant = 0;
$total_partial = 0;
$total_booked = 0;

while ($row = mysqli_fetch_assoc($result)) {
  $row['occupant_count'] = (int) $row['occupant_count'];
  $is_sharable = $row['is_sharable'] == 1;

  // Determine booking status
  if ($row['occupant_count'] == 0) {
    $row['status'] = "Vacant";
    $row['status_color'] = "bg-green-500";
    $total_vacant++;
  } elseif ($row['occupant_count'] == 1) {
    if ($is_sharable) {
      $row['status'] = "Partially Booked";
      $row['status_color'] = "bg-yellow-500";
      $total_partial++;
    } else {
      $row['status'] = "Fully Booked";
      $row['status_color'] = "bg-red-500";
      $total_booked++;
    }
  } elseif ($row['occupant_count'] >= 2) {
    $row['status'] = "Fully Booked";
    $row['status_color'] = "bg-red-500";
    $total_booked++;
  }

  // Skip if status filter is applied and doesn't match
  if (!empty($filter_status) && strtolower($row['status']) != strtolower($filter_status)) {
    continue;
  }

  // Collect unique property types for filter
  if (!in_array($row['property_type'], $property_types)) {
    $property_types[] = $row['property_type'];
  }

  $properties[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StayEase - Properties</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc;
    }

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

    .property-card {
      transition: all 0.3s ease;
    }

    .property-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .status-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      border-radius: 9999px;
      padding: 0.25rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 500;
      color: white;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .filter-pill {
      transition: all 0.2s ease;
    }

    .filter-pill:hover {
      transform: scale(1.05);
    }

    .filter-pill.active {
      background-color: #3b82f6;
      color: white;
    }
  </style>
</head>

<body class="min-h-screen font-Nrj-fonts">
  <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
    <!-- Header Section -->
    <div class="mb-8">
      <h1 class="text-2xl font-semibold text-gray-900">Property Listings</h1>
      <p class="text-gray-600 mt-1">Manage your property portfolio</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500 font-medium">Total Properties</p>
            <h3 class="text-2xl font-bold text-gray-900"><?php echo count($properties); ?></h3>
          </div>
          <div class="bg-blue-100 px-3 py-2 rounded-full">
            <i class="fa-solid fa-building text-blue-500 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500 font-medium">Vacant</p>
            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_vacant; ?></h3>
          </div>
          <div class="bg-green-100 px-3 py-2 rounded-full">
            <i class="fa-solid fa-check-circle text-green-500 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500 font-medium">Partially Booked</p>
            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_partial; ?></h3>
          </div>
          <div class="bg-yellow-100 px-3 py-2 rounded-full">
            <i class="fa-solid fa-clock text-yellow-500 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5 border-l-4 border-red-500">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500 font-medium">Fully Booked</p>
            <h3 class="text-2xl font-bold text-gray-900"><?php echo $total_booked; ?></h3>
          </div>
          <div class="bg-red-100 px-3 py-2 rounded-full">
            <i class="fa-solid fa-ban text-red-500 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow p-5 mb-8">
      <div class="flex flex-col gap-4">

        <!-- Search Bar -->
        <div class="w-full relative">
          <form action="" method="GET" class="flex items-center">
            <div class="relative flex-grow">
              <input type="text" id="search-input" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                placeholder="Search properties..."
                class="w-full pl-10 pr-10 py-2 border-[1.5px] border-gray-300 rounded-lg focus:outline-none  focus:ring-black focus:border-black transition" />

              <!-- Search Icon -->
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fa-solid fa-search text-gray-400"></i>
              </div>

              <!-- Clear Button -->
              <button type="button" id="clear-btn"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500">
                <i class="fa-solid fa-times-circle"></i>
              </button>
            </div>

            <button type="submit" class="ml-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
              Search
            </button>
          </form>
        </div>

        <!-- Filters Section -->
        <div class="flex flex-wrap gap-2">
          <!-- All Properties Filter -->
          <a href="dashboard.php" class="filter-pill px-4 py-2 bg-gray-100 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-200 
        <?php echo empty($filter_type) && empty($filter_status) ? 'active' : ''; ?>">
            All
          </a>

          <!-- Property Types Filters -->
          <?php foreach ($property_types as $type): ?>
            <a href="?type=<?php echo urlencode($type); ?>" class="filter-pill px-4 py-2 bg-gray-100 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-200 
          <?php echo $filter_type === $type ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($type); ?>
            </a>
          <?php endforeach; ?>

          <!-- Status Filters -->
          <a href="?status=vacant" class="filter-pill px-4 py-2 bg-gray-100 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-200 
        <?php echo $filter_status === 'vacant' ? 'active' : ''; ?>">
            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span> Vacant
          </a>

          <a href="?status=partially booked" class="filter-pill px-4 py-2 bg-gray-100 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-200 
        <?php echo $filter_status === 'partially booked' ? 'active' : ''; ?>">
            <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-1"></span> Partially Booked
          </a>

          <a href="?status=fully booked" class="filter-pill px-4 py-2 bg-gray-100 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-200 
        <?php echo $filter_status === 'fully booked' ? 'active' : ''; ?>">
            <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span> Fully Booked
          </a>
        </div>
      </div>
    </div>

    <!-- JavaScript for Clear Button -->
    <script>
      document.getElementById("clear-btn").addEventListener("click", function () {
        document.getElementById("search-input").value = "";
      });
    </script>

    <!-- Property Grid -->
    <?php if (count($properties) > 0): ?>
      <div class="grid grid-cols-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($properties as $property): ?>
          <div
            class="property-card bg-white rounded-xl border border-gray-200 overflow-hidden shadow hover:shadow-lg transition-all duration-300 flex flex-col h-full">
            <!-- Property Image Section -->
            <div class="relative">
              <img src="../public/<?php echo $property['main_image']; ?>"
                alt="<?php echo htmlspecialchars($property['property_name']); ?>" class="w-full h-64 object-cover">

              <!-- Status Badge -->
              <div class="absolute top-4 left-4 px-3 py-1.5 rounded-full text-xs font-meduim shadow-sm
          <?php
          if ($property['status'] === 'Vacant') {
            echo 'bg-green-500 text-white';
          } elseif ($property['status'] === 'Partially Booked') {
            echo 'bg-yellow-500 text-white';
          } elseif ($property['status'] === 'Fully Booked') {
            echo 'bg-red-500 text-white';
          } else {
            echo 'bg-gray-300 text-gray-800'; // Default color for unknown status
          }
          ?>">
                <?php echo ucfirst($property['status']); ?>
              </div>

              <!-- Verified Badge -->
              <div
                class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm text-xs px-3 py-1.5 rounded-full shadow-sm flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 mr-1" viewBox="0 0 20 20"
                  fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
                </svg>
                <span class="font-medium">Verified</span>
              </div>

              <!-- Price Tag -->
              <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-sm px-4 py-2 rounded-lg shadow">
                <p class="text-base font-semibold text-blue-600">₹<?php echo number_format($property['property_price']); ?>
                  <span class="text-xs font-medium text-gray-500">/month</span>
                </p>
              </div>
            </div>

            <div class="px-5 pt-5 flex-grow">
              <!-- Property Type & Features -->
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded-md">
                  <?php echo htmlspecialchars($property['property_type']); ?>
                </span>

                <?php if ($property['is_sharable'] == 1): ?>
                  <span class="bg-emerald-100 text-emerald-800 text-xs font-medium px-2.5 py-1 rounded-md flex items-center">
                    <i class="fa-regular fa-user-group mr-1"></i>
                    Sharable
                  </span>
                <?php else: ?>
                  <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-md flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Solely
                  </span>
                <?php endif; ?>
              </div>

              <!-- Property Name -->
              <h3
                class="text-lg font-semibold text-gray-900 mb-2 line-clamp-1 hover:line-clamp-none transition-all duration-300">
                <?php echo htmlspecialchars($property['property_name']); ?>
              </h3>

              <!-- Location -->
              <div class="flex items-center text-gray-600 mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 flex-shrink-0" fill="none"
                  viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm truncate"><?php echo htmlspecialchars($property['property_location']); ?></p>
              </div>

              <!-- Property Features -->
              <div class="grid grid-cols-3 gap-3 border-t border-gray-200 pt-4 pb-2">
                <div class="flex flex-col items-center justify-center">
                  <div class="flex items-center justify-center w-10 h-10 mb-2 rounded-full bg-blue-50">
                    <i class="fa-regular fa-bed text-base text-blue-600"></i>
                  </div>
                  <span class="text-xs text-gray-500 mb-0.5">Bedroom</span>
                  <span class="text-sm font-semibold"><?php echo $property['bedrooms']; ?></span>
                </div>

                <div class="flex flex-col items-center justify-center">
                  <div class="flex items-center justify-center w-10 h-10 mb-2 rounded-full bg-blue-50">
                    <i class="fa-regular fa-bath text-base text-blue-600"></i>
                  </div>
                  <span class="text-xs text-gray-500 mb-0.5">Bathroom</span>
                  <span class="text-sm font-semibold"><?php echo $property['bathrooms']; ?></span>
                </div>

                <div class="flex flex-col items-center justify-center">
                  <div class="flex items-center justify-center w-10 h-10 mb-2 rounded-full bg-blue-50">
                    <i class="fa-regular fa-arrows-maximize text-base text-blue-600"></i>
                  </div>
                  <span class="text-xs text-gray-500 mb-0.5">Area</span>
                  <span class="text-sm font-semibold"><?php echo $property['area']; ?></span>
                </div>
              </div>
            </div>

            <!-- Card Footer with Action Button -->
            <div class="flex gap-2 p-5 mt-auto border-t border-gray-100">
              <a href="edit_property.php?id=<?php echo $property['id']; ?>"
                class="group flex-1 flex justify-center items-center gap-1.5 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                <!-- Regular Icon -->
                <i class="fa-regular fa-pen-to-square group-hover:hidden"></i>
                <!-- Solid Icon (Visible on Hover) -->
                <i class="fa-solid fa-pen-to-square hidden group-hover:inline"></i>
                <span>Edit</span>
              </a>

              <button onclick="confirmDelete(<?php echo $property['id']; ?>)"
                class="group flex-1 flex justify-center items-center gap-1.5 px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-700 font-medium rounded-lg transition-colors">
                <!-- Regular Icon -->
                <i class="fa-regular fa-trash group-hover:hidden"></i>
                <!-- Solid Icon (Visible on Hover) -->
                <i class="fa-solid fa-trash hidden group-hover:inline"></i>
                <span>Delete</span>
              </button>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center py-12 bg-gray-50 rounded-xl">
        <div class="bg-white inline-flex p-5 rounded-full mb-4 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Properties Found</h3>
        <p class="text-gray-600 max-w-md mx-auto">We couldn't find any properties matching your criteria.</p>
      </div>
    <?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
      <div class="bg-white rounded-lg p-6 w-96 shadow-xl animate-fadeInUp">
        <div class="text-center mb-6">
          <div class="inline-flex justify-center items-center w-16 h-16 rounded-full bg-red-100 mb-4">
            <i class="fa-solid fa-exclamation-triangle text-red-500 text-2xl"></i>
          </div>
          <h2 class="text-xl font-semibold text-gray-900">Confirm Delete</h2>
          <p class="mt-2 text-gray-600">Are you sure you want to delete this property? This action cannot be undone.</p>
        </div>

        <div class="flex gap-3">
          <button id="cancelBtn"
            class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
            Cancel
          </button>
          <button id="deleteBtn"
            class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition">
            Delete
          </button>
        </div>
      </div>
    </div>

    <script>
      let propertyIdToDelete = null;

      // Show modal popup for delete confirmation
      function confirmDelete(id) {
        propertyIdToDelete = id;
        document.getElementById("deleteModal").classList.remove("hidden");
        document.body.style.overflow = "hidden"; // Prevent scrolling when modal is open
      }

      // Cancel button hides the modal
      document.getElementById("cancelBtn").addEventListener("click", function () {
        document.getElementById("deleteModal").classList.add("hidden");
        document.body.style.overflow = "auto"; // Re-enable scrolling
      });

      // Delete button redirects to the delete URL
      document.getElementById("deleteBtn").addEventListener("click", function () {
        window.location.href = "delete_property.php?id=" + propertyIdToDelete;
      });

      // Close modal when clicking outside
      document.getElementById("deleteModal").addEventListener("click", function (e) {
        if (e.target === this) {
          this.classList.add("hidden");
          document.body.style.overflow = "auto";
        }
      });
    </script>
</body>

</html>