<?php
include '../config/connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id_user'];
$error_message = '';
$success_message = '';

// Check if booking_id is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit;
}

$booking_id = $_GET['booking_id'];

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

// Verify booking belongs to user, is confirmed, and checkout date has passed
$sql = "SELECT b.*, r.name as room_name, r.image as room_image
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id
        WHERE b.id = ? AND b.user_id = ? 
        AND b.status = 'confirmed' 
        AND b.checkout_date < CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_bookings.php");
    exit;
}

$booking = $result->fetch_assoc();

// Check if review already exists
$checkSql = "SELECT id FROM testimonials WHERE booking_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $booking_id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    header("Location: my_bookings.php");
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    // Validate input
    if ($rating < 1 || $rating > 5) {
        $error_message = "Rating must be between 1 and 5";
    } elseif (empty($comment)) {
        $error_message = "Comment cannot be empty";
    } else {
        // Insert testimonial
        $insertsql = "INSERT INTO testimonials (booking_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
        $insertstmt = $conn->prepare($insertsql);
        $insertstmt->bind_param("iids", $booking_id, $user_id, $rating, $comment);
        
        if ($insertstmt->execute()) {
            $_SESSION['review_success'] = "Thank you for your feedback!";
            header("Location: my_bookings.php");
            exit;
        } else {
            $error_message = "Error submitting review. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - Cokro Hotel</title>
    <link rel="stylesheet" href="../styles/mybooking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .review-container {
            max-width: 800px;
            margin: 120px auto 50px;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .review-container h1 {
            margin-bottom: 25px;
            color: #333;
            text-align: center;
        }
        
        .booking-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .booking-summary img {
            width: 120px;
            height: 80px;
            border-radius: 5px;
            object-fit: cover;
            margin-right: 20px;
        }
        
        .booking-summary-details h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .booking-summary-details p {
            margin: 5px 0;
            color: #666;
        }
        
        .error-message {
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .review-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .rating-section, .comment-section {
            margin-bottom: 15px;
        }
        
        .rating-section h3, .comment-section h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            padding: 10px 0;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            font-size: 30px;
            color: #ddd;
            margin-right: 10px;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffc107;
        }
        
        .comment-section textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 16px;
            min-height: 120px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .btn-submit {
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #3e8e41;
        }
        
        .btn-cancel {
            padding: 12px 30px;
            background-color: #f1f1f1;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #ddd;
        }
    </style>
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
        <div class="review-container">
            <h1>Leave a Review</h1>
            
            <div class="booking-summary">
                <img src="<?php echo $booking['room_image']; ?>" alt="<?php echo $booking['room_name']; ?>">
                <div class="booking-summary-details">
                    <h3><?php echo $booking['room_name']; ?></h3>
                    <p>Check-in: <?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></p>
                    <p>Check-out: <?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></p>
                </div>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="post" class="review-form">
                <div class="rating-section">
                    <h3>How would you rate your stay?</h3>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1"><i class="fas fa-star"></i></label>
                    </div>
                </div>
                
                <div class="comment-section">
                    <h3>Share your experience</h3>
                    <textarea name="comment" required placeholder="Tell us about your stay at Cokro Hotel..."></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="my_bookings.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">Submit Review</button>
                </div>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Make the star rating interactive
        const stars = document.querySelectorAll('.star-rating label');
        
        stars.forEach(function(star, index) {
            star.addEventListener('click', function() {
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('selected');
                }
                for (let i = index + 1; i < stars.length; i++) {
                    stars[i].classList.remove('selected');
                }
            });
        });
    });
    </script>
</body>
</html>