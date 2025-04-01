<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expense";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $family_action = isset($_POST["family_action"]) ? $_POST["family_action"] : "create";
    
    // Set family_group based on the selected action
    $family_group = "";
    if ($family_action == "create") {
        $family_group = isset($_POST["family_group"]) ? $_POST["family_group"] : "";
    } else if ($family_action == "join") {
        // First check the dropdown value
        if (!empty($_POST["family_group"])) {
            $family_group = $_POST["family_group"];
        }
        // Then check the manual input (which takes precedence if filled)
        if (isset($_POST["manual_family_group"]) && !empty($_POST["manual_family_group"])) {
            $family_group = $_POST["manual_family_group"];
        }
    }
    // If action is "none", family_group remains empty
    
    // Log the values for debugging
    error_log("Name: " . $name . ", Email: " . $email . ", Family Group: " . $family_group . ", Action: " . $family_action);
    
    // Check if email already exists
    $check_sql = "SELECT id FROM user_accounts WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "Email already exists. Please use a different email or login.";
    } else {
        // Handle family group logic
        if ($family_action == "join" && !empty($family_group)) {
            // Verify if family group exists
            $verify_sql = "SELECT COUNT(*) as count FROM user_accounts WHERE family_group = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("s", $family_group);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $verify_row = $verify_result->fetch_assoc();
            
            if ($verify_row['count'] == 0) {
                $error_message = "This family group doesn't exist. Please check the name or create a new group.";
            }
        }
        
        // If no errors, proceed with registration
        if (empty($error_message)) {
            $sql = "INSERT INTO user_accounts (name, email, password, family_group) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $password, $family_group);
            
            if ($stmt->execute()) {
                $_SESSION["user_id"] = $stmt->insert_id;
                
                if (!empty($family_group)) {
                    $_SESSION["family_group"] = $family_group;
                    $_SESSION["login_type"] = "family";
                    header("Location: family_dashboard.php");
                    exit();
                } else {
                    $_SESSION["login_type"] = "individual";
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
    }
}

// Get list of existing family groups for dropdown
$family_groups = array();
$groups_sql = "SELECT DISTINCT family_group FROM user_accounts WHERE family_group IS NOT NULL AND family_group != '' ORDER BY family_group";
$groups_result = $conn->query($groups_sql);

if ($groups_result->num_rows > 0) {
    while ($row = $groups_result->fetch_assoc()) {
        $family_groups[] = $row['family_group'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
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
        .family-options {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .radio-group {
            margin-bottom: 10px;
        }
        .radio-label {
            display: inline;
            margin-right: 15px;
        }
    </style>
    <script>
        function toggleFamilyFields() {
            var familyAction = document.querySelector('input[name="family_action"]:checked').value;
            var joinGroup = document.getElementById('join-group');
            var createGroup = document.getElementById('create-group');
            
            // Clear values when hiding to prevent conflicts
            if (familyAction !== 'create') {
                document.getElementById('new-family').value = '';
            }
            
            if (familyAction !== 'join') {
                document.getElementById('existing-family').value = '';
                document.getElementById('manual-family').value = '';
            }
            
            if (familyAction === 'join') {
                joinGroup.style.display = 'block';
                createGroup.style.display = 'none';
            } else if (familyAction === 'create') {
                joinGroup.style.display = 'none';
                createGroup.style.display = 'block';
            } else {
                joinGroup.style.display = 'none';
                createGroup.style.display = 'none';
            }
            
            // Debug logging
            console.log("Family action:", familyAction);
            console.log("Create input value:", document.getElementById('new-family').value);
            console.log("Join dropdown value:", document.getElementById('existing-family').value);
            console.log("Join manual value:", document.getElementById('manual-family').value);
        }
        
        // Initialize on page load
        window.onload = function() {
            toggleFamilyFields();
        };
    </script>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Family Group Options:</label>
                <div class="family-options">
                    <div class="radio-group">
                        <input type="radio" id="none" name="family_action" value="none" onclick="toggleFamilyFields()">
                        <label for="none" class="radio-label">No Family Group</label>
                        
                        <input type="radio" id="create" name="family_action" value="create" checked onclick="toggleFamilyFields()">
                        <label for="create" class="radio-label">Create Family Group</label>
                        
                        <input type="radio" id="join" name="family_action" value="join" onclick="toggleFamilyFields()">
                        <label for="join" class="radio-label">Join Existing Family</label>
                    </div>
                    
                    <div id="create-group">
                        <label for="new-family">Create New Family Group:</label>
                        <input type="text" id="new-family" name="family_group" placeholder="Enter a unique family group name">
                    </div>
                    
                    <div id="join-group" style="display: none;">
                        <label for="existing-family">Select Existing Family Group:</label>
                        <select id="existing-family" name="family_group">
                            <option value="">Select a family group</option>
                            <?php foreach ($family_groups as $group): ?>
                                <option value="<?php echo htmlspecialchars($group); ?>"><?php echo htmlspecialchars($group); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p><small>If your family group is not listed, make sure you type the exact name</small></p>
                        <input type="text" id="manual-family" placeholder="Or type exact family group name" name="manual_family_group">
                    </div>
                </div>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="links">
            <a href="login.php">Already have an account? Login</a><br>
            <a href="index.php">Back to Home</a>
        </div>
    </div>
</body>
</html>