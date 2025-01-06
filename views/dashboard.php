<div class="container" id="dashboard">
    <!-- dashboard -->

    <div class="left-column">
        <div class="card">
            <h2>Dashboard</h2>
            <p>Welcome to your dashboard</p>
        </div>
    </div>

    <div class="right-column">
        <div class="card">
            <h2>DB Connection Status</h2>
            <?php
            // Check and display connection status
            if ($conn->connect_error) {
                echo "<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>";
            } else {
                echo "<p style='color: green;'>Connected successfully</p>";
            }
            ?>
        </div>
    </div>

</div>