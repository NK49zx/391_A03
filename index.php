<?php
session_start();
require 'dcon.php';

// handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $address = $_POST["address"];
    $phone = $_POST["phone"];
    $car_license = $_POST["car_license"];
    $car_engine = $_POST["car_engine"];
    $appointment_date = $_POST["appointment_date"];
    $mechanic_id = $_POST["mechanic_id"];
    $time_slot_id = $_POST["time_slot_id"];
    
    // check if the car already has an appointment on the same day
    $check_car_sql = "SELECT id FROM appointments_table 
                      WHERE car_license = ? AND appointment_date = ?";
    $stmt = $conn->prepare($check_car_sql);
    $stmt->bind_param("ss", $car_license, $appointment_date);
    $stmt->execute();
    $car_check = $stmt->get_result();
    
    if ($car_check->num_rows > 0) {
        $error_message = "This car (License: $car_license) already has an appointment scheduled for $appointment_date. You cannot book more than one appointment per day for the same car.";
    } else {
        // check if the time slot is available
        $check_sql = "SELECT id FROM appointments_table 
                      WHERE appointment_date = ? AND mechanic_id = ? AND time_slot_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("sii", $appointment_date, $mechanic_id, $time_slot_id);
        $stmt->execute();
        $slot_check = $stmt->get_result();
        
        if ($slot_check->num_rows > 0) {
            $error_message = "This time slot is already booked. Please select another time slot.";
        } else {
            // insert the data into the database
            $insert_sql = "INSERT INTO appointments_table (name, address, phone, car_license, car_engine, appointment_date, mechanic_id, time_slot_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssssii", $name, $address, $phone, $car_license, $car_engine, $appointment_date, $mechanic_id, $time_slot_id);
            
            if ($stmt->execute()) {
                // triggers the success modal
                $mechanic_sql = "SELECT name FROM mechanics WHERE id = ?";
                $stmt = $conn->prepare($mechanic_sql);
                $stmt->bind_param("i", $mechanic_id);
                $stmt->execute();
                $mech_res = $stmt->get_result();
                $mechanic = $mech_res->fetch_assoc();
                
                $time_slot_sql = "SELECT slot_name, start_time, end_time FROM time_slots WHERE id = ?";
                $stmt = $conn->prepare($time_slot_sql);
                $stmt->bind_param("i", $time_slot_id);
                $stmt->execute();
                $slot_res = $stmt->get_result();
                $time_slot = $slot_res->fetch_assoc();
                
                // stores the details for the success modal
                $_SESSION['success_appointment'] = [
                    'name' => $name,
                    'address' => $address,
                    'phone' => $phone,
                    'car_license' => $car_license,
                    'car_engine' => $car_engine,
                    'appointment_date' => $appointment_date,
                    'mechanic_name' => $mechanic['name'],
                    'time_slot' => $time_slot['slot_name'] . " (" . date("g:i A", strtotime($time_slot['start_time'])) . " - " . date("g:i A", strtotime($time_slot['end_time'])) . ")"
                ];
                header("Location: index.php?success=1");
                exit();
            } else {
                $error_message = "Unknown Error occured when booking appointment";
                echo $error_message;
            }
        }
    }
}

//gets mechanic list for the dropdown
$mechanics_sql = "SELECT * FROM mechanics ORDER BY name";
$mechanics_result = $conn->query($mechanics_sql);
$mechanics = [];

if ($mechanics_result->num_rows > 0) {
    while ($row = $mechanics_result->fetch_assoc()) {
        $mechanics[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Service Appointment</title>
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
        .appointment-form{
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

        .error {
            color: red;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 4px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 4px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #F7CFD8;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
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
        .modal-buttons {
            margin-top: 20px;
            text-align: center;
        }
        .modal-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-size: 14px;
            margin: 0 5px;
        }
        .ok-btn {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="active">NBR Workshop and Maintenance</a>
        <a href="" class="right">About</a>
        <a href="" class="right">Contact</a>
        <a href="admin_login.php" class="right">Admin Login</a>
    </div>
    <?php if (isset($error_message)): ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <div class="appointment-form">
        <h1>Get your appointments now!</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" required></textarea>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="car_license">Car License Number:</label>
                <input type="text" id="car_license" name="car_license" required>
            </div>
            <div class="form-group">
                <label for="car_engine">Car Engine Number:</label>
                <input type="text" id="car_engine" name="car_engine" required>
            </div>
            <div class="form-group">
                <label for="appointment_date">Appointment Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="mechanic_id">Select Mechanic:</label>
                <select id="mechanic_id" name="mechanic_id" required>
                    <option value="">Select a mechanic</option>
                    <?php foreach ($mechanics as $mechanic): ?>
                        <option value="<?php echo $mechanic['id']; ?>">
                            <?php echo $mechanic['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="time_slot_id">Select Time Slot:</label>
                <select id="time_slot_id" name="time_slot_id" required disabled>
                    <option value="">Select a date and mechanic first</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Book Appointment</button>
        </form>
    </div>
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Appointment Booked Successfully!</h2>
            <p>Your appointment has been confirmed. Here are the details:</p>
            
            <div class="appointment-details">
                <?php if (isset($_SESSION['success_appointment'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['name']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['address']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['phone']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Car License Number:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['car_license']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Car Engine Number:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['car_engine']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Appointment Date:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['appointment_date']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Time Slot:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['time_slot']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Mechanic:</div>
                        <div class="detail-value"><?php echo $_SESSION['success_appointment']['mechanic_name']; ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="modal-buttons">
                <button class="modal-btn ok-btn" id="okButton">OK</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('appointment_date');
            const mech = document.getElementById('mechanic_id');
            const timeslot = document.getElementById('time_slot_id');
            
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
                                }
                                else{
                                    bookedSlot = false;
                                }
                                timeslot.appendChild(option);
                            });
                            // if all slots are booked, mark the mechanic unavailable
                            if (bookedSlot && data.availability.length > 0) {
                                // disable the time slot select
                                timeslot.disabled = true;
                                timeslot.innerHTML = '<option value="">All slots are booked for this mechanic. Choose other available personnel</option>';
                                
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
                            timeslot.innerHTML = '<option value="">Error loading time slots</option>';
                        });
                } else {
                    // disable the time slot field if date or mechanic is not selected
                    timeslot.disabled = true;
                    timeslot.innerHTML = '<option value="">Select a date and mechanic first</option>';
                }
            }
            
            dateInput.addEventListener('change', loadTimeSlots);
            mech.addEventListener('change', loadTimeSlots);
            
            // modal handling
            const modal = document.getElementById('successModal');
            const closeBtn = document.getElementsByClassName('close')[0];
            const okBtn = document.getElementById('okButton');
            
            // Check if success parameter is in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                modal.style.display = 'block';
            }
            
            // Close modal when clicking the X
            closeBtn.onclick = function() {
                modal.style.display = 'none';
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            // close modal
            okBtn.onclick = function() {
                modal.style.display = 'none';
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            // validates phone no
            const phnInput = document.getElementById('phone');
            const appointmentForm = document.querySelector('form');
            
            // checks before submission
            appointmentForm.addEventListener('submit', function(event) {
                const digits = phnInput.value.replace(/\D/g, '');
                if (digits.length < 11) {
                    alert('Please enter a valid phone number with at least 10 digits');
                    event.preventDefault(); // stops form submission
                    phnInput.focus();
                }
            });
        });
    </script>
</body>
</html>

<?php
if (isset($_SESSION['success_appointment'])) {
    unset($_SESSION['success_appointment']);
}

$conn->close();
?>