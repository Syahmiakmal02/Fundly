<?php
include('auth/db_config.php');
$entries_per_page = 8;

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
function handleExpenseSubmission($conn, $user_id) {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') return null;
    
    if (isset($_POST['delete'])) {
        return handleDelete($conn, $user_id, $_POST['expense_id']);
    }
    
    if (isset($_POST['update'])) {
        return handleUpdate($conn, $user_id, $_POST);
    }
    
    return handleInsert($conn, $user_id, $_POST);
}

function handleInsert($conn, $user_id, $data) {
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

function handleUpdate($conn, $user_id, $data) {
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

function handleDelete($conn, $user_id, $expense_id) {
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
function fetchExpenseForEdit($conn, $user_id, $expense_id) {
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

// Get categories function
function getCategories() {
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

// Calculate monthly total
$month = date('Y-m');
$sql = "SELECT SUM(amount) as monthly_total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$monthly_total = $stmt->get_result()->fetch_assoc()['monthly_total'] ?? 0;
$stmt->close();

// Fetch recent expenses
$offset = ($current_page - 1) * $entries_per_page;
$sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $entries_per_page, $offset);
$stmt->execute();
$expenses = $stmt->get_result();
$stmt->close();

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
            
            <form id="expenseForm" action="index.php?page=expenses<?php echo $edit_data ? '&edit=1&expense_id='.$edit_data['expense_id'] : ''; ?>" method="POST">
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
        </div>
        
        <div class="card expense-list-card" id="expenseList">
            <h2>Recent Expenses</h2>
            <?php if ($expenses && $expenses->num_rows > 0): ?>
                <div class="expense-table-wrapper">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($expense = $expenses->fetch_assoc()): ?>
                                <tr>
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
                </div>
            <?php else: ?>
                <p class="no-data">No expenses recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $conn->close(); ?>