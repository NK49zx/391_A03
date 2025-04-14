<?php
session_start();
require 'dcon.php';

// validates admin information
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM admin_users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        // redirects to admin dashboard
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
        echo $error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0 auto;
            background-color: #A6F1E0;
            font-family: monospace;
            
        }

        .navbar {
            overflow: hidden;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #F7CFD8;
            padding: 20px;
        }
        .navbar a {
            float: left;
            color: black;
            text-align: center;
            padding: 14px 16px;
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
            
        }
        .navbar a:hover {
            background-color: #F4F8D3;
            color: black;
            border-radius: 10px;
        }
        .navbar .right {
            float: right;
        }

        .form-group {
            width: 600px;
            margin-bottom: 15px;
        }
        .login-form{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-items: center;
            gap: 10px;
            background-color: #F4F8D3;
            margin: 20%;
            margin-top: 0px;
            padding: 4%;
            border-radius: 15px;
            box-shadow: 5px 10px;
            margin-bottom: 2%;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: larger;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 18px;
        }

        button {
            background-color: #F7CFD8;
            color: black;
            padding: 15px 20px;
            cursor: pointer;
            border-radius: 10px;
            border: none;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .appointment-details {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="">About</a>
        <a href="">Contact</a>
        <a href="admin_login.php" class="active right">Admin Login</a>
    </div>

    <div class="login-form">
        <h1>Admin Login</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?> 