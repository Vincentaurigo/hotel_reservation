<?php
include "../config/connection.php";
session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id_user'];

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

$sql_rooms = "SELECT id, name, price, facilities, image FROM rooms LIMIT 6"; // Limit to 6 rooms for display
$result_rooms = $conn->query($sql_rooms);

$rooms = array();
if ($result_rooms->num_rows > 0) {
    while ($row = $result_rooms->fetch_assoc()) {
        $rooms[] = $row;
    }
}

$conn->close();
?>

<!-- // echo "Selamat datang, " . $_SESSION['username'] . "! <a href='logout.php'>Logout</a>"; -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - Cokro Hotel</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <!-- Replace the entire header section with this code -->
    <header>
        <!-- Background elements -->
        <div class="hero-bg-container">
            <div class="hero-bg"></div>
            <div class="hero-overlay"></div>
        </div>

        <!-- Navigation menu (keep your existing nav code) -->
        <nav>
            <div class="nav-wrap">
                <div class="logo">
                    <a href="../template/dashboard.php"><img src="../assets/hotel_logo.png" alt="Cokro Hotel Logo"></a>
                </div>
                <ul class="nav-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#rooms">Rooms</a></li>
                    <li><a href="../template/choose_room.php">Booking</a></li>
                    <li><a href="#facilities">Facilities</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
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

        <!-- Enhanced Hero Section -->
        <div class="hero">
            <!-- Floating decorative elements -->
            <div class="floating-element float-1"></div>
            <div class="floating-element float-2"></div>
            <div class="floating-element float-3"></div>
            <div class="floating-element float-4"></div>

            <div class="hero-content">
                <!-- Hero content with elegant styling -->
                <div class="hero-logo">
                </div>

                <p class="hero-tagline">Experience True Luxury</p>

                <div class="elegant-divider">
                    <div class="divider-line"></div>
                    <i class="fas fa-star divider-icon"></i>
                    <div class="divider-line"></div>
                </div>

                <h1>COKRO HOTEL
                    <span>YOUR HOME AWAY FROM HOME</span>
                </h1>

                <p>Indulge in the perfect blend of luxury, comfort, and exceptional hospitality.
                    Our dedicated staff and world-class amenities ensure an unforgettable stay in Lampung.</p>

                <div class="hero-buttons">
                    <a href="#rooms" class="btn-explore btn-primary">Explore Rooms</a>
                    <a href="../template/choose_room.php" class="btn-explore btn-outline">Book Now</a>
                </div>
            </div>

            <!-- Quick preview of hotel features -->

    </header>


    <section id="about" class="about-section animate" data-animation="fade-in">
        <div class="about-content">
            <h2>About <span>Us</span></h2>
            <p>Cokro Hotel is a luxurious hotel located in the heart of the city. We offer the best services and facilities to make your stay unforgettable.</p>
        </div>
        <div class="about-image animate" data-animation="slide-in-left">
            <img src="../assets/slide_assets/img_slide_1.jpg" alt="Hotel Lobby">
        </div>
    </section>

    <section id="rooms" class="rooms-section animate" data-animation="fade-in">
        <div class="section-header">
            <h2><span>Our</span> Rooms</h2>
            <div class="section-divider">
                <div class="divider-line"></div>
                <i class="fas fa-hotel divider-icon"></i>
                <div class="divider-line"></div>
            </div>
            <p class="section-subtitle">Experience comfort and luxury in our carefully designed accommodations</p>
        </div>

        <div class="room-cards">
            <?php if (count($rooms) > 0): ?>
                <?php foreach ($rooms as $index => $room): ?>
                    <?php
                    // Determine animation direction alternating between left and right
                    $animation = ($index % 2 == 0) ? "slide-in-left" : "slide-in-right";

                    // Format price with IDR currency
                    $formatted_price = number_format($room['price'], 0, ',', '.');

                    // Parse facilities into array
                    $facilities_array = explode(',', $room['facilities']);
                    ?>
                    <div class="room-card animate" data-animation="<?php echo $animation; ?>">
                        <div class="room-image">
                            <img src="../uploads/<?php echo $room['image']; ?>" alt="<?php echo $room['name']; ?>">
                            <div class="room-price">
                                <span>IDR <?php echo $formatted_price; ?></span>
                                <small>per night</small>
                            </div>
                        </div>
                        <div class="room-details">
                            <h3><?php echo $room['name']; ?></h3>
                            <div class="room-facilities">
                                <?php foreach (array_slice($facilities_array, 0, 4) as $facility): ?>
                                    <span class="facility-badge">
                                        <?php
                                        // Add appropriate icons based on facility name
                                        if (stripos($facility, 'wifi') !== false) {
                                            echo '<i class="fas fa-wifi"></i> ';
                                        } elseif (stripos($facility, 'ac') !== false || stripos($facility, 'air') !== false) {
                                            echo '<i class="fas fa-snowflake"></i> ';
                                        } elseif (stripos($facility, 'breakfast') !== false) {
                                            echo '<i class="fas fa-coffee"></i> ';
                                        } elseif (stripos($facility, 'tv') !== false) {
                                            echo '<i class="fas fa-tv"></i> ';
                                        } elseif (stripos($facility, 'bath') !== false) {
                                            echo '<i class="fas fa-bath"></i> ';
                                        } elseif (stripos($facility, 'parking') !== false) {
                                            echo '<i class="fas fa-parking"></i> ';
                                        } else {
                                            echo '<i class="fas fa-check-circle"></i> ';
                                        }
                                        echo trim($facility);
                                        ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($facilities_array) > 4): ?>
                                    <span class="facility-badge more-facilities">+<?php echo count($facilities_array) - 4; ?> more</span>
                                <?php endif; ?>
                            </div>
                            <div class="room-actions">
                                <a href="room_detail.php?id=<?php echo $room['id']; ?>" class="btn-view-details">View Details</a>
                                <a href="../template/choose_room.php?room_id=<?php echo $room['id']; ?>" class="btn-book-now">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-rooms">
                    <p>No rooms available at the moment. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($rooms) > 3): ?>
            <div class="view-all-rooms">
                <a href="all_rooms.php" class="btn-view-all">View All Rooms <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php endif; ?>
    </section>

    <section id="facilities" class="facilities-section animate" data-animation="fade-in">
        <h2>Our <span>Facilities</span></h2>
        <div class="facility-cards">
            <div class="facility animate" data-animation="slide-in-left">
                <div class="facility-content">
                    <i class="fas fa-swimming-pool"></i>
                    <h3>Swimming Pool</h3>
                    <p>Relax and unwind in our luxurious swimming pool.</p>
                </div>
                <div class="facility-image">
                    <img src="../assets/facilities/pool.jpg" alt="Swimming Pool">
                </div>
            </div>
            <div class="facility animate" data-animation="slide-in-right">
                <div class="facility-content">
                    <i class="fas fa-utensils"></i>
                    <h3>Restaurant</h3>
                    <p>Enjoy delicious meals at our in-house restaurant.</p>
                </div>
                <div class="facility-image">
                    <img src="../assets/facilities/restaurant.jpeg" alt="Restaurant">
                </div>
            </div>
            <div class="facility animate" data-animation="slide-in-left">
                <div class="facility-content">
                    <i class="fas fa-users"></i>
                    <h3>Meeting Room</h3>
                    <p>Enjoy your meeting with our meeting room.</p>
                </div>
                <div class="facility-image">
                    <img src="../assets/facilities/meeting_room.jpg" alt="Spa">
                </div>
            </div>
        </div>
    </section>
    <section id="testimonials" class="testimonials-section animate" data-animation="fade-in">
        <h2>What Our Guests Say</h2>
        <div class="testimonial-cards">
            <div class="testimonial animate" data-animation="slide-in-left">
                <img src="../assets/efrata_cen.jpg" alt="Guest 1">
                <div class="rating">
                    <i class="fas fa-star filled"></i>
                    <i class="fas fa-star filled"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <span class="rating-value">2.0</span>
                </div>
                <p>"kamarnya sih udah oke ya, tapi makanannya kurang. nasinya basi setelah itu, udangnnya tidak dibersihkan. tolong diperbaiki ya"</p>
                <h4>- Efrata Cen</h4>
            </div>
            <div class="testimonial animate" data-animation="slide-in-right">
                <img src="../assets/ibrani_cen.jpg" alt="Guest 2">
                <div class="rating">
                    <i class="fas fa-star filled"></i>
                    <i class="fas fa-star filled"></i>
                    <i class="fas fa-star filled"></i>
                    <i class="far fa-star"></i>
                    <i class="far fa-star"></i>
                    <span class="rating-value">3.0</span>
                </div>
                <p>"menurut saya parkirannya cukup oke. tapi saya rasa terlalu mahal, tolong harganya dikurangi dikit ya"</p>
                <h4>- Ibrani cen</h4>
            </div>
        </div>
    </section>

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
                    <!-- <a href="#"><i class="fab fa-twitter"></i></a> -->
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