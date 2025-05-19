<?php
include '../config/connection.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$bookingId = $_POST['id'];
$status = $_POST['status'];

// Validate status
$allowedStatuses = ['pending', 'confirmed', 'cancelled'];
if (!in_array($status, $allowedStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update booking status
$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $bookingId);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to update booking status: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>