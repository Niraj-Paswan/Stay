<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StayEase | Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
    crossorigin="anonymous" referrerpolicy="no-referrer">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    html,
    body {
      height: 100%;
      font-family: 'Poppins', sans-serif;
    }

    /* Floating Button Styles */
    .floating-button {
      position: fixed;
      bottom: 20px;
      right: 40px;
      background-color: white;
      border: 2px solid gray;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .floating-button:hover {
      background-color: #f3f3f3;
    }

    .floating-button::after {
      content: "Add New Listing";
      position: absolute;
      bottom: 60px;
      right: 50%;
      transform: translateX(50%);
      background-color: black;
      color: white;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 12px;
      white-space: nowrap;
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .floating-button:hover::after {
      opacity: 1;
    }

    .navbar {
      width: 100%;
      background-color: #ffffff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      padding: 15px 20px;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .navbar .brand {
      display: flex;
      align-items: center;
      font-weight: 600;
      color: #1d4ed8;
    }

    .navbar .brand img {
      width: 40px;
      height: 40px;
      margin-right: 10px;
    }

    .user-menu {
      position: relative;
      cursor: pointer;
    }

    .user-menu i {
      font-size: 2.5rem;
      color: #1d4ed8;
    }

    .user-dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: 50px;
      background: white;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      padding: 10px;
    }

    .user-dropdown a {
      display: flex;
      align-items: center;
      padding: 8px 12px;
      color: #333;
      text-decoration: none;
    }

    .user-dropdown a i {
      margin-right: 8px;
      color: red;
    }

    .user-dropdown a:hover {
      background: #f3f3f3;
    }

    .sidebar {
      width: 250px;
      background-color: #ffffff;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
      padding: 20px;
      height: 100vh;
      position: fixed;
      top: 60px;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      margin-bottom: 10px;
      border-radius: 8px;
      color: #4b5563;
      font-weight: 500;
      transition: background 0.3s ease;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background-color: #1d4ed8;
      color: #ffffff;
    }

    .sidebar i {
      margin-right: 10px;
    }

    .main-content {
      margin-left: 270px;
      padding: 90px 0px 0px;
      width: calc(100% - 270px);
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="navbar">
    <div class="brand">
      <img src="../assets/img/stayease logo.svg" alt="StayEase Logo">
      <span class="text-2xl font-semibold text-black">StayEase</span>
    </div>
    <div class="user-menu" onclick="toggleDropdown()" style="font-size: 1.5rem; padding: 5px;">
      <span class="font-meduim text-lg mr-2">Hii 👋 Admin!</span>
      <i class="fas fa-user-circle" style="font-size: 1.8rem;"></i>
      <div class="user-dropdown" id="userDropdown" style="min-width: 120px; padding: 5px 10px;">
        <a href="#" onclick="showSignOutConfirmation()" style="font-size: 0.9rem; padding: 5px 8px;">
          <i class="fas fa-sign-out-alt" style="font-size: 1rem;"></i> Log Out
        </a>
      </div>
    </div>

    <div id="signOutModal" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 hidden ">
      <div class="bg-white p-6 rounded-md shadow-lg w-96">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">
          Are you sure you want to log out?
        </h2>
        <div class="flex justify-end gap-8 space-x-4">
          <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" onclick="closeModal()">
            No
          </button>
          <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-red-700" onclick="signOut()">
            Yes
          </button>
        </div>
      </div>
    </div>

    <script>
      function showSignOutConfirmation() {
        document.getElementById("signOutModal").classList.remove("hidden");
      }

      function closeModal() {
        document.getElementById("signOutModal").classList.add("hidden");
      }

      function signOut() {
        alert("You have been signed out.");
        window.location.href = "../public/adminlogin.php";
        closeModal();
      }
    </script>

  </div>

  <div class="sidebar">
    <nav>
      <a href="#" class="active mt-4 " onclick="showContent('main_dashboard', this)">
        <i data-lucide="chart-column" class="sidebar-icon mr-2 w-5 h-5"></i> Dashboard
      </a>
      <a href="#" onclick="showContent('dashboard', this)">
        <i data-lucide="home" class="sidebar-icon  mr-2 w-5 h-5"></i> Listings
      </a>
      <a href="#" onclick="showContent('bookinggs', this)">
        <i data-lucide="calendar-check " class="sidebar-icon  mr-2 w-5 h-5"></i> Bookings
      </a>
      <a href="#" onclick="showContent('user', this)">
        <i data-lucide="users" class="sidebar-icon  mr-2 w-5 h-5"></i> Users
      </a>
      <a href="#" onclick="showContent('payments', this)">
        <i data-lucide="credit-card" class="sidebar-icon  mr-2 w-5 h-5"></i> Payments
      </a>
      <a href="#" onclick="showContent('query_info', this)">
        <i data-lucide="headphones" class="sidebar-icon  mr-2 w-5 h-5"></i> Queries
      </a>
    </nav>
  </div>

  <div class="main-content">
    <iframe class="w-full h-screen" src="../admin/main_dashboard.php" frameborder="0" id="content-frame"></iframe>
  </div>
  <div class="floating-button" onclick="location.href='property_upload.html'">
    <i class="fa-solid fa-house-circle-check text-black text-xl"></i>
  </div>

  <script>
    function showContent(contentId, clickedItem) {
      document.querySelectorAll(".sidebar a").forEach(link => {
        link.classList.remove("active");
      });
      clickedItem.classList.add("active");
      document.getElementById("content-frame").src = "../admin/" + contentId + ".php";
    }

    function toggleDropdown() {
      const dropdown = document.getElementById("userDropdown");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    function logout() {
      window.location.href = "../public/adminlogin.php";
    }

    document.addEventListener("click", (event) => {
      const dropdown = document.getElementById("userDropdown");
      const userMenu = document.querySelector(".user-menu");
      if (!userMenu.contains(event.target)) {
        dropdown.style.display = "none";
      }
    });
  </script>
  <script>
    // Initialize Lucide icons after the page has loaded
    lucide.createIcons();
  </script>

</body>

</html>