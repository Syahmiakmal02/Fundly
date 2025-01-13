<?php
require_once 'auth/db_config.php';

// User authentication
if (!isset($_SESSION['email'])) {
    $_SESSION['error'] = "User not logged in!";
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();
}

function fetchSavingGoals($conn, $user_id) {
    $sql = "SELECT saving_id, goal_name, collected_amount, goal_amount, account, due_date FROM savings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch all data in one query with monthly grouping
$query = "
    SELECT 
        'budget' as type,
        category,
        SUM(amount) as amount,
        DATE_FORMAT(CURRENT_DATE, '%Y-%m') as month
    FROM budgets 
    WHERE user_id = ?
    GROUP BY category
    
    UNION ALL
    
    SELECT 
        'expense' as type,
        category,
        SUM(amount) as amount,
        DATE_FORMAT(date, '%Y-%m') as month
    FROM expenses 
    WHERE user_id = ? 
    AND date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY category, DATE_FORMAT(date, '%Y-%m')
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$saving_data = fetchSavingGoals($conn, $user_id);
$total_saving = 0;

// Initialize data arrays
$budget_data = ['categories' => [], 'amounts' => [], 'total' => 0];
$expense_data = [];

// Process results
while ($row = $result->fetch_assoc()) {
    switch ($row['type']) {
        case 'budget':
            $budget_data['categories'][] = $row['category'];
            $budget_data['amounts'][] = floatval($row['amount']);
            $budget_data['total'] += floatval($row['amount']);
            break;
        case 'expense':
            if (!isset($expense_data[$row['month']])) {
                $expense_data[$row['month']] = [];
            }
            $expense_data[$row['month']][$row['category']] = floatval($row['amount']);
            break;
    }
}

$stmt->close();
$conn->close();
?>


<div class="dashboard-container">
    <div class="dashboard-grid">
        <!-- Budget Overview -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Budget Overview</h2>
                <span>Total: RM <?php echo number_format($budget_data['total'], 2); ?></span>
            </div>
            <?php if (empty($budget_data['categories'])): ?>
                <div class="no-data">No budget data available</div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="budgetChart"></canvas>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($budget_data['categories'] as $i => $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category); ?></td>
                                <td>RM <?php echo number_format($budget_data['amounts'][$i], 2); ?></td>
                                <td><?php echo number_format(($budget_data['amounts'][$i] / $budget_data['total']) * 100, 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Monthly Expenses -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Monthly Expenses</h2>
            </div>
            <?php if (empty($expense_data)): ?>
                <div class="no-data">No expense data available</div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="expenseChart"></canvas>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Expenses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expense_data as $month => $categories): ?>
                            <tr>
                                <td><?php echo date('M Y', strtotime($month)); ?></td>
                                <td>RM <?php echo number_format(array_sum($categories), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card-savings">
    <?php 
    $total_collected_amount = 0;
    $total_saving = 0;
    while ($row = $saving_data->fetch_assoc()) {
        $total_saving += $row['goal_amount'];
        $total_collected_amount += $row['collected_amount'];
    }
    ?>
    <div class="h3-total-savings">
        <h3>Target Savings: </h3>
        <h3 class="saving-amount">RM <?php echo number_format($total_collected_amount, 2); ?><span style="color: black;"> / RM <?php echo number_format($total_saving, 2); ?></span></h3>
    </div>
    <?php $saving_data->data_seek(0); 
    while ($row = $saving_data->fetch_assoc()): ?>
        <div class="container">
            <div class="progress">
                <?php if ($row['collected_amount'] == 0): ?>
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                        style="width: 0%">
                    </div>
                    <div class="progress-text-center">
                        <?php echo htmlspecialchars($row['goal_name']); ?> 
                        (RM 0.00 / RM <?php echo number_format($row['goal_amount'], 2); ?>)
                    </div>
                <?php else: ?>
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                        style="width:<?php echo number_format(($row['collected_amount'] / $row['goal_amount']) * 100, 2) . '%'; ?>">
                        <?php echo htmlspecialchars($row['goal_name']); ?>
                        (RM <?php echo number_format($row['collected_amount'], 2); ?> / RM <?php echo number_format($row['goal_amount'], 2); ?>)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>


<script>
// Simple chart initialization with minimal configuration
document.addEventListener('DOMContentLoaded', function() {
    const colors = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#8AC926', '#6A4C93', '#1982C4', '#FF595E'
    ];

    // Budget Chart
    <?php if (!empty($budget_data['categories'])): ?>
    new Chart(document.getElementById('budgetChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($budget_data['categories']); ?>,
            datasets: [{
                data: <?php echo json_encode($budget_data['amounts']); ?>,
                backgroundColor: colors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    <?php endif; ?>

    // Expenses Chart
    <?php if (!empty($expense_data)): ?>
    new Chart(document.getElementById('expenseChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(<?php echo json_encode($expense_data); ?>).map(m => {
                const [year, month] = m.split('-');
                return new Date(year, month - 1).toLocaleDateString('default', { month: 'short', year: '2-digit' });
            }),
            datasets: [{
                label: 'Monthly Expenses',
                data: Object.values(<?php echo json_encode($expense_data); ?>).map(m => Object.values(m).reduce((a, b) => a + b, 0)),
                backgroundColor: colors[1]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    <?php endif; ?>
});
</script>