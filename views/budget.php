<?php
// Config and Initialization
include('auth/db_config.php');
$user_id = 1; // Static user ID (replace with session)
$entries_per_page = 8;

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

    $sql = "INSERT INTO Budgets (user_id, category, amount, month) VALUES (?, ?, ?, ?)";
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

    $sql = "UPDATE Budgets SET category = ?, amount = ?, month = ? WHERE budget_id = ? AND user_id = ?";
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
    $sql = "DELETE FROM Budgets WHERE budget_id = ? AND user_id = ?";
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
    $sql = "SELECT * FROM Budgets WHERE budget_id = ? AND user_id = ?";
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
    $sql = "SELECT * FROM Budgets WHERE user_id = ? ORDER BY month DESC LIMIT ? OFFSET ?";
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
    $sql = "SELECT SUM(amount) as total FROM Budgets WHERE user_id = ?";
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
    $sql = "SELECT COUNT(*) as count FROM Budgets WHERE user_id = ?";
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

<!-- HTML Template -->
<div class="row">
    <div class="leftcolumn">
        <div class="main-card">
            <h2><?php echo $edit_data ? 'Edit Budget Entry' : 'New Budget Entry'; ?></h2>
            <?php if (!empty($message)): ?>
                <p class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>
            
            <form action="index.php?page=budget<?php echo $edit_data ? '&edit=1&budget_id='.$edit_data['budget_id'] : ''; ?>" 
                  method="POST" 
                  id="budgetForm">
                
                <?php if ($edit_data): ?>
                    <input type="hidden" name="budget_id" value="<?php echo $edit_data['budget_id']; ?>">
                    <input type="hidden" name="update" value="1">
                <?php endif; ?>

                <label for="category">Category:</label><br>
                <select name="category" id="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php $selected = ($edit_data && $edit_data['category'] == $cat) ? 'selected' : ''; ?>
                        <option value="<?php echo $cat; ?>" <?php echo $selected; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select><br>

                <label for="amount">Amount (RM):</label><br>
                <input type="number" name="amount" id="amount" step="0.01" required
                       value="<?php echo $edit_data ? $edit_data['amount'] : ''; ?>"><br>

                <label for="month">Month:</label><br>
                <input type="month" name="month" id="month" required 
                       value="<?php echo $edit_data ? $edit_data['month'] : ''; ?>"><br>

                <input type="submit" value="<?php echo $edit_data ? 'Update Budget Entry' : 'Save Budget Entry'; ?>">
                <?php if ($edit_data): ?>
                    <a href="index.php?page=budget" class="button">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="rightcolumn" id="budget-rightcolumn">
        <div class="card" id="budget-summary">
            <h2>Budget Summary</h2>
            <p>Total Budget: RM <?php echo number_format($total_budget, 2); ?></p>
        </div>
        
        <div class="card">
            <h2>Recent Budget Entries</h2>
            <?php if ($budgets && $budgets->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Month</th>
                        <th>Category</th>
                        <th>Amount (RM)</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($budget = $budgets->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($budget['month']); ?></td>
                        <td><?php echo htmlspecialchars($budget['category']); ?></td>
                        <td><?php echo number_format($budget['amount'], 2); ?></td>
                        <td>
                            <a href="index.php?page=budget&edit=1&budget_id=<?php echo $budget['budget_id']; ?>" class="edit-link">Edit</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                <input type="hidden" name="budget_id" value="<?php echo $budget['budget_id']; ?>">
                                <input type="hidden" name="delete" value="1">
                                <button type="submit" class="delete-button">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="index.php?page=budget&p=<?php echo ($current_page - 1); ?>" class="pagination-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="pagination-link active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="index.php?page=budget&p=<?php echo $i; ?>" class="pagination-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="index.php?page=budget&p=<?php echo ($current_page + 1); ?>" class="pagination-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No budget entries found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $conn->close(); ?>