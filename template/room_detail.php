<?php
include "../config/connection.php";
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Get room ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../template/dashboard.php");
    exit;
}

$room_id = $_GET['id'];
$user_id = $_SESSION['id_user'];

// Fetch user profile picture
$sql = "SELECT profile_picture FROM users WHERE ID_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile_pic = $row['profile_picture'];
} else {
    $profile_pic = "../assets/profile_pictures/default_profile.jpg";
}

// Fetch room details
$sql_room = "SELECT * FROM rooms WHERE id = ?";
$stmt_room = $conn->prepare($sql_room);
$stmt_room->bind_param("i", $room_id);
$stmt_room->execute();
$result_room = $stmt_room->get_result();

if ($result_room->num_rows == 0) {
    header("Location: ../template/dashboard.php");
    exit;
}

$room = $result_room->fetch_assoc();
$facilities_array = explode(',', $room['facilities']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $room['name']; ?> - Cokro Hotel</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/room_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header class="detail-header">
        <nav>
            <div class="nav-wrap">
                <div class="logo">
                    <a href="../template/dashboard.php"><img src="../assets/hotel_logo.png" alt="Cokro Hotel Logo"></a>
                </div>
                <ul class="nav-links">
                    <li><a href="../template/dashboard.php#home">Home</a></li>
                    <li><a href="../template/dashboard.php#rooms">Rooms</a></li>
                    <li><a href="../template/choose_room.php">Booking</a></li>
                    <li><a href="../template/dashboard.php#facilities">Facilities</a></li>
                    <li><a href="../template/dashboard.php#about">About Us</a></li>
                    <li><a href="../template/dashboard.php#testimonials">Testimonials</a></li>
                    <div class="auth-buttons">
                        <div class="profile-dropdown">
                            <img src="../assets/profile_pictures/<?php echo $profile_pic; ?>" alt="Profile" class="profile-pic">
                            <div class="dropdown-content">
                                <a href="profile.php">My Profile</a>
                                <a href="../template/mybooking.php">My Bookings</a>
                                <a href="../template/logout.php">Log Out</a>
                            </div>
                        </div>
                    </div>
                </ul>
            </div>
        </nav>
    </header>

    <main class="room-detail-main">
        <div class="room-detail-container animate" data-animation="fade-in">
            <div class="room-breadcrumb">
                <a href="../template/dashboard.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="../template/dashboard.php#rooms">Rooms</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo $room['name']; ?></span>
            </div>

            <div class="room-detail-content">
                <div class="room-detail-gallery">
                    <div class="main-image animate" data-animation="slide-in-left">
                        <img src="../uploads/<?php echo $room['image']; ?>" alt="<?php echo $room['name']; ?>">
                        <div class="room-price">
                            <span class="price-value">IDR <?php echo number_format($room['price'], 0, ',', '.'); ?></span>
                            <span class="price-period">per night</span>
                        </div>
                    </div>
                </div>

                <div class="room-detail-info animate" data-animation="slide-in-right">
                    <h1><?php echo $room['name']; ?></h1>
                    
                    
                    <div class="room-description">
                        <p>Experience the ultimate in comfort and luxury in our <?php echo $room['name']; ?>. Each room is designed with attention to detail to ensure a pleasant and memorable stay at Cokro Hotel.</p>
                    </div>
                    
                    <div class="room-features">
                        <h3>Room Facilities</h3>
                        <div class="features-list">
                            <?php foreach($facilities_array as $facility): ?>
                                <div class="feature-item">
                                    <?php 
                                    // Add appropriate icons based on facility name
                                    if (stripos($facility, 'wifi') !== false) {
                                        echo '<i class="fas fa-wifi"></i>';
                                    } elseif (stripos($facility, 'ac') !== false || stripos($facility, 'air') !== false) {
                                        echo '<i class="fas fa-snowflake"></i>';
                                    } elseif (stripos($facility, 'breakfast') !== false) {
                                        echo '<i class="fas fa-coffee"></i>';
                                    } elseif (stripos($facility, 'tv') !== false) {
                                        echo '<i class="fas fa-tv"></i>';
                                    } elseif (stripos($facility, 'bath') !== false) {
                                        echo '<i class="fas fa-bath"></i>';
                                    } elseif (stripos($facility, 'parking') !== false) {
                                        echo '<i class="fas fa-parking"></i>';
                                    } elseif (stripos($facility, 'bed') !== false) {
                                        echo '<i class="fas fa-bed"></i>';
                                    } elseif (stripos($facility, 'safe') !== false) {
                                        echo '<i class="fas fa-lock"></i>';
                                    } elseif (stripos($facility, 'mini') !== false) {
                                        echo '<i class="fas fa-cookie"></i>';
                                    } else {
                                        echo '<i class="fas fa-check-circle"></i>';
                                    }
                                    ?>
                                    <span><?php echo trim($facility); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="room-policy">
                        <h3>Room Policy</h3>
                        <div class="policy-list">
                            <div class="policy-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <h4>Check-in / Check-out</h4>
                                    <p>Check-in: 2:00 PM<br>Check-out: 12:00 PM</p>
                                </div>
                            </div>
                            <div class="policy-item">
                                <i class="fas fa-user-friends"></i>
                                <div>
                                    <h4>Cancellation</h4>
                                    <p>Free cancellation up to 24 hours before check-in</p>
                                </div>
                            </div>
                            <div class="policy-item">
                                <i class="fas fa-smoking-ban"></i>
                                <div>
                                    <h4>No Smoking</h4>
                                    <p>Smoking is not allowed in the room</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="room-actions">
                        <a href="../template/choose_room.php?room_id=<?php echo $room['id']; ?>" class="btn-book-now">Book This Room</a>
                        <a href="../template/dashboard.php#rooms" class="btn-back">Back to All Rooms</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: smkn4bl@yahoo.co.id</p>
                <p class="phonec">Phone: 021-5725610</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/c.shenfu"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Address</h3>
                <p>Jalan Hos Cokroaminoto, 35118 Lampung, Indonesia</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2023 Cokro Hotel. All rights reserved.</p>
        </div>
    </footer>

    <script src="../scripts/animate.js"></script>
</body>

</html>