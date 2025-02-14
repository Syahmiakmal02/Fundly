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

function fetchSavingGoals($conn, $user_id)
{
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
                <div class="total-expenses" id="totalMonthlyExpenses">
                    Total: RM <span id="monthlyTotal">0.00</span>
                </div>
            </div>
            <?php if (empty($expense_data)): ?>
                <div class="no-data">No expense data available</div>
            <?php else: ?>
                <div class="chart-navigation">
                    <button id="prevMonth" class="nav-button">&lt; Previous</button>
                    <span id="currentMonth"></span>
                    <button id="nextMonth" class="nav-button">Next &gt;</button>
                </div>
                <div class="chart-container">
                    <canvas id="expenseChart"></canvas>
                </div>
                <table class="data-table" id="expenseTable">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Expenses</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <!-- Enhanced Savings Dashboard -->
    <div class="savings-dashboard">
        <div class="card-savings">
            <?php
            $total_collected_amount = 0;
            $total_saving = 0;
            while ($row = $saving_data->fetch_assoc()) {
                $total_saving += $row['goal_amount'];
                $total_collected_amount += $row['collected_amount'];
            }
            ?>
            <div class="card-header">
                <div class="savings-header-content">
                    <h2 class="card-title">Target Savings</h2>
                    <div class="savings-stats">
                        <div class="stat-item">
                            <span class="stat-label">Total Progress</span>
                            <h3 class="saving-amount">
                                RM <?php echo number_format($total_collected_amount, 2); ?>
                                <span class="total-amount">/ RM <?php echo number_format($total_saving, 2); ?></span>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="savings-content">
                <div id="savingsContainer" class="savings-container"></div>

                <div class="pagination-controls">
                    <button id="prevPage" class="pagination-btn">&lt; Previous</button>
                    <div id="pageIndicator" class="page-indicator">
                        Page <span id="currentPage">1</span> of <span id="totalPages">1</span>
                    </div>
                    <button id="nextPage" class="pagination-btn">Next &gt;</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

        // Expenses Chart and Navigation
        <?php if (!empty($expense_data)): ?>
            const expenseData = <?php echo json_encode($expense_data); ?>;
            const months = Object.keys(expenseData).sort();
            let currentMonthIndex = months.length - 1;

            const formatMonth = (monthStr) => {
                const [year, month] = monthStr.split('-');
                return new Date(year, month - 1).toLocaleDateString('default', {
                    month: 'long',
                    year: 'numeric'
                });
            };

            let expenseChart;
            const updateExpenseChart = (monthStr) => {
                const monthData = expenseData[monthStr];
                const total = Object.values(monthData).reduce((sum, amount) => sum + amount, 0);
                document.getElementById('monthlyTotal').textContent = total.toFixed(2);
                const categories = Object.keys(monthData);
                const amounts = Object.values(monthData);

                if (expenseChart) {
                    expenseChart.destroy();
                }

                expenseChart = new Chart(document.getElementById('expenseChart'), {
                    type: 'doughnut',
                    data: {
                        labels: categories,
                        datasets: [{
                            data: amounts,
                            backgroundColor: colors.slice(0, categories.length)
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });

                document.getElementById('currentMonth').textContent = formatMonth(monthStr);
                updateNavigationButtons();

                // Update the expense table for the current month
                const tableBody = document.querySelector('#expenseTable tbody');
                tableBody.innerHTML = ''; // Clear existing rows

                const monthTotal = Object.values(monthData).reduce((sum, amount) => sum + amount, 0);
                const row = document.createElement('tr');

                // Create table cells
                row.innerHTML = `
                    <td>${formatMonth(monthStr)}</td>
                    <td>RM ${monthTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td>
                        <ul>
                            ${Object.entries(monthData)
                                .map(([category, amount]) => 
                                    `<li>${category}: RM ${amount.toLocaleString('en-US', {
                                        minimumFractionDigits: 2, 
                                        maximumFractionDigits: 2
                                    })}</li>`
                                )
                                .join('')}
                        </ul>
                    </td>
                `;

                tableBody.appendChild(row);
            };

            const updateNavigationButtons = () => {
                const prevButton = document.getElementById('prevMonth');
                const nextButton = document.getElementById('nextMonth');

                prevButton.disabled = currentMonthIndex <= 0;
                prevButton.style.opacity = currentMonthIndex <= 0 ? '0.5' : '1';
                prevButton.style.cursor = currentMonthIndex <= 0 ? 'not-allowed' : 'pointer';

                nextButton.disabled = currentMonthIndex >= months.length - 1;
                nextButton.style.opacity = currentMonthIndex >= months.length - 1 ? '0.5' : '1';
                nextButton.style.cursor = currentMonthIndex >= months.length - 1 ? 'not-allowed' : 'pointer';
            };

            document.getElementById('prevMonth').addEventListener('click', () => {
                if (currentMonthIndex > 0) {
                    currentMonthIndex--;
                    updateExpenseChart(months[currentMonthIndex]);
                }
            });

            document.getElementById('nextMonth').addEventListener('click', () => {
                if (currentMonthIndex < months.length - 1) {
                    currentMonthIndex++;
                    updateExpenseChart(months[currentMonthIndex]);
                }
            });

            updateExpenseChart(months[currentMonthIndex]);
        <?php endif; ?>

        // Savings Pagination
        <?php
        $saving_data->data_seek(0);
        $savings = [];
        while ($row = $saving_data->fetch_assoc()) {
            $savings[] = $row;
        }
        ?>
        const savingsData = <?php echo json_encode($savings); ?>;
        const itemsPerPage = 3;
        let currentPage = 1;
        const totalPages = Math.ceil(savingsData.length / itemsPerPage);

        function updatePageIndicator() {
            document.getElementById('currentPage').textContent = currentPage;
            document.getElementById('totalPages').textContent = totalPages;

            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
        }

        function displaySavings(page) {
            const container = document.getElementById('savingsContainer');
            container.innerHTML = '';

            const start = (page - 1) * itemsPerPage;
            const end = Math.min(start + itemsPerPage, savingsData.length);

            for (let i = start; i < end; i++) {
                const saving = savingsData[i];
                const percentage = (saving.collected_amount / saving.goal_amount) * 100;

                const savingCard = document.createElement('div');
                savingCard.className = 'saving-goal-card';

                const dueDate = new Date(saving.due_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                savingCard.innerHTML = `
                    <div class="goal-info">
                        <div class="goal-header">
                            <h3 class="goal-name">${saving.goal_name}</h3>
                            <span class="goal-account">${saving.account}</span>
                        </div>
                        <div class="goal-details">
                            <span class="due-date">Due: ${dueDate}</span>
                            <span class="amount-text">
                                RM ${Number(saving.collected_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} 
                                / RM ${Number(saving.goal_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: ${percentage}%">
                            <span class="progress-text">${percentage.toFixed(1)}%</span>
                        </div>
                    </div>
                `;

                container.appendChild(savingCard);
            }

            updatePageIndicator();
        }

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                displaySavings(currentPage);
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                displaySavings(currentPage);
            }
        });

        displaySavings(currentPage);
    });
</script>