<?php
// Config and Initialization
include('auth/db_config.php');
$user_id = 1; // Static user ID (replace with session)

// Form Handler
function handleSavingGoalSubmission($conn, $user_id) {
    if ($_SERVER["REQUEST_METHOD"] != "POST") return null;

    $goal_name = $_POST['goal_name'];
    $goal_amount = $_POST['goal_amount'];
    $account = $_POST['account'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO savings (user_id, goal_name, goal_amount, account, due_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $user_id, $goal_name, $goal_amount, $account, $due_date);

    $success = $stmt->execute();
    $error = $success ? null : $stmt->error;

    $stmt->close();

    if ($success) {
        header("Location: index.php?page=saving");
        exit();
    }
    return $error;
}

// Data Fetcher
function fetchSavingGoals($conn, $user_id) {
    $sql = "SELECT goal_name, goal_amount, account, due_date FROM savings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Main Execution
$error = handleSavingGoalSubmission($conn, $user_id);
$savings_data = fetchSavingGoals($conn, $user_id);
$total_saving = 0;
?>

<!-- Main Container -->
<div class="savings-container">
    <!-- Error Display -->
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Add Goal Form -->
    <section class="add-goal">
        <h2>Add Saving Goal</h2>
        <form action="index.php?page=saving" method="POST">
            <div class="form-group">
                <label for="goal_name">Goal Name</label>
                <input type="text" id="goal_name" name="goal_name" required>
            </div>

            <div class="form-group">
                <label for="goal_amount">Target Amount</label>
                <input type="number" id="goal_amount" name="goal_amount" required>
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

    <!-- Goals List -->
    <section class="goal-list">
        <h2>Your Saving Goals</h2>
        <?php if ($savings_data && $savings_data->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Goal</th>
                        <th>Amount</th>
                        <th>Account</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $savings_data->fetch_assoc()):
                        $total_saving += $row['goal_amount']; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['goal_name']); ?></td>
                            <td>$<?php echo number_format($row['goal_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['account']); ?></td>
                            <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total-savings">
                <h3>Total Savings: $<?php echo number_format($total_saving, 2); ?></h3>
            </div>
        <?php else: ?>
            <p class="no-goals">No saving goals found. Start by adding a new goal!</p>
        <?php endif; ?>
    </section>
</div>

<?php $conn->close(); ?>