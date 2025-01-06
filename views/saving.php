<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = 1; // Static user ID (replace with session or dynamic data as needed)
    $goal_name = $_POST['goal_name'];
    $goal_amount = $_POST['goal_amount'];
    $account = $_POST['account'];
    $due_date = $_POST['due_date'];

    include('auth/db_config.php');

    $sql = "INSERT INTO savings (user_id, goal_name, goal_amount, account, due_date) VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiss", $user_id, $goal_name, $goal_amount, $account, $due_date);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: index.php?page=saving");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<div class="">
    <div class="container">
        <h1>Saving</h1>
        <p>Here you can see your saving</p>
    </div>
    <div class="add-goal">
        <h2>Add Saving Goal</h2>
        <form action="" method="POST">
            <label for="goal_name">Goal Name:</label>
            <input type="text" id="goal_name" name="goal_name" required>

            <label for="goal_amount">Total Save:</label>
            <input type="number" id="goal_amount" name="goal_amount" required>

            <label for="account">Account:</label>
            <input type="text" id="account" name="account" required>

            <label for="due_date">Due Date:</label>
            <input type="date" id="due_date" name="due_date" required>

            <button type="submit">Add Goal</button>
        </form>
    </div>

<div class="goal-list">
    <h2>Your Saving Goals</h2>
    <?php


    // $conn = new mysqli('localhost', 'root', 'password', 'fundly');

    // if ($conn->connect_error) {
    //     die("Connection failed: " . $conn->connect_error);
    // }

    include('auth/db_config.php');

    $sql = "SELECT goal_name, goal_amount, account, due_date FROM savings WHERE user_id = 1";
    $result = $conn->query($sql);

    $total_saving = 0;

    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>Goal Name</th><th>Total Save</th><th>Account</th><th>Due Date</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $total_saving += $row['goal_amount'];
            echo "<tr><td>" . htmlspecialchars($row['goal_name']) . "</td><td>" . htmlspecialchars($row['goal_amount']) . "</td><td>" . htmlspecialchars($row['account']) . "</td><td>" . htmlspecialchars($row['due_date']) . "</td></tr>";
        }
        echo "</table>";
        echo "<h3>Total Savings: $total_saving</h3>";
    } else {
        echo "<p>No goals found.</p>";
    }
    $conn->close();
    ?>
</div>