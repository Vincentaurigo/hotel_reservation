<?php
session_start();
include '../config/connection.php';

// Debugging - lihat semua variabel $_SESSION
// echo "<pre>"; print_r($_SESSION); echo "</pre>";
// echo "<pre>"; print_r($_POST); echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if(!isset($_SESSION['id_user'])) {
        echo "Error: You are not logged in. Please login and try again.";
        exit();
    }
    
    $user_id = $_POST['user_id']; // Ambil dari POST untuk memastikan konsistensi dengan form
    $room_id = $_POST['room_id'];
    $checkin_date = $_POST['checkin_date'];
    $checkout_date = $_POST['checkout_date'];
    
    // Basic validation
    if (empty($user_id) || empty($room_id) || empty($checkin_date) || empty($checkout_date)) {
        echo "Error: All fields are required.";
        echo "<br>User ID: " . $user_id;
        echo "<br>Room ID: " . $room_id;
        echo "<br>Check-in: " . $checkin_date;
        echo "<br>Check-out: " . $checkout_date;
        exit();
    }
    
    // Check date validity
    if (strtotime($checkout_date) <= strtotime($checkin_date)) {
        echo "Error: Check-out date must be after check-in date.";
        exit();
    }
    
    try {
        // Verify that room_id exists in rooms table
        $checkRoom = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
        $checkRoom->bind_param("i", $room_id);
        $checkRoom->execute();
        $roomResult = $checkRoom->get_result();
        
        if ($roomResult->num_rows === 0) {
            echo "Error: Room not found.";
            exit();
        }
        
        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, checkin_date, checkout_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $room_id, $checkin_date, $checkout_date);
        
        if ($stmt->execute()) {
            header("Location: mybooking.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
            
            // Debugging - check if it's a foreign key issue
            if (strpos($stmt->error, "foreign key constraint") !== false) {
                echo "<p>Foreign key error. Checking user details:</p>";
                
                // Check user details
                $userCheck = $conn->prepare("SELECT * FROM users WHERE ID_user = ?");
                $userCheck->bind_param("i", $user_id);
                $userCheck->execute();
                $userResult = $userCheck->get_result();
                
                if ($userResult->num_rows === 0) {
                    echo "<p>User ID " . $user_id . " not found in users table!</p>";
                } else {
                    $userData = $userResult->fetch_assoc();
                    echo "<p>User found: Username - " . $userData['username'] . "</p>";
                }
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo "Exception caught: " . $e->getMessage();
    }
} else {
    echo "Invalid request method. Please use the booking form.";
}

$conn->close();
?>