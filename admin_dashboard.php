<?php
session_start();
require 'dcon.php';

// validates admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: admin_login.php");
    exit();
}

// delete appointments
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $delete_sql = "DELETE FROM appointments_table WHERE id = $id";
    $conn->query($delete_sql);
    
    header("Location: admin_dashboard.php");
    exit();
}

// fetches appointments from the database with mechanic and time slot details
$sql = "SELECT a.*, m.name as mechanic_name, t.slot_name, t.start_time, t.end_time 
        FROM appointments_table a
        JOIN mechanics m ON a.mechanic_id = m.id
        JOIN time_slots t ON a.time_slot_id = t.id
        ORDER BY a.appointment_date DESC, t.start_time ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }   
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-btn {
            padding: 8px 15px;
            margin: 0 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
            display: inline-block;
            text-decoration: none;
            min-width: 80px;
            text-align: center;
        }
        .view-btn {
            background-color: #4CAF50;
        }
        .edit-btn {
            background-color: #2196F3;
            margin-top: 2%;
            margin-bottom: 2%;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .admin-info {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-info p {
            margin: 0;
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
    <div class="admin-info">
        <p>Welcome, Admin</p>
        <a href="admin_logout.php" class="logout-btn">Logout</a>
    </div>
    <table id="appointmentsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Car License</th>
                <th>Car Engine</th>
                <th>Appointment Date</th>
                <th>Time Slot</th>
                <th>Mechanic</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["id"] . "</td>";
                    echo "<td>" . $row["name"] . "</td>";
                    echo "<td>" . $row["phone"] . "</td>";
                    echo "<td>" . $row["car_license"] . "</td>";
                    echo "<td>" . $row["car_engine"] . "</td>";
                    echo "<td>" . $row["appointment_date"] . "</td>";
                    echo "<td>" . $row["slot_name"] . " (" . date("g:i A", strtotime($row["start_time"])) . " - " . date("g:i A", strtotime($row["end_time"])) . ")</td>";
                    echo "<td>" . $row["mechanic_name"] . "</td>";
                    echo "<td>
                            <button class='action-btn view-btn' onclick='viewAppointment(" . $row["id"] . ")'>View</button>
                            <button class='action-btn edit-btn' onclick='editAppointment(" . $row["id"] . ")'>Edit</button>
                            <a href='admin_dashboard.php?delete=" . $row["id"] . "' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this appointment?\")'>Delete</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No appointments found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <script>
        // function to view appointment details
        function viewAppointment(id) {
            window.location.href = "view_appointment.php?id=" + id;
        }

        // function to edit appointment details
        function editAppointment(id) {
            window.location.href = "edit_appointment.php?id=" + id;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?> 