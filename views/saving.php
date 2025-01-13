<?php
// Config and Initialization
include('auth/db_config.php');

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
    $_SESSION['error'] = "User not logged in!";
    header("Location: ../auth/login.php");
    exit();
}

// Form Handlers
function handleSavingSubmission($conn, $user_id) {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['action'])) return null;
    
    switch ($_POST['action']) {
        case 'add':
            return handleAdd($conn, $user_id, $_POST);
        case 'update':
            return handleUpdate($conn, $user_id, $_POST);
        case 'delete':
            return handleDelete($conn, $user_id, $_POST['saving_id']);
    }
    return null;
}

function handleAdd($conn, $user_id, $data) {
    $stmt = $conn->prepare("INSERT INTO savings (user_id, goal_name, collected_amount, goal_amount, account, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $collected_amount = 0.00;
    
    if ($stmt) {
        $stmt->bind_param("isddss", $user_id, $data['goal_name'], $collected_amount, $data['goal_amount'], $data['account'], $data['due_date']);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();

        if ($success) {
            $_SESSION['success_message'] = "New saving goal added successfully!";
            header("Location: index.php?page=saving");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function handleUpdate($conn, $user_id, $data) {
    // First get the current amount
    $stmt = $conn->prepare("SELECT collected_amount FROM savings WHERE saving_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $data['saving_id'], $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_amount = $result->fetch_assoc()['collected_amount'];
    $stmt->close();

    // Update with new total
    $new_total = $current_amount + $data['collected_amount'];
    $stmt = $conn->prepare("UPDATE savings SET collected_amount = ? WHERE saving_id = ? AND user_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("dii", $new_total, $data['saving_id'], $user_id);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();

        if ($success) {
            $_SESSION['success_message'] = 'Added RM ' . number_format($data['collected_amount'], 2) . ' to savings. New total: RM ' . number_format($new_total, 2);
            header("Location: index.php?page=saving");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function handleDelete($conn, $user_id, $saving_id) {
    $stmt = $conn->prepare("DELETE FROM savings WHERE saving_id = ? AND user_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("ii", $saving_id, $user_id);
        $success = $stmt->execute();
        $error = $success ? null : $stmt->error;
        $stmt->close();

        if ($success) {
            $_SESSION['success_message'] = "Saving goal deleted successfully!";
            header("Location: index.php?page=saving");
            exit();
        }
        return $error;
    }
    return "Failed to prepare statement";
}

function fetchSavingGoals($conn, $user_id) {
    $sql = "SELECT saving_id, goal_name, collected_amount, goal_amount, account, due_date FROM savings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Main Execution
$error = handleSavingSubmission($conn, $user_id);
$savings_data = fetchSavingGoals($conn, $user_id);
?>

<!-- Add the SweetAlert2 CDN links -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.all.min.js"></script>

<?php if ($error): ?>
    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="dashboard-container">
    <!-- Left Column - Add Goal Form -->
    <div class="add-goal-section">
        <div class="card">
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
        </div>
    </div>

    <!-- Right Column - Goals List -->
    <div class="goals-list-section">
        <?php 
        $total_collected_amount = 0;
        $total_saving = 0;
        
        if ($savings_data) {
            while ($row = $savings_data->fetch_assoc()) {
                $total_saving += $row['goal_amount'];
                $total_collected_amount += $row['collected_amount'];
            }
            $savings_data->data_seek(0);
        }
        ?>
        
        <div class="card summary-card">
            <h3><i class="fas fa-piggy-bank"></i> Savings Summary</h3>
            <div class="summary-item">
                <span class="label">Total Collected Savings</span>
                <span class="amount">RM <?php echo number_format($total_collected_amount, 2); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Target Savings</span>
                <span class="amount">RM <?php echo number_format($total_saving, 2); ?></span>
            </div>
        </div>

        <div class="card goals-list-card">
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
                        <?php while ($row = $savings_data->fetch_assoc()): ?>
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
                                        <br>
                                        <span class="collected-amount">Current: </span>
                                        <span style="color: green">RM <?php echo number_format($row['collected_amount'], 2); ?></span>
                                        <span class="target-amount">/ Target: RM <?php echo number_format($row['goal_amount'], 2); ?></span>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars($row['account']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td>
                                    <form action="index.php?page=saving" method="POST" class="inline-form delete-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="saving_id" value="<?php echo $row['saving_id']; ?>">
                                        <button type="submit" class="action-btn delete-btn">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-goals">No saving goals found. Start by adding a new goal!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete confirmation
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });

    // Show success message if it exists
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: <?php echo json_encode($_SESSION['success_message']); ?>,
            confirmButtonColor: '#3085d6'
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    // Show error message if it exists
    <?php if (isset($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?php echo json_encode($_SESSION['error']); ?>,
            confirmButtonColor: '#3085d6'
        });
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
});
</script>

<?php $conn->close(); ?>