<?php
include('auth/db_config.php');
$entries_per_page = 5;

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

// Form handling functions
function handleExpenseSubmission($conn, $user_id)
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return null;

    if (isset($_POST['delete'])) {
        return handleDelete($conn, $user_id, $_POST['expense_id']);
    }

    if (isset($_POST['update'])) {
        return handleUpdate($conn, $user_id, $_POST);
    }

    return handleInsert($conn, $user_id, $_POST);
}

function handleInsert($conn, $user_id, $data)
{
    $description = $conn->real_escape_string($data['description']);
    $category = $conn->real_escape_string($data['category']);
    $amount = floatval($data['amount']);
    $date = $conn->real_escape_string($data['date']);

    $sql = "INSERT INTO expenses (user_id, description, category, amount, date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("issds", $user_id, $description, $category, $amount, $date);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();

        if ($success) {
            $_SESSION['message'] = "Expense successfully recorded!";
            header("Location: index.php?page=expenses");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function handleUpdate($conn, $user_id, $data)
{
    $expense_id = intval($data['expense_id']);
    $description = $conn->real_escape_string($data['description']);
    $category = $conn->real_escape_string($data['category']);
    $amount = floatval($data['amount']);
    $date = $conn->real_escape_string($data['date']);

    $sql = "UPDATE expenses SET description = ?, category = ?, amount = ?, date = ? 
            WHERE expense_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssdsis", $description, $category, $amount, $date, $expense_id, $user_id);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();

        if ($success) {
            $_SESSION['message'] = "Expense updated successfully!";
            header("Location: index.php?page=expenses");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function handleDelete($conn, $user_id, $expense_id)
{
    $expense_id = intval($expense_id);
    $sql = "DELETE FROM expenses WHERE expense_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $expense_id, $user_id);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();

        if ($success) {
            $_SESSION['message'] = "Expense deleted successfully!";
            header("Location: index.php?page=expenses");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

// Fetch expense for editing
function fetchExpenseForEdit($conn, $user_id, $expense_id)
{
    $sql = "SELECT * FROM expenses WHERE expense_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $expense_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $expense = $result->fetch_assoc();
        $stmt->close();
        return $expense;
    }
    return null;
}

// Modified function to fetch expenses with search
function fetchExpenses($conn, $user_id, $limit, $offset, $search = '')
{
    $search = '%' . $search . '%';
    $sql = "SELECT * FROM expenses 
            WHERE user_id = ? 
            AND (description LIKE ? 
                OR category LIKE ? 
                OR CAST(amount AS CHAR) LIKE ? 
                OR DATE_FORMAT(date, '%d %M %Y') LIKE ?)
            ORDER BY date DESC 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issssii", $user_id, $search, $search, $search, $search, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }
    return null;
}

// Modified function to get total records with search
function getTotalRecords($conn, $user_id, $search = '')
{
    $search = '%' . $search . '%';
    $sql = "SELECT COUNT(*) as total 
            FROM expenses 
            WHERE user_id = ? 
            AND (description LIKE ? 
                OR category LIKE ? 
                OR CAST(amount AS CHAR) LIKE ? 
                OR DATE_FORMAT(date, '%d %M %Y') LIKE ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $search, $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    return 0;
}

// Get categories function
function getCategories()
{
    return [
        'Food & Dining',
        'Transportation',
        'Shopping',
        'Entertainment',
        'Bills & Utilities',
        'Education',
        'Healthcare',
        'Personal Care',
        'Others'
    ];
}

// Initialize variables
$current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$error = handleExpenseSubmission($conn, $user_id);
$categories = getCategories();
$edit_data = null;

// Check if we're in edit mode
if (isset($_GET['edit']) && isset($_GET['expense_id'])) {
    $edit_data = fetchExpenseForEdit($conn, $user_id, intval($_GET['expense_id']));
}

// Get search term
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Calculate monthly total
$month = date('Y-m');
$sql = "SELECT SUM(amount) as monthly_total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$monthly_total = $stmt->get_result()->fetch_assoc()['monthly_total'] ?? 0;
$stmt->close();

// calculate total expenses
$sql = "SELECT SUM(amount) as total FROM expenses WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_expenses = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get total records with search
$total_records = getTotalRecords($conn, $user_id, $search_term);
$total_pages = ceil($total_records / $entries_per_page);
$current_page = isset($_GET['p']) ? max(1, min($total_pages, intval($_GET['p']))) : 1;
$offset = ($current_page - 1) * $entries_per_page;

// Fetch expenses with search
$expenses = fetchExpenses($conn, $user_id, $entries_per_page, $offset, $search_term);

$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<div class="dashboard-container">
    <!-- Left Column - Expense Form -->
    <div class="expense-form-section">
        <div class="card expense-form-card">
            <h2><?php echo $edit_data ? 'Edit Expense' : 'Add New Expense'; ?></h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form id="expenseForm" action="index.php?page=expenses<?php echo $edit_data ? '&edit=1&expense_id=' . $edit_data['expense_id'] : ''; ?>" method="POST">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="expense_id" value="<?php echo $edit_data['expense_id']; ?>">
                    <input type="hidden" name="update" value="1">
                <?php endif; ?>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description"
                        value="<?php echo $edit_data ? htmlspecialchars($edit_data['description']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"
                                <?php echo ($edit_data && $edit_data['category'] == $cat) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Amount (RM)</label>
                        <input type="number" id="amount" name="amount" step="0.01"
                            value="<?php echo $edit_data ? $edit_data['amount'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date"
                            value="<?php echo $edit_data ? $edit_data['date'] : ''; ?>" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <?php echo $edit_data ? 'Update Expense' : 'Add Expense'; ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="index.php?page=expenses" class="cancel-btn">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column - Expense List -->
    <div class="expense-list-section">
        <div class="card summary-card">
            <h3>Monthly Summary</h3>
            <div class="summary-content">
                <div class="summary-item">
                    <span class="label">This Month's Total</span>
                    <span class="amount">RM <?php echo number_format($monthly_total, 2); ?></span>
                </div>
            </div>
            <div class="summary-content">
                <div class="summary-item">
                    <span class="label">Overall Total Expenses</span>
                    <span class="amount">RM <?php echo number_format($total_expenses, 2); ?> </span>
                </div>
            </div>
        </div>

        <div class="card expense-list-card" id="expenseList">
            <h2>Recent Expenses</h2>
            <form method="GET" action="index.php" class="search-form">
                <input type="hidden" name="page" value="expenses">
                <div class="search-container">
                    <input type="text" name="search"
                        placeholder="Search by description, category, amount, or date..."
                        value="<?php echo htmlspecialchars($search_term); ?>"
                        class="search-input">
                    <button type="submit" class="search-btn">Search</button>
                    <a href="index.php?page=expenses" class="clear-search">Clear</a>

                </div>
            </form>

            <?php if ($expenses && $expenses->num_rows > 0): ?>
                <div class="expense-table-wrapper">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            while ($expense = $expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo date('d M Y', strtotime($expense['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($expense['category']); ?>
                                        </span>
                                    </td>
                                    <td class="amount-cell">RM <?php echo number_format($expense['amount'], 2); ?></td>
                                    <td class="actions-cell">
                                        <a href="index.php?page=expenses&edit=1&expense_id=<?php echo $expense['expense_id']; ?>"
                                            class="action-btn edit-btn">Edit</a>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                            <input type="hidden" name="expense_id" value="<?php echo $expense['expense_id']; ?>">
                                            <input type="hidden" name="delete" value="1">
                                            <button type="submit" class="action-btn delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($search_term)): ?>
                        <div class="search-results-summary">
                            Found <?php echo $total_records; ?> result<?php echo $total_records != 1 ? 's' : ''; ?>
                            for "<?php echo htmlspecialchars($search_term); ?>"
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="index.php?page=expenses&p=<?php echo ($current_page - 1); ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="pagination-btn">&laquo; Previous</a>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<a href="index.php?page=expenses&p=1' . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . '" class="pagination-btn">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $current_page) ? ' active' : '';
                                echo '<a href="index.php?page=expenses&p=' . $i . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . '" class="pagination-btn' . $active_class . '">' . $i . '</a>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                                echo '<a href="index.php?page=expenses&p=' . $total_pages . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . '" class="pagination-btn">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="index.php?page=expenses&p=<?php echo ($current_page + 1); ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" class="pagination-btn">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-data">No expenses recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $conn->close(); ?>