<?php
include('auth/db_config.php');

// Fetch user ID
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

$success_message = '';

function handleSavingGoalSubmission($conn, $user_id) {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['action'])) return null;
    
    $success = false;
    $message = '';

    switch ($_POST['action']) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO savings (user_id, goal_name, collected_amount, goal_amount, account, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            $collected_amount = 0.00;
            $stmt->bind_param("isddss", $user_id, $_POST['goal_name'], $collected_amount, $_POST['goal_amount'], $_POST['account'], $_POST['due_date']);
            $message = 'New saving goal added successfully!';
            break;

        case 'update':
            // First get the current amount
            $stmt = $conn->prepare("SELECT collected_amount FROM savings WHERE saving_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['saving_id'], $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_amount = $result->fetch_assoc()['collected_amount'];
            $stmt->close();

            // Add new amount to current amount
            $new_total = $current_amount + $_POST['collected_amount'];
            
            // Update with new total
            $stmt = $conn->prepare("UPDATE savings SET collected_amount = ? WHERE saving_id = ? AND user_id = ?");
            $stmt->bind_param("dii", $new_total, $_POST['saving_id'], $user_id);
            $message = 'Added RM ' . number_format($_POST['collected_amount'], 2) . ' to savings. New total: RM ' . number_format($new_total, 2);
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM savings WHERE saving_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['saving_id'], $user_id);
            $message = 'Saving goal deleted successfully!';
            break;
    }

    $success = $stmt->execute();
    $error = $success ? null : $stmt->error;
    $stmt->close();

    if ($success) {
        $_SESSION['success_message'] = $message;
        header("Location: index.php?page=saving");
        exit();
    }
    return $error;
}

function fetchSavingGoals($conn, $user_id) {
    $sql = "SELECT saving_id, goal_name, collected_amount, goal_amount, account, due_date FROM savings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

$error = handleSavingGoalSubmission($conn, $user_id);
$savings_data = fetchSavingGoals($conn, $user_id);
$total_saving = 0;
?>

<div class="savings-container">
    <!-- Rest of the HTML remains the same -->
    <div class="success-message" id="successMessage">
        <?php 
        if (isset($_SESSION['success_message'])) {
            echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
        }
        ?>
    </div>

    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <section class="add-goal">
        <h2>Add Saving Goal</h2>
        <form action="index.php?page=saving" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="goal_name">Goal Name</label>
                <input type="text" id="goal_name" name="goal_name" required>
            </div>

            <div class="form-group">
                <label for="goal_amount">Target Amount</label>
                <input type="number" id="goal_amount" name="goal_amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="account">Account</label>
                <input type="text" id="account" name="account" required>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" required>
            </div>

            <button type="submit" class="btn-submit">Add Goal</button>
        </form>
    </section>

   <!-- Goal List and Table -->
   <section class="goal-list">
        <?php 
        $total_collected_amount = 0;
        $total_saving = 0;
        // Loop through the saving goals to calculate the totals
        while ($row = $savings_data->fetch_assoc()) {
            $total_saving += $row['goal_amount'];
            $total_collected_amount += $row['collected_amount'];
        }
        ?>
        <div class="total-savings">
            <h3>Total Collected Savings: <br><span style="font-size: 48px;">RM <?php echo number_format($total_collected_amount, 2); ?></span></h3>
        </div>
        
        <h2>Your Saving Goals</h2>
        
        <?php if ($savings_data && $savings_data->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Goal</th>
                        <th>Progress</th>
                        <th>Account</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Loop through the savings data and display each goal in a table row
                    $savings_data->data_seek(0); // Reset pointer to the first row for display
                    while ($row = $savings_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['goal_name']); ?></td>
                            <td>
                                <form action="index.php?page=saving" method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="saving_id" value="<?php echo $row['saving_id']; ?>">
                                    <input type="number" name="collected_amount" step="0.01" 
                                           placeholder="Add amount" 
                                           class="amount-input" required>
                                    <button type="submit" class="btn-update">Add</button>
                                    <br><span class="collected-amount">Current: </span><span style="color: green"> RM <?php echo number_format($row['collected_amount'], 2); ?></span>
                                    <span class="target-amount">/ Target: RM <?php echo number_format($row['goal_amount'], 2); ?></span>
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars($row['account']); ?></td>
                            <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                            <td>
                                <form action="index.php?page=saving" method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="saving_id" value="<?php echo $row['saving_id']; ?>">
                                    <button type="submit" class="btn-delete" 
                                            onclick="return confirm('Are you sure you want to delete this goal?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total-savings">
                <h3>Target Savings: RM <?php echo number_format($total_saving, 2); ?></h3>
            </div>
        <?php else: ?>
            <p class="no-goals">No saving goals found. Start by adding a new goal!</p>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var successMessage = document.getElementById('successMessage');
    if (successMessage.textContent.trim() !== '') {
        successMessage.style.display = 'block';
        setTimeout(function() {
            successMessage.style.display = 'none';
        }, 2000);
    }
});
</script>

<?php $conn->close(); ?>