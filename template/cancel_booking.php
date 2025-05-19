<?php
include '../config/connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $user_id = $_SESSION['id_user'];
    
    // Verify that this booking belongs to the current user
    $sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update booking status to cancelled
        $status = 'cancelled';
        $sql = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['cancel_success'] = "Your booking has been cancelled successfully.";
        } else {
            $_SESSION['cancel_error'] = "There was an error cancelling your booking. Please try again.";
        }
    } else {
        $_SESSION['cancel_error'] = "Invalid booking or you don't have permission to cancel this booking.";
    }
}

// Redirect back to my bookings page
header("Location: mybooking.php");
exit;
?>