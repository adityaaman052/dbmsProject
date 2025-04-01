<?php
// login.php - Updated with Family Group Option
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$login_type = isset($_GET['type']) ? $_GET['type'] : 'individual';
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    
    // Regular individual login
    if ($login_type == 'individual') {
        $sql = "SELECT id, password FROM user_accounts WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["id"];
                $_SESSION["login_type"] = "individual";
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No user found with this email.";
        }
    } 
    // Family group login
    else if ($login_type == 'family') {
        $family_group = $_POST["family_group"];
        
        $sql = "SELECT id, password, family_group FROM user_accounts WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                // Verify family group matches
                if (!empty($row["family_group"]) && $row["family_group"] == $family_group) {
                    $_SESSION["user_id"] = $row["id"];
                    $_SESSION["family_group"] = $family_group;
                    $_SESSION["login_type"] = "family";
                    header("Location: family_dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid family group or you don't belong to this family group.";
                }
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No user found with this email.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - <?php echo ucfirst($login_type); ?></title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .links {
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><?php echo ucfirst($login_type); ?> Login</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <?php if ($login_type == 'family'): ?>
            <div class="form-group">
                <label for="family_group">Family Group:</label>
                <input type="text" id="family_group" name="family_group" required>
            </div>
            <?php endif; ?>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="links">
            <a href="register.php">Don't have an account? Register</a><br>
            <a href="index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>