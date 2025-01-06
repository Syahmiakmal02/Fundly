<link rel="stylesheet" href="css/budget.css">

<?php
include('auth/db_config.php');

// Ensure the connection is active
if (!$conn || $conn->connect_error) {
    die("Database connection is not active or failed.");
}

$message = '';
$user_id = 1; // Hardcoded user_id for testing
$edit_data = null;

// Handle Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete']) && !isset($_POST['update'])) {
    if (!$conn) {
        die("Database connection is closed.");
    }

    $category = $conn->real_escape_string($_POST['category']);
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;
    $month = $conn->real_escape_string($_POST['month']);

    $sql = "INSERT INTO Budgets (user_id, category, amount, month) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("isds", $user_id, $category, $amount, $month);
        if ($stmt->execute()) {
            $message = "Budget entry successfully saved!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit Form Display
if (isset($_GET['edit']) && isset($_GET['budget_id'])) {
    $budget_id = intval($_GET['budget_id']);
    $edit_sql = "SELECT * FROM Budgets WHERE budget_id = ? AND user_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    if ($edit_stmt) {
        $edit_stmt->bind_param("ii", $budget_id, $user_id);
        $edit_stmt->execute();
        $edit_result = $edit_stmt->get_result();
        if ($edit_result->num_rows > 0) {
            $edit_data = $edit_result->fetch_assoc();
        }
        $edit_stmt->close();
    }
}

// Handle Delete
if (isset($_POST['delete']) && isset($_POST['budget_id'])) {
    $budget_id = intval($_POST['budget_id']);
    $delete_sql = "DELETE FROM Budgets WHERE budget_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    if ($delete_stmt) {
        $delete_stmt->bind_param("ii", $budget_id, $user_id);
        if ($delete_stmt->execute()) {
            $message = "Budget entry deleted successfully!";
        } else {
            $message = "Error deleting entry: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
}

// Handle Update
if (isset($_POST['update']) && isset($_POST['budget_id'])) {
    $budget_id = intval($_POST['budget_id']);
    $category = $conn->real_escape_string($_POST['category']);
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;
    $month = $conn->real_escape_string($_POST['month']);

    $update_sql = "UPDATE Budgets SET category = ?, amount = ?, month = ? WHERE budget_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("sdsii", $category, $amount, $month, $budget_id, $user_id);
        if ($update_stmt->execute()) {
            $message = "Budget entry updated successfully!";
        } else {
            $message = "Error updating entry: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}

// Fetch existing budget entries
$budgets = array();
$sql = "SELECT * FROM Budgets WHERE user_id = ? ORDER BY month DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }
    $stmt->close();
}

// Calculate total budget
$total_budget = 0;
foreach ($budgets as $budget) {
    $total_budget += $budget['amount'];
}
?>

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
                    <br>
                    <?php
                    $categories = ['Rent/Accommodation', 'Transportation', 'Groceries', 'Utilities', 'Books/Supplies', 'Entertainment', 'Healthcare', 'Food', 'Others'];
                    foreach ($categories as $cat) {
                        $selected = ($edit_data && $edit_data['category'] == $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
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
    <div class="rightcolumn">
        <div class="card">
            <h2>Budget Summary</h2>
            <p>Total Budget: RM <?php echo number_format($total_budget, 2); ?></p>
        </div>
        
        <div class="card">
            <h2>Recent Budget Entries</h2>
            <?php if (!empty($budgets)): ?>
                <table>
                    <tr>
                        <th>Month</th>
                        <th>Category</th>
                        <th>Amount (RM)</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($budgets as $budget): ?>
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
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No budget entries found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
