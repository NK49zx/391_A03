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
$error = '';
$success = '';

// handles form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $car_license = $_POST['car_license'];
    $car_engine = $_POST['car_engine'];
    $appointment_date = $_POST['appointment_date'];
    $mechanic_id = $_POST['mechanic'];
    $time_slot_id = $_POST['time_slot'];
    
    // check if the car already has an appointment on the same day
    $check_car_sql = "SELECT id FROM appointments_table 
                      WHERE car_license = ? AND appointment_date = ? AND id != ?";
    $stmt = $conn->prepare($check_car_sql);
    $stmt->bind_param("ssi", $car_license, $appointment_date, $id);
    $stmt->execute();
    $check_car_result = $stmt->get_result();
    
    if ($check_car_result->num_rows > 0) {
        $error = "This car (License: $car_license) already has an appointment scheduled for $appointment_date. You cannot book more than one appointment per day for the same car.";
    } else {
        // check if the time slot is available
        $check_sql = "SELECT id FROM appointments_table 
                      WHERE appointment_date = ? AND time_slot_id = ? AND mechanic_id = ? AND id != ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("siii", $appointment_date, $time_slot_id, $mechanic_id, $id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "This time slot is already booked for the selected mechanic. Please choose a different time slot.";
        } else {
            // updates the appointment
            $update_sql = "UPDATE appointments_table SET 
                          name = ?, address = ?, phone = ?, car_license = ?, 
                          car_engine = ?, appointment_date = ?, mechanic_id = ?, 
                          time_slot_id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssiii", $name, $address, $phone, $car_license, 
                             $car_engine, $appointment_date, $mechanic_id, 
                             $time_slot_id, $id);
            
            if ($stmt->execute()) {
                header("Location: view_appointment.php?id=$id");
                exit();
                
            } else {
                $error = "Error updating appointment: " . $conn->error;
                echo $error;
            }
        }
    }
}

// fetches the existing appointment details
$sql = "SELECT * FROM appointments_table WHERE id = $id";
$result = $conn->query($sql);

// redirects to admin dashboard if no appointment found
if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$appointment = $result->fetch_assoc();

// fetches all mechanics
$mechanics_sql = "SELECT * FROM mechanics ORDER BY name";
$mechanics_result = $conn->query($mechanics_sql);

// fetches all time slots
$time_slots_sql = "SELECT * FROM time_slots ORDER BY start_time";
$time_slots_result = $conn->query($time_slots_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment</title>
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
        .form-container{
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
        input[type="text"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .error {
            color: red;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 4px;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
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
        .save-btn {
            background-color: #4CAF50;
        }
        .cancel-btn {
            background-color: #f44336;
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

    <h1 style="text-align:center;">Edit Appointment</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $appointment['name']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" required><?php echo $appointment['address']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo $appointment['phone']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="car_license">Car License Number:</label>
                <input type="text" id="car_license" name="car_license" value="<?php echo $appointment['car_license']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="car_engine">Car Engine Number:</label>
                <input type="text" id="car_engine" name="car_engine" value="<?php echo $appointment['car_engine']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="appointment_date">Appointment Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" value="<?php echo $appointment['appointment_date']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="mechanic">Mechanic:</label>
                <select id="mechanic" name="mechanic" required>
                    <?php while ($mechanic = $mechanics_result->fetch_assoc()): ?>
                        <option value="<?php echo $mechanic['id']; ?>" <?php echo ($mechanic['id'] == $appointment['mechanic_id']) ? 'selected' : ''; ?>>
                            <?php echo $mechanic['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="time_slot">Time Slot:</label>
                <select id="time_slot" name="time_slot" required>
                    <?php while ($time_slot = $time_slots_result->fetch_assoc()): ?>
                        <option value="<?php echo $time_slot['id']; ?>" <?php echo ($time_slot['id'] == $appointment['time_slot_id']) ? 'selected' : ''; ?>>
                            <?php echo $time_slot['slot_name'] . " (" . date("g:i A", strtotime($time_slot['start_time'])) . " - " . date("g:i A", strtotime($time_slot['end_time'])) . ")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="action-btn save-btn">Save Changes</button>
                <a href="view_appointment.php?id=<?php echo $id; ?>" class="action-btn cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('appointment_date');
        const mech = document.getElementById('mechanic');
        const timeslot = document.getElementById('time_slot');
        
        // function to load time slots
        function loadTimeSlots() {
            const date = dateInput.value;
            const mechId = mech.value;
            
            if (date && mechId) {
                timeslot.disabled = false;
                
                // fetch time slots
                fetch(`check_availability.php?date=${date}&mechanic_id=${mechId}`)
                    .then(response => response.json())
                    .then(data => {
                        timeslot.innerHTML = '<option value="">Select a time slot</option>';
                        let bookedSlot = true;
                        // add available time slot
                        data.availability.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.id;
                            option.textContent = `${slot.name} (${slot.start_time} - ${slot.end_time})`;
                            if (!slot.is_available) {
                                option.disabled = true;
                                option.textContent += ' (Booked)';
                            } else {
                                bookedSlot = false;
                            }
                            timeslot.appendChild(option);
                        });
                        
                        // if all slots are booked, mark the mechanic unavailable
                        if (bookedSlot && data.availability.length > 0) {
                            // disable the time slot select
                            timeslot.disabled = true;
                            timeslot.innerHTML = '<option value="">All slots booked for this mechanic on this date</option>';
                            
                            // disable the mechanic option in the dropdown
                            const mechOption = mech.querySelector(`option[value="${mechId}"]`);
                                if (mechOption) {
                                    mechOption.disabled = true;
                                    mechOption.textContent += ' (Slot Full)';
                                }
                        }
                    })

                    .catch(error => {
                        console.error('Error fetching time slots:', error);
                        timeSlotSelect.innerHTML = '<option value="">Error loading time slots</option>';
                    });
            } else {
                // disable the time slot field if date or mechanic is not selected
                timeslot.disabled = true;
                    timeslot.innerHTML = '<option value="">Select a date and mechanic first</option>';
            }
        }
        dateInput.addEventListener('change', loadTimeSlots);
        mech.addEventListener('change', loadTimeSlots);
    });
</script>
</body>
</html>

<?php
$conn->close();
?> 