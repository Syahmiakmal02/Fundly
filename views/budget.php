<head>
    <link rel="stylesheet" type="text/css" href="../css/budget.css">
</head>
<?php
// Config and Initialization
include('auth/db_config.php');
$entries_per_page = 5;

// Fetch user ID from database based on session email
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
    // Handle case where session email is not set
    $_SESSION['error'] = "User not logged in!";
    header("Location: ../auth/login.php");
    exit();
}

// Form Handlers
function handleBudgetSubmission($conn, $user_id) {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return null;
    
    if (isset($_POST['delete'])) {
        return handleDelete($conn, $user_id, $_POST['budget_id']);
    }
    
    if (isset($_POST['update'])) {
        return handleUpdate($conn, $user_id, $_POST);
    }
    
    return handleInsert($conn, $user_id, $_POST);
}

function handleInsert($conn, $user_id, $data) {
    $category = $conn->real_escape_string($data['category']);
    $amount = floatval($data['amount']);
    $month = $conn->real_escape_string($data['month']);

    $sql = "INSERT INTO budgets (user_id, category, amount, month) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("isds", $user_id, $category, $amount, $month);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();
        
        if ($success) {
            $_SESSION['message'] = "Budget entry successfully saved!";
            header("Location: index.php?page=budget");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function handleUpdate($conn, $user_id, $data) {
    $budget_id = intval($data['budget_id']);
    $category = $conn->real_escape_string($data['category']);
    $amount = floatval($data['amount']);
    $month = $conn->real_escape_string($data['month']);

    $sql = "UPDATE budgets SET category = ?, amount = ?, month = ? WHERE budget_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sdsii", $category, $amount, $month, $budget_id, $user_id);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();
        
        if ($success) {
            $_SESSION['message'] = "Budget entry updated successfully!";
            header("Location: index.php?page=budget");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function handleDelete($conn, $user_id, $budget_id) {
    $budget_id = intval($budget_id);
    $sql = "DELETE FROM budgets WHERE budget_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ii", $budget_id, $user_id);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();
        
        if ($success) {
            $_SESSION['message'] = "Budget entry deleted successfully!";
            header("Location: index.php?page=budget");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

// Data Fetchers
function fetchEditData($conn, $user_id, $budget_id) {
    $sql = "SELECT * FROM budgets WHERE budget_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ii", $budget_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_data = $result->fetch_assoc();
        $stmt->close();
        return $edit_data;
    }
    return null;
}

function fetchBudgetEntries($conn, $user_id, $entries_per_page, $current_page) {
    $offset = ($current_page - 1) * $entries_per_page;
    $sql = "SELECT * FROM budgets WHERE user_id = ? ORDER BY month DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iii", $user_id, $entries_per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    return null;
}

function calculateTotalBudget($conn, $user_id) {
    $sql = "SELECT SUM(amount) as total FROM budgets WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        return $total;
    }
    return 0;
}

function getPaginationData($conn, $user_id, $entries_per_page) {
    $sql = "SELECT COUNT(*) as count FROM budgets WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_entries = $result->fetch_assoc()['count'];
        $stmt->close();
        return ceil($total_entries / $entries_per_page);
    }
    return 0;
}

function getCategories() {
    return [
        'Rent/Accommodation',
        'Transportation',
        'Groceries',
        'Utilities',
        'Books/Supplies',
        'Entertainment',
        'Healthcare',
        'Food',
        'Others'
    ];
}

// Main Execution
$current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$error = handleBudgetSubmission($conn, $user_id);
$edit_data = isset($_GET['edit']) && isset($_GET['budget_id']) ? 
             fetchEditData($conn, $user_id, $_GET['budget_id']) : null;
$budgets = fetchBudgetEntries($conn, $user_id, $entries_per_page, $current_page);
$total_budget = calculateTotalBudget($conn, $user_id);
$total_pages = getPaginationData($conn, $user_id, $entries_per_page);
$categories = getCategories();
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<div class="dashboard-container">
    <!-- Left Column - Budget Form -->
    <div class="budget-form-section">
        <div class="card budget-form-card">
            <h2><?php echo $edit_data ? 'Edit Budget Entry' : 'Add New Budget Entry'; ?></h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form id="budgetForm" action="index.php?page=budget<?php echo $edit_data ? '&edit=1&budget_id=' . $edit_data['budget_id'] : ''; ?>" method="POST">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="budget_id" value="<?php echo $edit_data['budget_id']; ?>">
                    <input type="hidden" name="update" value="1">
                <?php endif; ?>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo ($edit_data && $edit_data['category'] == $cat) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">Amount (RM)</label>
                    <input type="number" name="amount" id="amount" step="0.01" value="<?php echo $edit_data ? $edit_data['amount'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="month">Month</label>
                    <input type="month" name="month" id="month" value="<?php echo $edit_data ? $edit_data['month'] : ''; ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn"><?php echo $edit_data ? 'Update Budget Entry' : 'Save Budget Entry'; ?></button>
                    <?php if ($edit_data): ?>
                        <a href="index.php?page=budget" class="cancel-btn">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column - Budget List -->
    <div class="budget-list-section">
        <div class="card summary-card">
            <h3>Budget Summary</h3>
            <div class="summary-item">
                <span class="label">Total Budget</span>
                <span class="amount">RM <?php echo number_format($total_budget, 2); ?></span>
            </div>
        </div>

        <div class="card budget-list-card">
            <h2>Recent Budget Entries</h2>
            <?php if ($budgets && $budgets->num_rows > 0): ?>
                <div class="budget-table-wrapper">
                    <table class="budget-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Category</th>
                                <th>Amount (RM)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($budget = $budgets->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($budget['month']); ?></td>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($budget['category']); ?>
                                        </span>
                                    </td>
                                    <td>RM <?php echo number_format($budget['amount'], 2); ?></td>
                                    <td>
                                        <a href="index.php?page=budget&edit=1&budget_id=<?php echo $budget['budget_id']; ?>" class="action-btn edit-btn">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                            <input type="hidden" name="budget_id" value="<?php echo $budget['budget_id']; ?>">
                                            <input type="hidden" name="delete" value="1">
                                            <button type="submit" class="action-btn delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="index.php?page=budget&p=<?php echo ($current_page - 1); ?>" class="pagination-btn">&laquo; Previous</a>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<a href="index.php?page=budget&p=1" class="pagination-btn">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $current_page) ? ' active' : '';
                                echo '<a href="index.php?page=budget&p=' . $i . '" class="pagination-btn' . $active_class . '">' . $i . '</a>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                                echo '<a href="index.php?page=budget&p=' . $total_pages . '" class="pagination-btn">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="index.php?page=budget&p=<?php echo ($current_page + 1); ?>" class="pagination-btn">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-data">No budget entries found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $conn->close(); ?>