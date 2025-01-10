<?php
// Database connection and authentication
require_once 'auth/db_config.php';

if (!isset($_SESSION['email'])) {
    $_SESSION['error'] = "User not logged in!";
    header("Location: ../auth/login.php");
    exit();
}

// User authentication check
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();
} else {
    $_SESSION['error'] = "User not logged in!";
    header("Location: ../auth/login.php");
    exit();
}

// Get budget data
$sql = "SELECT category, SUM(amount) as total 
        FROM budgets 
        WHERE user_id = ? 
        GROUP BY category 
        ORDER BY total DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [
    'categories' => [],
    'amounts' => [],
    'total' => 0
];

while ($row = $result->fetch_assoc()) {
    $data['categories'][] = $row['category'];
    $data['amounts'][] = floatval($row['total']);
    $data['total'] += floatval($row['total']);
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Budget Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <div class="dashboard">
        <div class="chart-card">
            <div class="chart-header">
                <h2 class="chart-title">Budget Distribution</h2>
                <div class="total-budget">
                    Total: RM <?php echo number_format($data['total'], 2); ?>
                </div>
            </div>

            <?php if (empty($data['categories'])): ?>
                <div class="no-data">
                    <p>No budget data available. Start by adding some budget entries!</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="budgetChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($data['categories'])): ?>
            <div class="grid-layout">
                <div class="category-card">
                    <h3>Category Breakdown</h3>
                    <?php 
                    $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
                              '#FF9F40', '#FF6384', '#C9CBCF', '#67748E'];
                    
                    foreach ($data['categories'] as $index => $category): 
                        $percentage = ($data['amounts'][$index] / $data['total']) * 100;
                    ?>
                        <div class="category-item">
                            <div class="category-name">
                                <div class="color-dot" style="background-color: <?php echo $colors[$index % count($colors)]; ?>"></div>
                                <?php echo htmlspecialchars($category); ?>
                            </div>
                            <div class="category-amount">
                                RM <?php echo number_format($data['amounts'][$index], 2); ?>
                                (<?php echo number_format($percentage, 1); ?>%)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($data['categories'])): ?>
    <script>
        const ctx = document.getElementById('budgetChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($data['categories']); ?>,
                datasets: [{
                    data: <?php echo json_encode($data['amounts']); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($colors, 0, count($data['categories']))); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { size: 14 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `RM ${value.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>