<?php
include '../Database/dbconfig.php';
// SQL query to fetch booking details
$sql = "SELECT 
            p.userID, 
            p.transaction_id, 
            u.full_name, 
            u.email_address, 
            pr.property_name, 
            u.rent_start_date, 
            DATE_ADD(u.rent_start_date, INTERVAL 1 MONTH) AS next_due_date, 
            p.payment_status, 
            p.payment_method ,
            p.booking_type
        FROM payments p
        JOIN users u ON p.userID = u.userID
        JOIN properties pr ON p.property_id = pr.id";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Booking Details - Admin</title>
  <!-- Link to Tailwind CSS -->
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <link rel="shortcut icon" href="../assets/img/stayease logo.svg" type="image/x-icon" />
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.5.1/css/all.css" />
  <!-- Google Fonts for Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>

<body class="bg-gray-100 font-Nrj-fonts">
  <div class="w-full bg-gray-100 p-8">
    <!-- Section Title -->
    <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
      Booking Details
    </h2>

    <!-- Booking Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white border border-gray-200 table-auto">
        <!-- Table Header -->
        <thead>
          <tr class="bg-for">
            <th class="py-3 px-6 text-left text-sm font-semibold text-white rounded-tl-md">
              User ID
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Transaction ID
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Name
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Email
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Property Name
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Rent Start Date
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Next Due Date
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">
              Booking Status
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white ">
              Payment Method
            </th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white rounded-tr-md">
              Booking Type
            </th>

          </tr>
        </thead>
        <!-- Table Body -->
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr class='border-b border-gray-200'>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["userID"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["transaction_id"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["full_name"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["email_address"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["property_name"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["rent_start_date"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["next_due_date"] . "</td>";

              $status = $row["payment_status"];
              if ($status == "successful") {
                $classes = "bg-green-100 text-green-800 rounded-full px-2.5 py-0.5 inline-flex items-center text-xs font-semibold";
              } elseif ($status == "cancelled") {
                $classes = "bg-red-100 text-red-800 rounded-full px-2.5 py-0.5 inline-flex items-center text-xs font-semibold";
              } else {
                $classes = "bg-gray-100 text-gray-800 rounded-full px-2.5 py-0.5 inline-flex items-center text-xs font-semibold";
              }
              echo "<td class='py-4 px-6 text-sm text-gray-800'><span class='$classes'>" . htmlspecialchars($status) . "</span></td>";

              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["payment_method"] . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>" . $row["booking_type"] . "</td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='9' class='py-4 px-6 text-center text-gray-800'>No bookings found.</td></tr>";
          }
          ?>
        </tbody>

      </table>
    </div>
  </div>
</body>

</html>
<?php
$conn->close();
?>