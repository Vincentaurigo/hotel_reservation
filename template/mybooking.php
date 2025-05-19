<?php
include '../config/connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id_user'];

// Get user profile picture
$sql = "SELECT profile_picture FROM users WHERE ID_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile_pic = $row['profile_picture'];
} else {
    $profile_pic = "default_profile.png";
}

// Get all bookings for this user with testimonial info
$sql = "SELECT b.*, r.name as room_name, r.image as room_image, r.price as room_price,
        (SELECT COUNT(*) FROM testimonials t WHERE t.booking_id = b.id) as has_review
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.user_id = ? 
        ORDER BY b.checkin_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Function to calculate nights between two dates
function calculateNights($checkin, $checkout)
{
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $interval = $checkin_date->diff($checkout_date);
    return $interval->days;
}

// Function to calculate total price
function calculateTotalPrice($price, $nights)
{
    return $price * $nights;
}

// Function to check if booking is eligible for review
function isEligibleForReview($status, $checkout_date)
{
    $today = new DateTime();
    $checkout = new DateTime($checkout_date);
    return ($status === 'confirmed' && $checkout < $today);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Cokro Hotel</title>
    <link rel="stylesheet" href="../styles/my_bookings.css">
    <link rel="stylesheet" href="../style/profile.css">
    <style>
        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .bookings-container {
            min-height: 100vh;
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 0 0 0;
        }

        .booking-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
            display: flex;
            flex-direction: row;
        }

        .booking-image {
            width: 250px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 20px;
        }

        .booking-details {
            flex: 1;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .booking-id {
            font-family: 'Poppins';
            color: #888;
            font-size: 0.9em;
        }

        .booking-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-confirmed {
            background-color: #e7f7ed;
            color: #28a745;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            font-family: 'inter';
            background-color: #f8d7da;
            color: #dc3545;
        }

        .booking-dates {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }

        .date-group {
            flex: 1;
        }

        .date-label {
            font-family: 'inter';
            font-weight: bold;
            font-size: 0.8em;
            color: black;
        }

        .date-value {
            font-family: 'inter';
            color: #888;
            font-size: 1.1em;
        }

        .booking-price {
            font-family: 'inter';
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 10px;
        }

        .booking-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-cancel {
            font-family: 'inter';
            padding: 8px 15px;
            background-color: #f8d7da;
            color: #dc3545;
            border: 1px solid #dc3545;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .btn-cancel:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-review {
            font-family: 'inter';
            padding: 8px 15px;
            background-color: #e8f4fd;
            color: #0077cc;
            border: 1px solid #0077cc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-review:hover {
            background-color: #0077cc;
            color: white;
        }
        
        .review-submitted {
            font-family: 'inter';
            padding: 8px 15px;
            background-color: #e7f7ed;
            color: #28a745;
            border-radius: 4px;
            font-size: 0.9em;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .booking-card {
                flex-direction: column;
            }

            .booking-image {
                width: 100%;
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
    <link rel="stylesheet" href="../styles/mybooking.css">
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
                    <li><a href="../tempalate/dashboard.php#rooms">Rooms</a></li>
                    <li><a href="../template/choose_room.php">Booking</a></li>
                    <li><a href="../template/dashboard.php#facilities">Facilities</a></li>
                    <li><a href="../template/dashboard.php#about">About Us</a></li>
                    <li><a href="../template/dashboard.php#testimonials">Testimonials</a></li>
                    <div class="auth-buttons">
                        <div class="profile-dropdown">
                            <img src="../assets/profile_pictures/<?php echo $profile_pic; ?>" alt="Profile" class="profile-pic">
                            <div class="dropdown-content">
                                <a href="profile.php">My Profile</a>
                                <a href="my_bookings.php">My Bookings</a>
                                <a href="../template/logout.php">Log Out</a>
                            </div>
                        </div>
                    </div>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="bookings-container">
            <h2>My Bookings</h2>

            <?php if (isset($_SESSION['booking_success']) && $_SESSION['booking_success']): ?>
                <div class="success-message">
                    Booking successful! Your booking ID is: <?php echo $_SESSION['booking_id']; ?>
                </div>
            <?php
                unset($_SESSION['booking_success']);
                unset($_SESSION['booking_id']);
            endif;
            ?>

            <?php if (isset($_SESSION['cancel_success'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['cancel_success']; ?>
                </div>
            <?php
                unset($_SESSION['cancel_success']);
            endif;
            ?>
            
            <?php if (isset($_SESSION['review_success'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['review_success']; ?>
                </div>
            <?php
                unset($_SESSION['review_success']);
            endif;
            ?>

            <?php if ($bookings->num_rows > 0): ?>
                <?php while ($booking = $bookings->fetch_assoc()):
                    $nights = calculateNights($booking['checkin_date'], $booking['checkout_date']);
                    $total_price = calculateTotalPrice($booking['room_price'], $nights);
                    $can_review = isEligibleForReview($booking['status'], $booking['checkout_date']);
                ?>
                    <div class="booking-card">
                        <img src="<?php echo $booking['room_image']; ?>" alt="<?php echo $booking['room_name']; ?>" class="booking-image">

                        <div class="booking-details">
                            <div class="booking-header">
                                <h3><?php echo $booking['room_name']; ?></h3>
                                <span class="booking-id">Booking ID: <?php echo $booking['id']; ?></span>
                            </div>

                            <div class="booking-dates">
                                <div class="date-group">
                                    <div class="date-label">CHECK-IN</div>
                                    <div class="date-value"><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></div>
                                </div>

                                <div class="date-group">
                                    <div class="date-label">CHECK-OUT</div>
                                    <div class="date-value"><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></div>
                                </div>

                                <div class="date-group">
                                    <div class="date-label">DURATION</div>
                                    <div class="date-value"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></div>
                                </div>
                            </div>

                            <div class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </div>

                            <div class="booking-price">
                                Total: Rp <?php echo number_format($total_price, 1); ?>
                            </div>

                            <div class="booking-actions">
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <button class="btn-cancel" data-booking-id="<?php echo $booking['id']; ?>">Cancel Booking</button>
                                <?php endif; ?>
                                
                                <?php if ($can_review && $booking['has_review'] == 0): ?>
                                    <a href="leave_review.php?booking_id=<?php echo $booking['id']; ?>" class="btn-review">Leave Review</a>
                                <?php elseif ($booking['has_review'] > 0): ?>
                                    <span class="review-submitted">Review Submitted</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You don't have any bookings yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cancelButtons = document.querySelectorAll('.btn-cancel');
            cancelButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.getAttribute('data-booking-id');
                    if (confirm('Are you sure you want to cancel this booking?')) {
                        window.location.href = 'cancel_booking.php?id=' + bookingId;
                    }
                });
            });
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>