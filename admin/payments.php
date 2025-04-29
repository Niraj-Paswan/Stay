<?php
// Database connection
include '../Database/dbconfig.php';

// Fetch payment records
$sql = "SELECT payment_id, userID, transaction_id, original_rent, payment_amount, payment_method, payment_status FROM payments";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Records - Admin</title>
  <link href="../assets/css/styles.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
    crossorigin="anonymous" referrerpolicy="no-referrer">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 font-Nrj-fonts">
  <div class="container mx-auto p-6">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800 text-center">
      Payment Records
    </h2>

    <div class="overflow-x-auto">
      <table class="w-full bg-white border border-gray-200 table-auto rounded-lg shadow overflow-hidden">
        <thead class="bg-for">
          <tr>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white rounded-tl-md">User ID</th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">Payment ID</th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">Transaction ID</th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">Original Amount</th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">Amount Paid</th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white">Payment Method</th>
            <th class="py-3 px-6 text-left text-sm font-semibold text-white rounded-tr-md">Payment Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr class='border-b border-gray-200'>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>{$row['userID']}</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>{$row['payment_id']}</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>{$row['transaction_id']}</td>";
              echo "<td class='py-4 px-6 text-sm font-semibold text-gray-800'>₹" . number_format($row['original_rent'], 2) . "</td>";
              echo "<td class='py-4 px-6 text-sm font-semibold text-gray-800'>₹" . number_format($row['payment_amount'], 2) . "</td>";
              echo "<td class='py-4 px-6 text-sm text-gray-800'>{$row['payment_method']}</td>";

              // Styled badge for status
              $status = strtolower($row['payment_status']);
              if ($status === 'successful') {
                $badgeClass = "bg-green-100 text-green-800 ";
              } elseif ($status === 'cancelled') {
                $badgeClass = "bg-red-100 text-red-800";
              } else {
                $badgeClass = "bg-red-100 text-red-700";
              }

              echo "<td class='py-4 px-6   text-gray-800'>
                      <span class='px-2.5 py-0.5 rounded-full text-xs font-semibold inline-flex items-center $badgeClass'>
                        {$row['payment_status']}
                      </span>
                    </td>";

              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='7' class='py-4 px-6 text-center text-gray-800'>No payment records found</td></tr>";
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