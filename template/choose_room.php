<?php
include '../config/connection.php';
session_start(); // Start the session

// Check if user is logged in and is a user
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Get rooms data
$sql = "SELECT * FROM rooms";
$result = $conn->query($sql);

// Get user profile picture
$user_id = $_SESSION['id_user'];
$sqlpict = "SELECT profile_picture FROM users WHERE ID_user = ?";
$stmt = $conn->prepare($sqlpict);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultpict = $stmt->get_result();

if ($resultpict->num_rows > 0) {
    $row = $resultpict->fetch_assoc();
    $profile_pic = $row['profile_picture'];
} else {
    $profile_pic = "default_profile.jpg";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Room - Cokro Hotel</title>
    <link rel="stylesheet" href="../styles/choose_room.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <nav>
            <div class="nav-wrap">
                <div class="logo">
                    <a href="../template/dashboard.php"> <img src="../assets/hotel_logo.png" alt="Cokro Hotel Logo"></a>
                </div>
                <ul class="nav-links">
                    <li><a href="../template/dashboard.php">Home</a></li>
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
                                <a href="mybooking.php">My Bookings</a>
                                <a href="../template/logout.php">Log Out</a>
                            </div>
                        </div>
                    </div>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="room-list">
            <h2>Choose Your Room</h2>
            <div class="rooms">
                <?php
                if ($result->num_rows > 0) {
                    while ($room = $result->fetch_assoc()) {
                        // Parse facilities into array
                        $facilities_array = explode(',', $room["facilities"]);
                        
                        // Format price with IDR currency
                        $formatted_price = number_format($room["price"], 0, ',', '.');
                        
                        echo '
                        <div class="room-card">
                            <div class="room-image" style="position: relative;">
                                <img src="' . $room["image"] . '" alt="' . $room["name"] . '">
                                <div class="price-tag">
                                    IDR ' . $formatted_price . '
                                    <span>per night</span>
                                </div>
                            </div>
                            <h3>' . $room["name"] . '</h3>
                            <div class="room-facilities">';
                            
                        // Display up to 3 facilities with icons
                        foreach (array_slice($facilities_array, 0, 3) as $facility) {
                            echo '<span class="facility-badge">';
                            
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
                            
                            echo trim($facility) . '</span>';
                        }
                        
                        // Show "+X more" if there are more facilities
                        if (count($facilities_array) > 3) {
                            echo '<span class="facility-badge more-facilities">+' . (count($facilities_array) - 3) . ' more</span>';
                        }
                        
                        echo '</div>
                            <div class="booking-border">
                                <button class="btn-book" data-room-id="' . $room["id"] . '">Book Now</button>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="no-rooms"><p>No rooms available at the moment. Please check back later.</p></div>';
                }
                ?>
            </div>
        </section>

        <!-- Booking Modal -->
        <div id="bookingModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Book Your Room</h2>

                <?php if (isset($_SESSION['id_user'])): ?>
                    <form id="bookingForm" action="process_booking.php" method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $_SESSION['id_user']; ?>">
                        <input type="hidden" name="room_id" id="room_id">

                        <label for="checkin_date">Check-in Date:</label>
                        <input type="date" name="checkin_date" id="checkin_date" required>

                        <label for="checkout_date">Check-out Date:</label>
                        <input type="date" name="checkout_date" id="checkout_date" required>

                        <div class="confirm-button">
                            <button type="submit">Confirm Booking</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p>You must be logged in to book a room. <a href="../template/login.php">Click here to login</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const modal = document.getElementById("bookingModal");
            const closeModal = document.querySelector(".close");
            const roomIdInput = document.getElementById("room_id");

            <?php if (isset($_SESSION['id_user'])): ?>
                const bookingForm = document.getElementById("bookingForm");

                // Set minimum date to today for check-in
                const today = new Date().toISOString().split('T')[0];
                document.getElementById("checkin_date").min = today;

                // Update checkout date min when checkin date changes
                document.getElementById("checkin_date").addEventListener("change", function() {
                    document.getElementById("checkout_date").min = this.value;
                });

                // Form submission
                bookingForm.addEventListener("submit", function(e) {
                    const checkin = new Date(document.getElementById("checkin_date").value);
                    const checkout = new Date(document.getElementById("checkout_date").value);

                    if (checkout <= checkin) {
                        e.preventDefault();
                        alert("Check-out date must be after check-in date");
                    }
                });
            <?php endif; ?>

            document.querySelectorAll(".btn-book").forEach(button => {
                button.addEventListener("click", function() {
                    if (roomIdInput) {
                        roomIdInput.value = this.getAttribute("data-room-id");
                    }
                    modal.style.display = "block";
                });
            });

            closeModal.onclick = function() {
                modal.style.display = "none";
            };

            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            };
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>