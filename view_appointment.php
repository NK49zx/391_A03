<?php
session_start();
require 'dcon.php';

// checks admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// checks valid admin ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = $_GET['id'];

// fetches appointment details with mechanic and time slot information
$sql = "SELECT a.*, m.name as mechanic_name, t.slot_name, t.start_time, t.end_time 
        FROM appointments_table a
        JOIN mechanics m ON a.mechanic_id = m.id
        JOIN time_slots t ON a.time_slot_id = t.id
        WHERE a.id = $id";
$result = $conn->query($sql);

// redirects to admin dashboard if no appointments found
if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$appointment = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment</title>
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
        .appointment-details {
            display: flex;
            flex-direction: column;
            border: solid 1px;
            width: 600px;
            place-self: center;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .appointment-details h2 {
            margin-top: 0;
            color: #333;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            width: 200px;
        }
        .detail-value {
            flex: 1;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            place-self: center;
        }
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn {
            background-color: #2196F3;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .back-btn {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="">About</a>
        <a href="">Contact</a>
        <a href="admin_dashboard.php" class="active right">Admin Dashboard</a>
    </div>
    <h1 style="text-align:center;">Appointment Details</h1>
    <div class="appointment-details">
        <h2>Appointment #<?php echo $appointment['id']; ?></h2>
        
        <div class="detail-row">
            <div class="detail-label">Name:</div>
            <div class="detail-value"><?php echo $appointment['name']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Address:</div>
            <div class="detail-value"><?php echo $appointment['address']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Phone:</div>
            <div class="detail-value"><?php echo $appointment['phone']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Car License Number:</div>
            <div class="detail-value"><?php echo $appointment['car_license']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Car Engine Number:</div>
            <div class="detail-value"><?php echo $appointment['car_engine']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Appointment Date:</div>
            <div class="detail-value"><?php echo $appointment['appointment_date']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Time Slot:</div>
            <div class="detail-value">
                <?php 
                echo $appointment['slot_name'] . " (" . 
                     date("g:i A", strtotime($appointment['start_time'])) . " - " . 
                     date("g:i A", strtotime($appointment['end_time'])) . ")"; 
                ?>
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Mechanic:</div>
            <div class="detail-value"><?php echo $appointment['mechanic_name']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">Created At:</div>
            <div class="detail-value"><?php echo $appointment['created_at']; ?></div>
        </div>
    </div>
    
    <div class="action-buttons">
        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="action-btn edit-btn">Edit Appointment</a>
        <a href="admin_dashboard.php?delete=<?php echo $appointment['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this appointment?')">Delete Appointment</a>
        <a href="admin_dashboard.php" class="action-btn back-btn">Back to Dashboard</a>
    </div>
</body>
</html>

<?php
$conn->close();
?> 