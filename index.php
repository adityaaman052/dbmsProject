<?php
// index.php - New Home Page with Login Options
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expense Tracker - Home</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }
        .login-options {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
        }
        .option-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            width: 30%;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .option-card:hover {
            transform: translateY(-5px);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Expense Tracker</h1>
        <p>Manage your finances efficiently and collaborate with your family members.</p>
        
        <div class="login-options">
            <div class="option-card">
                <h2>Individual Login</h2>
                <p>Access your personal expense tracking account.</p>
                <a href="login.php?type=individual" class="btn">Login</a>
            </div>
            
            <div class="option-card">
                <h2>Family Login</h2>
                <p>Access expense tracking for your entire family group.</p>
                <a href="login.php?type=family" class="btn">Family Login</a>
            </div>
            
            <div class="option-card">
                <h2>New User?</h2>
                <p>Create a new account to start tracking your expenses.</p>
                <a href="register.php" class="btn">Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>