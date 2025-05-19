<?php
include '../config/connection.php';
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $facilities = $_POST['facilities'];
    
    // Check if room exists
    $check = $conn->prepare("SELECT image FROM rooms WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $current_image = $row['image'];
        
        // Check if new image is uploaded
        if ($_FILES['image']['size'] > 0) {
            $target_dir = "../uploads/";
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check file type
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            if (!in_array($file_extension, $allowed_types)) {
                $_SESSION['error'] = "Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan!";
                header("Location: manage_rooms.php");
                exit;
            }
            
            // Check file size (max 2MB)
            if ($_FILES["image"]["size"] > 2000000) {
                $_SESSION['error'] = "Ukuran file terlalu besar (maksimal 2MB)!";
                header("Location: manage_rooms.php");
                exit;
            }
            
            // Upload file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image if it exists
                if (file_exists("../uploads/" . $current_image)) {
                    unlink("../uploads/" . $current_image);
                }
                
                // Update room with new image
                $stmt = $conn->prepare("UPDATE rooms SET name = ?, price = ?, facilities = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sissi", $name, $price, $facilities, $new_filename, $id);
            } else {
                $_SESSION['error'] = "Gagal mengunggah gambar!";
                header("Location: manage_rooms.php");
                exit;
            }
        } else {
            // Update room without changing image
            $stmt = $conn->prepare("UPDATE rooms SET name = ?, price = ?, facilities = ? WHERE id = ?");
            $stmt->bind_param("sisi", $name, $price, $facilities, $id);
        }
        
        // Execute the update
        $stmt->execute();
        
        if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
            $_SESSION['success'] = "Kamar berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui kamar!";
        }
    } else {
        $_SESSION['error'] = "Kamar tidak ditemukan!";
    }
    
    header("Location: manage_rooms.php");
    exit;
} else {
    // If accessed directly without form submission
    header("Location: manage_rooms.php");
    exit;
}
?>