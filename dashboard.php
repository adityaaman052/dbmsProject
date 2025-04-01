<?php
// dashboard.php - Updated to link to family dashboard
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"];

// Get user's family group
$user_sql = "SELECT family_group FROM user_accounts WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$family_group = $user_data['family_group'];

// Handle transaction submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["transaction_type"])) {
    $type = $_POST["transaction_type"];
    $category = $_POST["category"];
    $amount = $_POST["amount"];
    $date = date("Y-m-d");
    
    $sql = "INSERT INTO transactions (user_id, type, category, amount, date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $user_id, $type, $category, $amount, $date);
    $stmt->execute();
}

// Handle budget submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["budget_category"])) {
    $category = $_POST["budget_category"];
    $amount = $_POST["budget_amount"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    
    $sql = "INSERT INTO budgets (user_id, category, amount, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $user_id, $category, $amount, $start_date, $end_date);
    $stmt->execute();
}

// Fetch transactions
$transactions = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY date DESC");

// Fetch budgets
$budgets = $conn->query("SELECT * FROM budgets WHERE user_id = $user_id ORDER BY start_date DESC");

// Calculate totals
$income_total = 0;
$expense_total = 0;

$totals_sql = "SELECT type, SUM(amount) as total FROM transactions WHERE user_id = ? GROUP BY type";
$totals_stmt = $conn->prepare($totals_sql);
$totals_stmt->bind_param("i", $user_id);
$totals_stmt->execute();
$totals_result = $totals_stmt->get_result();

while ($row = $totals_result->fetch_assoc()) {
    if ($row['type'] == 'income') {
        $income_total = $row['total'];
    } else if ($row['type'] == 'expense') {
        $expense_total = $row['total'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        .container {
            width: 90%;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .section {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .transaction-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn-secondary {
            background-color: #607d8b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to Your Dashboard</h2>
            <div class="nav-buttons">
                <?php if (!empty($family_group)): ?>
                    <a href="family_dashboard.php" class="btn">Family Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        
        <div class="summary">
            <h3>Financial Summary</h3>
            <p>Total Income: $<?php echo number_format($income_total, 2); ?></p>
            <p>Total Expenses: $<?php echo number_format($expense_total, 2); ?></p>
            <p>Net Balance: $<?php echo number_format($income_total - $expense_total, 2); ?></p>
            <?php if (empty($family_group)): ?>
                <p><em>Note: You are not part of a family group. Set a family group in your profile to enable family features.</em></p>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-grid">
            <div class="section">
                <h3>Add Transaction</h3>
                <form method="POST">
                    <div>
                        <label>Type:</label>
                        <select name="transaction_type">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label>Category:</label>
                        <input type="text" name="category" required>
                    </div>
                    <div>
                        <label>Amount:</label>
                        <input type="number" step="0.01" name="amount" required>
                    </div>
                    <button type="submit" class="btn">Add Transaction</button>
                </form>
            </div>
            
            <div class="section">
                <h3>Set Budget</h3>
                <form method="POST">
                    <div>
                        <label>Category:</label>
                        <input type="text" name="budget_category" required>
                    </div>
                    <div>
                        <label>Amount:</label>
                        <input type="number" step="0.01" name="budget_amount" required>
                    </div>
                    <div>
                        <label>Start Date:</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div>
                        <label>End Date:</label>
                        <input type="date" name="end_date" required>
                    </div>
                    <button type="submit" class="btn">Set Budget</button>
                </form>
            </div>
        </div>
        
        <div class="section">
            <h3>Transaction History</h3>
            <div class="transaction-list">
                <?php if ($transactions->num_rows > 0): ?>
                    <ul>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <li><?php echo $row["date"] . " - " . ucfirst($row["type"]) . " - " . $row["category"] . " - $" . $row["amount"]; ?></li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No transactions found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
    <h3>Budget Summary</h3>
    <?php if ($budgets->num_rows > 0): ?>
        <ul>
            <?php while ($row = $budgets->fetch_assoc()): ?>
                <li><?php echo $row["category"] . " - Budget: $" . $row["amount"] . " (" . date("M d, Y", strtotime($row["start_date"])) . " to " . date("M d, Y", strtotime($row["end_date"])) . ")"; ?></li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No budgets found.</p>
    <?php endif; ?>
</div>

</div>
</body>
</html>