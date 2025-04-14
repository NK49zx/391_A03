<?php
require 'dcon.php';

// header to return JSON
header('Content-Type: application/json');

// validate date and mech_id
if (!isset($_GET['date']) || !isset($_GET['mechanic_id'])) {
    echo json_encode(['error' => 'Date and mechanic ID are required']);
    exit();
}

$date = $_GET['date'];
$mechanic_id = $_GET['mechanic_id'];


// get all time slots
$time_slots_sql = "SELECT * FROM time_slots ORDER BY start_time";
$time_slots_result = $conn->query($time_slots_sql);
$time_slots = [];
if ($time_slots_result->num_rows > 0) {
    while ($row = $time_slots_result->fetch_assoc()) {
        $time_slots[] = $row;
    }
}

// get booked time slots for the specified date and mechanic
$booked_slots_sql = "SELECT time_slot_id FROM appointments_table 
                     WHERE appointment_date = ? AND mechanic_id = ?";
$stmt = $conn->prepare($booked_slots_sql);
$stmt->bind_param("si", $date, $mechanic_id);
$stmt->execute();
$booked_result = $stmt->get_result();

$booked_slots = [];
if ($booked_result->num_rows > 0) {
    while ($row = $booked_result->fetch_assoc()) {
        $booked_slots[] = $row['time_slot_id'];
    }
}

// prepare response
$availability = [];
foreach ($time_slots as $slot) {
    $is_available = !in_array($slot['id'], $booked_slots);
    $availability[] = [
        'id' => $slot['id'],
        'name' => $slot['slot_name'],
        'start_time' => date("g:i A", strtotime($slot['start_time'])),
        'end_time' => date("g:i A", strtotime($slot['end_time'])),
        'is_available' => $is_available
    ];
}

// get mechanic details
$mechanic_sql = "SELECT * FROM mechanics WHERE id = ?";
$stmt = $conn->prepare($mechanic_sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$mechanic_result = $stmt->get_result();
$mechanic = $mechanic_result->fetch_assoc();

// returns the response
echo json_encode([
    'date' => $date,
    'mechanic' => $mechanic,
    'availability' => $availability
]);

$conn->close();

?> 