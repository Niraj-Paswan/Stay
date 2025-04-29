<?php
include '../Database/dbconfig.php';

$sql = "SELECT query_id, name, email, message, submitted_at FROM user_queries ORDER BY submitted_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>User Queries | Admin Panel</title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>

<body class="bg-gray-100 text-gray-800 font-Nrj-fonts">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <h2 class="text-2xl font-bold mb-6 text-center">User Queries</h2>

        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-for text-sm font-semibold text-white uppercase">
                    <tr>
                        <th class="px-6 py-3">Query ID</th>
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Message</th>
                        <th class="px-6 py-3">Submitted At</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 relative">
                                <td class="px-6 py-4 font-medium"><?php echo 'Q-' . $row['query_id']; ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php
                                    $msg = htmlspecialchars($row['message']);
                                    echo strlen($msg) > 50 ? substr($msg, 0, 50) . '...' : $msg;
                                    ?>
                                </td>
                                <td class="px-6 py-4"><?php echo date('M j, Y', strtotime($row['submitted_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <span id="status-<?php echo $row['query_id']; ?>"
                                        class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-3 py-1 rounded-full">
                                        Unresolved
                                    </span>
                                </td>
                                <td class="px-6 py-4 relative text-right">
                                    <button onclick="toggleDropdown(<?php echo $row['query_id']; ?>)"
                                        class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                        &#x22EE;
                                    </button>
                                    <div id="dropdown-<?php echo $row['query_id']; ?>"
                                        class="hidden absolute z-10 right-6 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg">
                                        <ul class="py-1 text-sm text-gray-700">
                                            <li>
                                                <button onclick="updateStatus(<?php echo $row['query_id']; ?>, 'Processing')"
                                                    class="block w-full text-left px-4 py-2 hover:bg-gray-100">Mark as
                                                    Processing</button>
                                            </li>
                                            <li>
                                                <button onclick="updateStatus(<?php echo $row['query_id']; ?>, 'Resolved')"
                                                    class="block w-full text-left px-4 py-2 hover:bg-gray-100">Mark as
                                                    Resolved</button>
                                            </li>
                                            <li>
                                                <button onclick="updateStatus(<?php echo $row['query_id']; ?>, 'Unresolved')"
                                                    class="block w-full text-left px-4 py-2 hover:bg-gray-100">Mark as
                                                    Unresolved</button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-6 text-gray-500">No queries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Hide all dropdowns before showing a new one
        function toggleDropdown(id) {
            document.querySelectorAll('[id^="dropdown-"]').forEach(drop => {
                if (drop.id !== 'dropdown-' + id) drop.classList.add('hidden');
            });
            const dropdown = document.getElementById('dropdown-' + id);
            dropdown.classList.toggle('hidden');
        }

        function updateStatus(queryId, newStatus) {
            const badge = document.getElementById('status-' + queryId);

            // Reset styles
            badge.classList.remove('bg-yellow-100', 'text-yellow-800', 'bg-blue-100', 'text-blue-800', 'bg-green-100', 'text-green-800');

            if (newStatus === 'Resolved') {
                badge.innerText = 'Resolved';
                badge.classList.add('bg-green-100', 'text-green-800');
            } else if (newStatus === 'Processing') {
                badge.innerText = 'Processing';
                badge.classList.add('bg-blue-100', 'text-blue-800');
            } else {
                badge.innerText = 'Unresolved';
                badge.classList.add('bg-yellow-100', 'text-yellow-800');
            }

            // Hide dropdown after selection
            toggleDropdown(queryId);
        }

        // Hide dropdowns on clicking outside
        window.addEventListener('click', function (e) {
            if (!e.target.matches('button') && !e.target.closest('[id^="dropdown-"]')) {
                document.querySelectorAll('[id^="dropdown-"]').forEach(drop => drop.classList.add('hidden'));
            }
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>