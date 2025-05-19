<?php
include "../config/connection.php";

session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id_user'];
$username = $_SESSION['username'];

// Handle profile picture upload
$message = "";
$message_type = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Profile picture update
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] != 4) {
        $target_dir = "../assets/profile_pictures/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
        $new_filename = $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file size (max 5MB)
        if ($_FILES["profile_picture"]["size"] > 5000000) {
            $message = "Sorry, your file is too large. Maximum size is 5MB.";
            $message_type = "error";
        } 
        // Allow certain file formats
        else if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" ) {
            $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $message_type = "error";
        } 
        else {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                // Update database with new profile picture
                $sql = "UPDATE users SET profile_picture = ? WHERE ID_user = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_filename, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Profile picture updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error updating profile picture: " . $conn->error;
                    $message_type = "error";
                }
            } else {
                $message = "Sorry, there was an error uploading your file.";
                $message_type = "error";
            }
        }
    }
    
    // Profile information update
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format";
            $message_type = "error";
        } else {
            // Check if email already exists for another user
            $check_sql = "SELECT ID_user FROM users WHERE email = ? AND ID_user != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = "Email already in use by another account";
                $message_type = "error";
            } else {
                // Update user information
                $update_sql = "UPDATE users SET email = ? WHERE ID_user = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $email, $user_id);
                
                if ($update_stmt->execute()) {
                    $message = "Profile updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error updating profile: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    }
}

// Fetch current user data
$sql = "SELECT profile_picture, email FROM users WHERE ID_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile_pic = $row['profile_picture'];
    $email = $row['email'];
} else {
    $profile_pic = "default_profile.jpg";
    $email = "";
}

// Fetch booking history
$booking_sql = "SELECT b.*, r.name as room_name, r.image as room_image, r.price 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.id 
                WHERE b.user_id = ? 
                ORDER BY b.checkin_date DESC";
$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$bookings = $booking_stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Cokro Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #fca311;
            --danger-color: #e63946;
            --text-color: #333;
            --light-gray: #e9ecef;
            --medium-gray: #adb5bd;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: var(--dark-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary-color);
        }
        
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .profile-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 20px;
            text-align: center;
            position: relative;
        }
        
        .profile-title {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .profile-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .tab {
            padding: 15px 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab:hover:not(.active) {
            background-color: #f9f9f9;
        }
        
        .tab-content {
            padding: 25px;
        }
        
        .tab-panel {
            display: none;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        .profile-picture-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .current-picture {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        
        .current-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border: 5px solid white;
        }
        
        .profile-picture-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .profile-picture-overlay:hover {
            background-color: var(--secondary-color);
        }
        
        .file-upload {
            margin: 20px 0;
        }
        
        .file-upload label {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--light-gray);
            color: var(--dark-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload label:hover {
            background-color: var(--medium-gray);
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c1121f;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--light-gray);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: var(--medium-gray);
        }
        
        .info-value {
            font-weight: 600;
        }
        
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .booking-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            gap: 20px;
            transition: transform 0.3s;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .booking-image {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .booking-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .booking-details {
            flex: 1;
        }
        
        .booking-room {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .booking-dates {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .booking-price {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .booking-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .buttons-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 30px;
        }
        
        .footer-column {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
        }
        
        .footer-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .social-icon:hover {
            background-color: var(--primary-color);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            font-size: 0.9rem;
            color: #adb5bd;
        }
        
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .booking-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .booking-image {
                width: 150px;
                height: 150px;
            }
            
            .footer-content {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="#" class="logo">Cokro Hotel</a>
            <div class="nav-links">
                <a href="../template/dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="../template/choose_room.php"><i class="fas fa-hotel"></i> Rooms</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Message Display -->
        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-container">
            <div class="profile-header">
                <h1 class="profile-title">My Profile</h1>
                <p class="profile-subtitle">Manage your account information and view your booking history</p>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" data-tab="account">Account Settings</div>
                <div class="tab" data-tab="bookings">My Bookings</div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Account Tab -->
                <div class="tab-panel active" id="account-tab">
                    <div class="profile-picture-section">
                        <div class="current-picture">
                            <img src="../assets/profile_pictures/<?php echo $profile_pic; ?>" alt="Profile Picture" id="profile-preview">
                            <div class="profile-picture-overlay" id="trigger-file-upload">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <form action="profile.php" method="post" enctype="multipart/form-data" id="profile-pic-form">
                            <div class="file-upload">
                                <label for="profile_picture">Choose New Profile Picture</label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Picture</button>
                        </form>
                    </div>

                    <form action="profile.php" method="post">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" class="form-control" value="<?php echo $username; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo $email; ?>">
                        </div>
                        <div class="buttons-container">
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                            <a href="change_password.php" class="btn btn-outline">Change Password</a>
                        </div>
                    </form>
                </div>

                <!-- Bookings Tab -->
                <div class="tab-panel" id="bookings-tab">
                    <div class="bookings-list">
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while($booking = $bookings->fetch_assoc()): ?>
                                <div class="booking-card">
                                    <div class="booking-image">
                                        <img src="../uploads/<?php echo $booking['room_image']; ?>" alt="<?php echo $booking['room_name']; ?>">
                                    </div>
                                    <div class="booking-details">
                                        <div class="booking-room"><?php echo $booking['room_name']; ?></div>
                                        <div class="booking-dates">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?php echo date('d M Y', strtotime($booking['checkin_date'])); ?> - 
                                            <?php echo date('d M Y', strtotime($booking['checkout_date'])); ?>
                                        </div>
                                        <div class="booking-price">
                                            <?php 
                                                $checkin = new DateTime($booking['checkin_date']);
                                                $checkout = new DateTime($booking['checkout_date']);
                                                $nights = $checkout->diff($checkin)->days;
                                                $total = $booking['price'] * $nights;
                                                echo "Rp" . number_format($total, 0, ',', '.');
                                            ?>
                                            for <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>
                                        </div>
                                        <span class="booking-status status-<?php echo $booking['status']; ?>">
                                            <?php 
                                                if ($booking['status'] == 'pending') echo 'Pending Confirmation';
                                                elseif ($booking['status'] == 'confirmed') echo 'Confirmed';
                                                else echo 'Cancelled';
                                            ?>
                                        </span>
                                        
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <div class="buttons-container" style="justify-content: flex-start;">
                                                <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel Booking</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="booking-card" style="text-align: center;">
                                <p>You don't have any bookings yet.</p>
                                <a href="../template/rooms.php" class="btn btn-primary" style="margin-top: 15px;">Book a Room Now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3 class="footer-title">Cokro Hotel</h3>
                    <p>Experience luxury and comfort in the heart of the city. Our hotel offers premium accommodations with exceptional service.</p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="../template/dashboard.php">Home</a></li>
                        <li><a href="../template/rooms.php">Rooms</a></li>
                        <li><a href="../template/services.php">Services</a></li>
                        <li><a href="../template/about.php">About Us</a></li>
                        <li><a href="../template/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Jl. Cokro No. 11, Jakarta</li>
                        <li><i class="fas fa-phone"></i> +62 123 456 7890</li>
                        <li><i class="fas fa-envelope"></i> info@cokrohotel.com</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Cokro Hotel. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab panels
                document.querySelectorAll('.tab-panel').forEach(panel => {
                    panel.classList.remove('active');
                });
                
                // Show the corresponding tab panel
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
        
        // Preview image before upload
        document.getElementById('profile_picture').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Trigger file input when clicking on camera icon
        document.getElementById('trigger-file-upload').addEventListener('click', function() {
            document.getElementById('profile_picture').click();
        });
    </script>
</body>

</html>